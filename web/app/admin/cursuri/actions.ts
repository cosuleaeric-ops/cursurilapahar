"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { fetchCourseMeta, type MetaResult } from "@/lib/livetickets";
import { COURSE_TIMES } from "./times";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();
const TZ = "Europe/Bucharest";

/** Preia imaginea/locația dintr-un link de bilete (fost /api/livetickets.php). */
export async function lookupTicketMeta(url: string): Promise<MetaResult> {
  await requireAuth();
  return fetchCourseMeta(url);
}

/**
 * Salvare din formularul inline (adăugare + editare), ca `save_course` din
 * admin/actions.php: validează ora, completează imaginea din linkul de bilete,
 * iar `active` urmează existența linkului.
 */
export async function saveCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id")) || 0;
  const title = g(formData, "title");
  const dateRaw = g(formData, "date_raw");
  const time = g(formData, "time");
  const speaker = g(formData, "speaker_name");
  const ltUrl = g(formData, "livetickets_url");
  let imageUrl = g(formData, "image_url");
  let location = g(formData, "location");

  const err = (m: string) => redirect(`/admin/cursuri?course_error=${encodeURIComponent(m)}`);
  if (!title) err("Completează numele cursului.");
  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateRaw)) err("Alege o dată validă.");
  if (!(COURSE_TIMES as readonly string[]).includes(time))
    err("Alege ora din listă (17:00, 17:30, 18:00, 18:30 sau 19:00).");
  if (!speaker) err("Alege un speaker din listă.");

  if (ltUrl && !imageUrl) {
    const meta = await fetchCourseMeta(ltUrl);
    if (meta.success) {
      imageUrl = meta.data.image_url;
      if (!location && meta.data.location) location = meta.data.location;
    }
  }

  const startsAt = `${dateRaw} ${time}`;
  const active = ltUrl !== "";

  if (id) {
    // „NOU" 48h: marcajul se pune doar când linkul de bilete apare prima dată.
    await sql`
      UPDATE events SET
        title = ${title},
        starts_at = (${startsAt}::timestamp AT TIME ZONE ${TZ}),
        speaker_name = ${speaker || null},
        location = ${location || null},
        livetickets_url = ${ltUrl || null},
        image_url = ${imageUrl || null},
        active = ${active},
        link_added_at = CASE
          WHEN ${ltUrl} = '' THEN link_added_at
          WHEN coalesce(livetickets_url, '') = '' THEN now()
          ELSE link_added_at END,
        updated_at = now()
      WHERE id = ${id}
    `;
  } else {
    await sql`
      INSERT INTO events (title, starts_at, speaker_name, location, livetickets_url, image_url, active, link_added_at)
      VALUES (${title}, (${startsAt}::timestamp AT TIME ZONE ${TZ}), ${speaker || null}, ${location || null},
              ${ltUrl || null}, ${imageUrl || null}, ${active},
              CASE WHEN ${ltUrl} = '' THEN NULL ELSE now() END)
    `;
  }

  revalidatePath("/admin/cursuri");
  revalidatePath("/");
  const [y, m] = dateRaw.split("-");
  redirect(
    `/admin/cursuri?year=${Number(y)}&month=${Number(m)}&ctab=cursuri&saved=1${id ? `&edit=${id}` : ""}`,
  );
}

/** Salvează / șterge reducerea unui curs (fost `save_discount`). */
export async function saveDiscount(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  const pct = Number(g(formData, "discount_percent"));
  const local = g(formData, "discount_ends_at"); // YYYY-MM-DDTHH:mm, ora București
  const clear = formData.get("clear") != null;

  if (clear || !(pct > 0 && pct <= 100) || !local) {
    await sql`UPDATE events SET discount_percent = NULL, discount_ends_at = NULL, updated_at = now() WHERE id = ${id}`;
  } else {
    await sql`
      UPDATE events SET
        discount_percent = ${pct},
        discount_ends_at = (${local.replace("T", " ")}::timestamp AT TIME ZONE ${TZ}),
        updated_at = now()
      WHERE id = ${id}
    `;
  }
  revalidatePath("/admin/cursuri");
  revalidatePath("/");
}

/** Ștergere permisă doar dacă evenimentul nu are bilete (protejăm istoricul financiar). */
export async function deleteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  const cnt = (await sql`SELECT count(*)::int AS n FROM tickets WHERE event_id = ${id}`) as { n: number }[];
  if (cnt[0].n > 0) return;
  await sql`DELETE FROM events WHERE id = ${id}`;
  revalidatePath("/admin/cursuri");
  revalidatePath("/");
}

/** Toggle rapid din listă. */
export async function toggleActive(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`UPDATE events SET active = NOT active, updated_at = now() WHERE id = ${id}`;
  revalidatePath("/admin/cursuri");
  revalidatePath("/");
}
