/**
 * Restaurare din backup-ul JSON făcut cu backup/neon-*.json.
 * Rulare:  node --env-file=.env src/restore.mjs backup/neon-<stamp>.json --apply
 * Fără --apply doar raportează ce ar face.
 */
import pg from "pg"; import fs from "node:fs";
const file = process.argv[2];
const apply = process.argv.includes("--apply");
if (!file) { console.error("Dă calea către backup: node --env-file=.env src/restore.mjs backup/neon-....json [--apply]"); process.exit(1); }
const d = JSON.parse(fs.readFileSync(file, "utf8"));
const c = new pg.Client({ connectionString: process.env.DATABASE_URL, ssl: { rejectUnauthorized: false } });
await c.connect();
console.log("backup din", d.luat_la, "|", Object.keys(d.tabele).length, "tabele");
if (!apply) {
  for (const [t, rows] of Object.entries(d.tabele)) {
    const acum = (await c.query(`SELECT count(*)::int n FROM "${t}"`)).rows[0].n;
    if (acum !== rows.length) console.log(`  ${t.padEnd(24)} acum ${acum} → backup ${rows.length}`);
  }
  console.log("\nDRY-RUN. Adaugă --apply ca să restaurezi.");
} else {
  await c.query("BEGIN");
  for (const [t, rows] of Object.entries(d.tabele)) {
    await c.query(`TRUNCATE "${t}" CASCADE`);
    for (const row of rows) {
      const cols = Object.keys(row);
      const vals = cols.map(k => (row[k] !== null && typeof row[k] === "object") ? JSON.stringify(row[k]) : row[k]);
      await c.query(
        `INSERT INTO "${t}"(${cols.map(x=>`"${x}"`).join(",")}) OVERRIDING SYSTEM VALUE VALUES(${cols.map((_,i)=>"$"+(i+1)).join(",")})`,
        vals);
    }
    console.log(`  ${t}: ${rows.length}`);
  }
  await c.query("COMMIT");
  console.log("\nRestaurat.");
}
await c.end();
