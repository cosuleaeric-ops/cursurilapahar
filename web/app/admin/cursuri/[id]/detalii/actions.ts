"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();
const back = (id: number) => redirect(`/admin/cursuri/${id}/detalii`);

/** Rescrie lista de participanți (un nume pe linie) — ca update_participants din PHP. */
export async function updateParticipants(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  const names = g(formData, "participants")
    .split("\n")
    .map((n) => n.trim())
    .filter(Boolean);
  await sql`DELETE FROM tickets WHERE event_id = ${id}`;
  for (const name of names) {
    await sql`INSERT INTO tickets (event_id, participant_name) VALUES (${id}, ${name})`;
  }
  revalidatePath(`/admin/cursuri/${id}/detalii`);
  back(id);
}

export async function addVizaSubtip(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const seria = g(formData, "seria");
  const tarif = Number(g(formData, "tarif").replace(",", "."));
  const nr = Number(g(formData, "nr_unitati"));
  const deLa = g(formData, "de_la");
  const panaLa = g(formData, "pana_la");
  if (!id || !seria || !(nr > 0) || !(tarif > 0)) back(id);
  await sql`
    INSERT INTO viza_subtips (event_id, seria, tarif, nr_unitati, de_la, pana_la)
    VALUES (${id}, ${seria}, ${tarif}, ${nr}, ${deLa || null}, ${panaLa || null})
  `;
  revalidatePath(`/admin/cursuri/${id}/detalii`);
  back(id);
}

export async function deleteVizaSubtip(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const subtipId = Number(g(formData, "subtip_id"));
  if (!id || !subtipId) return;
  await sql`DELETE FROM viza_subtips WHERE id = ${subtipId} AND event_id = ${id}`;
  revalidatePath(`/admin/cursuri/${id}/detalii`);
  back(id);
}

/** Șterge seriile duplicate (aceeași serie + tarif + nr_unitati), păstrând prima. */
export async function dedupVizaSubtips(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`
    DELETE FROM viza_subtips v USING viza_subtips keep
    WHERE v.event_id = ${id} AND keep.event_id = v.event_id
      AND keep.seria = v.seria AND keep.tarif = v.tarif AND keep.nr_unitati = v.nr_unitati
      AND keep.id < v.id
  `;
  revalidatePath(`/admin/cursuri/${id}/detalii`);
  back(id);
}

export async function deleteRaport(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM event_reports WHERE event_id = ${id}`;
  revalidatePath(`/admin/cursuri/${id}/detalii`);
  back(id);
}
