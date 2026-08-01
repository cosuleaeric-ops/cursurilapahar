// La o verificare picată, motivul singur nu ajunge: ca să afli CE cheie ar fi
// verificat semnătura, îți trebuie tokenul și corpul exact, păstrate ca atare.
import { neon } from "@neondatabase/serverless";
import { readFileSync } from "node:fs";

const url = /^DATABASE_URL=["']?(.+?)["']?$/m.exec(readFileSync(".env.local", "utf8"))[1];
const sql = neon(url);
await sql`ALTER TABLE webhook_log ADD COLUMN IF NOT EXISTS token text`;
await sql`ALTER TABLE webhook_log ADD COLUMN IF NOT EXISTS corp text`;
console.log(await sql`SELECT column_name FROM information_schema.columns WHERE table_name='webhook_log' ORDER BY ordinal_position`);
