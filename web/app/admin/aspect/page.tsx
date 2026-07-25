import { sql } from "@/lib/db";
import { uploadLogo, uploadFavicon, saveDesign } from "./actions";
import ColorField, { ColorisInit } from "./ColorField";

export const dynamic = "force-dynamic";

const COLOR_FIELDS: Record<string, { label: string; default: string }> = {
  color_bg: { label: "Fundal principal", default: "#0D0D0D" },
  color_accent: { label: "Culoare accent", default: "#C9A84C" },
  color_text: { label: "Culoare text", default: "#E8E4DC" },
  color_text_muted: { label: "Text secundar", default: "#9CA3AF" },
  color_surface: { label: "Fundal carduri/secțiuni", default: "#161616" },
  color_btn_hover: { label: "Hover butoane", default: "#b8922e" },
  color_banner: { label: "Fundal banner anunț", default: "#FFB000" },
};

// mesajele de eroare la favicon, cuvânt cu cuvânt din upload_favicon
const FVERR: Record<string, string> = {
  nofile: "Nu ai selectat niciun fișier.",
  read: "Nu am putut citi imaginea. Încearcă alt fișier.",
  save: "Eroare la salvare favicon. Verifică permisiunile directorului.",
};

// „Format neacceptat” include extensia efectivă a fișierului trimis
const fverrText = (code: string, ext?: string) =>
  code === "format"
    ? `Format neacceptat: ${ext ?? ""}. Folosește PNG, JPG sau WEBP.`
    : (FVERR[code] ?? "Eroare la upload.");

// eroarea de favicon e o casetă roz în interiorul cardului, nu un notice global
const FVERR_BOX: React.CSSProperties = {
  background: "#fcf0f1",
  border: "1px solid #f5c6cb",
  color: "#c0392b",
  padding: "10px 14px",
  borderRadius: 4,
  fontSize: 13,
  marginBottom: 12,
};

const FILE_INPUT: React.CSSProperties = {
  border: "1px solid var(--border)",
  padding: "6px 10px",
  borderRadius: 4,
  fontSize: 13,
  background: "#fff",
};

export default async function AspectPage({
  searchParams,
}: {
  searchParams: Promise<{ saved?: string; fverr?: string; ext?: string }>;
}) {
  const { saved, fverr, ext } = await searchParams;
  const rows = (await sql`
    SELECT key, value FROM settings
    WHERE key IN ('logo_path', 'favicon_path', 'color_bg', 'color_accent', 'color_text',
                  'color_text_muted', 'color_surface', 'color_btn_hover', 'color_banner')
  `) as { key: string; value: unknown }[];
  const s = Object.fromEntries(rows.map((r) => [r.key, r.value]));
  const str = (k: string, d = "") => (typeof s[k] === "string" && s[k] ? (s[k] as string) : d);

  return (
    <>
      <h1 className="wp-page-title">Aspect</h1>
      {saved && <div className="notice notice-success">Setările de aspect au fost salvate.</div>}

      <div className="card">
        <div className="card-title">Logo</div>
        <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 12 }}>
          Logo curent: <code>{str("logo_path")}</code>
        </p>
        {str("logo_path") && (
          <img
            src={str("logo_path")}
            alt="Logo"
            style={{ maxHeight: 60, marginBottom: 12, display: "block", background: "#1d2327", padding: 8, borderRadius: 4 }}
          />
        )}
        <form action={uploadLogo}>
          <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
            <input type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.svg" style={FILE_INPUT} />
            <button type="submit" className="btn btn-primary">
              Încarcă logo
            </button>
          </div>
          <p className="form-desc">Formate: JPG, PNG, WEBP, SVG.</p>
        </form>
      </div>

      <div className="card">
        <div className="card-title">Favicon</div>
        {str("favicon_path") && (
          <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 12 }}>
            Favicon curent: <code>{str("favicon_path")}</code>
          </p>
        )}
        {fverr && <div style={FVERR_BOX}>{fverrText(fverr, ext)}</div>}
        <form action={uploadFavicon}>
          <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
            <input type="file" name="favicon_file" accept=".ico,.png,.jpg,.jpeg,.webp" style={FILE_INPUT} />
            <button type="submit" className="btn btn-primary">
              Încarcă favicon
            </button>
          </div>
          <p className="form-desc">Formate: ICO, PNG, JPG, WEBP. Fișierul va fi salvat în rădăcina site-ului.</p>
        </form>
      </div>

      <ColorisInit />
      <form action={saveDesign}>
        <div className="card" style={{ marginTop: 20 }}>
          <div className="card-title">Culori</div>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16, marginBottom: 20 }}>
            {Object.entries(COLOR_FIELDS).map(([name, meta]) => (
              <ColorField key={name} name={name} label={meta.label} value={str(name, meta.default)} />
            ))}
          </div>
          <button type="submit" className="btn btn-primary">
            Salvează design
          </button>
        </div>
      </form>
    </>
  );
}
