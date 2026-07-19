"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireUser(): Promise<void> {
  const s = await getSession();
  if (!s) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

export async function addItem(formData: FormData): Promise<void> {
  await requireUser();
  const sectionId = Number(g(formData, "section_id"));
  const text = g(formData, "text");
  let link = g(formData, "link");
  if (link && !/^https?:\/\//i.test(link)) link = "https://" + link;
  if (!sectionId || (!text && !link)) return;
  await sql`
    INSERT INTO marketing_items (section_id, payload, position)
    VALUES (
      ${sectionId},
      ${JSON.stringify({ text, link, done: false })},
      (SELECT COALESCE(MAX(position), 0) + 1 FROM marketing_items WHERE section_id = ${sectionId})
    )
  `;
  revalidatePath("/admin/marketing");
}

export async function toggleItem(formData: FormData): Promise<void> {
  await requireUser();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`
    UPDATE marketing_items
    SET payload = jsonb_set(payload, '{done}', to_jsonb(NOT COALESCE((payload->>'done')::boolean, false)))
    WHERE id = ${id}
  `;
  revalidatePath("/admin/marketing");
}

export async function deleteItem(formData: FormData): Promise<void> {
  await requireUser();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM marketing_items WHERE id = ${id}`;
  revalidatePath("/admin/marketing");
}
