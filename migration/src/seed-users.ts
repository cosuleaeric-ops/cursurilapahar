/**
 * Seed users în Neon din data/users.json (nu-s în bundle-ul sync-export).
 * Hash-urile bcrypt PHP ($2y$) sunt portabile în bcryptjs.
 * Rulează:  npm run seed-users
 * Idempotent: ON CONFLICT (username) actualizează hash + rol.
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

type User = { username: string; password_hash: string; role: string };
const users: User[] = JSON.parse(readFileSync("../data/users.json", "utf8"));

const db = new Client({ connectionString: process.env.DATABASE_URL });
await db.connect();
try {
  for (const u of users) {
    await db.query(
      `INSERT INTO users(username, password_hash, role)
       VALUES($1, $2, $3)
       ON CONFLICT (username) DO UPDATE SET password_hash = EXCLUDED.password_hash, role = EXCLUDED.role`,
      [u.username, u.password_hash, u.role]
    );
    console.log(`  ✓ ${u.username} (${u.role})`);
  }
  console.log(`✓ ${users.length} useri seed-uiți în Neon`);
} finally {
  await db.end();
}
