"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

export async function saveCollaboration(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const name = g(formData, "name");
  if (!name) return;
  if (id) {
    await sql`
      UPDATE collaborations SET
        name = ${name},
        contact = ${g(formData, "contact") || null},
        contact_info = ${g(formData, "contact_info") || null},
        status = ${g(formData, "status") || null},
        notes = ${g(formData, "notes") || null},
        updated_at = now()
      WHERE id = ${id}
    `;
  } else {
    await sql`
      INSERT INTO collaborations (name, contact, contact_info, status, notes)
      VALUES (${name}, ${g(formData, "contact") || null}, ${g(formData, "contact_info") || null},
              ${g(formData, "status") || null}, ${g(formData, "notes") || null})
    `;
  }
  revalidatePath("/admin/colaborari");
  redirect("/admin/colaborari?saved=1");
}

export async function deleteCollaboration(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM collaborations WHERE id = ${id}`;
  revalidatePath("/admin/colaborari");
}
