import pg from "pg";
const c = new pg.Client({ connectionString: process.env.DATABASE_URL, ssl: { rejectUnauthorized: false } });
await c.connect();
// clp_date_display_from_raw() = clp_format_date_ro($raw, true, true): luna cu majusculă
const L = ["","Ianuarie","Februarie","Martie","Aprilie","Mai","Iunie","Iulie","August","Septembrie","Octombrie","Noiembrie","Decembrie"];
const rows = (await c.query(`SELECT id, to_char(starts_at AT TIME ZONE 'Europe/Bucharest','YYYY-MM-DD') d
  FROM events WHERE date_display IS NULL AND starts_at IS NOT NULL`)).rows;
for (const r of rows) {
  const [y,m,dd] = r.d.split("-");
  await c.query("UPDATE events SET date_display = $1 WHERE id = $2", [`${Number(dd)} ${L[Number(m)]} ${y}`, r.id]);
}
console.log("date_display completat pe", rows.length, "evenimente");
console.log((await c.query("SELECT date_display FROM events ORDER BY starts_at DESC LIMIT 3")).rows);
await c.end();
