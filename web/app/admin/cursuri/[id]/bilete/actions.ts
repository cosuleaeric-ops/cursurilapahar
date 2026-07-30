"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { createTypes, DEFAULT_TYPES, syncPool } from "@/lib/bilete";

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
 * Schimbă numele, prețul sau stocul. Stocul poate doar să crească — biletele
 * deja vizate la primărie nu se pot retrage, se casează la final.
 */
export async function updateType(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const typeId = Number(g(formData, "type_id"));
  const name = g(formData, "name");
  const price = Number(g(formData, "price").replace(",", "."));
  const stock = Number(g(formData, "stock"));
  if (!id || !typeId || !name || !(price >= 0) || !(stock > 0)) back(id);

  await sql`
    UPDATE ticket_types
    SET name = ${name}, price = ${price}, stock = GREATEST(stock, ${stock})
    WHERE id = ${typeId} AND event_id = ${id}
  `;
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

/** Casează biletele rămase libere, după ce PV-ul a fost depus la primărie. */
export async function caseazaLibere(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) back(id);
  await sql`UPDATE ticket_pool SET status = 'casat' WHERE event_id = ${id} AND status = 'liber'`;
  revalidatePath(`/admin/cursuri/${id}/bilete`);
  back(id);
}
