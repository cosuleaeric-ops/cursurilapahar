"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

// actions.php:674 salvează trim($_POST['vc_emoji'] ?? '📚'): 📚 doar când câmpul
// lipsește din POST, deci un emoji golit se salvează ca string gol.
const emojiOf = (fd: FormData) => (fd.has("emoji") ? g(fd, "emoji") : "📚");

export async function createVoteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const name = g(formData, "name");
  if (!name) return;
  await sql`
    INSERT INTO vote_courses (name, emoji, description, active)
    VALUES (${name}, ${emojiOf(formData)}, ${g(formData, "description") || null}, true)
  `;
  revalidatePath("/admin/voturi");
  revalidatePath("/voteaza-cursuri");
  // actions.php:696 redirecționează cu saved=1 la ORICE salvare, și la curs nou
  redirect("/admin/voturi?saved=1");
}

export async function updateVoteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const name = g(formData, "name");
  if (!id || !name) return;
  await sql`
    UPDATE vote_courses SET
      name = ${name},
      emoji = ${emojiOf(formData)},
      description = ${g(formData, "description") || null}
    WHERE id = ${id}
  `;
  revalidatePath("/admin/voturi");
  revalidatePath("/voteaza-cursuri");
  redirect("/admin/voturi?saved=1");
}

export async function toggleVoteActive(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`UPDATE vote_courses SET active = NOT active WHERE id = ${id}`;
  revalidatePath("/admin/voturi");
  revalidatePath("/voteaza-cursuri");
  // actions.php:722 duce pe /admin/?tab=vot curat: se pierd edit= și saved=
  redirect("/admin/voturi");
}

export async function deleteVoteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM vote_courses WHERE id = ${id}`;
  revalidatePath("/admin/voturi");
  revalidatePath("/voteaza-cursuri");
  // actions.php:706 la fel ca la toggle: URL curat, fără edit= și fără saved=
  redirect("/admin/voturi");
}
