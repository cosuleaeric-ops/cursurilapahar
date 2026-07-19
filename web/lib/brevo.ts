// Confirmări automate prin Brevo pentru formularele publice — port din api/contact.php.
// Best-effort: erorile nu blochează salvarea mesajului.

import { sql } from "@/lib/db";

const IG = "http://instagram.com/cursurilapahar";
const LNK = "color:#7a2733;font-weight:bold;text-decoration:none";

const CONFIRMATIONS: Record<string, { subject: string; body: string }> = {
  contact: {
    subject: "Am primit mesajul tău 🍻",
    body:
      '<p style="margin:0 0 14px">Salutare!</p>' +
      '<p style="margin:0 0 14px">Îți mulțumim că ne-ai scris. Am primit mesajul tău și îl citim cu atenție.</p>' +
      `<p style="margin:0 0 14px">Îți răspundem cât mai curând, de obicei în câteva zile lucrătoare. Dacă între timp ai o întrebare rapidă, ne găsești oricând pe <a href="${IG}" style="${LNK}">Instagram</a>.</p>` +
      '<p style="margin:0 0 14px">Apreciem enorm că faci parte din comunitatea Cursuri la Pahar.</p>' +
      '<p style="margin:0">Ținem legătura!</p>',
  },
  sustine: {
    subject: "Am primit propunerea ta de curs 🎤",
    body:
      '<p style="margin:0 0 14px">Salutare!</p>' +
      '<p style="margin:0 0 14px">Îți mulțumim că ne-ai contactat și ne-ai oferit detaliile referitoare la cursul pe care vrei să îl susții. 🍷</p>' +
      '<p style="margin:0 0 14px">Primim recurent un număr ridicat de propuneri de cursuri și le revizuim săptămânal, pe măsură ce ne planificăm următoarele evenimente. Dacă subiectul și expertiza ta se potrivesc cu publicul nostru, <b>vom reveni cu un mesaj pe WhatsApp</b>.</p>' +
      '<p style="margin:0 0 14px">Apreciem enorm interesul tău de a face parte din comunitatea Cursuri la Pahar, precum și dorința de a pune o cărămidă în domeniul educației.</p>' +
      '<p style="margin:0">Ținem legătura!</p>',
  },
  gazduieste: {
    subject: "Am primit propunerea ta de locație 🏠",
    body:
      '<p style="margin:0 0 14px">Salutare!</p>' +
      '<p style="margin:0 0 14px">Îți mulțumim că ne-ai oferit detaliile despre locația ta. Ne bucurăm că vrei să găzduiești un curs la pahar. 🍷</p>' +
      '<p style="margin:0 0 14px">Analizăm fiecare propunere de spațiu pe măsură ce ne planificăm următoarele evenimente și ne asigurăm că atmosfera se potrivește cu vibe-ul comunității noastre. Dacă e o potrivire, <b>vom reveni cu un mesaj pe WhatsApp</b> ca să punem la punct detaliile.</p>' +
      '<p style="margin:0 0 14px">Apreciem enorm că vrei să deschizi ușa locației tale către oameni curioși și să faci parte din comunitatea Cursuri la Pahar.</p>' +
      '<p style="margin:0">Ținem legătura!</p>',
  },
  parteneriat: {
    subject: "Am primit propunerea ta de parteneriat 🤝",
    body:
      '<p style="margin:0 0 14px">Salutare!</p>' +
      '<p style="margin:0 0 14px">Îți mulțumim că ne-ai contactat și ne-ai oferit detaliile despre parteneriatul pe care îl ai în minte. 🍷</p>' +
      '<p style="margin:0 0 14px">Analizăm fiecare propunere de colaborare cu atenție și ne uităm la cum putem construi împreună ceva care aduce valoare reală comunității noastre. Dacă vedem o potrivire, <b>revenim cu un mesaj</b> ca să discutăm pașii următori.</p>' +
      '<p style="margin:0 0 14px">Apreciem enorm interesul tău de a face parte din povestea Cursuri la Pahar.</p>' +
      '<p style="margin:0">Ținem legătura!</p>',
  },
};

export async function sendConfirmationEmail(category: string, email: string, name: string): Promise<void> {
  try {
    const rows = (await sql`SELECT value FROM settings WHERE key = 'brevo_api_key'`) as { value: unknown }[];
    const apiKey = String(rows[0]?.value ?? "").replace(/\s+/g, "");
    if (!apiKey) return;

    const conf = CONFIRMATIONS[category] ?? CONFIRMATIONS.contact;
    const banner = "https://cursurilapahar.ro/assets/images/email.jpeg";
    const html =
      '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 0"><tr><td align="center">' +
      "<table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;font-family:Georgia,'Times New Roman',serif\">" +
      `<tr><td><img src="${banner}" alt="Cursuri la Pahar" width="600" style="display:block;width:100%;height:auto;border:0"></td></tr>` +
      '<tr><td style="padding:28px 32px;color:#2b2b2b;font-size:16px;line-height:1.65">' +
      `<div style="font-size:25px;font-weight:bold;color:#1a1a1a;margin-bottom:18px">${conf.subject}</div>` +
      conf.body +
      '<p style="margin:18px 0 4px">Cu drag,</p>' +
      '<p style="margin:0;font-weight:bold;color:#1a1a1a">Echipa Cursuri la Pahar</p>' +
      "</td></tr>" +
      '<tr><td style="padding:18px 32px 26px;border-top:1px solid #eaeaea;text-align:center;font-family:Arial,Helvetica,sans-serif;font-size:13px">' +
      `<a href="${IG}" style="${LNK};margin:0 10px">Instagram</a>` +
      `<a href="https://facebook.com/cursurilapahar" style="${LNK};margin:0 10px">Facebook</a>` +
      `<a href="https://tiktok.com/@cursurilapahar" style="${LNK};margin:0 10px">TikTok</a>` +
      "</td></tr>" +
      "</table></td></tr></table>";

    await fetch("https://api.brevo.com/v3/smtp/email", {
      method: "POST",
      headers: { accept: "application/json", "api-key": apiKey, "content-type": "application/json" },
      body: JSON.stringify({
        sender: { name: "Cursuri la Pahar", email: "contact@cursurilapahar.ro" },
        to: [{ email, name: name || email }],
        replyTo: { name: "Cursuri la Pahar", email: "contact@cursurilapahar.ro" },
        subject: conf.subject,
        htmlContent: html,
      }),
    });
  } catch {
    // best-effort — confirmarea nu blochează salvarea mesajului
  }
}
