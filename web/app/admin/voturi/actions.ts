"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

export async function createVoteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const name = g(formData, "name");
  if (!name) return;
  await sql`
    INSERT INTO vote_courses (name, emoji, description, active)
    VALUES (${name}, ${g(formData, "emoji") || "📚"}, ${g(formData, "description") || null}, true)
  `;
  revalidatePath("/admin/voturi");
  revalidatePath("/voteaza-cursuri");
  redirect("/admin/voturi");
}

export async function updateVoteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const name = g(formData, "name");
  if (!id || !name) return;
  await sql`
    UPDATE vote_courses SET
      name = ${name},
      emoji = ${g(formData, "emoji") || "📚"},
      description = ${g(formData, "description") || null}
    WHERE id = ${id}
  `;
  revalidatePath("/admin/voturi");
  revalidatePath("/voteaza-cursuri");
  redirect("/admin/voturi");
}

export async function toggleVoteActive(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`UPDATE vote_courses SET active = NOT active WHERE id = ${id}`;
  revalidatePath("/admin/voturi");
  revalidatePath("/voteaza-cursuri");
}

export async function deleteVoteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM vote_courses WHERE id = ${id}`;
  revalidatePath("/admin/voturi");
  revalidatePath("/voteaza-cursuri");
}
