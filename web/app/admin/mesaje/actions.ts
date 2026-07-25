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

/** Evaluarea unui candidat speaker: nope / meh / top (gol = neevaluat). */
export async function setEvaluation(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  const evaluation = g(formData, "evaluation");
  await sql`UPDATE messages SET rating = ${evaluation || null} WHERE id = ${id}`;
  revalidatePath("/admin/mesaje");
}

/** Marchează leadul „contactat" — de aici ajunge în lista de Speakeri. */
export async function setContacted(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`UPDATE messages SET contacted = ${g(formData, "contacted") === "1"} WHERE id = ${id}`;
  revalidatePath("/admin/mesaje");
  revalidatePath("/admin/speakeri");
}

export async function addComment(formData: FormData): Promise<void> {
  const session = await getSession();
  if (!session) redirect("/login");
  const id = Number(g(formData, "id"));
  const text = g(formData, "text");
  if (!id || !text) return;
  const at = new Intl.DateTimeFormat("ro-RO", {
    timeZone: "Europe/Bucharest",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date());
  const entry = JSON.stringify([{ at, by: session.username, text }]);
  await sql`UPDATE messages SET comments = comments || ${entry}::jsonb WHERE id = ${id}`;
  revalidatePath("/admin/mesaje");
}

export async function deleteComment(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const index = Number(g(formData, "index"));
  if (!id || Number.isNaN(index)) return;
  await sql`UPDATE messages SET comments = comments - ${index} WHERE id = ${id}`;
  revalidatePath("/admin/mesaje");
}
