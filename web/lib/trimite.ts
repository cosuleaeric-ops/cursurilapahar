import { sql } from "@/lib/db";
import { biletele } from "@/lib/comenzi";
import { CTX_EXEMPLU, EMAILURI, ramaEmail, type CtxEmail } from "@/lib/emailuri";

// Trimiterea propriu-zisă a emailurilor din jurul unei comenzi. Best-effort:
// dacă pică Brevo, comanda rămâne plătită și biletele emise — se retrimit din
// admin.

const TZ = "Europe/Bucharest";
const ziFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, weekday: "long", day: "numeric", month: "long" });
const oraFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, hour: "2-digit", minute: "2-digit" });
const bani = (v: number) => v.toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const pad = (n: number) => String(n).padStart(4, "0");

async function brevo(catre: string, nume: string, subiect: string, html: string): Promise<boolean> {
  const rows = (await sql`SELECT value FROM settings WHERE key = 'brevo_api_key'`) as { value: unknown }[];
  const apiKey = String(rows[0]?.value ?? "").replace(/\s+/g, "");
  if (!apiKey) return false;
  const res = await fetch("https://api.brevo.com/v3/smtp/email", {
    method: "POST",
    headers: { accept: "application/json", "api-key": apiKey, "content-type": "application/json" },
    body: JSON.stringify({
      sender: { name: "Cursuri la Pahar", email: "contact@cursurilapahar.ro" },
      to: [{ email: catre, name: nume || catre }],
      replyTo: { name: "Cursuri la Pahar", email: "contact@cursurilapahar.ro" },
      subject: subiect,
      htmlContent: html,
    }),
  });
  return res.ok;
}

/** Contextul pentru șabloane, construit din comanda reală. */
export async function ctxComanda(orderId: number): Promise<CtxEmail | null> {
  const [o] = (await sql`
    SELECT o.id, o.cod, o.buyer_name, o.buyer_email, o.total,
           e.id AS event_id, e.title, e.slug, e.starts_at, e.location
    FROM orders o JOIN events e ON e.id = o.event_id WHERE o.id = ${orderId}
  `) as {
    id: number;
    cod: string;
    buyer_name: string;
    buyer_email: string;
    total: string;
    event_id: number;
    title: string;
    slug: string | null;
    starts_at: string | null;
    location: string | null;
  }[];
  if (!o) return null;

  const d = o.starts_at ? new Date(o.starts_at) : null;
  const acces = d ? new Date(d.getTime() - 30 * 60_000) : null;
  const bilete = (await biletele(orderId)).map((b) => ({
    serie: b.serie,
    numar: pad(b.numar),
    tip: b.tip,
    link: `https://cursurilapahar.ro/bilet/${b.qr_token}`,
  }));
  const [loc, ...rest] = (o.location ?? "").split(",").map((x) => x.trim());

  return {
    ...CTX_EXEMPLU,
    nume: o.buyer_name.split(/\s+/)[0] || o.buyer_name,
    curs: o.title.replace(/\s+\/\/\s+.+$/u, ""),
    data: d ? ziFmt.format(d) : "",
    ora: d ? oraFmt.format(d) : "",
    acces: acces ? oraFmt.format(acces) : "",
    locatie: loc ?? "",
    adresa: rest.join(", "),
    bilete,
    total: `${bani(Number(o.total))} lei`,
    linkCurs: `https://cursurilapahar.ro/curs/${o.slug ?? ""}`,
    linkComanda: `https://cursurilapahar.ro/curs/${o.slug ?? ""}`,
  };
}

async function trimite(orderId: number, sablon: keyof typeof EMAILURI): Promise<boolean> {
  try {
    const ctx = await ctxComanda(orderId);
    if (!ctx) return false;
    const [o] = (await sql`SELECT buyer_email, buyer_name FROM orders WHERE id = ${orderId}`) as {
      buyer_email: string;
      buyer_name: string;
    }[];
    const t = EMAILURI[sablon];
    return await brevo(o.buyer_email, o.buyer_name, t.subject(ctx), ramaEmail(t.subject(ctx), t.body(ctx)));
  } catch {
    return false;
  }
}

export const trimiteEmailComanda = (orderId: number) => trimite(orderId, "comanda");
export const trimiteEmailPlataEsuata = (orderId: number) => trimite(orderId, "plata_esuata");
export const retrimiteBilete = (orderId: number) => trimite(orderId, "retrimitere");
