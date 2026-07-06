"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

export async function createLocation(formData: FormData): Promise<void> {
  await requireAuth();
  const name = g(formData, "name");
  if (!name) return;
  await sql`
    INSERT INTO locations (name, phone, maps_link, days, notes)
    VALUES (${name}, ${g(formData, "phone") || null}, ${g(formData, "maps_link") || null},
            ${g(formData, "days") || null}, ${g(formData, "notes") || null})
  `;
  revalidatePath("/admin/locatii");
  redirect("/admin/locatii");
}

export async function updateLocation(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const name = g(formData, "name");
  if (!id || !name) return;
  await sql`
    UPDATE locations SET
      name = ${name},
      phone = ${g(formData, "phone") || null},
      maps_link = ${g(formData, "maps_link") || null},
      days = ${g(formData, "days") || null},
      notes = ${g(formData, "notes") || null},
      updated_at = now()
    WHERE id = ${id}
  `;
  revalidatePath("/admin/locatii");
  redirect("/admin/locatii");
}

export async function deleteLocation(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM locations WHERE id = ${id}`;
  revalidatePath("/admin/locatii");
}
