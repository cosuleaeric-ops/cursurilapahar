// Urma fiecărei notificări de la Netopia. Fără ea, o verificare picată e
// invizibilă: nu se poate spune dacă notificarea n-a venit deloc sau a venit și
// am respins-o noi.
import { neon } from "@neondatabase/serverless";
import { readFileSync } from "node:fs";

const url = /^DATABASE_URL=["']?(.+?)["']?$/m.exec(readFileSync(".env.local", "utf8"))[1];
const sql = neon(url);

await sql`
  CREATE TABLE IF NOT EXISTS webhook_log (
    id serial PRIMARY KEY,
    created_at timestamptz NOT NULL DEFAULT now(),
    ok boolean NOT NULL,
    motiv text NOT NULL,
    cod text,
    status integer
  )
`;
await sql`CREATE INDEX IF NOT EXISTS webhook_log_created_idx ON webhook_log (created_at DESC)`;
console.log("gata:", await sql`SELECT column_name FROM information_schema.columns WHERE table_name='webhook_log' ORDER BY ordinal_position`);
