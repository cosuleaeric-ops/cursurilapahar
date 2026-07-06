"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();
const parseTopics = (raw: string): string[] =>
  raw.split("\n").map((s) => s.trim()).filter(Boolean);

export async function createSpeaker(formData: FormData): Promise<void> {
  await requireAuth();
  const name = g(formData, "name");
  if (!name) return;
  await sql`
    INSERT INTO speakers (name, email, phone, status, notes, topics)
    VALUES (${name}, ${g(formData, "email") || null}, ${g(formData, "phone") || null},
            ${g(formData, "status") || "MID"}, ${g(formData, "notes") || null},
            ${parseTopics(g(formData, "topics"))})
  `;
  revalidatePath("/admin/speakeri");
  redirect("/admin/speakeri");
}

export async function updateSpeaker(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const name = g(formData, "name");
  if (!id || !name) return;
  await sql`
    UPDATE speakers SET
      name = ${name},
      email = ${g(formData, "email") || null},
      phone = ${g(formData, "phone") || null},
      status = ${g(formData, "status") || "MID"},
      notes = ${g(formData, "notes") || null},
      topics = ${parseTopics(g(formData, "topics"))},
      updated_at = now()
    WHERE id = ${id}
  `;
  revalidatePath("/admin/speakeri");
  redirect("/admin/speakeri");
}

export async function deleteSpeaker(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM speakers WHERE id = ${id}`;
  revalidatePath("/admin/speakeri");
}
