"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

const VALID = ["eric6", "andy"];

async function requireUser(): Promise<string> {
  const s = await getSession();
  if (!s) redirect("/login");
  return s.username;
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

export async function addTodo(formData: FormData): Promise<void> {
  const me = await requireUser();
  const title = g(formData, "title");
  const assigned = g(formData, "assigned_to");
  if (!title || !VALID.includes(assigned)) return;
  await sql`
    INSERT INTO todos (title, assigned_to, created_by, completed)
    VALUES (${title}, ${assigned}, ${me}, false)
  `;
  revalidatePath("/admin/todos");
  revalidatePath("/admin");
  redirect("/admin/todos");
}

export async function toggleTodo(formData: FormData): Promise<void> {
  await requireUser();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`UPDATE todos SET completed = NOT completed, updated_at = now() WHERE id = ${id}`;
  revalidatePath("/admin/todos");
  revalidatePath("/admin");
}

export async function deleteTodo(formData: FormData): Promise<void> {
  await requireUser();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM todos WHERE id = ${id}`;
  revalidatePath("/admin/todos");
  revalidatePath("/admin");
}
