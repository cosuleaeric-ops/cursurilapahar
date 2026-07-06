"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireOwner(): Promise<void> {
  const s = await getSession();
  if (!s) redirect("/login");
  if (s.role !== "owner") redirect("/admin");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

export async function addVenit(formData: FormData): Promise<void> {
  await requireOwner();
  const data = g(formData, "data");
  const descriere = g(formData, "descriere");
  const suma = g(formData, "suma");
  if (!data || !descriere || !suma) return;
  await sql`INSERT INTO venituri (data, descriere, suma) VALUES (${data}, ${descriere}, ${suma})`;
  revalidatePath("/admin/pnl");
  redirect("/admin/pnl");
}

export async function addCheltuiala(formData: FormData): Promise<void> {
  await requireOwner();
  const data = g(formData, "data");
  const descriere = g(formData, "descriere");
  const suma = g(formData, "suma");
  const categorieId = Number(g(formData, "categorie_id"));
  if (!data || !descriere || !suma || !categorieId) return;
  await sql`INSERT INTO cheltuieli (data, descriere, suma, categorie_id) VALUES (${data}, ${descriere}, ${suma}, ${categorieId})`;
  revalidatePath("/admin/pnl");
  redirect("/admin/pnl");
}

export async function deleteVenit(formData: FormData): Promise<void> {
  await requireOwner();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM venituri WHERE id = ${id}`;
  revalidatePath("/admin/pnl");
}

export async function deleteCheltuiala(formData: FormData): Promise<void> {
  await requireOwner();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM cheltuieli WHERE id = ${id}`;
  revalidatePath("/admin/pnl");
}
