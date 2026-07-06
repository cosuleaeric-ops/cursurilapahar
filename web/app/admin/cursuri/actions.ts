"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();
const bool = (fd: FormData, k: string) => fd.get(k) != null;
const TZ = "Europe/Bucharest";

export async function createCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const title = g(formData, "title");
  const date = g(formData, "date");
  if (!title || !date) return;
  const startsAt = `${date} ${g(formData, "time") || "19:00"}`;
  await sql`
    INSERT INTO events (title, starts_at, location, livetickets_url, image_url, active, sold_out)
    VALUES (${title}, (${startsAt}::timestamp AT TIME ZONE ${TZ}),
            ${g(formData, "location") || null}, ${g(formData, "livetickets_url") || null},
            ${g(formData, "image_url") || null}, ${bool(formData, "active")}, ${bool(formData, "sold_out")})
  `;
  revalidatePath("/admin/cursuri");
  revalidatePath("/");
  redirect("/admin/cursuri");
}

export async function updateCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  const title = g(formData, "title");
  const date = g(formData, "date");
  if (!id || !title || !date) return;
  const startsAt = `${date} ${g(formData, "time") || "19:00"}`;
  await sql`
    UPDATE events SET
      title = ${title},
      starts_at = (${startsAt}::timestamp AT TIME ZONE ${TZ}),
      location = ${g(formData, "location") || null},
      livetickets_url = ${g(formData, "livetickets_url") || null},
      image_url = ${g(formData, "image_url") || null},
      active = ${bool(formData, "active")},
      sold_out = ${bool(formData, "sold_out")},
      updated_at = now()
    WHERE id = ${id}
  `;
  revalidatePath("/admin/cursuri");
  revalidatePath("/");
  redirect("/admin/cursuri");
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
