/**
 * Aplică neon_schema.sql pe baza Neon (fără psql).
 * Rulează:  npm run schema
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

if (!process.env.DATABASE_URL) throw new Error("Setează DATABASE_URL în .env");
const sql = readFileSync("neon_schema.sql", "utf8");
const db = new Client({ connectionString: process.env.DATABASE_URL });
await db.connect();
try {
  await db.query(sql);
  console.log("✓ Schema aplicată (neon_schema.sql)");
} finally {
  await db.end();
}
