import { sql } from "@/lib/db";
import { getSettings } from "@/lib/settings";

// Bilete de intrare la spectacole, regim HG 846/2002. Pool-ul se vizează la
// primărie înainte de vânzare, deci biletele există toate din start cu status
// 'liber'; vânzarea doar le atribuie. Trei documente ies din datele astea:
// cererea de vizare (înainte), decontul de impozit (lunar), PV de casare (după).

export type TicketType = {
  id: number;
  event_id: number;
  name: string;
  price: string;
  stock: number;
  serie: string;
  position: number;
  discount_code_id: number | null;
  description: string | null;
  sale_starts_at: string | null;
  sale_ends_at: string | null;
  max_per_order: number;
  only_with_code: boolean;
  serie_start: number;
  bundle_size: number;
};

export type PoolStatus = "liber" | "vandut" | "casat";

/** Numărul afișat pe bilet: 1 → „0001". Ca la LiveTickets (ORU 0001 - ORU 0055). */
export const formatNumar = (n: number): string => String(n).padStart(4, "0");

/** „ORU 0001 - ORU 0055”, sau „ORU 0001” când intervalul are un singur bilet. */
export const formatInterval = (serie: string, from: number, to: number): string =>
  from === to ? `${serie} ${formatNumar(from)}` : `${serie} ${formatNumar(from)} - ${serie} ${formatNumar(to)}`;

/** Grupează numere sortate în intervale consecutive: [1,2,3,7,8] → [[1,3],[7,8]]. */
export function ranges(nums: number[]): [number, number][] {
  const out: [number, number][] = [];
  for (const n of [...nums].sort((a, b) => a - b)) {
    const last = out[out.length - 1];
    if (last && n === last[1] + 1) last[1] = n;
    else out.push([n, n]);
  }
  return out;
}

const LETTERS = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

/** Serie de 3 litere, unică între seriile deja folosite la același eveniment. */
export function randomSerie(taken: Set<string>): string {
  for (let i = 0; i < 200; i++) {
    let s = "";
    for (let j = 0; j < 3; j++) s += LETTERS[Math.floor(Math.random() * 26)];
    if (!taken.has(s)) return s;
  }
  throw new Error("Nu s-a putut genera o serie unică");
}

/** Datele organizatorului de pe bilet și de pe formularele pentru primărie. */
export async function getOrganizator() {
  const s = await getSettings();
  const get = (k: string, fallback: string) => (typeof s[k] === "string" && s[k] ? String(s[k]) : fallback);
  return {
    nume: get("firma_nume", "Elite Experience S.R.L."),
    cui: get("firma_cui", "53231839"),
    regCom: get("firma_reg_com", "J2026001200007"),
    sediu: get("firma_sediu", "Bld 1 Decembrie 1918, 2, Bl:my9, Sc:1, Et:2, Ap:9, -, București, Bucuresti"),
    telefon: get("firma_telefon", "+40787368185"),
    email: get("firma_email", "contact@cursurilapahar.ro"),
  };
}

/**
 * Tipurile cu care pornește orice curs nou — aceleași ca în cererile de vizare
 * de până acum. Tarifele și stocul se editează per curs după creare.
 */
export const DEFAULT_TYPES: { name: string; price: number; stock: number }[] = [
  { name: "Bilet standard", price: 50, stock: 55 },
  { name: "Bilet student", price: 30, stock: 25 },
  { name: "Bilet 1+1 GRATIS", price: 50, stock: 8 },
];

/** Creează tipuri cu serii unice în cadrul cursului și le generează pool-ul. */
export async function createTypes(
  eventId: number,
  defs: { name: string; price: number; stock: number }[],
): Promise<void> {
  const existing = (await sql`SELECT serie FROM ticket_types WHERE event_id = ${eventId}`) as { serie: string }[];
  const taken = new Set(existing.map((r) => r.serie));
  const [{ pos }] = (await sql`
    SELECT COALESCE(MAX(position), -1) + 1 AS pos FROM ticket_types WHERE event_id = ${eventId}
  `) as { pos: number }[];

  for (const [i, d] of defs.entries()) {
    const serie = randomSerie(taken);
    const [type] = (await sql`
      INSERT INTO ticket_types (event_id, name, price, stock, serie, position)
      VALUES (${eventId}, ${d.name}, ${d.price}, ${d.stock}, ${serie}, ${pos + i})
      RETURNING id
    `) as { id: number }[];
    await syncPool(type.id);
  }
}

export type DiscountCode = {
  id: number;
  event_id: number;
  code: string;
  percent: string;
  active: boolean;
  valid_until: string | null;
};

/** Prețul redus, rotunjit la ban. */
export const pretRedus = (price: number, percent: number) => Math.round(price * (1 - percent / 100) * 100) / 100;

/**
 * Un cod de reducere nu scade prețul biletului existent: creează bilete noi, cu
 * serie proprie și tarif propriu. Cererea de vizare declară tariful per serie,
 * deci un bilet vândut cu 32,50 lei nu poate purta seria declarată la 50 de lei.
 * Seriile astea intră automat în cererea de vizare, ca oricare altele.
 */
export async function createDiscountTypes(eventId: number, codeId: number, percent: number): Promise<number> {
  const baza = (await sql`
    SELECT name, description, price, stock, position FROM ticket_types
    WHERE event_id = ${eventId} AND discount_code_id IS NULL
    ORDER BY position, id
  `) as { name: string; description: string | null; price: string; stock: number; position: number }[];
  if (!baza.length) return 0;

  const existing = (await sql`SELECT serie FROM ticket_types WHERE event_id = ${eventId}`) as { serie: string }[];
  const taken = new Set(existing.map((r) => r.serie));
  const [{ pos }] = (await sql`
    SELECT COALESCE(MAX(position), -1) + 1 AS pos FROM ticket_types WHERE event_id = ${eventId}
  `) as { pos: number }[];

  for (const [i, b] of baza.entries()) {
    const [t] = (await sql`
      INSERT INTO ticket_types (event_id, name, description, price, stock, serie, position, discount_code_id)
      VALUES (${eventId}, ${b.name}, ${b.description}, ${pretRedus(Number(b.price), percent)},
              ${b.stock}, ${randomSerie(taken)}, ${pos + i}, ${codeId})
      RETURNING id
    `) as { id: number }[];
    await syncPool(t.id);
  }
  return baza.length;
}

/** Codul valid pentru un curs, după text — pentru pagina publică. */
export async function findDiscountCode(eventId: number, code: string): Promise<DiscountCode | null> {
  const c = code.trim().toUpperCase();
  if (!c) return null;
  const rows = (await sql`
    SELECT id, event_id, code, percent, active, valid_until FROM discount_codes
    WHERE event_id = ${eventId} AND upper(code) = ${c} AND active = true
      AND (valid_until IS NULL OR valid_until > now())
  `) as DiscountCode[];
  return rows[0] ?? null;
}

export type TypeRow = TicketType & { vandute: number; libere: number; casate: number };

/** Tipurile de bilete ale unui curs, cu numărătoarea pe status. */
export async function getTypes(eventId: number): Promise<TypeRow[]> {
  return (await sql`
    SELECT t.id, t.event_id, t.name, t.price, t.stock, t.serie, t.position, t.discount_code_id,
           t.description, t.sale_starts_at, t.sale_ends_at, t.max_per_order, t.only_with_code,
           t.serie_start, t.bundle_size,
           COUNT(p.id) FILTER (WHERE p.status = 'vandut')::int AS vandute,
           COUNT(p.id) FILTER (WHERE p.status = 'liber')::int  AS libere,
           COUNT(p.id) FILTER (WHERE p.status = 'casat')::int  AS casate
    FROM ticket_types t
    LEFT JOIN ticket_pool p ON p.type_id = t.id
    WHERE t.event_id = ${eventId}
    GROUP BY t.id
    ORDER BY t.position, t.id
  `) as TypeRow[];
}

/**
 * Creează biletele lipsă pentru un tip (1..stock). Nu șterge nimic: dacă stocul
 * a crescut, adaugă doar numerele noi — biletele deja vizate rămân valabile.
 */
export async function syncPool(typeId: number): Promise<void> {
  await sql`
    INSERT INTO ticket_pool (event_id, type_id, serie, numar, qr_token)
    SELECT t.event_id, t.id, t.serie, g, replace(gen_random_uuid()::text, '-', '')
    FROM ticket_types t, generate_series(t.serie_start, t.serie_start + t.stock - 1) g
    WHERE t.id = ${typeId}
    ON CONFLICT (event_id, serie, numar) DO NOTHING
  `;
}

/** Rândurile din cererea de înregistrare/vizare: tot pool-ul, pe tipuri. */
export async function vizareRows(eventId: number) {
  const rows = (await sql`
    SELECT t.name, t.price, t.serie, MIN(p.numar)::int AS de_la, MAX(p.numar)::int AS pana_la,
           COUNT(p.id)::int AS nr
    FROM ticket_types t JOIN ticket_pool p ON p.type_id = t.id
    WHERE t.event_id = ${eventId}
    GROUP BY t.id, t.name, t.price, t.serie
    ORDER BY t.position, t.id
  `) as { name: string; price: string; serie: string; de_la: number; pana_la: number; nr: number }[];
  return rows.map((r) => ({
    name: r.name,
    price: Number(r.price),
    nr: r.nr,
    total: Number(r.price) * r.nr,
    interval: formatInterval(r.serie, r.de_la, r.pana_la),
  }));
}

/** Rândurile din PV de casare: biletele rămase nevândute, pe intervale. */
export async function casareRows(eventId: number) {
  const rows = (await sql`
    SELECT t.name, t.price, p.serie, p.numar
    FROM ticket_pool p JOIN ticket_types t ON t.id = p.type_id
    WHERE p.event_id = ${eventId} AND p.status = 'liber'
    ORDER BY t.position, t.id, p.numar
  `) as { name: string; price: string; serie: string; numar: number }[];

  const byType = new Map<string, { name: string; price: number; serie: string; nums: number[] }>();
  for (const r of rows) {
    const k = `${r.serie}|${r.name}`;
    if (!byType.has(k)) byType.set(k, { name: r.name, price: Number(r.price), serie: r.serie, nums: [] });
    byType.get(k)!.nums.push(r.numar);
  }
  return [...byType.values()].flatMap((t) =>
    ranges(t.nums).map(([from, to]) => ({
      name: t.name,
      price: t.price,
      nr: to - from + 1,
      total: t.price * (to - from + 1),
      interval: formatInterval(t.serie, from, to),
    })),
  );
}

export type DecontEvent = {
  id: number;
  title: string;
  location: string | null;
  starts_at: string | null;
  incasari: number;
  timbre: number;
  baza: number;
  cota: number;
  impozit: number;
  serii: { interval: string; nr: number; price: number; total: number }[];
};

/**
 * Decontul lunar de impozit pe spectacole: un rând per curs ținut în luna dată,
 * plus situația seriilor vândute. `luna` = „2026-08".
 */
export async function decontLuna(luna: string): Promise<DecontEvent[]> {
  const events = (await sql`
    SELECT e.id, e.title, e.location, e.starts_at, e.impozit_cota, e.timbru_cota
    FROM events e
    WHERE to_char(e.starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM') = ${luna}
      AND EXISTS (SELECT 1 FROM ticket_pool p WHERE p.event_id = e.id AND p.status = 'vandut')
    ORDER BY e.starts_at
  `) as {
    id: number;
    title: string;
    location: string | null;
    starts_at: string | null;
    impozit_cota: string;
    timbru_cota: string;
  }[];

  const out: DecontEvent[] = [];
  for (const e of events) {
    const sold = (await sql`
      SELECT t.name, t.price, p.serie, p.numar
      FROM ticket_pool p JOIN ticket_types t ON t.id = p.type_id
      WHERE p.event_id = ${e.id} AND p.status = 'vandut'
      ORDER BY t.position, t.id, p.numar
    `) as { name: string; price: string; serie: string; numar: number }[];

    const byType = new Map<string, { price: number; serie: string; nums: number[] }>();
    for (const r of sold) {
      const k = `${r.serie}|${r.name}`;
      if (!byType.has(k)) byType.set(k, { price: Number(r.price), serie: r.serie, nums: [] });
      byType.get(k)!.nums.push(r.numar);
    }
    const serii = [...byType.values()].flatMap((t) =>
      ranges(t.nums).map(([from, to]) => ({
        interval: formatInterval(t.serie, from, to),
        nr: to - from + 1,
        price: t.price,
        total: t.price * (to - from + 1),
      })),
    );

    const incasari = serii.reduce((s, r) => s + r.total, 0);
    const timbre = incasari * (Number(e.timbru_cota) / 100);
    const baza = incasari - timbre;
    const cota = Number(e.impozit_cota);
    out.push({
      id: e.id,
      title: e.title,
      location: e.location,
      starts_at: e.starts_at,
      incasari,
      timbre,
      baza,
      cota,
      impozit: baza * (cota / 100),
      serii,
    });
  }
  return out;
}
