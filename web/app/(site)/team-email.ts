// Notificarea internă către echipă: api/contact.php:41-67 trimitea tot conținutul
// formularului la contact@cursurilapahar.ro, cu Reply-To pe emailul vizitatorului.
// Pe Next nu există mail(), deci pleacă prin Brevo, cu aceeași cheie ca la confirmări.
// Best-effort: o eroare nu blochează salvarea mesajului.

import { sql } from "@/lib/db";
import { phpDate } from "@/lib/messages";

const TEAM_EMAIL = "contact@cursurilapahar.ro";

/** api/contact.php:30-39 — subiectul depinde de tipul formularului. */
const SUBJECTS: Record<string, string> = {
  contact: "Mesaj nou de pe site",
  sustine: "Cerere nouă: Prezintă un curs",
  gazduieste: "Cerere nouă: Găzduiește un curs",
  parteneriat: "Cerere nouă: Propune un parteneriat",
  sponsorizare: "Cerere nouă: Parteneriat",
};

export async function sendTeamNotification(
  formType: string,
  visitorEmail: string,
  payload: Record<string, string>,
): Promise<void> {
  try {
    const rows = (await sql`SELECT value FROM settings WHERE key = 'brevo_api_key'`) as { value: unknown }[];
    const apiKey = String(rows[0]?.value ?? "").replace(/\s+/g, "");
    if (!apiKey) return;

    // api/contact.php:39 + 50 — subiect cu sufixul brandului, corp „Cheie: valoare" + data.
    const subject = `${SUBJECTS[formType] ?? "Mesaj nou"} — Cursuri la Pahar`;
    const text =
      Object.entries(payload)
        .map(([k, v]) => `${k}: ${v}`)
        .join("\n") + `\n\n---\nData: ${phpDate(new Date())}`;

    await fetch("https://api.brevo.com/v3/smtp/email", {
      method: "POST",
      headers: { accept: "application/json", "api-key": apiKey, "content-type": "application/json" },
      body: JSON.stringify({
        sender: { name: "Cursuri la Pahar", email: TEAM_EMAIL },
        to: [{ email: TEAM_EMAIL }],
        replyTo: { email: visitorEmail },
        subject,
        textContent: text,
      }),
    });
  } catch {
    // best-effort, ca @mail() din PHP
  }
}
