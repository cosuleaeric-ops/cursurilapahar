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
import { createHash } from 'node:crypto';
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
interface SiteCard { id: string; title: string; date_raw: string; date_display?: string; time?: string; speaker_id?: string; location?: string; livetickets_url?: string; image_url?: string; active?: boolean; speaker_name?: string; link_added_at?: string; discount_percent?: number | string; discount_ends_at?: string; }
interface StatCourse { id: number; name: string; date: string; created_at?: string; viza_done?: number; external_id?: string | null; }
interface Ticket { course_id: number; participant_name: string; }
interface VizaSub { course_id: number; seria?: string; tarif?: number|string; nr_unitati?: number|string; de_la?: string|null; pana_la?: string|null; }
interface Report { course_id: number; total_bilete: number; total_incasari: number; original_name?: string; uploaded_at?: string; types_json?: unknown; }
interface Speaker { id: string; name: string; email?: string; phone?: string; status?: string; notes?: string; courses?: string[]; }
interface Loc { id: string; name: string; phone?: string; maps_link?: string; days?: string; notes?: string; }
interface Collab { id?: string; name?: string; contact?: string; contact_info?: string; status?: string; notes?: string; }
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
  ab_button?: Record<string, { views?: number; clicks?: number }> | null;
  course_clicks?: Record<string, number> | null;
  vote_views?: Record<string, number> | null;
  recurring?: {
    id?: string;
    type?: string;
    system_key?: string;
    assigned_to?: string;
    title?: string;
    schedule?: string;
    description?: string;
    days?: (number | string)[];
  }[] | null;
  courses: SiteCard[];
  vote_courses: Vote[];
  speakers: Speaker[];
  locations: Loc[];
  collaborations?: Collab[] | null;
  statistici: { courses: StatCourse[]; tickets: Ticket[]; course_reports: Report[]; viza_subtips?: VizaSub[] };
  pnl: { venituri: Venit[]; cheltuieli: Chelt[]; venit_categorii?: { id: number; nume: string }[] };
  messages_log?: string | null;
  messages_meta?: Record<string, MsgMeta> | null;
  instagram_posts?: Record<string, string[]> | null;
  todos?: TodoJson[] | null;
}

type TodoJson = {
  id?: string;
  title?: string;
  assigned_to?: string;
  created_by?: string;
  completed?: boolean;
  created_at?: string;
  completed_at?: string;
};

type MsgMeta = {
  read?: boolean;
  evaluation?: string;
  contacted?: boolean;
  comments?: { at?: string; by?: string; text?: string }[];
};

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

/**
 * messages.log — blocuri `=== <data> | <tip> ===` urmate de linii `Cheie: valoare`.
 * Metadatele (citit/evaluare/contactat/comentarii) sunt cheiate pe primele 12
 * caractere din md5-ul blocului, exact ca msg_id_from_block() din PHP.
 */
function parseMessageLog(raw: string, meta: Record<string, MsgMeta>) {
  const out = [];
  for (const chunk of raw.split(/(?=^===)/m)) {
    const block = chunk.trim();
    if (!block) continue;
    const head = block.match(/^===\s*(.*?)\s*\|\s*(\S+)\s*===/m);
    if (!head) continue;

    const fields: Record<string, string> = {};
    let lastKey: string | null = null;
    for (const line of block.replace(/^===.*===\n?/m, "").trim().split("\n").map((l) => l.trim())) {
      if (line === "---") break;
      if (!line) continue;
      const sep = line.indexOf(":");
      if (sep !== -1 && sep <= 40) {
        lastKey = line.slice(0, sep).trim();
        fields[lastKey] = line.slice(sep + 1).trim();
      } else if (lastKey) {
        fields[lastKey] += ` ${line}`;
      }
    }

    const m = meta[createHash("md5").update(block).digest("hex").slice(0, 12)] ?? {};
    const parsed = new Date(head[1].replace(" ", "T"));
    out.push({
      category: head[2] || "contact",
      name: fields.Nume ?? fields.nume ?? fields.Name ?? fields["Organizație"] ?? fields.organizatie ?? null,
      email: fields.Email ?? fields.email ?? null,
      fields,
      read: Boolean(m.read),
      rating: m.evaluation || null,
      contacted: Boolean(m.contacted),
      comments: m.comments ?? [],
      createdAt: Number.isNaN(parsed.getTime()) ? new Date() : parsed,
    });
  }
  return out;
}

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
      marketing_sections, marketing_items, collaborations, recurring_tasks,
      ab_experiments
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

    // instagram_posts.json — zilele marcate pe calendarul de cursuri
    if (bundle.instagram_posts && Object.keys(bundle.instagram_posts).length) {
      await db.query("INSERT INTO settings(key, value) VALUES('instagram_posts', $1)", [
        JSON.stringify(bundle.instagram_posts),
      ]);
    }

    // messages.log + message_meta.json — mesajele din formulare, cu evaluări,
    // marcajul „contactat" și comentariile. Re-import complet la fiecare rulare.
    if (bundle.messages_log) {
      await db.query("TRUNCATE messages RESTART IDENTITY");
      for (const m of parseMessageLog(bundle.messages_log, bundle.messages_meta ?? {})) {
        await db.query(
          `INSERT INTO messages(category, name, email, payload, read, rating, contacted, comments, created_at)
           VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9)`,
          [m.category, m.name, m.email, JSON.stringify(m.fields), m.read, m.rating, m.contacted, JSON.stringify(m.comments), m.createdAt]
        );
      }
    }

    // todos.json — lista de sarcini (live-ul e sursa de adevăr, re-import complet)
    if (Array.isArray(bundle.todos)) {
      await db.query("TRUNCATE todos RESTART IDENTITY");
      for (const t of bundle.todos) {
        await db.query(
          `INSERT INTO todos(legacy_id, title, assigned_to, created_by, completed, created_at, completed_at)
           VALUES($1,$2,$3,$4,$5,coalesce($6::timestamptz, now()),$7)`,
          [t.id ?? null, t.title ?? "", t.assigned_to ?? null, t.created_by ?? null,
           Boolean(t.completed), t.created_at ?? null, t.completed_at ?? null]
        );
      }
    }

    // ab_button.json — testul A/B pentru butonul „Vreau să vin"
    const abButton = bundle.ab_button ?? {};
    for (const [variant, m] of Object.entries(abButton)) {
      await db.query(
        "INSERT INTO ab_experiments(experiment, variant, views, conversions) VALUES('button', $1, $2, $3)",
        [variant, m.views ?? 0, m.clicks ?? 0]
      );
    }

    // recurring_tasks.json — definițiile taskurilor recurente (owner-managed)
    const recurring = bundle.recurring ?? [];
    for (const [ri, rt] of recurring.entries()) {
      const days = (rt.days ?? []).map(Number).filter((d) => d >= 1 && d <= 31);
      await db.query(
        `INSERT INTO recurring_tasks(legacy_id, type, system_key, assigned_to, title, schedule, description, days, position)
         VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9)`,
        [rt.id ?? null, rt.type ?? "monthly", rt.system_key ?? null, rt.assigned_to ?? null, rt.title ?? "", rt.schedule ?? null, rt.description ?? null, days, ri]
      );
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
    // speaker_id din card e id-ul legacy din speakers.json -> id-ul nou din Neon
    const speakerByLegacy = new Map<string, number>();
    for (const r of (await db.query("SELECT id, legacy_id FROM speakers WHERE legacy_id IS NOT NULL")).rows) {
      speakerByLegacy.set(String(r.legacy_id), Number(r.id));
    }
    let cardsMatched = 0, cardsNew = 0;
    for (const card of bundle.courses) {
      const startsAt = `${card.date_raw} ${card.time || "00:00"}`;
      const slug = slugFromUrl(card.livetickets_url);
      const existing = eventByExternal.get(card.id);
      const speakerId = speakerByLegacy.get(String(card.speaker_id ?? "")) ?? null;
      if (existing) {
        await db.query(
          `UPDATE events SET title=$1, slug=$2, legacy_card_id=$3,
             starts_at=($4::timestamp AT TIME ZONE $9),
             location=$5, livetickets_url=$6, image_url=$7, active=$8, speaker_name=$11,
             link_added_at=($12::timestamp AT TIME ZONE $9),
             discount_percent=$13, discount_ends_at=($14::timestamp AT TIME ZONE $9),
             speaker_id=$15, date_display=$16
           WHERE id=$10`,
          [card.title, slug, card.id, startsAt, card.location ?? null, card.livetickets_url ?? null, card.image_url ?? null, !!card.active, BUCHAREST, existing, card.speaker_name ?? null,
           card.link_added_at || null, Number(card.discount_percent) || null, card.discount_ends_at || null,
           speakerId, card.date_display ?? null]
        );
        cardsMatched++;
      } else {
        await db.query(
          `INSERT INTO events(title, slug, legacy_card_id, starts_at, location, livetickets_url, image_url, active, speaker_name,
                              link_added_at, discount_percent, discount_ends_at, speaker_id, date_display)
           VALUES($1,$2,$3,($4::timestamp AT TIME ZONE $9),$5,$6,$7,$8,$10,
                  ($11::timestamp AT TIME ZONE $9),$12,($13::timestamp AT TIME ZONE $9),$14,$15)`,
          [card.title, slug, card.id, startsAt, card.location ?? null, card.livetickets_url ?? null, card.image_url ?? null, !!card.active, BUCHAREST, card.speaker_name ?? null,
           card.link_added_at || null, Number(card.discount_percent) || null, card.discount_ends_at || null,
           speakerId, card.date_display ?? null]
        );
        cardsNew++;
      }
    }

    // 3c) course_clicks.json — contorul de click-uri pe carduri (după ce events au legacy_card_id)
    let clicksApplied = 0;
    for (const [cardId, n] of Object.entries(bundle.course_clicks ?? {})) {
      const res = await db.query("UPDATE events SET clicks=$1 WHERE legacy_card_id=$2", [n, cardId]);
      if (res.rowCount) clicksApplied++;
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

    // 5b) viza_subtips — altfel TRUNCATE de mai sus le golește și nu le mai pune
    //     nimeni la loc (seriile se pot re-extrage și din PDF, dar sursa e SQLite)
    let vizaOk = 0;
    for (const v of bundle.statistici.viza_subtips ?? []) {
      const eid = eventByStatId.get(v.course_id);
      if (!eid) continue;
      await db.query(
        `INSERT INTO viza_subtips(event_id, seria, tarif, nr_unitati, de_la, pana_la)
         VALUES($1,$2,$3,$4,$5,$6)`,
        [eid, v.seria ?? "", Number(v.tarif) || 0, Number(v.nr_unitati) || 0, v.de_la ?? null, v.pana_la ?? null]
      );
      vizaOk++;
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

    // 7b) collaborations (CRM branduri/parteneri)
    const collabs = bundle.collaborations ?? [];
    for (const [ci, col] of collabs.entries()) {
      await db.query(
        `INSERT INTO collaborations(legacy_id, name, contact, contact_info, status, notes, position)
         VALUES($1,$2,$3,$4,$5,$6,$7)`,
        [col.id ?? null, col.name ?? "", col.contact ?? null, col.contact_info ?? null, col.status ?? null, col.notes ?? null, ci]
      );
    }

    // 8) vote_courses
    for (const v of bundle.vote_courses) {
      await db.query(
        `INSERT INTO vote_courses(legacy_id, name, emoji, description, likes, active, views)
         VALUES($1,$2,$3,$4,$5,$6,$7)`,
        [v.id, v.name, v.emoji ?? null, v.description ?? null, v.likes ?? 0, v.active ?? true,
         Number(bundle.vote_views?.[v.id] ?? 0)]
      );
    }
    // vizitele pe pagina de vot (cheia '__page__' din vote_views.json)
    const votePageViews = Number(bundle.vote_views?.["__page__"] ?? 0);
    if (votePageViews) {
      await db.query("INSERT INTO settings(key, value) VALUES('vote_page_views', $1) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value", [
        JSON.stringify(votePageViews),
      ]);
    }

    // 9) P&L — categoriile de venituri (tabel curatat in pnl.sqlite, cu fallback pe
    // descrierile existente daca bundle-ul e mai vechi decat exportul cu ele)
    for (const nume of bundle.pnl.venit_categorii?.length
      ? bundle.pnl.venit_categorii.map((c) => c.nume)
      : [...new Set(bundle.pnl.venituri.map((v) => v.descriere))]) {
      await db.query("INSERT INTO venit_categorii(nume) VALUES($1) ON CONFLICT (nume) DO NOTHING", [nume]);
    }
    // categorii de cheltuieli din valori distincte, apoi FK
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
    console.log(`  recurring        ${recurring.length} taskuri`);
    console.log(`  ab_button        ${Object.keys(abButton).length} variante`);
    console.log(`  course_clicks    ${clicksApplied} events actualizate`);
    console.log(`  events           ${bundle.statistici.courses.length} stats + ${cardsNew} carduri noi (${cardsMatched} carduri unite)`);
    console.log(`  tickets          ${ticketsOk}${ticketsOrphan ? ` (${ticketsOrphan} orfane, ignorate)` : ""}`);
    console.log(`  event_reports    ${reportsOk}`);
    console.log(`  viza_subtips     ${vizaOk}`);
    console.log(`  speakers         ${bundle.speakers.length}`);
    console.log(`  locations        ${bundle.locations.length}`);
    console.log(`  collaborations   ${collabs.length}`);
    console.log(`  vote_courses     ${bundle.vote_courses.length}`);
    console.log(`  cheltuiala_cat.  ${chCat.size}`);
    console.log(`  venituri         ${bundle.pnl.venituri.length}`);
    console.log(`  cheltuieli       ${bundle.pnl.cheltuieli.length}`);
    console.log(`  todos            ${bundle.todos?.length ?? 0}`);
    console.log(`  messages         ${bundle.messages_log ? parseMessageLog(bundle.messages_log, bundle.messages_meta ?? {}).length : 0}`);
    console.log("\nNOTĂ: users și soldout NU sunt în bundle-ul live — se migrează separat.");
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
