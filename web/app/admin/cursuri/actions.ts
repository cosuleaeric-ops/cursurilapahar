"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { fetchCourseMeta, type MetaResult } from "@/lib/livetickets";
import { createTypes, DEFAULT_TYPES } from "@/lib/bilete";
import { COURSE_TIMES } from "./times";
import { IG_POST_TYPES, RO_MONTHS } from "./stats-data";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();
const TZ = "Europe/Bucharest";

/**
 * clp_date_display_from_raw() (lib/dates.php:82) → clp_format_date_ro($raw, true, true):
 * luna cu majusculă, cu an — „11 August 2026". În PHP textul se generează o dată, la
 * salvare (admin/actions.php:121), și e cel afișat mai târziu în listă și pe dashboard.
 */
function dateDisplayFromRaw(dateRaw: string): string {
  const [y, m, d] = dateRaw.split("-");
  const month = RO_MONTHS[Number(m)] ?? "";
  if (!month) return "";
  return `${Number(d)} ${month.charAt(0).toUpperCase()}${month.slice(1)} ${y}`;
}

/**
 * admin/actions.php:70 cere ȘI `strtotime($date_raw)`, nu doar formatul, așa că
 * „2026-13-45" pică cu „Alege o dată validă.". Aici verificăm că ziua chiar există
 * (an/lună/zi reale); altfel `date_display` ar ieși gol și cast-ul ::timestamp ar crăpa.
 */
function isRealDate(dateRaw: string): boolean {
  const [y, m, d] = dateRaw.split("-").map(Number);
  const dt = new Date(0);
  dt.setUTCFullYear(y, m - 1, d);
  return dt.getUTCFullYear() === y && dt.getUTCMonth() === m - 1 && dt.getUTCDate() === d;
}

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
  const speakerId = Number(g(formData, "speaker_id")) || 0;
  const ltUrl = g(formData, "livetickets_url");
  let imageUrl = g(formData, "image_url");
  let location = g(formData, "location");

  const err = (m: string) => redirect(`/admin/cursuri?course_error=${encodeURIComponent(m)}`);
  if (!title) err("Completează numele cursului.");
  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateRaw) || !isRealDate(dateRaw)) err("Alege o dată validă.");
  if (!(COURSE_TIMES as readonly string[]).includes(time))
    err("Alege ora din listă (17:00, 17:30, 18:00, 18:30 sau 19:00).");
  // admin/actions.php:75-79 — se salvează doar un speaker existent, căutat după id;
  // numele scris liber în câmp nu ajunge niciodată în curs.
  const speakerRows = speakerId
    ? ((await sql`SELECT name FROM speakers WHERE id = ${speakerId}`) as { name: string }[])
    : [];
  if (!speakerRows.length) err("Alege un speaker din listă.");
  const speaker = (speakerRows[0]?.name ?? "").trim();

  if (ltUrl && !imageUrl) {
    const meta = await fetchCourseMeta(ltUrl);
    if (meta.success) {
      imageUrl = meta.data.image_url;
      if (!location && meta.data.location) location = meta.data.location;
    }
  }

  const startsAt = `${dateRaw} ${time}`;
  const active = ltUrl !== "";
  const dateDisplay = dateDisplayFromRaw(dateRaw);

  if (id) {
    // „NOU" 48h: marcajul se pune doar când linkul de bilete apare prima dată.
    await sql`
      UPDATE events SET
        title = ${title},
        date_display = ${dateDisplay},
        starts_at = (${startsAt}::timestamp AT TIME ZONE ${TZ}),
        speaker_id = ${speakerId},
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
    // În courses.json fiecare card primea un id propriu (`uniqid('c', true)`); în Neon acela
    // e `legacy_card_id`, marcajul după care cursul e recunoscut ca „card de site".
    const cardId = `c${Date.now().toString(16)}${Math.random().toString(16).slice(2, 10)}`;
    const [created] = (await sql`
      INSERT INTO events (title, legacy_card_id, date_display, starts_at, speaker_id, speaker_name, location, livetickets_url, image_url, active, link_added_at)
      VALUES (${title}, ${cardId}, ${dateDisplay}, (${startsAt}::timestamp AT TIME ZONE ${TZ}), ${speakerId}, ${speaker || null}, ${location || null},
              ${ltUrl || null}, ${imageUrl || null}, ${active},
              CASE WHEN ${ltUrl} = '' THEN NULL ELSE now() END)
      RETURNING id
    `) as { id: number }[];
    // Biletele fac parte din curs: tipurile implicite și pool-ul numerotat există
    // de la creare, ca să nu fie nevoie de un pas manual înainte de vizare.
    await createTypes(created.id, DEFAULT_TYPES);
  }

  revalidatePath("/admin/cursuri");
  revalidatePath("/");
  const [y, m] = dateRaw.split("-");
  // `saved` primește un marcaj unic: în PHP fiecare salvare însemna un page load nou,
  // deci formularul revenea gol (la adăugare) sau repopulat (la editare).
  redirect(
    `/admin/cursuri?year=${Number(y)}&month=${Number(m)}&ctab=cursuri&saved=${Date.now()}${id ? `&edit=${id}` : ""}`,
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
  // admin/actions.php:204 — după salvare se revine pe tabul Cursuri, fără lună/edit în URL.
  redirect("/admin/cursuri");
}

/** Ștergere necondiționată, ca `delete_course` din admin/actions.php:24-32. */
export async function deleteCourse(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`DELETE FROM events WHERE id = ${id}`;
  revalidatePath("/admin/cursuri");
  revalidatePath("/");
  redirect("/admin/cursuri");
}

/** Toggle rapid din listă. */
export async function toggleActive(formData: FormData): Promise<void> {
  await requireAuth();
  const id = Number(g(formData, "id"));
  if (!id) return;
  await sql`UPDATE events SET active = NOT active, updated_at = now() WHERE id = ${id}`;
  revalidatePath("/admin/cursuri");
  revalidatePath("/");
  // admin/actions.php:50 — și toggle-ul reîncarcă pagina pe /admin/?tab=cursuri.
  redirect("/admin/cursuri");
}

/**
 * Bifează/debifează o postare Instagram pe o zi — fost POST /api/instagram_posts.php.
 * Întoarce lista rămasă pentru ziua respectivă, ca `clp_toggle_ig_post()`.
 */
export async function toggleIgPost(date: string, type: string, on: boolean): Promise<string[]> {
  await requireAuth();
  // api/instagram_posts.php:20 — doar dată YYYY-MM-DD și un tip din listă.
  if (!/^\d{4}-\d{2}-\d{2}$/.test(date) || !IG_POST_TYPES[type]) throw new Error("Invalid date or type");
  const [row] = (await sql`SELECT value FROM settings WHERE key = 'instagram_posts'`) as { value: unknown }[];
  const map: Record<string, string[]> =
    row?.value && typeof row.value === "object" ? { ...(row.value as Record<string, string[]>) } : {};
  const cur = (map[date] ?? []).filter((t) => t !== type);
  if (on) cur.push(type);
  if (cur.length) map[date] = cur;
  else delete map[date];
  await sql`
    INSERT INTO settings (key, value) VALUES ('instagram_posts', ${JSON.stringify(map)})
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()
  `;
  revalidatePath("/admin/cursuri");
  revalidatePath("/admin");
  return cur;
}
