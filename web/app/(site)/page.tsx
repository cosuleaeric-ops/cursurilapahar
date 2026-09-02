import type { Metadata } from "next";
import { after } from "next/server";
import { pageMetadata } from "@/lib/metadata";
import { sql } from "@/lib/db";
import { getSettings } from "@/lib/settings";
import HeroCarousel from "./HeroCarousel";
import AbView from "./AbView";
import DiscountCountdown from "./DiscountCountdown";
import FaqList from "./FaqList";
import Gallery from "./Gallery";
import { NewsletterForm, ContactForm } from "./forms";
import { ltGetEventByUrl, ltImageUrlFromEvent, ltIsSoldOut } from "@/lib/livetickets";

// Pagina se prerandează și se servește din cache-ul CDN. Modificările din admin o
// invalidează imediat (revalidatePath("/") din app/admin/*/actions.ts); fereastra
// de mai jos e doar pentru ce ține de trecerea timpului — sold-out-ul împrospătat
// în after(), badge-ul „NOU" la 48h, reducerile expirate, schimbarea zilei.
export const revalidate = 120;

export const metadata: Metadata = pageMetadata({
  title: "Cursuri la Pahar - Educație la un pahar în oraș",
  description: "Cursuri ținute de experți într-un cadru relaxat, la un pahar în oraș.",
  ogTitle: "Învață ceva nou la un pahar în oraș",
  ogDescription: "Experți și profesori îți predau la un pahar, într-un bar din București.",
  path: "/",
});

type EventRow = {
  id: number;
  title: string;
  starts_at: string | null;
  location: string | null;
  image_url: string | null;
  livetickets_url: string | null;
  sold_out: boolean;
  sold_out_checked_at: string | null;
  link_added_at: string | null;
  discount_percent: number | null;
  discount_ends_at: string | null;
};

const NEW_MS = 48 * 3600 * 1000;

// „Următorul curs este azi / mâine / peste X (de) zile" — ca în index.php
function heroNextLabel(events: EventRow[], todayBucharest: string): string {
  const dates = events
    .map((e) => (e.starts_at ? new Intl.DateTimeFormat("en-CA", { timeZone: "Europe/Bucharest" }).format(new Date(e.starts_at)) : ""))
    .filter((d) => d && d >= todayBucharest)
    .sort();
  if (!dates.length) return "";
  const days = Math.round((Date.parse(dates[0]) - Date.parse(todayBucharest)) / 86400000);
  if (days === 0) return "Următorul curs este azi";
  if (days === 1) return "Următorul curs este mâine";
  return `Următorul curs este peste ${days}${days >= 20 ? " de" : ""} zile`;
}

const isoDayFmt = new Intl.DateTimeFormat("en-CA", { timeZone: "Europe/Bucharest" });
const dayFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: "Europe/Bucharest", day: "numeric", month: "long" });
const weekdayFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: "Europe/Bucharest", weekday: "long" });
const timeFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: "Europe/Bucharest", hour: "2-digit", minute: "2-digit" });
const badgeDayFmt = new Intl.DateTimeFormat("en-US", { timeZone: "Europe/Bucharest", day: "2-digit" });
const badgeMonFmt = new Intl.DateTimeFormat("en-US", { timeZone: "Europe/Bucharest", month: "short" });

// clp_ro_day_prefix (lib/dates.php:43-62): „Astăzi" în ziua cursului, „Mâine" a
// doua zi (fus București), altfel numele zilei.
function dayPrefix(d: Date, todayBucharest: string): string {
  const days = Math.round((Date.parse(isoDayFmt.format(d)) - Date.parse(todayBucharest)) / 86400000);
  if (days === 0) return "Astăzi";
  if (days === 1) return "Mâine";
  const w = weekdayFmt.format(d);
  return w.charAt(0).toUpperCase() + w.slice(1);
}

function datetimeLabel(iso: string | null, todayBucharest: string): string {
  if (!iso) return "";
  const d = new Date(iso);
  return `${dayPrefix(d, todayBucharest)}, ${dayFmt.format(d)}, ${timeFmt.format(d)}`;
}

const cardTitle = (t: string) => t.replace(/\s+\/\/\s+.+$/u, "");

/**
 * index.php:55-72 — la fiecare afișare, cursurile cu link de bilete dar fără
 * imagine își iau posterul din LiveTickets și îl salvează (pe toate cursurile,
 * nu doar pe cele publice).
 */
async function backfillImages(): Promise<void> {
  const rows = (await sql`
    SELECT id, livetickets_url FROM events
    WHERE coalesce(image_url, '') = '' AND coalesce(livetickets_url, '') <> ''
  `) as { id: number; livetickets_url: string }[];
  await Promise.all(
    rows.map(async (r) => {
      const ev = await ltGetEventByUrl(r.livetickets_url);
      const img = ev ? ltImageUrlFromEvent(ev) : "";
      if (img) await sql`UPDATE events SET image_url = ${img} WHERE id = ${r.id}`;
    }),
  );
}

/**
 * index.php:103-125 — sold-out din LiveTickets, cu cache (fost
 * data/soldout_cache.json, aici events.sold_out + sold_out_checked_at).
 * Cardurile se randează din coloana `sold_out`; asta doar o împrospătează, în
 * `after()`, ca apelurile către LiveTickets să nu mai stea pe calea critică.
 * TTL 600s (sub cele 15 minute cerute, chiar cu fereastra de revalidare a
 * paginii deasupra), doar 60s dacă e deja epuizat — ca în PHP.
 */
async function refreshSoldOut(events: EventRow[]): Promise<void> {
  const now = Date.now();
  await Promise.all(
    events.map(async (e) => {
      const url = e.livetickets_url?.trim() ?? "";
      if (!url) return;
      const ttl = e.sold_out ? 60_000 : 120_000;
      const checkedAt = e.sold_out_checked_at ? new Date(e.sold_out_checked_at).getTime() : 0;
      if (now - checkedAt < ttl) return;
      const ev = await ltGetEventByUrl(url);
      const sold = ev ? ltIsSoldOut(ev) : null;
      // Apel picat sau răspuns fără bilete = nu știm. Păstrăm valoarea veche și
      // reîncercăm la randarea următoare, altfel o secundă de indisponibilitate
      // la LiveTickets ștergea un sold-out real pentru tot TTL-ul.
      if (sold === null) return;
      // Randarea curentă folosește direct valoarea proaspătă, nu pe cea din SELECT.
      e.sold_out = sold;
      await sql`UPDATE events SET sold_out = ${sold}, sold_out_checked_at = now() WHERE id = ${e.id}`;
    }),
  );
}

export default async function Home() {
  const s = await getSettings();
  const str = (k: string, d = "") => (typeof s[k] === "string" ? (s[k] as string) : d);

  // index.php:212-224 iterează exact lista din settings: dacă adminul o golește,
  // nu se randează niciun .hero-slide și rămâne doar overlay-ul negru.
  const rawHero = Array.isArray(s.hero_images) ? (s.hero_images as string[]) : [];
  const heroTransforms =
    s.hero_transforms && typeof s.hero_transforms === "object"
      ? (s.hero_transforms as Record<string, { x?: number; y?: number; zoom?: number }>)
      : {};
  // servim webp (ca site-ul real via img_webp) — mult mai mici decât jpg;
  // transformările sunt cheiate pe URL-ul brut din settings
  const heroSlides = rawHero.map((p) => {
    const t = heroTransforms[p] ?? {};
    return {
      src: p.replace(/\.jpe?g$/i, ".webp"),
      pos: `${t.x ?? 50}% ${t.y ?? 50}%`,
      zoom: (t.zoom ?? 100) / 100,
    };
  });
  const heroTitle = str("hero_title", "Curs la Pahar");
  const coursesTitle = str("courses_title", "PROGRAM CURSURI");
  const announcement = str("announcement");
  const newsletterTitle = str("newsletter_title");
  const newsletterDesc = str("newsletter_desc");
  const collabTitle = str("collab_title", "COLABORARE");
  const collabSubtitle = str("collab_subtitle");
  const faqTitle = str("faq_title", "ÎNTREBĂRI FRECVENTE");
  const faqItems = Array.isArray(s.faq_items) ? (s.faq_items as { q: string; a: string }[]) : [];
  // Fundaluri de secțiune (port clp_section_bg): imagine + blur/overlay din settings.
  // Servim webp, ca peste tot pe site. Newsletter și FAQ au imagini implicite.
  const sectionBgs = (s.section_bgs && typeof s.section_bgs === "object" ? s.section_bgs : {}) as Record<
    string,
    { image?: string; blur?: number; overlay?: number }
  >;
  const bgFor = (id: string, defaultImg = "") => {
    const bg = sectionBgs[id] ?? {};
    const img = (typeof bg.image === "string" && bg.image ? bg.image : defaultImg).replace(/\.jpe?g$/i, ".webp");
    return {
      hasBg: !!img,
      attrs: {
        "data-section-bg": id,
        style: img
          ? ({
              "--section-bg-img": `url('${img}')`,
              "--section-blur": `${bg.blur ?? 6}px`,
              "--section-overlay": `${bg.overlay ?? 0.72}`,
            } as React.CSSProperties)
          : undefined,
      },
    };
  };
  const cursuriBg = bgFor("cursuri");
  const newsletterBg = bgFor("newsletter", "/assets/images/hero1.webp");
  const colaborareBg = bgFor("colaborare");
  const faqBg = bgFor("faq", "/assets/images/hero2.webp");
  const galerieBg = bgFor("galerie");
  const contactBg = bgFor("contact");

  const galleryTitle = str("gallery_title", "GALERIE");
  const galleryImages = Array.isArray(s.gallery_featured) ? (s.gallery_featured as string[]) : [];
  const contactTitle = str("contact_title", "CONTACT");
  const contactSubtitle = str("contact_subtitle");

  const firstCourseBenefits = [
    {
      img: "primul-curs-1",
      alt: "Speaker cu microfon pe scena de la MOJO, cu logoul barului în spate",
      text: "Vei învăța ceva nou într-un mediu relaxat",
    },
    {
      img: "primul-curs-2",
      alt: "Doi participanți râd în timpul unui exercițiu, cu sala plină în spate",
      text: "Vei cunoaște oameni cu aceleași curiozități ca și tine",
    },
    {
      img: "primul-curs-3",
      alt: "Grup de prietene la o masă de bar, seara, cu cocktailuri în față",
      text: "Vei transforma o simplă ieșire în oraș într-o experiență memorabilă",
    },
  ];

  const collabCards = [
    { href: "/prezinta-un-curs", img: "sustine", title: "Prezintă un curs", text: "Ai expertiză într-un domeniu care te pasionează? Vino să susții un curs în fața comunității noastre." },
    { href: "/gazduieste-un-curs", img: "gazduieste", title: "Găzduiește un curs", text: "Ai o locație cu vibe fain? Transformă-o în spațiul unde se nasc conexiunile și ideile noi." },
    { href: "/parteneri", img: "parteneriat", title: "Propune un parteneriat", text: "Reprezinți un brand sau o platformă media? Hai să explorăm ce putem construi împreună." },
  ];

  // Vizibil public = activ + link bilete + ziua nu a trecut (ca clp_course_is_public);
  // cursurile „NOU" (link pus în ultimele 48h) apar primele, apoi pe dată.
  const todayBucharest = new Intl.DateTimeFormat("en-CA", { timeZone: "Europe/Bucharest" }).format(new Date());
  const events = (await sql`
    SELECT id, title, starts_at, location, image_url, livetickets_url, sold_out, sold_out_checked_at,
           link_added_at, discount_percent, discount_ends_at
    FROM events
    WHERE active = true
      AND livetickets_url IS NOT NULL AND livetickets_url <> ''
      AND (starts_at IS NULL OR to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') >= ${todayBucharest})
    ORDER BY (link_added_at IS NOT NULL AND link_added_at > now() - interval '48 hours') DESC, starts_at ASC
  `) as EventRow[];
  const nextLabel = heroNextLabel(events, todayBucharest);

  // Sold-out-ul se verifică ÎN randare, nu în `after()`: apelurile către
  // LiveTickets din `after()` nu se finalizau pe Vercel, așa că fiecare tur ieșea
  // „nu știu" și badge-ul nu apărea niciodată. Pagina e ISR (revalidate 120), deci
  // vizitatorii primesc versiunea veche cât timp se regenerează — costul nu cade
  // pe cererea lor. Backfill-ul de imagini rămâne după răspuns, nu e urgent.
  await refreshSoldOut(events);
  after(backfillImages);

  return (
    <>
      <AbView />
      <section className="hero" id="hero">
        <HeroCarousel slides={heroSlides} />
        <div className="hero-overlay"></div>
        <div className="hero-content">
          {nextLabel && (
            <div className="hero-next-card">
              <span className="hero-next-dot"></span>
              {nextLabel}
            </div>
          )}
          <h1 className="hero-title" dangerouslySetInnerHTML={{ __html: heroTitle }} />
          <p className="hero-subtitle">Experți și profesori îți predau la un pahar, într-un bar din București.</p>
          <a href="#cursuri" className="btn btn-primary hero-cta">
            Vezi cursurile ↓
          </a>
        </div>
      </section>

      <section className={`section${cursuriBg.hasBg ? " section-bg-blur section-dark" : ""}`} id="cursuri" {...cursuriBg.attrs}>
        <div className="container">
          <h2 className="section-title">{coursesTitle}</h2>

          {events.length === 0 ? (
            <p className="no-events">
              Nu există cursuri programate momentan.
              <br />
              Abonează-te la newsletter să fii primul care află!
            </p>
          ) : (
            <div className="events-grid">
              {events.map((e) => {
                const d = e.starts_at ? new Date(e.starts_at) : null;
                const isNew = !!e.link_added_at && Date.now() - new Date(e.link_added_at).getTime() < NEW_MS;
                const soldOut = e.sold_out;
                const discountActive =
                  !soldOut &&
                  !!e.discount_percent &&
                  !!e.discount_ends_at &&
                  new Date(e.discount_ends_at).getTime() > Date.now();
                const linkProps =
                  soldOut || !e.livetickets_url
                    ? {}
                    : { href: `/go/course?id=${e.id}`, target: "_blank", rel: "noopener" };
                return (
                  <a
                    key={e.id}
                    {...linkProps}
                    data-ph-label={cardTitle(e.title)}
                    className={`event-card${soldOut ? " event-card--soldout" : ""}`}
                  >
                    {soldOut && <div className="sold-out-badge">SOLD OUT</div>}
                    <div className="event-card-img">
                      {e.image_url ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img src={e.image_url} alt={cardTitle(e.title)} loading="lazy" />
                      ) : (
                        <div className="event-card-img-placeholder"></div>
                      )}
                      {d && (
                        <div className="event-card-date-badge">
                          <span className="badge-day">{badgeDayFmt.format(d)}</span>
                          <span className="badge-month">{badgeMonFmt.format(d).toUpperCase()}</span>
                        </div>
                      )}
                      {discountActive && <div className="discount-badge">−{e.discount_percent}%</div>}
                      {isNew && <div className={`new-badge${discountActive ? " new-badge--below-discount" : ""}`}>NOU</div>}
                    </div>
                    <div className="event-card-body">
                      <h3 className="event-card-title">{cardTitle(e.title)}</h3>
                      <div className="event-card-meta">
                        {e.starts_at && (
                          <span className="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <circle cx="12" cy="12" r="10" />
                              <path d="M12 6v6l4 2" />
                            </svg>
                            {datetimeLabel(e.starts_at, todayBucharest)}
                          </span>
                        )}
                        {e.location && (
                          <span className="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                              <circle cx="12" cy="10" r="3" />
                            </svg>
                            {e.location}
                          </span>
                        )}
                      </div>
                      {discountActive && e.discount_ends_at && (
                        <DiscountCountdown endsAt={e.discount_ends_at} code="VARA30" />
                      )}
                      {/* Vizibil doar în varianta „on" a testului A/B - comutarea
                          se face din CSS-ul inline al layout-ului, pe data-ab-btn. */}
                      <span
                        className={`event-card-cta${soldOut ? " event-card-cta--disabled" : ""}`}
                        aria-disabled={soldOut || undefined}
                      >
                        Vezi detalii & bilete
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                      </span>
                    </div>
                  </a>
                );
              })}
            </div>
          )}
        </div>
      </section>

      {/* index.php:332-334 randează banda necondiționat, chiar și cu textul gol */}
      <div className="announcement-banner">{announcement}</div>

      <section className="section section-dark section-bg-blur" id="newsletter" {...newsletterBg.attrs}>
        <div className="container container-narrow">
          {/* index.php:343-345 - plicul auriu de 44px de deasupra titlului */}
          <div className="newsletter-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2" />
              <path d="m2 6 10 7L22 6" />
            </svg>
          </div>
          <h2 className="section-title">{newsletterTitle}</h2>
          <p className="newsletter-desc">{newsletterDesc}</p>
          <NewsletterForm />
        </div>
      </section>

      <section className="section" id="primul-curs">
        <div className="container">
          <h2 className="section-title">Cum te vei simți după primul curs?</h2>
          <div className="primul-curs-grid">
            {firstCourseBenefits.map((b, i) => (
              <figure className="primul-curs-card" key={b.img}>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={`/assets/images/primul-curs/${b.img}.webp`} alt={b.alt} loading="lazy" />
                <span className="primul-curs-num" aria-hidden="true">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <figcaption>{b.text}</figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>

      <section className={`section${colaborareBg.hasBg ? " section-bg-blur section-dark" : ""}`} id="colaborare" {...colaborareBg.attrs}>
        <div className="container">
          <h2 className="section-title">{collabTitle}</h2>
          <p className="section-subtitle">{collabSubtitle}</p>
          <div className="collab-grid">
            {collabCards.map((c) => (
              <a key={c.img} href={c.href} className="collab-card">
                <div className="collab-card-img">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={`/assets/images/uploads/${c.img}.webp`} alt={c.title} loading="lazy" />
                </div>
                <h3>{c.title}</h3>
                <p>{c.text}</p>
                <span className="collab-link">Află mai multe →</span>
              </a>
            ))}
          </div>
        </div>
      </section>

      <section className="section section-dark section-bg-blur" id="faq" {...faqBg.attrs}>
        <div className="container container-narrow">
          <h2 className="section-title">{faqTitle}</h2>
          <FaqList items={faqItems} />
        </div>
      </section>

      {galleryImages.length > 0 && (
        <section className={`section${galerieBg.hasBg ? " section-bg-blur section-dark" : ""}`} id="galerie" {...galerieBg.attrs}>
          <div className="container">
            <h2 className="section-title">{galleryTitle}</h2>
            <Gallery images={galleryImages} />
          </div>
        </section>
      )}

      <section className={`section section-dark${contactBg.hasBg ? " section-bg-blur" : ""}`} id="contact" {...contactBg.attrs}>
        <div className="container container-narrow">
          <h2 className="section-title">{contactTitle}</h2>
          <p className="section-subtitle">{contactSubtitle}</p>
          <ContactForm />
        </div>
      </section>
    </>
  );
}
