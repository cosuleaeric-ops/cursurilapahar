/**
 * Re-extrage seriile de viză din PDF-urile urcate în Blob și le scrie în Neon.
 *
 * De rulat DUPĂ `npm run migrate` și `npm run migrate-files -- --apply`:
 * migrate golește viza_subtips și pune la loc ce e în SQLite (83 de serii), dar
 * parserul din port scoate mai multe — prinde și seriile partajate între
 * produse, pe care PHP-ul nu le prinde.
 *
 *   cd web && DATABASE_URL=... npx tsx scripts-extrage-viza.mts
 */
import { neon } from "@neondatabase/serverless";
import { parseVizaSubtips, pdfToText } from "./lib/rapoarte.js";

const sql = neon(process.env.DATABASE_URL!);
const files = (await sql`
  SELECT event_id, blob_url FROM event_files WHERE file_type='viza' ORDER BY event_id
`) as { event_id: number; blob_url: string }[];

await sql`TRUNCATE viza_subtips RESTART IDENTITY`;
let total = 0;
const fara: number[] = [];
for (const f of files) {
  const buf = new Uint8Array(await (await fetch(f.blob_url)).arrayBuffer());
  let subs: ReturnType<typeof parseVizaSubtips> = [];
  try {
    subs = parseVizaSubtips(await pdfToText(buf));
  } catch {
    // PDF ilizibil — îl raportăm la final, nu oprim tot
  }
  if (!subs.length) fara.push(f.event_id);
  for (const s of subs) {
    await sql`INSERT INTO viza_subtips(event_id, seria, tarif, nr_unitati, de_la, pana_la)
              VALUES(${f.event_id}, ${s.seria}, ${s.tarif}, ${s.nr_unitati}, ${s.de_la ?? null}, ${s.pana_la ?? null})`;
    total++;
  }
}
console.log(`✓ ${total} serii din ${files.length} PDF-uri`);
if (fara.length) console.log(`  fără serii: evenimentele ${fara.join(", ")}`);
