"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { put } from "@vercel/blob";
import sharp from "sharp";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { createTypes } from "@/lib/bilete";
import { cleanDescriere } from "@/lib/descriere";
import { COURSE_TIMES } from "./times";

// Editorul complet de curs: tot ce ține de un eveniment într-un singur formular,
// pe secțiuni. Vechiul formular inline din tab-ul Cursuri rămâne pentru editări
// rapide de dată/oră.

const TZ = "Europe/Bucharest";
const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

/** „Cum îți începi ziua?" → „cum-iti-incepi-ziua" */
function slugify(s: string): string {
  return s
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .replace(/[ăâ]/gi, "a")
    .replace(/[îí]/gi, "i")
    .replace(/[șş]/gi, "s")
    .replace(/[țţ]/gi, "t")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "")
    .slice(0, 80);
}

async function slugLiber(baza: string, exclude: number): Promise<string> {
  const s = baza || "curs";
  for (let i = 0; i < 50; i++) {
    const cand = i === 0 ? s : `${s}-${i + 1}`;
    const [luat] = (await sql`
      SELECT id FROM events WHERE slug = ${cand} AND id <> ${exclude}
    `) as { id: number }[];
    if (!luat) return cand;
  }
  return `${s}-${Date.now()}`;
}

/** Redimensionează și urcă în Blob; întoarce URL-ul sau "" dacă n-a venit fișier. */
async function urcaImagine(fd: FormData, camp: string, latime: number): Promise<string> {
  const f = fd.get(camp);
  if (!(f instanceof File) || !f.name || f.size === 0) return "";
  const webp = await sharp(Buffer.from(await f.arrayBuffer()))
    .rotate()
    .resize({ width: latime, withoutEnlargement: true })
    .webp({ quality: 88 })
    .toBuffer();
  const { url } = await put(`cursuri/${camp}-${Date.now()}.webp`, webp, {
    access: "public",
    addRandomSuffix: false,
    contentType: "image/webp",
  });
  return url;
}

type TipNou = { name: string; price: number; stock: number; description: string };

/** Rândurile de bilete din formular: `tip_name[]`, `tip_price[]`, … */
function tipuriDinForm(fd: FormData): TipNou[] {
  const nume = fd.getAll("tip_name").map(String);
  const preturi = fd.getAll("tip_price").map(String);
  const stocuri = fd.getAll("tip_stock").map(String);
  const descrieri = fd.getAll("tip_description").map(String);
  const out: TipNou[] = [];
  for (let i = 0; i < nume.length; i++) {
    const name = nume[i]?.trim();
    const price = Number((preturi[i] ?? "").replace(",", "."));
    const stock = Number(stocuri[i] ?? "");
    if (!name || !(price >= 0) || !(stock > 0)) continue;
    out.push({ name, price, stock, description: (descrieri[i] ?? "").trim() });
  }
  return out;
}

export async function saveCourseFull(formData: FormData): Promise<void> {
  await requireAuth();

  const id = Number(g(formData, "id")) || 0;
  const title = g(formData, "title");
  const dateRaw = g(formData, "date_raw");
  const time = g(formData, "time");
  const speakerId = Number(g(formData, "speaker_id")) || 0;
  const location = g(formData, "location");
  const descriere = cleanDescriere(g(formData, "description"));
  const activ = formData.get("active") != null;

  const err = (m: string) =>
    redirect(`${id ? `/admin/cursuri/${id}/editeaza` : "/admin/cursuri/nou"}?err=${encodeURIComponent(m)}`);

  if (!title) err("Cursul are nevoie de un titlu.");
  if (!dateRaw) err("Alege data cursului.");
  if (!(COURSE_TIMES as readonly string[]).includes(time)) err("Alege ora din listă.");

  const [speaker] = speakerId
    ? ((await sql`SELECT name FROM speakers WHERE id = ${speakerId}`) as { name: string }[])
    : [];
  if (!speaker) err("Alege un speaker din listă.");

  const [portret, landscape] = await Promise.all([
    urcaImagine(formData, "image_portrait", 1400),
    urcaImagine(formData, "image_landscape", 2400),
  ]);

  const startsAt = `${dateRaw} ${time}`;
  const slug = await slugLiber(slugify(title), id);

  if (id) {
    await sql`
      UPDATE events SET
        title = ${title}, slug = ${slug},
        starts_at = (${startsAt}::timestamp AT TIME ZONE ${TZ}),
        speaker_id = ${speakerId}, speaker_name = ${speaker.name},
        location = ${location || null},
        description = ${descriere || null}, active = ${activ},
        image_url = COALESCE(NULLIF(${portret}, ''), image_url),
        image_landscape_url = COALESCE(NULLIF(${landscape}, ''), image_landscape_url),
        updated_at = now()
      WHERE id = ${id}
    `;
    revalidatePath("/admin/cursuri");
    revalidatePath("/");
    redirect(`/admin/cursuri/${id}/editeaza?ok=1`);
  }

  const cardId = `c${Date.now().toString(16)}${Math.random().toString(16).slice(2, 10)}`;
  const [created] = (await sql`
    INSERT INTO events (title, slug, legacy_card_id, starts_at, speaker_id, speaker_name, location,
                        image_url, image_landscape_url, description, active)
    VALUES (${title}, ${slug}, ${cardId}, (${startsAt}::timestamp AT TIME ZONE ${TZ}), ${speakerId},
            ${speaker.name}, ${location || null}, ${portret || null},
            ${landscape || null}, ${descriere || null}, ${activ})
    RETURNING id
  `) as { id: number }[];

  const tipuri = tipuriDinForm(formData);
  if (tipuri.length) await createTypes(created.id, tipuri);
  // descrierile biletelor nu trec prin createTypes — le punem după, pe poziție
  for (const [i, t] of tipuri.entries()) {
    if (!t.description) continue;
    await sql`
      UPDATE ticket_types SET description = ${t.description}
      WHERE event_id = ${created.id} AND position = ${i}
    `;
  }

  revalidatePath("/admin/cursuri");
  revalidatePath("/");
  redirect(`/admin/cursuri/${created.id}/editeaza?ok=1`);
}
