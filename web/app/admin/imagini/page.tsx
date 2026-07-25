import { readdir, stat } from "node:fs/promises";
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
      const names = (await readdir(dir, { withFileTypes: true }))
        .filter((f) => f.isFile() && IMG_EXT.test(f.name))
        .map((f) => f.name);
      // fiecare fișier poartă data lui reală, ca să intre în sortarea globală a bibliotecii
      const withMtime = await Promise.all(
        names.map(async (name) => ({
          url: `/${rel}/${name}`,
          name,
          deletable: false,
          mtime: await stat(join(dir, name)).then((st) => st.mtimeMs, () => 0),
        })),
      );
      out.push(...withMtime);
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
    return blobs.map((b) => ({
      url: b.url,
      name: b.pathname.replace(/^uploads\//, ""),
      deletable: true,
      mtime: +new Date(b.uploadedAt),
    }));
  } catch {
    return [];
  }
}

export default async function ImaginiPage({
  searchParams,
}: {
  searchParams: Promise<{ saved?: string; up?: string; uperr?: string; nofile?: string }>;
}) {
  const { saved, up, uperr, nofile } = await searchParams;
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

  // o singură sortare globală peste toate sursele, cele mai noi primele
  const library = [...blobs, ...statics].sort((a, b) => b.mtime - a.mtime);
  const okCount = Number(up ?? 0);
  const errCount = Number(uperr ?? 0);

  return (
    <>
      <h1 className="wp-page-title">Imagini</h1>

      {saved && <div className="notice notice-success">Setările imaginilor au fost salvate.</div>}
      {/* reușitele și eșecurile sunt două notice-uri distincte, care pot apărea simultan */}
      {okCount > 0 && (
        <div className="notice notice-success">
          {`${okCount} imagine${okCount > 1 ? "i" : ""} încărcată${okCount > 1 ? "e" : ""} cu succes.`}
        </div>
      )}
      {errCount > 0 && (
        <div className="notice notice-error">
          {`${errCount} fișier${errCount > 1 ? "e" : ""} nu ${errCount > 1 ? "au" : "a"} putut fi încărcate.`}
        </div>
      )}
      {nofile && <div className="notice notice-error">Niciun fișier selectat.</div>}

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
