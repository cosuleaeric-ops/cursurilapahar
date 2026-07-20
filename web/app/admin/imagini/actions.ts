"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { put, del } from "@vercel/blob";
import sharp from "sharp";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAuth(): Promise<void> {
  if (!(await getSession())) redirect("/login");
}

const ALLOWED = new Set(["jpg", "jpeg", "png", "webp", "gif", "avif"]);

export async function uploadImages(formData: FormData): Promise<void> {
  await requireAuth();
  const files = formData.getAll("image_files").filter((f): f is File => f instanceof File && f.size > 0);
  let ok = 0;
  let err = 0;
  for (const [i, f] of files.entries()) {
    const ext = (f.name.split(".").pop() ?? "").toLowerCase();
    if (!ALLOWED.has(ext)) {
      err++;
      continue;
    }
    try {
      const buf = Buffer.from(await f.arrayBuffer());
      const webp = await sharp(buf)
        .rotate()
        .resize({ width: 2560, withoutEnlargement: true })
        .webp({ quality: 88 })
        .toBuffer();
      const base = (f.name.replace(/\.[^.]+$/, "") || "img").replace(/[^a-zA-Z0-9_-]/g, "_");
      await put(`uploads/${base}-${Date.now()}${i}.webp`, webp, {
        access: "public",
        addRandomSuffix: false,
        contentType: "image/webp",
      });
      ok++;
    } catch {
      err++;
    }
  }
  revalidatePath("/admin/imagini");
  redirect(`/admin/imagini?up=${ok}&uperr=${err}`);
}

export async function deleteImage(formData: FormData): Promise<void> {
  await requireAuth();
  const url = String(formData.get("url") ?? "");
  if (!url.includes(".blob.vercel-storage.com/")) return;
  await del(url);
  revalidatePath("/admin/imagini");
}

export async function saveImageSelection(formData: FormData): Promise<void> {
  await requireAuth();
  const hero = formData.getAll("hero_images").map(String).filter(Boolean);
  const gallery = formData.getAll("gallery_featured").map(String).filter(Boolean);
  let transforms: unknown = {};
  try {
    transforms = JSON.parse(String(formData.get("hero_transforms") ?? "{}"));
  } catch {
    transforms = {};
  }
  const set = async (key: string, value: unknown) =>
    sql`
      INSERT INTO settings (key, value) VALUES (${key}, ${JSON.stringify(value)})
      ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()
    `;
  await set("hero_images", hero);
  await set("gallery_featured", gallery);
  await set("hero_transforms", transforms);
  revalidatePath("/", "layout");
  revalidatePath("/admin/imagini");
  redirect("/admin/imagini?saved=1");
}
