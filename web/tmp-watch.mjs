import { neon } from "@neondatabase/serverless";
import { readFileSync } from "node:fs";
const url = /^DATABASE_URL=["']?(.+?)["']?$/m.exec(readFileSync(".env.local","utf8"))[1];
const sql = neon(url);
let last = Number((await sql`SELECT coalesce(max(id),0)::int AS m FROM webhook_log`)[0].m);
const start = Date.now();
while (Date.now() - start < 1500000) {
  try {
    for (const r of await sql`SELECT id, ok, cod, motiv FROM webhook_log WHERE id > ${last} ORDER BY id`) {
      last = r.id;
      console.log(`${r.ok ? "OK" : "ESEC"} ${r.cod ?? "-"} | ${r.motiv.slice(0, 120)}`);
    }
  } catch (e) {
    console.log("EROARE jurnal:", String(e).slice(0, 80));
  }
  await new Promise((r) => setTimeout(r, 20000));
}
