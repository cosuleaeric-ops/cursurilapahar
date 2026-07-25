/**
 * Contopește duplicatele din tabelul `speakers` direct în Neon, cu aceleași
 * reguli ca PHP-ul (lib/speakers.php: clp_deduplicate_speakers(),
 * clp_speaker_dedupe_key(), clp_normalize_speaker_phone(),
 * clp_merge_speaker_entries()).
 *
 * Pe PHP, load_speakers() rescrie speakers.json după dedup, deci duplicatele
 * dispar din date. În Next dedup-ul e doar la afișare, deci rândurile duble
 * rămân în Neon — scriptul ăsta le contopește definitiv.
 *
 * Rulează:  npm run dedupe-speakers              (DRY-RUN, nu scrie nimic)
 *           npm run dedupe-speakers -- --apply   (execută)
 */
import { readFileSync, existsSync } from "node:fs";
import { Client } from "pg";

function loadEnv(): void {
  if (!existsSync(".env")) return;
  for (const line of readFileSync(".env", "utf8").split("\n")) {
    const m = line.match(/^\s*([A-Z_][A-Z0-9_]*)\s*=\s*(.*)\s*$/);
    if (m && process.env[m[1]] === undefined) process.env[m[1]] = m[2].replace(/^["']|["']$/g, "");
  }
}
loadEnv();

const APPLY = process.argv.includes("--apply");

interface SpeakerRow {
  id: string;
  legacy_id: string | null;
  name: string | null;
  email: string | null;
  phone: string | null;
  status: string | null;
  notes: string | null;
  topics: string[] | null;
  meet: Record<string, unknown> | null;
}

// --- regulile din lib/speakers.php ------------------------------------------

/** clp_normalize_speaker_email() */
const normEmail = (v: string | null | undefined) => (v ?? "").trim().toLowerCase();

/** clp_normalize_speaker_phone(): doar cifrele, fără prefixul 40 (min. 11 cifre) sau 0 (min. 10) */
function normPhone(v: string | null | undefined): string {
  let d = (v ?? "").trim().replace(/\D+/g, "");
  if (d === "") return "";
  if (d.startsWith("40") && d.length >= 11) d = d.slice(2);
  if (d.startsWith("0") && d.length >= 10) d = d.slice(1);
  return d;
}

/** clp_speaker_dedupe_key(): email, altfel telefon, altfel nume */
function dedupeKey(s: SpeakerRow): string {
  const email = normEmail(s.email);
  if (email !== "") return `e:${email}`;
  const phone = normPhone(s.phone);
  if (phone !== "") return `p:${phone}`;
  return `n:${(s.name ?? "").trim().toLowerCase()}`;
}

/** clp_speaker_status_order() + clp_speaker_status_rank(): necunoscut => 2 */
const STATUS_ORDER: Record<string, number> = { CONTACTAT: 0, "URMEAZĂ": 1, RECURENT: 2, MID: 3, NOPE: 4 };
const statusRank = (s: string) => STATUS_ORDER[s] ?? 2;
/** ca în PHP: `$sp['status'] ?? 'MID'` (NULL => MID, '' rămâne '' și cade pe rangul 2) */
const statusOf = (s: SpeakerRow) => s.status ?? "MID";

/** clp_merge_speaker_entries() — mută pe fișa păstrată doar ce lipsește */
function mergeSpeakers(keep: SpeakerRow, other: SpeakerRow): SpeakerRow {
  const out: SpeakerRow = { ...keep };

  // courses -> topics: array_unique(array_filter(array_merge(...))) (PHP aruncă '' și '0')
  const seen = new Set<string>();
  const topics: string[] = [];
  for (const t of [...(keep.topics ?? []), ...(other.topics ?? [])]) {
    if (t === "" || t === "0" || seen.has(t)) continue;
    seen.add(t);
    topics.push(t);
  }
  out.topics = topics;

  // clp_pick_speaker_status(): câștigă rangul mai mic, la egalitate rămâne fișa păstrată
  out.status = statusRank(statusOf(keep)) <= statusRank(statusOf(other)) ? statusOf(keep) : statusOf(other);

  for (const f of ["name", "email", "phone", "notes"] as const) {
    if ((keep[f] ?? "").trim() === "" && (other[f] ?? "").trim() !== "") out[f] = (other[f] ?? "").trim();
  }

  const meet: Record<string, unknown> = { ...(keep.meet ?? {}) };
  for (const [k, v] of Object.entries(other.meet ?? {})) {
    if (String(v ?? "").trim() !== "" && String(meet[k] ?? "").trim() === "") meet[k] = String(v).trim();
  }
  out.meet = meet;

  return out;
}

// --- raportare ---------------------------------------------------------------

const label = (s: SpeakerRow) => `#${s.id} ${s.name || "(fără nume)"} <${s.email || "-"}> ${s.phone || "-"}`;
const sameTopics = (a: string[], b: string[]) => a.length === b.length && a.every((v, i) => v === b[i]);
const quoteIdent = (v: string) => `"${v.replace(/"/g, '""')}"`;

const db = new Client({ connectionString: process.env.DATABASE_URL });
await db.connect();

try {
  // 1) ce tabele referă speakers (events.speaker_id + orice FK adăugat ulterior)
  const { rows: deps } = await db.query<{ table_name: string; column_name: string }>(
    `SELECT c.conrelid::regclass::text AS table_name, a.attname AS column_name
       FROM pg_constraint c
       JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
      WHERE c.contype = 'f' AND c.confrelid = 'speakers'::regclass
        AND array_length(c.conkey, 1) = 1
      ORDER BY 1, 2`
  );
  console.log(`Mod: ${APPLY ? "APPLY (scrie în Neon)" : "DRY-RUN (nu scrie nimic)"}`);
  console.log(`Tabele care referă speakers: ${deps.length ? deps.map((d) => `${d.table_name}.${d.column_name}`).join(", ") : "(niciunul)"}`);

  // 2) fișele, în ordinea inserării = ordinea din speakers.json pe care o vedea PHP-ul
  const { rows } = await db.query<SpeakerRow>(
    `SELECT id::text, legacy_id, name, email, phone, status, notes, topics, meet
       FROM speakers ORDER BY id`
  );
  console.log(`Fișe în Neon: ${rows.length}\n`);

  // 3) grupare pe cheia de dedup (fișele fără nume sunt aruncate de PHP)
  const nameless = rows.filter((r) => (r.name ?? "").trim() === "");
  const groups = new Map<string, { keep: SpeakerRow; merged: SpeakerRow; dups: SpeakerRow[] }>();
  for (const r of rows) {
    if ((r.name ?? "").trim() === "") continue;
    const key = dedupeKey(r);
    const g = groups.get(key);
    if (!g) {
      groups.set(key, { keep: r, merged: r, dups: [] });
      continue;
    }
    g.merged = mergeSpeakers(g.merged, r);
    g.dups.push(r);
  }

  const toMerge = [...groups.entries()].filter(([, g]) => g.dups.length > 0);

  // 4) raport
  const deleteIds: string[] = [];
  const plan: { keepId: string; merged: SpeakerRow; dupIds: string[] }[] = [];

  for (const [key, g] of toMerge) {
    console.log(`Cheie ${key}`);
    console.log(`  PĂSTREZ  ${label(g.keep)}`);
    for (const d of g.dups) console.log(`  ȘTERG    ${label(d)}`);

    const changes: string[] = [];
    for (const f of ["name", "email", "phone", "notes", "status"] as const) {
      const before = g.keep[f] ?? null;
      const after = f === "status" ? g.merged.status : g.merged[f] ?? null;
      if (before !== after) changes.push(`${f}: ${JSON.stringify(before)} -> ${JSON.stringify(after)}`);
    }
    if (!sameTopics(g.keep.topics ?? [], g.merged.topics ?? [])) {
      changes.push(`topics: ${(g.keep.topics ?? []).length} -> ${(g.merged.topics ?? []).length} teme`);
    }
    if (JSON.stringify(g.keep.meet ?? {}) !== JSON.stringify(g.merged.meet ?? {})) changes.push("meet: completat din duplicat");
    console.log(changes.length ? `  Modific fișa păstrată: ${changes.join("; ")}` : "  Fișa păstrată rămâne neschimbată");

    const dupIds = g.dups.map((d) => d.id);
    for (const d of deps) {
      const { rows: cnt } = await db.query<{ n: string }>(
        `SELECT count(*)::text n FROM ${d.table_name} WHERE ${quoteIdent(d.column_name)} = ANY($1::bigint[])`,
        [dupIds]
      );
      if (cnt[0].n !== "0") console.log(`  Repointez ${cnt[0].n} rând(uri) ${d.table_name}.${d.column_name} -> #${g.keep.id}`);
    }

    deleteIds.push(...dupIds);
    plan.push({ keepId: g.keep.id, merged: g.merged, dupIds });
    console.log("");
  }

  // fișele fără nume: PHP le scoate din date la prima citire
  const namelessBlocked: string[] = [];
  for (const n of nameless) {
    let referenced = 0;
    for (const d of deps) {
      const { rows: cnt } = await db.query<{ n: string }>(
        `SELECT count(*)::text n FROM ${d.table_name} WHERE ${quoteIdent(d.column_name)} = $1::bigint`,
        [n.id]
      );
      referenced += Number(cnt[0].n);
    }
    if (referenced > 0) {
      namelessBlocked.push(n.id);
      console.log(`Fișă fără nume ${label(n)} — SAR peste (are ${referenced} referințe, PHP nu are echivalent)`);
    } else {
      console.log(`Fișă fără nume ${label(n)} — ȘTERG (PHP o aruncă la dedup)`);
      deleteIds.push(n.id);
    }
  }

  if (toMerge.length === 0 && nameless.length === 0) {
    console.log("Fără duplicate și fără fișe goale. Nimic de făcut.");
  } else {
    console.log(`\nTotal: ${toMerge.length} grup(uri) de contopit, ${deleteIds.length} fișă/fișe de șters.`);
  }

  if (!APPLY) {
    console.log("\nDRY-RUN: nu s-a scris nimic. Rulează cu --apply ca să execute.");
  } else if (deleteIds.length === 0) {
    console.log("\nNimic de aplicat.");
  } else {
    await db.query("BEGIN");
    try {
      for (const p of plan) {
        // întâi mut dependențele pe fișa păstrată, ca să pot șterge duplicatele
        for (const d of deps) {
          await db.query(
            `UPDATE ${d.table_name} SET ${quoteIdent(d.column_name)} = $1::bigint
              WHERE ${quoteIdent(d.column_name)} = ANY($2::bigint[])`,
            [p.keepId, p.dupIds]
          );
        }
        await db.query(
          `UPDATE speakers SET name=$2, email=$3, phone=$4, status=$5, notes=$6, topics=$7, meet=$8::jsonb, updated_at=now()
            WHERE id=$1::bigint`,
          [p.keepId, p.merged.name, p.merged.email, p.merged.phone, p.merged.status, p.merged.notes,
           p.merged.topics ?? [], JSON.stringify(p.merged.meet ?? {})]
        );
      }
      const { rowCount } = await db.query(`DELETE FROM speakers WHERE id = ANY($1::bigint[])`, [deleteIds]);
      await db.query("COMMIT");
      console.log(`\n✓ Aplicat: ${plan.length} fișe contopite, ${rowCount} șterse.`);
      if (namelessBlocked.length) console.log(`  Sărite (referite): ${namelessBlocked.map((i) => `#${i}`).join(", ")}`);
    } catch (e) {
      await db.query("ROLLBACK");
      throw e;
    }
  }
} finally {
  await db.end();
}
