/**
 * Verificare integritate după migrare (join-uri, FK, agregate).
 * Rulează:  npm run verify
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

const db = new Client({ connectionString: process.env.DATABASE_URL });
await db.connect();

const q = async (label: string, sql: string) => {
  const { rows } = await db.query(sql);
  console.log(`\n${label}`);
  for (const r of rows) console.log("  ", JSON.stringify(r));
};

try {
  await q("Events: total / active / cu card unit / cu viza",
    `SELECT count(*) total,
            count(*) FILTER (WHERE active) active,
            count(*) FILTER (WHERE legacy_card_id IS NOT NULL) cu_card,
            count(*) FILTER (WHERE viza_done) viza_done
     FROM events`);

  await q("Top 5 events după bilete (join tickets)",
    `SELECT e.title, count(t.id) bilete
     FROM events e LEFT JOIN tickets t ON t.event_id = e.id
     GROUP BY e.id ORDER BY bilete DESC LIMIT 5`);

  await q("Integritate FK: bilete/rapoarte orfane (trebuie 0)",
    `SELECT
       (SELECT count(*) FROM tickets t LEFT JOIN events e ON e.id=t.event_id WHERE e.id IS NULL) tickets_orfane,
       (SELECT count(*) FROM event_reports r LEFT JOIN events e ON e.id=r.event_id WHERE e.id IS NULL) rapoarte_orfane,
       (SELECT count(*) FROM cheltuieli WHERE categorie_id IS NULL) cheltuieli_fara_categorie`);

  await q("P&L: total venituri vs cheltuieli",
    `SELECT
       (SELECT round(sum(suma)) FROM venituri) total_venituri,
       (SELECT round(sum(suma)) FROM cheltuieli) total_cheltuieli`);

  await q("Rapoarte: total încasări declarate + types_json parsabil",
    `SELECT round(sum(total_incasari)) total_incasari,
            count(*) FILTER (WHERE jsonb_typeof(types_json) = 'array') types_ok
     FROM event_reports`);

  await q("Speakers: câți au teme (topics)",
    `SELECT count(*) total, count(*) FILTER (WHERE array_length(topics,1) > 0) cu_teme
     FROM speakers`);

  await q("Settings: câteva chei",
    `SELECT key, left(value::text, 40) AS value FROM settings
     WHERE key IN ('hero_title','announcement','hero_images') ORDER BY key`);

  console.log("\n✓ Verificare terminată.");
} finally {
  await db.end();
}
