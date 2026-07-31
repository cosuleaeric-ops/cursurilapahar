"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { createDiscountTypes, createTypes, DEFAULT_TYPES, syncPool } from "@/lib/bilete";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();
const back = (id: number) => redirect(`/admin/cursuri/${id}/bilete`);

/** Adaugă un tip de bilet și îi generează pool-ul numerotat. */
export async function addType(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const name = g(formData, "name");
  const price = Number(g(formData, "price").replace(",", "."));
  const stock = Number(g(formData, "stock"));
  if (!id || !name || !(price >= 0) || !(stock > 0)) back(id);

  await createTypes(id, [{ name, price, stock }]);
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/** Pune tipurile implicite pe un curs creat înainte ca ele să fie automate. */
export async function addDefaultTypes(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) back(id);
  await createTypes(id, DEFAULT_TYPES);
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/**
 * Salvează toate setările unui tip. Stocul poate doar să crească — biletele
 * deja vizate la primărie nu se retrag, se casează la final. Seria și numărul
 * de start se pot schimba doar cât timp nu s-a vândut nimic din ele: sunt
 * tipărite pe biletele deja emise.
 */
export async function updateType(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const typeId = Number(g(formData, "type_id"));
  const name = g(formData, "name");
  const price = Number(g(formData, "price").replace(",", "."));
  const stock = Number(g(formData, "stock"));
  if (!id || !typeId || !name || !(price >= 0) || !(stock > 0)) back(id);

  const description = g(formData, "description") || null;
  const position = Number(g(formData, "position")) || 0;
  const maxOrder = Math.max(1, Number(g(formData, "max_per_order")) || 10);
  const bundle = Math.max(1, Number(g(formData, "bundle_size")) || 1);
  const onlyCode = formData.get("only_with_code") != null;
  const ts = (k: string) => {
    const v = g(formData, k);
    return v ? v.replace("T", " ") : null;
  };

  const [{ vandute }] = (await sql`
    SELECT COUNT(*)::int AS vandute FROM ticket_pool WHERE type_id = ${typeId} AND status = 'vandut'
  `) as { vandute: number }[];

  await sql`
    UPDATE ticket_types SET
      name = ${name}, description = ${description}, price = ${price},
      stock = GREATEST(stock, ${stock}), position = ${position},
      max_per_order = ${maxOrder}, bundle_size = ${bundle}, only_with_code = ${onlyCode},
      sale_starts_at = ${ts("sale_starts_at")}::timestamptz,
      sale_ends_at = ${ts("sale_ends_at")}::timestamptz
    WHERE id = ${typeId} AND event_id = ${id}
  `;

  // Seria și numerotarea se rescriu doar cât timp niciun bilet n-a plecat.
  const serie = g(formData, "serie").toUpperCase();
  const serieStart = Math.max(1, Number(g(formData, "serie_start")) || 1);
  if (vandute === 0 && /^[A-Z]{3}$/.test(serie)) {
    const [curent] = (await sql`
      SELECT serie, serie_start FROM ticket_types WHERE id = ${typeId}
    `) as { serie: string; serie_start: number }[];
    if (curent && (curent.serie !== serie || curent.serie_start !== serieStart)) {
      const [dubla] = (await sql`
        SELECT id FROM ticket_types WHERE event_id = ${id} AND serie = ${serie} AND id <> ${typeId}
      `) as { id: number }[];
      if (dubla) redirect(`/admin/cursuri/${id}/bilete?err=Seria ${serie} e deja folosită pe cursul ăsta.`);
      await sql`DELETE FROM ticket_pool WHERE type_id = ${typeId}`;
      await sql`UPDATE ticket_types SET serie = ${serie}, serie_start = ${serieStart} WHERE id = ${typeId}`;
    }
  }

  await syncPool(typeId);
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

export async function deleteType(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const typeId = Number(g(formData, "type_id"));
  if (!id || !typeId) back(id);
  // biletele vândute sunt documente emise — tipul nu se mai șterge după prima vânzare
  const [{ vandute }] = (await sql`
    SELECT COUNT(*)::int AS vandute FROM ticket_pool WHERE type_id = ${typeId} AND status = 'vandut'
  `) as { vandute: number }[];
  if (vandute > 0) redirect(`/admin/cursuri/${id}/bilete?err=Tipul are bilete vândute și nu se poate șterge.`);

  await sql`DELETE FROM ticket_types WHERE id = ${typeId} AND event_id = ${id}`;
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/** Cotele care intră în decontul de impozit pe spectacole. */
export async function updateCote(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const impozit = Number(g(formData, "impozit_cota").replace(",", "."));
  const timbru = Number(g(formData, "timbru_cota").replace(",", "."));
  if (!id || !(impozit >= 0) || !(timbru >= 0)) back(id);
  await sql`
    UPDATE events SET impozit_cota = ${impozit}, timbru_cota = ${timbru} WHERE id = ${id}
  `;
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/** Marchează cererea de vizare ca depusă (sau o retrage). */
export async function toggleVizat(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) back(id);
  await sql`
    UPDATE events SET vizat_at = CASE WHEN vizat_at IS NULL THEN now() ELSE NULL END WHERE id = ${id}
  `;
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/**
 * Fixează câte bilete dintr-un tip sunt vândute. Vinde de la cel mai mic număr
 * liber în sus și eliberează de la cel mai mare înapoi, ca seriile vândute să
 * rămână un interval continuu — așa arată și decontul.
 */
export async function setVandute(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const typeId = Number(g(formData, "type_id"));
  const tinta = Number(g(formData, "vandute"));
  if (!id || !typeId || !(tinta >= 0)) back(id);

  const [{ acum }] = (await sql`
    SELECT COUNT(*)::int AS acum FROM ticket_pool WHERE type_id = ${typeId} AND status = 'vandut'
  `) as { acum: number }[];

  if (tinta > acum) {
    await sql`
      UPDATE ticket_pool SET status = 'vandut', sold_at = now()
      WHERE id IN (
        SELECT id FROM ticket_pool WHERE type_id = ${typeId} AND status = 'liber'
        ORDER BY numar LIMIT ${tinta - acum}
      )
    `;
  } else if (tinta < acum) {
    await sql`
      UPDATE ticket_pool SET status = 'liber', sold_at = NULL, buyer_name = NULL, buyer_email = NULL
      WHERE id IN (
        SELECT id FROM ticket_pool WHERE type_id = ${typeId} AND status = 'vandut'
        ORDER BY numar DESC LIMIT ${acum - tinta}
      )
    `;
  }
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/**
 * Un cod de reducere generează bilete noi, cu serie și tarif propriu, nu scade
 * prețul celor existente. Trebuie făcut ÎNAINTE de a depune cererea de vizare:
 * seriile reduse se declară la primărie ca oricare altele.
 */
export async function addDiscountCode(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const code = g(formData, "code").toUpperCase();
  const percent = Number(g(formData, "percent").replace(",", "."));
  const until = g(formData, "valid_until");
  if (!id || !code || !(percent > 0) || percent >= 100) back(id);

  const [dubla] = (await sql`
    SELECT id FROM discount_codes WHERE event_id = ${id} AND upper(code) = ${code}
  `) as { id: number }[];
  if (dubla) redirect(`/admin/cursuri/${id}/bilete?err=Codul ${code} există deja pe cursul ăsta.`);

  const [cod] = (await sql`
    INSERT INTO discount_codes (event_id, code, percent, valid_until)
    VALUES (${id}, ${code}, ${percent}, ${until ? `${until.replace("T", " ")}` : null}::timestamptz)
    RETURNING id
  `) as { id: number }[];

  const create = await createDiscountTypes(id, cod.id, percent);
  if (!create) {
    await sql`DELETE FROM discount_codes WHERE id = ${cod.id}`;
    redirect(`/admin/cursuri/${id}/bilete?err=Defineşte întâi tipurile de bilete normale.`);
  }

  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/** Șterge codul împreună cu biletele lui, dacă niciunul n-a fost vândut. */
export async function deleteDiscountCode(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const codeId = Number(g(formData, "code_id"));
  if (!id || !codeId) back(id);

  const [{ vandute }] = (await sql`
    SELECT COUNT(*)::int AS vandute FROM ticket_pool p
    JOIN ticket_types t ON t.id = p.type_id
    WHERE t.discount_code_id = ${codeId} AND p.status = 'vandut'
  `) as { vandute: number }[];
  if (vandute > 0)
    redirect(`/admin/cursuri/${id}/bilete?err=Codul are bilete vândute; dezactivează-l în loc să-l ştergi.`);

  await sql`DELETE FROM discount_codes WHERE id = ${codeId} AND event_id = ${id}`;
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/** Oprește sau repornește un cod fără să atingă biletele deja emise. */
export async function toggleDiscountCode(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const codeId = Number(g(formData, "code_id"));
  if (!id || !codeId) back(id);
  await sql`UPDATE discount_codes SET active = NOT active WHERE id = ${codeId} AND event_id = ${id}`;
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}

/** Casează biletele rămase libere, după ce PV-ul a fost depus la primărie. */
export async function caseazaLibere(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) back(id);
  await sql`UPDATE ticket_pool SET status = 'casat' WHERE event_id = ${id} AND status = 'liber'`;
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}
