// Trimite toate emailurile din jurul unui bilet, cu date de probă, către o adresă
// dată — ca să se poată citi în inbox real, nu doar în cod.
//   npx tsx --env-file=.env.local scripts/trimite-emailuri-test.mts adresa@exemplu.ro

import { neon } from "@neondatabase/serverless";
import { EMAILURI, ramaEmail, CTX_EXEMPLU } from "@/lib/emailuri";

const catre = process.argv[2];
if (!catre) throw new Error("Dă adresa ca argument.");

const sql = neon(process.env.DATABASE_URL!);
const [row] = (await sql`SELECT value FROM settings WHERE key = 'brevo_api_key'`) as { value: unknown }[];
const apiKey = String(row?.value ?? "").replace(/\s+/g, "");
if (!apiKey) throw new Error("Lipsește brevo_api_key din settings.");

for (const [cheie, t] of Object.entries(EMAILURI)) {
  const html = ramaEmail(t.subject(CTX_EXEMPLU), t.body(CTX_EXEMPLU));
  const res = await fetch("https://api.brevo.com/v3/smtp/email", {
    method: "POST",
    headers: { accept: "application/json", "api-key": apiKey, "content-type": "application/json" },
    body: JSON.stringify({
      sender: { name: "Cursuri la Pahar", email: "contact@cursurilapahar.ro" },
      to: [{ email: catre }],
      replyTo: { name: "Cursuri la Pahar", email: "contact@cursurilapahar.ro" },
      subject: `[TEST ${cheie}] ${t.subject(CTX_EXEMPLU)}`,
      htmlContent: html,
    }),
  });
  console.log(res.ok ? `OK   ${cheie.padEnd(16)} ${t.subject(CTX_EXEMPLU)}` : `EROARE ${cheie}: ${res.status} ${await res.text()}`);
}
