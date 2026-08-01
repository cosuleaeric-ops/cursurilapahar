import { sql } from "@/lib/db";

// O comandă rezervă bilete din pool înainte de plată și le eliberează dacă
// plata nu vine. Biletele trec prin trei stări: liber → rezervat → vandut.

export type Linie = { typeId: number; qty: number };

/** Rezervările mai vechi de atât se eliberează singure la următoarea comandă. */
const MINUTE_REZERVARE = 30;

// Și comenzile eșuate țin biletele până la expirare: pagina Netopia lasă omul
// să reîncerce cu alt card, pe același orderID, iar dacă am elibera la primul
// refuz ar plăti a doua oară pe bilete care nu mai sunt ale lui.
export async function elibereazaRezervariVechi(): Promise<number> {
  const rows = (await sql`
    UPDATE ticket_pool p SET status = 'liber', order_id = NULL
    FROM orders o
    WHERE p.order_id = o.id AND p.status = 'rezervat'
      AND o.status IN ('noua', 'esuata')
      AND o.created_at < now() - ${`${MINUTE_REZERVARE} minutes`}::interval
    RETURNING p.id
  `) as { id: number }[];
  if (rows.length) {
    await sql`
      UPDATE orders SET status = 'expirata'
      WHERE status IN ('noua', 'esuata') AND created_at < now() - ${`${MINUTE_REZERVARE} minutes`}::interval
    `;
  }
  return rows.length;
}

/** Cod scurt, ușor de citit la telefon: CLP-7K2M9. */
function codComanda(): string {
  const litere = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  let s = "";
  for (let i = 0; i < 5; i++) s += litere[Math.floor(Math.random() * litere.length)];
  return `CLP-${s}`;
}

export type ComandaNoua =
  | { ok: true; orderId: number; cod: string; total: number }
  | { ok: false; mesaj: string };

/**
 * Creează comanda și rezervă biletele. Prețurile se recitesc din bază, nu se
 * iau din formular. Dacă nu mai sunt destule bilete libere, nu rezervă nimic.
 */
export async function creeazaComanda(
  eventId: number,
  linii: Linie[],
  nume: string,
  email: string,
): Promise<ComandaNoua> {
  await elibereazaRezervariVechi();

  const tipuri = (await sql`
    SELECT t.id, t.name, t.price,
           COUNT(p.id) FILTER (WHERE p.status = 'liber')::int AS libere
    FROM ticket_types t LEFT JOIN ticket_pool p ON p.type_id = t.id
    WHERE t.event_id = ${eventId} AND t.id = ANY(${linii.map((l) => l.typeId)})
    GROUP BY t.id
  `) as { id: number; name: string; price: string; libere: number }[];

  let total = 0;
  for (const l of linii) {
    const t = tipuri.find((x) => x.id === l.typeId);
    if (!t) return { ok: false, mesaj: "Un tip de bilet nu mai există." };
    if (t.libere < l.qty) return { ok: false, mesaj: `Au mai rămas doar ${t.libere} la „${t.name}".` };
    total += Number(t.price) * l.qty;
  }
  if (total <= 0) return { ok: false, mesaj: "Comanda e goală." };

  const [comanda] = (await sql`
    INSERT INTO orders (event_id, cod, buyer_name, buyer_email, total)
    VALUES (${eventId}, ${codComanda()}, ${nume}, ${email}, ${total})
    RETURNING id, cod
  `) as { id: number; cod: string }[];

  // Ce s-a comandat se scrie separat de rezervare. Rezervarea e volatilă — o
  // încercare de plată eșuată o poate desface — dar liniile rămân, ca să știm
  // ce avem de emis când intră banii.
  for (const l of linii) {
    const t = tipuri.find((x) => x.id === l.typeId)!;
    await sql`
      INSERT INTO order_items (order_id, type_id, qty, unit_price)
      VALUES (${comanda.id}, ${l.typeId}, ${l.qty}, ${t.price})
    `;
  }

  // Rezervarea merge tip cu tip, pe cele mai mici numere libere. Dacă între timp
  // altcineva le-a luat, comanda se anulează întreagă.
  for (const l of linii) {
    if ((await rezerva(comanda.id, l.typeId, l.qty)) < l.qty) {
      await anuleazaComanda(comanda.id, "Biletele au fost luate între timp.");
      return { ok: false, mesaj: "Biletele au fost luate între timp. Încearcă din nou." };
    }
  }

  return { ok: true, orderId: comanda.id, cod: comanda.cod, total };
}

/** Pune pe comandă cele mai mici numere libere dintr-un tip. Câte a apucat. */
async function rezerva(orderId: number, typeId: number, qty: number): Promise<number> {
  const luate = (await sql`
    UPDATE ticket_pool SET status = 'rezervat', order_id = ${orderId}
    WHERE id IN (
      SELECT id FROM ticket_pool
      WHERE type_id = ${typeId} AND status = 'liber'
      ORDER BY numar LIMIT ${qty}
    )
    RETURNING id
  `) as { id: number }[];
  return luate.length;
}

/** Plata a fost refuzată, dar biletele rămân rezervate până la expirare. */
export async function marcheazaEsec(orderId: number, motiv: string): Promise<void> {
  await sql`UPDATE orders SET status = 'esuata', eroare = ${motiv} WHERE id = ${orderId} AND status = 'noua'`;
}

/** Eliberează biletele și marchează comanda. */
export async function anuleazaComanda(orderId: number, motiv: string, status = "anulata"): Promise<void> {
  await sql`
    UPDATE ticket_pool SET status = 'liber', order_id = NULL
    WHERE order_id = ${orderId} AND status = 'rezervat'
  `;
  await sql`UPDATE orders SET status = ${status}, eroare = ${motiv} WHERE id = ${orderId}`;
}

/**
 * Plata a intrat: biletele devin vândute și primesc numele cumpărătorului.
 *
 * Rezervarea poate să nu mai fie acolo — o încercare eșuată pe aceeași comandă
 * o desface, iar expirarea la fel. În cazul ăla o refacem din liniile comenzii.
 * Dacă nici așa nu iese, comanda NU se marchează plătită normal: am încasat
 * bani fără să dăm bilete, și asta trebuie să se vadă, nu să treacă tăcut.
 */
export async function confirmaComanda(orderId: number, ntpID: string): Promise<boolean> {
  const [o] = (await sql`SELECT id, status, buyer_name, buyer_email FROM orders WHERE id = ${orderId}`) as {
    id: number;
    status: string;
    buyer_name: string;
    buyer_email: string;
  }[];
  if (!o) return false;
  if (o.status === "platita") return true; // IPN-ul poate veni de mai multe ori

  const [{ n }] = (await sql`
    SELECT count(*)::int AS n FROM ticket_pool WHERE order_id = ${orderId} AND status = 'rezervat'
  `) as { n: number }[];

  if (n === 0) {
    const items = (await sql`
      SELECT type_id, qty FROM order_items WHERE order_id = ${orderId}
    `) as { type_id: number; qty: number }[];

    if (!items.length) {
      await sql`
        UPDATE orders SET status = 'platita_fara_bilete', paid_at = now(), ntp_id = ${ntpID},
               eroare = 'Plata a intrat, dar comanda nu are linii de emis.'
        WHERE id = ${orderId}
      `;
      return false;
    }

    for (const it of items) {
      const luate = await rezerva(orderId, it.type_id, it.qty);
      if (luate < it.qty) {
        await sql`
          UPDATE orders SET status = 'platita_fara_bilete', paid_at = now(), ntp_id = ${ntpID},
                 eroare = ${`Plata a intrat, dar nu mai sunt bilete libere la tipul ${it.type_id}.`}
          WHERE id = ${orderId}
        `;
        return false;
      }
    }
  }

  await sql`
    UPDATE ticket_pool SET status = 'vandut', sold_at = now(),
           buyer_name = ${o.buyer_name}, buyer_email = ${o.buyer_email}
    WHERE order_id = ${orderId} AND status = 'rezervat'
  `;
  await sql`UPDATE orders SET status = 'platita', paid_at = now(), ntp_id = ${ntpID} WHERE id = ${orderId}`;
  return true;
}

/** Biletele unei comenzi, pentru email și pentru pagina de mulțumire. */
export async function biletele(orderId: number) {
  return (await sql`
    SELECT p.serie, p.numar, p.qr_token, t.name AS tip
    FROM ticket_pool p JOIN ticket_types t ON t.id = p.type_id
    WHERE p.order_id = ${orderId}
    ORDER BY t.position, p.numar
  `) as { serie: string; numar: number; qr_token: string; tip: string }[];
}
