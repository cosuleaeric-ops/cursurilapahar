import { readdir } from "node:fs/promises";
import { join } from "node:path";
import { list } from "@vercel/blob";
import { sql } from "@/lib/db";
import { uploadImages } from "./actions";
import ImaginiManager, { type LibImage } from "./ImaginiManager";

export const dynamic = "force-dynamic";

const IMG_EXT = /\.(jpe?g|png|webp|gif|avif)$/i;

async function staticImages(): Promise<LibImage[]> {
  const out: LibImage[] = [];
  const collect = async (rel: string) => {
    try {
      const dir = join(process.cwd(), "public", rel);
      for (const f of await readdir(dir, { withFileTypes: true })) {
        if (f.isFile() && IMG_EXT.test(f.name)) {
          out.push({ url: `/${rel}/${f.name}`, name: f.name, deletable: false });
        }
      }
    } catch {
      // folderul poate lipsi în unele deploy-uri — biblioteca rămâne doar cu Blob
    }
  };
  await collect("assets/images");
  await collect("assets/images/gallery");
  await collect("assets/images/uploads");
  return out;
}

async function blobImages(): Promise<LibImage[]> {
  try {
    const { blobs } = await list({ prefix: "uploads/" });
    return blobs
      .sort((a, b) => +new Date(b.uploadedAt) - +new Date(a.uploadedAt))
      .map((b) => ({ url: b.url, name: b.pathname.replace(/^uploads\//, ""), deletable: true }));
  } catch {
    return [];
  }
}

export default async function ImaginiPage({
  searchParams,
}: {
  searchParams: Promise<{ saved?: string; up?: string; uperr?: string }>;
}) {
  const { saved, up, uperr } = await searchParams;
  const [rowsRaw, statics, blobs] = await Promise.all([
    sql`SELECT key, value FROM settings WHERE key IN ('hero_images', 'gallery_featured', 'hero_transforms')`,
    staticImages(),
    blobImages(),
  ]);
  const rows = rowsRaw as { key: string; value: unknown }[];
  const s = Object.fromEntries(rows.map((r) => [r.key, r.value]));
  const hero = Array.isArray(s.hero_images) ? (s.hero_images as string[]) : [];
  const gallery = Array.isArray(s.gallery_featured) ? (s.gallery_featured as string[]) : [];
  const transforms =
    s.hero_transforms && typeof s.hero_transforms === "object"
      ? (s.hero_transforms as Record<string, { x?: number; y?: number; zoom?: number }>)
      : {};

  const library = [...blobs, ...statics];

  return (
    <>
      <h1 className="wp-page-title">Imagini</h1>

      {saved && <div className="notice notice-success">Setările imaginilor au fost salvate.</div>}
      {up !== undefined && (
        <div className={`notice ${Number(uperr) ? "notice-error" : "notice-success"}`}>
          {Number(up)} imagini încărcate{Number(uperr) ? `, ${uperr} eșuate` : ""}.
        </div>
      )}

      <div className="card">
        <div className="card-title">Încarcă imagine nouă</div>
        <form action={uploadImages}>
          <div style={{ display: "flex", gap: 8, alignItems: "center", flexWrap: "wrap" }}>
            <input
              type="file"
              name="image_files"
              accept="image/*"
              multiple
              style={{ border: "1px solid var(--border)", padding: "6px 10px", borderRadius: 4, fontSize: 13, background: "#fff" }}
            />
            <button type="submit" className="btn btn-primary">
              Încarcă
            </button>
          </div>
          <p className="form-desc">
            JPG, PNG, WEBP, GIF. Poți selecta mai multe. Convertite automat în WebP (calitate 88) și redimensionate la max
            2560px. Pentru hero, urcă imaginea la rezoluție cât mai mare. După încărcare apar primele în Bibliotecă.
          </p>
        </form>
      </div>

      <ImaginiManager library={library} heroInit={hero} galleryInit={gallery} transformsInit={transforms} />
    </>
  );
}
