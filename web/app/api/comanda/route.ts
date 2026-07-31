import { NextResponse } from "next/server";
import { sql } from "@/lib/db";
import { creeazaComanda, anuleazaComanda, type Linie } from "@/lib/comenzi";
import { startPayment } from "@/lib/netopia";

export const dynamic = "force-dynamic";

const SITE = "https://cursurilapahar.ro";

/** „12x2,13x1" → linii. */
function parseLinii(t: string): Linie[] {
  return t
    .split(",")
    .map((p) => /^(\d+)x(\d+)$/.exec(p.trim()))
    .filter((m): m is RegExpExecArray => !!m)
    .map((m) => ({ typeId: Number(m[1]), qty: Math.min(Number(m[2]), 10) }))
    .filter((x) => x.qty > 0);
}

export async function POST(req: Request) {
  const fd = await req.formData();
  const eventId = Number(fd.get("e"));
  const linii = parseLinii(String(fd.get("t") ?? ""));
  const nume = String(fd.get("nume") ?? "").trim();
  const email = String(fd.get("email") ?? "").trim().toLowerCase();

  const inapoi = (m: string) =>
    NextResponse.redirect(`${SITE}/cos?e=${eventId}&t=${fd.get("t") ?? ""}&err=${encodeURIComponent(m)}`, 303);

  if (!eventId || !linii.length) return inapoi("Comanda e goală.");
  if (!nume) return inapoi("Scrie-ți numele.");
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) return inapoi("Adresa de email nu pare validă.");

  const [ev] = (await sql`SELECT id, title, active FROM events WHERE id = ${eventId}`) as {
    id: number;
    title: string;
    active: boolean;
  }[];
  if (!ev?.active) return inapoi("Cursul nu mai e disponibil.");

  const c = await creeazaComanda(eventId, linii, nume, email);
  if (!c.ok) return inapoi(c.mesaj);

  const plata = await startPayment({
    cod: c.cod,
    suma: c.total,
    descriere: `Bilete - ${ev.title}`.slice(0, 100),
    nume,
    email,
    redirectUrl: `${SITE}/cos/plata?cod=${c.cod}`,
    notifyUrl: `${SITE}/api/netopia/confirm`,
  });

  if (!plata.ok) {
    await anuleazaComanda(c.orderId, plata.mesaj, "esuata");
    return inapoi(`Nu am putut porni plata: ${plata.mesaj}`);
  }

  await sql`UPDATE orders SET ntp_id = ${plata.ntpID} WHERE id = ${c.orderId}`;
  return NextResponse.redirect(plata.paymentURL, 303);
}
