"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { put } from "@vercel/blob";
import { parseReportXlsx, parseVizaSubtips, pdfToText } from "@/lib/rapoarte";

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
  // lista vine ca JSON din parserul XLSX/CSV (participants_json), ca pe live
  let names: string[] = [];
  const raw = g(formData, "participants_json");
  if (raw) {
    try {
      const parsed: unknown = JSON.parse(raw);
      if (Array.isArray(parsed)) names = parsed.map((n) => String(n).trim()).filter(Boolean);
    } catch {
      return;
    }
  }
  if (!names.length) return;
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

/** Încarcă raportul XLSX (export eveniment sau decont) → event_reports + fișierul în Blob. */
export async function uploadRaport(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const file = formData.get("raport_file");
  if (!id || !(file instanceof File) || !file.size) back(id);
  const f = file as File;

  const parsed = parseReportXlsx(await f.arrayBuffer());
  if ("error" in parsed) redirect(`/admin/cursuri/${id}/detalii?err=${encodeURIComponent(parsed.error)}`);

  let blobUrl: string | null = null;
  try {
    const { url } = await put(`rapoarte/${id}-${Date.now()}-${f.name}`, f, { access: "public", addRandomSuffix: false });
    blobUrl = url;
  } catch {
    // fișierul e opțional — cifrele parsate sunt ce contează
  }

  await sql`
    INSERT INTO event_reports (event_id, total_bilete, total_incasari, types_json, original_name, blob_url)
    VALUES (${id}, ${parsed.totalBilete}, ${parsed.totalIncasari},
            ${JSON.stringify(parsed.types.map((t) => ({ denumire: t.bilet, pret: t.pret, vandute: t.vandute, refund: t.refund })))},
            ${f.name}, ${blobUrl})
    ON CONFLICT (event_id) DO UPDATE SET
      total_bilete = EXCLUDED.total_bilete, total_incasari = EXCLUDED.total_incasari,
      types_json = EXCLUDED.types_json, original_name = EXCLUDED.original_name,
      blob_url = EXCLUDED.blob_url, uploaded_at = now()
  `;
  revalidatePath(`/admin/cursuri/${id}/detalii`);
  revalidatePath("/admin/cursuri");
  back(id);
}

/** Încarcă PDF-ul de viză, extrage seriile și le salvează (înlocuiește seriile existente). */
export async function uploadViza(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const file = formData.get("viza_file");
  if (!id || !(file instanceof File) || !file.size) back(id);
  const f = file as File;
  if (!/\.pdf$/i.test(f.name)) redirect(`/admin/cursuri/${id}/detalii?err=${encodeURIComponent("Doar fișiere PDF sunt acceptate.")}`);

  const bytes = new Uint8Array(await f.arrayBuffer());
  let subtips: Awaited<ReturnType<typeof parseVizaSubtips>> = [];
  try {
    subtips = parseVizaSubtips(await pdfToText(bytes));
  } catch {
    redirect(`/admin/cursuri/${id}/detalii?err=${encodeURIComponent("Nu am putut citi textul din PDF.")}`);
  }

  let blobUrl: string | null = null;
  try {
    const { url } = await put(`viza/${id}-${Date.now()}-${f.name}`, f, { access: "public", addRandomSuffix: false });
    blobUrl = url;
  } catch {
    /* fișierul e opțional */
  }

  if (blobUrl) {
    await sql`
      INSERT INTO event_files (event_id, blob_url, original_name, file_type)
      VALUES (${id}, ${blobUrl}, ${f.name}, 'viza')
    `;
  }
  if (subtips.length) {
    await sql`DELETE FROM viza_subtips WHERE event_id = ${id}`;
    for (const s of subtips) {
      await sql`
        INSERT INTO viza_subtips (event_id, seria, tarif, nr_unitati, de_la, pana_la)
        VALUES (${id}, ${s.seria}, ${s.tarif}, ${s.nr_unitati}, ${s.de_la}, ${s.pana_la})
      `;
    }
  }
  revalidatePath(`/admin/cursuri/${id}/detalii`);
  revalidatePath("/admin/cursuri");
  redirect(`/admin/cursuri/${id}/detalii?serii=${subtips.length}`);
}

/** Șterge PDF-ul de viză (și seriile extrase din el). */
export async function deleteViza(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(String(formData.get("id") ?? ""));
  const fileId = Number(String(formData.get("file_id") ?? ""));
  if (!id || !fileId) return;
  await sql`DELETE FROM event_files WHERE id = ${fileId} AND event_id = ${id}`;
  revalidatePath(`/admin/cursuri/${id}/detalii`);
}

/** Danger zone — șterge cursul cu tot cu bilete/rapoarte (ON DELETE CASCADE). */
export async function deleteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(String(formData.get("id") ?? ""));
  if (!id) return;
  await sql`DELETE FROM events WHERE id = ${id}`;
  revalidatePath("/admin/cursuri");
  redirect("/admin/cursuri");
}
