import type { Metadata } from "next";
import { getSettings } from "@/lib/settings";
import SiteNav from "./SiteNav";
import SiteFooter from "./SiteFooter";
import HeadScripts from "./HeadScripts";
import ScrollReveal from "./ScrollReveal";
import AdminBar from "./AdminBar";
import { PostHogInit } from "./PostHogInit";

// Meta OG/Twitter se construiesc per pagină cu pageMetadata() din lib/metadata.ts
// (Next înlocuiește integral openGraph/twitter, nu le merge-uiește).

// Test A/B buton „Vezi detalii & bilete". Atribuirea variantei stătea în proxy.ts și se
// citea la render; homepage-ul fiind acum cache-uit, HTML-ul e identic pentru toți,
// deci varianta se aplică în browser. Scriptul rulează în <head>, înainte de orice
// pixel desenat, așa că butonul nu apare și dispare. Aceeași împărțire 50/50 și
// același cookie de 90 de zile ca înainte.
const AB_SCRIPT =
  "(function(){var m=document.cookie.match(/(?:^|;\\s*)clp_ab_btn=(on|off)/),v=m&&m[1];" +
  "if(!v){v=Math.random()<0.5?'off':'on';document.cookie='clp_ab_btn='+v+';path=/;max-age=7776000;samesite=lax';}" +
  "document.documentElement.setAttribute('data-ab-btn',v);})();";

// Inline, nu în style.css: regula trebuie să ajungă odată cu markup-ul, altfel un
// stylesheet vechi din cache ar arăta butonul și celor din varianta „off".
const AB_CSS =
  '.event-card-cta{display:none !important}html[data-ab-btn="on"] .event-card-cta{display:flex !important}';

// Înălțime viewport reală în px, blocată pe scroll (se schimbă doar la rotație/
// lățime) — hero-ul nu-și mai schimbă înălțimea când se retrage bara browserului
// in-app (Instagram). Identic cu scriptul din index.php.
const VPH_SCRIPT =
  "(function(){var w=window.innerWidth;function s(){document.documentElement.style.setProperty('--vph',window.innerHeight+'px');}s();window.addEventListener('resize',function(){if(window.innerWidth!==w){w=window.innerWidth;s();}});window.addEventListener('orientationchange',function(){w=window.innerWidth;s();});})();";

const FONTS =
  "https://fonts.googleapis.com/css2?family=Anton&family=Bebas+Neue&family=Poppins:wght@800&family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400&display=swap";

// includes/head-scripts.php:20-33 adaugă hardcodat, pe fiecare pagină publică,
// Plausible și analytics-ul self-hosted de pe ericcosulea.ro — pe lângă ce e în
// settings.head_scripts.
// Pixel Meta — ACELAȘI dataset ca integrarea LiveTickets (1375094074585211), ca
// vizitatorii site-ului și cumpărătorii să intre în același bazin de audiențe:
// site view → AddToCart/Purchase pe LiveTickets rămâne o pâlnie continuă.
// Fără el, oricine citește site-ul fără să ajungă la bilete era invizibil pentru retargeting.
const META_PIXEL_ID = "1375094074585211";
const META_PIXEL_INIT =
  "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?" +
  "n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;" +
  "n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;" +
  "t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script'," +
  "'https://connect.facebook.net/en_US/fbevents.js');" +
  `fbq('init','${META_PIXEL_ID}');fbq('track','PageView');`;

const PLAUSIBLE_SRC = "https://plausible.io/js/pa-3t0zbcrOJNHSBQ4-KIokx.js";
const PLAUSIBLE_INIT =
  "window.plausible=window.plausible||function(){(plausible.q=plausible.q||[]).push(arguments)},plausible.init=plausible.init||function(i){plausible.o=i||{}};\n  plausible.init()";

type NavLink = { url: string; label: string };

export default async function SiteLayout({ children }: { children: React.ReactNode }) {
  const s = await getSettings();
  const str = (k: string, d = "") => (typeof s[k] === "string" ? (s[k] as string) : d);

  // Default-urile sunt cele din index.php:164-170 (identice pe toate paginile
  // publice și în lib/design.php), nu cele din style.css.
  const vars: Record<string, string> = {
    "--bg": str("color_bg", "#0D0D0D"),
    "--bg-surface": str("color_surface", "#161616"),
    // index.php:167 scrie și `--surface` din aceeași setare; CSS-ul local al
    // paginilor voteaza-cursuri / parteneri o consumă.
    "--surface": str("color_surface", "#161616"),
    "--accent": str("color_accent", "#C9A84C"),
    "--text": str("color_text", "#E8E4DC"),
    "--text-muted": str("color_text_muted", "#9CA3AF"),
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
      <style dangerouslySetInnerHTML={{ __html: AB_CSS }} />
      <script dangerouslySetInnerHTML={{ __html: AB_SCRIPT }} />
      <HeadScripts html={str("head_scripts")} />
      <script dangerouslySetInnerHTML={{ __html: META_PIXEL_INIT }} />
      <noscript>
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          height="1"
          width="1"
          style={{ display: "none" }}
          alt=""
          src={`https://www.facebook.com/tr?id=${META_PIXEL_ID}&ev=PageView&noscript=1`}
        />
      </noscript>
      <script async src={PLAUSIBLE_SRC} />
      <script dangerouslySetInnerHTML={{ __html: PLAUSIBLE_INIT }} />
      <script
        defer
        data-website-id="dfid_a1e25f0ab3"
        data-domain="cursurilapahar.ro"
        src="https://www.ericcosulea.ro/js/script.js"
      />
      {favicon && <link rel="icon" href={favicon} />}
      {/* clasa e ținta regulii din AdminBar: cu bara de admin, padding-ul urcă la 120px */}
      <div className="clp-site-shell" style={vars as React.CSSProperties}>
        <AdminBar />
        <PostHogInit />
        <SiteNav brand={brand} logo={str("logo_path", "/assets/images/logo.webp")} links={links} />
        {children}
        <SiteFooter />
        <ScrollReveal />
      </div>
    </>
  );
}
