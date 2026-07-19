import { sql } from "@/lib/db";
import SiteNav from "./SiteNav";
import HeadScripts from "./HeadScripts";

export const dynamic = "force-dynamic";

const FONTS =
  "https://fonts.googleapis.com/css2?family=Anton&family=Bebas+Neue&family=Poppins:wght@800&family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400&display=swap";

type NavLink = { url: string; label: string };

export default async function SiteLayout({ children }: { children: React.ReactNode }) {
  const rows = (await sql`SELECT key, value FROM settings`) as { key: string; value: unknown }[];
  const s = Object.fromEntries(rows.map((r) => [r.key, r.value]));
  const str = (k: string, d = "") => (typeof s[k] === "string" ? (s[k] as string) : d);

  const vars: Record<string, string> = {
    "--bg": str("color_bg", "#0D0D0D"),
    "--bg-surface": str("color_surface", "#161616"),
    "--accent": str("color_accent", "#C9A84C"),
    "--text": str("color_text", "#F0EBE1"),
    "--text-muted": str("color_text_muted", "#8A8A8A"),
    paddingTop: "88px",
  };

  const links: NavLink[] = Array.isArray(s.nav_links) ? (s.nav_links as NavLink[]) : [];
  const brand = str("nav_brand_text", "Cursuri la Pahar");

  return (
    <>
      <link rel="preconnect" href="https://fonts.googleapis.com" />
      <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
      <link rel="stylesheet" href={FONTS} />
      <link rel="stylesheet" href="/assets/css/style.css" />
      <HeadScripts html={str("head_scripts")} />
      <div style={vars as React.CSSProperties}>
        <SiteNav brand={brand} logo="/assets/images/logo.webp" links={links} />
        {children}
      </div>
    </>
  );
}
