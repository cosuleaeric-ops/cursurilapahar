"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { put } from "@vercel/blob";
import sharp from "sharp";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

async function setSetting(key: string, value: unknown): Promise<void> {
  await sql`
    INSERT INTO settings (key, value) VALUES (${key}, ${JSON.stringify(value)})
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()
  `;
}

export async function uploadLogo(formData: FormData): Promise<void> {
  await requireAuth();
  const f = formData.get("logo_file");
  if (!(f instanceof File) || !f.size) redirect("/admin/aspect");
  const ext = (f.name.split(".").pop() ?? "").toLowerCase();
  if (!["jpg", "jpeg", "png", "webp", "svg"].includes(ext)) redirect("/admin/aspect");
  const blob = await put(`uploads/logo-${Date.now()}.${ext}`, Buffer.from(await f.arrayBuffer()), {
    access: "public",
    addRandomSuffix: false,
    contentType: f.type || undefined,
  });
  await setSetting("logo_path", blob.url);
  revalidatePath("/", "layout");
  redirect("/admin/aspect?saved=1");
}

export async function uploadFavicon(formData: FormData): Promise<void> {
  await requireAuth();
  const f = formData.get("favicon_file");
  if (!(f instanceof File) || !f.size) redirect("/admin/aspect?fverr=nofile");
  const ext = (f.name.split(".").pop() ?? "").toLowerCase();
  if (!["ico", "png", "jpg", "jpeg", "webp"].includes(ext)) redirect("/admin/aspect?fverr=format");
  try {
    // Center-crop pătrat → 128px → mască circulară (transparent în afara cercului) → PNG,
    // exact ca varianta GD din admin/actions.php.
    const SIZE = 128;
    const circle = Buffer.from(
      `<svg width="${SIZE}" height="${SIZE}"><circle cx="${SIZE / 2}" cy="${SIZE / 2}" r="${SIZE / 2}"/></svg>`
    );
    const png = await sharp(Buffer.from(await f.arrayBuffer()))
      .rotate()
      .resize(SIZE, SIZE, { fit: "cover" })
      .composite([{ input: circle, blend: "dest-in" }])
      .png()
      .toBuffer();
    const blob = await put(`uploads/favicon-${Date.now()}.png`, png, {
      access: "public",
      addRandomSuffix: false,
      contentType: "image/png",
    });
    await setSetting("favicon_path", blob.url);
  } catch {
    redirect("/admin/aspect?fverr=read");
  }
  revalidatePath("/", "layout");
  redirect("/admin/aspect?saved=1");
}

export async function saveDesign(formData: FormData): Promise<void> {
  await requireAuth();
  const fields = ["color_bg", "color_accent", "color_text", "color_text_muted", "color_surface", "color_btn_hover", "color_banner"];
  for (const f of fields) {
    const val = String(formData.get(f) ?? "").trim();
    if (/^#[0-9a-fA-F]{3,8}$/.test(val)) await setSetting(f, val);
  }
  revalidatePath("/", "layout");
  redirect("/admin/aspect?saved=1");
}
