import { sql } from "@/lib/db";

// O comandă rezervă bilete din pool înainte de plată și le eliberează dacă
// plata nu vine. Biletele trec prin trei stări: liber → rezervat → vandut.

export type Linie = { typeId: number; qty: number };

/** Rezervările mai vechi de atât se eliberează singure la următoarea comandă. */
const MINUTE_REZERVARE = 30;

export async function elibereazaRezervariVechi(): Promise<number> {
  const rows = (await sql`
    UPDATE ticket_pool p SET status = 'liber', order_id = NULL
    FROM orders o
    WHERE p.order_id = o.id AND p.status = 'rezervat'
      AND o.status = 'noua' AND o.created_at < now() - ${`${MINUTE_REZERVARE} minutes`}::interval
    RETURNING p.id
  `) as { id: number }[];
  if (rows.length) {
    await sql`
      UPDATE orders SET status = 'expirata'
      WHERE status = 'noua' AND created_at < now() - ${`${MINUTE_REZERVARE} minutes`}::interval
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

  // Rezervarea merge tip cu tip, pe cele mai mici numere libere. Dacă între timp
  // altcineva le-a luat, comanda se anulează întreagă.
  for (const l of linii) {
    const luate = (await sql`
      UPDATE ticket_pool SET status = 'rezervat', order_id = ${comanda.id}
      WHERE id IN (
        SELECT id FROM ticket_pool
        WHERE type_id = ${l.typeId} AND status = 'liber'
        ORDER BY numar LIMIT ${l.qty}
      )
      RETURNING id
    `) as { id: number }[];
    if (luate.length < l.qty) {
      await anuleazaComanda(comanda.id, "Biletele au fost luate între timp.");
      return { ok: false, mesaj: "Biletele au fost luate între timp. Încearcă din nou." };
    }
  }

  return { ok: true, orderId: comanda.id, cod: comanda.cod, total };
}

/** Eliberează biletele și marchează comanda. */
export async function anuleazaComanda(orderId: number, motiv: string, status = "anulata"): Promise<void> {
  await sql`
    UPDATE ticket_pool SET status = 'liber', order_id = NULL
    WHERE order_id = ${orderId} AND status = 'rezervat'
  `;
  await sql`UPDATE orders SET status = ${status}, eroare = ${motiv} WHERE id = ${orderId}`;
}

/** Plata a intrat: biletele devin vândute și primesc numele cumpărătorului. */
export async function confirmaComanda(orderId: number, ntpID: string): Promise<boolean> {
  const [o] = (await sql`SELECT id, status, buyer_name, buyer_email FROM orders WHERE id = ${orderId}`) as {
    id: number;
    status: string;
    buyer_name: string;
    buyer_email: string;
  }[];
  if (!o) return false;
  if (o.status === "platita") return true; // IPN-ul poate veni de mai multe ori

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
