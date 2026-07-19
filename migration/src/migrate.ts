/**
 * Migrare date live Curs la Pahar -> Neon Postgres.
 *
 * Sursa: bundle JSON de la admin/sync-export.php (SYNC_URL) sau un fișier local (LIVE_FILE).
 * Țintă: schema din ../neon_schema.sql (rulează întâi `npm run schema`).
 *
 * Rulează:  npm run migrate
 * Re-rulabil: golește (TRUNCATE) tabelele țintă și reîncarcă, totul într-o tranzacție.
 */
import { readFileSync, existsSync } from 'node:fs';
import { Client } from 'pg';

// --- încărcare .env minimală (fără dependință externă) ---
function loadEnv(): void {
  if (!existsSync('.env')) return;
  for (const line of readFileSync('.env', 'utf8').split('\n')) {
    const m = line.match(/^\s*([A-Z_][A-Z0-9_]*)\s*=\s*(.*)\s*$/);
    if (m && process.env[m[1]] === undefined) {
      process.env[m[1]] = m[2].replace(/^["']|["']$/g, '');
    }
  }
}
loadEnv();

// --- tipuri (permisive; oglindesc bundle-ul live) ---
interface SiteCard { id: string; title: string; date_raw: string; time?: string; location?: string; livetickets_url?: string; image_url?: string; active?: boolean; }
interface StatCourse { id: number; name: string; date: string; created_at?: string; viza_done?: number; external_id?: string | null; }
interface Ticket { course_id: number; participant_name: string; }
interface Report { course_id: number; total_bilete: number; total_incasari: number; original_name?: string; uploaded_at?: string; types_json?: unknown; }
interface Speaker { id: string; name: string; email?: string; phone?: string; status?: string; notes?: string; courses?: string[]; }
interface Loc { id: string; name: string; phone?: string; maps_link?: string; days?: string; notes?: string; }
interface Vote { id: string; name: string; emoji?: string; description?: string; likes?: number; active?: boolean; }
interface Venit { data: string; descriere: string; suma: number; }
interface Chelt { data: string; descriere: string; suma: number; categorie: string; }
interface Bundle {
  settings: Record<string, unknown>;
  course_ideas?: { intro?: string; categories?: unknown[] } | null;
  marketing?: {
    sections?: {
      id?: string;
      title?: string;
      is_default?: boolean;
      items?: { id?: string; text?: string; link?: string; done?: boolean }[];
    }[];
  } | null;
  courses: SiteCard[];
  vote_courses: Vote[];
  speakers: Speaker[];
  locations: Loc[];
  statistici: { courses: StatCourse[]; tickets: Ticket[]; course_reports: Report[] };
  pnl: { venituri: Venit[]; cheltuieli: Chelt[] };
}

const BUCHAREST = "Europe/Bucharest";

async function loadBundle(): Promise<Bundle> {
  const file = process.env.LIVE_FILE;
  if (file) return JSON.parse(readFileSync(file, "utf8"));
  const url = process.env.SYNC_URL;
  if (!url) throw new Error("Setează SYNC_URL sau LIVE_FILE în .env");
  const res = await fetch(url, { headers: { "User-Agent": "Mozilla/5.0" } });
  if (!res.ok) throw new Error(`sync-export ${res.status} — verifică tokenul din admin → Config`);
  return (await res.json()) as Bundle;
}

function slugFromUrl(u?: string | null): string | null {
  if (!u) return null;
  const last = u.split("?")[0].split("/").pop() ?? "";
  return last || null;
}

const asJson = (v: unknown): string => (typeof v === "string" ? v : JSON.stringify(v ?? []));

async function main(): Promise<void> {
  if (!process.env.DATABASE_URL) throw new Error("Setează DATABASE_URL în .env");
  const bundle = await loadBundle();
  const db = new Client({ connectionString: process.env.DATABASE_URL });
  await db.connect();

  try {
    await db.query("BEGIN");
    // Doar tabelele pe care bundle-ul le repopulează — users/todos/messages etc.
    // trăiesc în Neon și NU trebuie șterse la re-sync.
    await db.query(`TRUNCATE
      events, tickets, event_files, event_reports, viza_subtips,
      speakers, locations, settings, vote_courses,
      venit_categorii, cheltuiala_categorii, venituri, cheltuieli,
      marketing_sections, marketing_items
      RESTART IDENTITY CASCADE`);

    // 1) settings (fiecare cheie -> JSONB)
    for (const [k, v] of Object.entries(bundle.settings ?? {})) {
      await db.query("INSERT INTO settings(key, value) VALUES($1, $2)", [k, JSON.stringify(v)]);
    }
    // course_ideas.json (pagina /cursuri-posibile) — o singură cheie JSONB
    if (bundle.course_ideas?.categories?.length) {
      await db.query("INSERT INTO settings(key, value) VALUES('course_ideas', $1)", [
        JSON.stringify(bundle.course_ideas),
      ]);
    }

    // marketing_posts.json — secțiuni + idei de postări
    let marketingItems = 0;
    const marketingSections = bundle.marketing?.sections ?? [];
    for (const [si, sec] of marketingSections.entries()) {
      const { rows: secRows } = await db.query(
        "INSERT INTO marketing_sections(slug, title, is_default, position) VALUES($1, $2, $3, $4) RETURNING id",
        [sec.id ?? null, sec.title ?? "", sec.is_default ?? false, si]
      );
      for (const [ii, item] of (sec.items ?? []).entries()) {
        await db.query(
          "INSERT INTO marketing_items(section_id, payload, position) VALUES($1, $2, $3)",
          [
            secRows[0].id,
            JSON.stringify({
              legacy_id: item.id ?? null,
              text: item.text ?? "",
              link: item.link ?? "",
              done: item.done ?? false,
            }),
            ii,
          ]
        );
        marketingItems++;
      }
    }

    // 2) events — ancora canonică din statistici.courses
    const eventByStatId = new Map<number, number>();
    const eventByExternal = new Map<string, number>();
    for (const c of bundle.statistici.courses) {
      const { rows } = await db.query(
        `INSERT INTO events(title, starts_at, external_id, viza_done, created_at)
         VALUES($1, ($2::timestamp AT TIME ZONE $6), $3, $4, ($5::timestamp AT TIME ZONE $6))
         RETURNING id`,
        [c.name, `${c.date} 00:00`, c.external_id ?? null, !!c.viza_done, c.created_at ?? `${c.date} 00:00`, BUCHAREST]
      );
      const id = rows[0].id as number;
      eventByStatId.set(c.id, id);
      if (c.external_id) eventByExternal.set(c.external_id, id);
    }

    // 3) îmbogățire din cardurile de site (match pe external_id == card.id); altfel insert nou
    let cardsMatched = 0, cardsNew = 0;
    for (const card of bundle.courses) {
      const startsAt = `${card.date_raw} ${card.time || "00:00"}`;
      const slug = slugFromUrl(card.livetickets_url);
      const existing = eventByExternal.get(card.id);
      if (existing) {
        await db.query(
          `UPDATE events SET title=$1, slug=$2, legacy_card_id=$3,
             starts_at=($4::timestamp AT TIME ZONE $9),
             location=$5, livetickets_url=$6, image_url=$7, active=$8
           WHERE id=$10`,
          [card.title, slug, card.id, startsAt, card.location ?? null, card.livetickets_url ?? null, card.image_url ?? null, !!card.active, BUCHAREST, existing]
        );
        cardsMatched++;
      } else {
        await db.query(
          `INSERT INTO events(title, slug, legacy_card_id, starts_at, location, livetickets_url, image_url, active)
           VALUES($1,$2,$3,($4::timestamp AT TIME ZONE $9),$5,$6,$7,$8)`,
          [card.title, slug, card.id, startsAt, card.location ?? null, card.livetickets_url ?? null, card.image_url ?? null, !!card.active, BUCHAREST]
        );
        cardsNew++;
      }
    }

    // 4) tickets
    let ticketsOk = 0, ticketsOrphan = 0;
    for (const t of bundle.statistici.tickets) {
      const eid = eventByStatId.get(t.course_id);
      if (!eid) { ticketsOrphan++; continue; }
      await db.query("INSERT INTO tickets(event_id, participant_name) VALUES($1,$2)", [eid, t.participant_name]);
      ticketsOk++;
    }

    // 5) event_reports (fișierul fizic nu e în bundle -> blob_url rămâne NULL, mută-l pe Blob ulterior)
    let reportsOk = 0;
    for (const r of bundle.statistici.course_reports) {
      const eid = eventByStatId.get(r.course_id);
      if (!eid) continue;
      await db.query(
        `INSERT INTO event_reports(event_id, total_bilete, total_incasari, original_name, types_json, uploaded_at)
         VALUES($1,$2,$3,$4,$5, ($6::timestamp AT TIME ZONE $7))`,
        [eid, r.total_bilete, r.total_incasari, r.original_name ?? "", asJson(r.types_json), r.uploaded_at ?? `${new Date().toISOString().slice(0, 19).replace("T", " ")}`, BUCHAREST]
      );
      reportsOk++;
    }

    // 6) speakers (courses[] -> topics text[])
    for (const s of bundle.speakers) {
      await db.query(
        `INSERT INTO speakers(legacy_id, name, email, phone, status, notes, topics)
         VALUES($1,$2,$3,$4,$5,$6,$7)`,
        [s.id, s.name, s.email ?? null, s.phone ?? null, s.status ?? null, s.notes ?? null, s.courses ?? []]
      );
    }

    // 7) locations
    for (const l of bundle.locations) {
      await db.query(
        `INSERT INTO locations(legacy_id, name, phone, maps_link, days, notes)
         VALUES($1,$2,$3,$4,$5,$6)`,
        [l.id, l.name, l.phone ?? null, l.maps_link ?? null, l.days ?? null, l.notes ?? null]
      );
    }

    // 8) vote_courses
    for (const v of bundle.vote_courses) {
      await db.query(
        `INSERT INTO vote_courses(legacy_id, name, emoji, description, likes, active)
         VALUES($1,$2,$3,$4,$5,$6)`,
        [v.id, v.name, v.emoji ?? null, v.description ?? null, v.likes ?? 0, v.active ?? true]
      );
    }

    // 9) P&L — categorii de cheltuieli din valori distincte, apoi FK
    const chCat = new Map<string, number>();
    for (const nume of [...new Set(bundle.pnl.cheltuieli.map((c) => c.categorie))]) {
      const { rows } = await db.query("INSERT INTO cheltuiala_categorii(nume) VALUES($1) RETURNING id", [nume]);
      chCat.set(nume, rows[0].id as number);
    }
    for (const v of bundle.pnl.venituri) {
      await db.query("INSERT INTO venituri(data, descriere, suma) VALUES($1,$2,$3)", [v.data.slice(0, 10), v.descriere, v.suma]);
    }
    for (const c of bundle.pnl.cheltuieli) {
      await db.query("INSERT INTO cheltuieli(data, descriere, suma, categorie_id) VALUES($1,$2,$3,$4)", [c.data.slice(0, 10), c.descriere, c.suma, chCat.get(c.categorie)]);
    }

    await db.query("COMMIT");

    console.log("✓ Migrare completă:");
    console.log(`  settings         ${Object.keys(bundle.settings ?? {}).length}`);
    console.log(`  marketing        ${marketingSections.length} secțiuni, ${marketingItems} idei`);
    console.log(`  events           ${bundle.statistici.courses.length} stats + ${cardsNew} carduri noi (${cardsMatched} carduri unite)`);
    console.log(`  tickets          ${ticketsOk}${ticketsOrphan ? ` (${ticketsOrphan} orfane, ignorate)` : ""}`);
    console.log(`  event_reports    ${reportsOk}`);
    console.log(`  speakers         ${bundle.speakers.length}`);
    console.log(`  locations        ${bundle.locations.length}`);
    console.log(`  vote_courses     ${bundle.vote_courses.length}`);
    console.log(`  cheltuiala_cat.  ${chCat.size}`);
    console.log(`  venituri         ${bundle.pnl.venituri.length}`);
    console.log(`  cheltuieli       ${bundle.pnl.cheltuieli.length}`);
    console.log("\nNOTĂ: todos, recurring_tasks, ab_*, users, course_clicks, soldout NU sunt în bundle-ul live — se migrează separat.");
  } catch (e) {
    await db.query("ROLLBACK");
    throw e;
  } finally {
    await db.end();
  }
}

main().catch((e) => {
  console.error("✗ Migrare eșuată:", e instanceof Error ? e.message : e);
  process.exit(1);
});
