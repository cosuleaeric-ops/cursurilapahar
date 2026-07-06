"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

export async function toggleRead(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`UPDATE messages SET read = NOT read WHERE id = ${id}`;
  revalidatePath("/admin/mesaje");
  revalidatePath("/admin");
}

export async function deleteMessage(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM messages WHERE id = ${id}`;
  revalidatePath("/admin/mesaje");
  revalidatePath("/admin");
}
