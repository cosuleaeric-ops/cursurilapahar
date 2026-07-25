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

type Transform = { x: number; y: number; zoom: number };

// echivalentul cast-ului (float) din PHP: lipsă → default, valoare necastabilă → 0
function num(v: unknown, def: number): number {
  if (v === undefined || v === null) return def;
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
}

export async function uploadImages(formData: FormData): Promise<void> {
  await requireAuth();
  // fără niciun fișier ales → un singur mesaj roșu, fără contoare
  const files = formData.getAll("image_files").filter((f): f is File => f instanceof File && f.name !== "");
  if (!files.length) redirect("/admin/imagini?nofile=1");
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
  // Curăță referințele orfane din hero/galerie ca să nu rămână slide-uri albe
  const refRows = (await sql`
    SELECT key, value FROM settings WHERE key IN ('hero_images', 'gallery_featured')
  `) as { key: string; value: unknown }[];
  for (const r of refRows) {
    if (!Array.isArray(r.value)) continue;
    const next = (r.value as unknown[]).filter((u) => u !== url);
    if (next.length === r.value.length) continue;
    await sql`UPDATE settings SET value = ${JSON.stringify(next)}, updated_at = now() WHERE key = ${r.key}`;
  }
  revalidatePath("/", "layout");
  revalidatePath("/admin/imagini");
}

export async function saveImageSelection(formData: FormData): Promise<void> {
  await requireAuth();
  const hero = formData.getAll("hero_images").map(String).filter(Boolean);
  const gallery = formData.getAll("gallery_featured").map(String).filter(Boolean);
  let raw: unknown = null;
  try {
    raw = JSON.parse(String(formData.get("hero_transforms") ?? "{}"));
  } catch {
    raw = null;
  }
  // Poziționare hero per-imagine (x/y/zoom), doar pentru imaginile hero curente
  const transforms: Record<string, Transform> = {};
  if (raw && typeof raw === "object") {
    const src = raw as Record<string, unknown>;
    for (const hu of hero) {
      const t = src[hu];
      if (!t || typeof t !== "object") continue;
      const tt = t as Partial<Record<keyof Transform, unknown>>;
      const x = Math.max(0, Math.min(100, num(tt.x, 50)));
      const y = Math.max(0, Math.min(100, num(tt.y, 50)));
      const z = Math.max(100, Math.min(220, num(tt.zoom, 100)));
      // stochează doar ce diferă de default
      if (x !== 50 || y !== 50 || z !== 100) transforms[hu] = { x, y, zoom: z };
    }
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
