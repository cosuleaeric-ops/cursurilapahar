import type { Metadata } from "next";
import { sql } from "@/lib/db";
import SiteNav from "./SiteNav";
import HeadScripts from "./HeadScripts";
import AdminBar from "./AdminBar";

export const dynamic = "force-dynamic";

// Meta OG/Twitter se construiesc per pagină cu pageMetadata() din lib/metadata.ts
// (Next înlocuiește integral openGraph/twitter, nu le merge-uiește).

// Înălțime viewport reală în px, blocată pe scroll (se schimbă doar la rotație/
// lățime) — hero-ul nu-și mai schimbă înălțimea când se retrage bara browserului
// in-app (Instagram). Identic cu scriptul din index.php.
const VPH_SCRIPT =
  "(function(){var w=window.innerWidth;function s(){document.documentElement.style.setProperty('--vph',window.innerHeight+'px');}s();window.addEventListener('resize',function(){if(window.innerWidth!==w){w=window.innerWidth;s();}});window.addEventListener('orientationchange',function(){w=window.innerWidth;s();});})();";

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
    "--btn-hover": str("color_btn_hover", "#b8922e"),
    "--banner-bg": str("color_banner", "#FFB000"),
    paddingTop: "88px",
  };
  const favicon = str("favicon_path");

  const links: NavLink[] = Array.isArray(s.nav_links) ? (s.nav_links as NavLink[]) : [];
  const brand = str("nav_brand_text", "Cursuri la Pahar");

  return (
    <>
      <link rel="preconnect" href="https://fonts.googleapis.com" />
      <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
      <link rel="stylesheet" href={FONTS} />
      <link rel="stylesheet" href="/assets/css/style.css" />
      <script dangerouslySetInnerHTML={{ __html: VPH_SCRIPT }} />
      <HeadScripts html={str("head_scripts")} />
      {favicon && <link rel="icon" href={favicon} />}
      <div style={vars as React.CSSProperties}>
        <AdminBar />
        <SiteNav brand={brand} logo={str("logo_path", "/assets/images/logo.webp")} links={links} />
        {children}
      </div>
    </>
  );
}
