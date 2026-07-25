"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

/** Salvare din modal — adaugă sau actualizează, ca `save_speaker` din PHP. */
export async function saveSpeaker(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id")) || 0;
  const name = g(formData, "name");
  if (!name) return;
  const email = g(formData, "email") || null;
  const phone = g(formData, "phone") || null;
  const status = g(formData, "status") || "MID";
  const notes = g(formData, "notes") || null;
  // notițele din tabul „Meet" (meet_auzit, meet_ocupatie, …)
  const meet: Record<string, string> = {};
  for (const [k, v] of formData.entries()) {
    if (k.startsWith("meet_")) {
      const val = String(v).trim();
      if (val) meet[k.slice(5)] = val;
    }
  }

  if (id) {
    await sql`
      UPDATE speakers SET name = ${name}, email = ${email}, phone = ${phone},
        status = ${status}, notes = ${notes}, meet = ${JSON.stringify(meet)}::jsonb, updated_at = now()
      WHERE id = ${id}
    `;
  } else {
    await sql`
      INSERT INTO speakers (name, email, phone, status, notes, meet)
      VALUES (${name}, ${email}, ${phone}, ${status}, ${notes}, ${JSON.stringify(meet)}::jsonb)
    `;
  }
  revalidatePath("/admin/speakeri");
}

/** Schimbare rapidă de status din popover-ul de pe badge. */
export async function setStatus(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const status = g(formData, "status");
  if (!id || !status) return;
  await sql`UPDATE speakers SET status = ${status}, updated_at = now() WHERE id = ${id}`;
  revalidatePath("/admin/speakeri");
}

/** Temele pe care le poate susține (tab-ul „Cursuri" din modalul Detalii). */
export async function saveTopics(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  const topics = formData
    .getAll("topics")
    .map((t) => String(t).trim())
    .filter(Boolean);
  await sql`UPDATE speakers SET topics = ${topics}, updated_at = now() WHERE id = ${id}`;
  revalidatePath("/admin/speakeri");
}

export async function deleteSpeaker(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM speakers WHERE id = ${id}`;
  revalidatePath("/admin/speakeri");
}

/** „Scoate" un lead contactat din lista de speakeri (rămâne în Mesaje). */
export async function unmarkContacted(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`UPDATE messages SET contacted = false WHERE id = ${id}`;
  revalidatePath("/admin/speakeri");
  revalidatePath("/admin/mesaje");
}
