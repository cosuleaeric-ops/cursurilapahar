import { sql } from "@/lib/db";
import HeroCarousel from "./HeroCarousel";
import { abVariant, shouldCountClick, trackAb } from "@/lib/ab";
import FaqList from "./FaqList";
import Gallery from "./Gallery";
import { NewsletterForm, ContactForm } from "./forms";

export const dynamic = "force-dynamic";

type EventRow = {
  id: number;
  title: string;
  starts_at: string | null;
  location: string | null;
  image_url: string | null;
  livetickets_url: string | null;
  sold_out: boolean;
};

const dayFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: "Europe/Bucharest", weekday: "long", day: "numeric", month: "long" });
const timeFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: "Europe/Bucharest", hour: "2-digit", minute: "2-digit" });
const badgeDayFmt = new Intl.DateTimeFormat("en-US", { timeZone: "Europe/Bucharest", day: "2-digit" });
const badgeMonFmt = new Intl.DateTimeFormat("en-US", { timeZone: "Europe/Bucharest", month: "short" });

function datetimeLabel(iso: string | null): string {
  if (!iso) return "";
  const d = new Date(iso);
  const date = dayFmt.format(d);
  return `${date.charAt(0).toUpperCase() + date.slice(1)}, ${timeFmt.format(d)}`;
}

const cardTitle = (t: string) => t.replace(/\s+\/\/\s+.+$/u, "");

export default async function Home() {
  // Test A/B buton „Vreau să vin" — varianta e atribuită de proxy.ts (cookie)
  const ab = await abVariant();
  if (ab && (await shouldCountClick())) await trackAb(ab, "views");

  const settingsRows = (await sql`SELECT key, value FROM settings`) as { key: string; value: unknown }[];
  const s = Object.fromEntries(settingsRows.map((r) => [r.key, r.value]));
  const str = (k: string, d = "") => (typeof s[k] === "string" ? (s[k] as string) : d);

  const rawHero =
    Array.isArray(s.hero_images) && s.hero_images.length ? (s.hero_images as string[]) : ["/assets/images/hero1.jpg"];
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
  const galleryTitle = str("gallery_title", "GALERIE");
  const galleryImages = Array.isArray(s.gallery_featured) ? (s.gallery_featured as string[]) : [];
  const contactTitle = str("contact_title", "CONTACT");
  const contactSubtitle = str("contact_subtitle");
  const year = new Date().getFullYear();

  const collabCards = [
    { href: "/prezinta-un-curs", img: "sustine", title: "Prezintă un curs", text: "Ai expertiză într-un domeniu care te pasionează? Vino să susții un curs în fața comunității noastre." },
    { href: "/gazduieste-un-curs", img: "gazduieste", title: "Găzduiește un curs", text: "Ai o locație cu vibe fain? Transformă-o în spațiul unde se nasc conexiunile și ideile noi." },
    { href: "/propune-un-parteneriat", img: "parteneriat", title: "Propune un parteneriat", text: "Reprezinți un brand sau o platformă media? Hai să explorăm ce putem construi împreună." },
  ];

  const events = (await sql`
    SELECT id, title, starts_at, location, image_url, livetickets_url, sold_out
    FROM events
    WHERE active = true
    ORDER BY starts_at ASC
  `) as EventRow[];

  return (
    <>
      <section className="hero" id="hero">
        <HeroCarousel slides={heroSlides} />
        <div className="hero-overlay"></div>
        <div className="hero-content">
          <h1 className="hero-title" dangerouslySetInnerHTML={{ __html: heroTitle }} />
          <p className="hero-subtitle">Experți și profesori îți predau la un pahar, într-un bar din București.</p>
          <a href="#cursuri" className="btn btn-primary hero-cta">
            Vezi cursurile ↓
          </a>
        </div>
      </section>

      <section className="section" id="cursuri">
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
                const linkProps =
                  e.sold_out || !e.livetickets_url
                    ? {}
                    : { href: `/go/course?id=${e.id}`, target: "_blank", rel: "noopener" };
                return (
                  <a
                    key={e.id}
                    {...linkProps}
                    className={`event-card${e.sold_out ? " event-card--soldout" : ""}`}
                  >
                    {e.sold_out && <div className="sold-out-badge">SOLD OUT</div>}
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
                            {datetimeLabel(e.starts_at)}
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
                      {ab === "on" && !e.sold_out && (
                        <span className="event-card-cta">
                          Vreau să vin
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M5 12h14M13 6l6 6-6 6" />
                          </svg>
                        </span>
                      )}
                    </div>
                  </a>
                );
              })}
            </div>
          )}
        </div>
      </section>

      {announcement && <div className="announcement-banner">{announcement}</div>}

      <section className="section section-dark" id="newsletter">
        <div className="container container-narrow">
          <h2 className="section-title">{newsletterTitle}</h2>
          <p className="newsletter-desc">{newsletterDesc}</p>
          <NewsletterForm />
        </div>
      </section>

      <section className="section" id="colaborare">
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

      <section className="section section-dark" id="faq">
        <div className="container container-narrow">
          <h2 className="section-title">{faqTitle}</h2>
          <FaqList items={faqItems} />
        </div>
      </section>

      {galleryImages.length > 0 && (
        <section className="section" id="galerie">
          <div className="container">
            <h2 className="section-title">{galleryTitle}</h2>
            <Gallery images={galleryImages} />
          </div>
        </section>
      )}

      <section className="section section-dark" id="contact">
        <div className="container container-narrow">
          <h2 className="section-title">{contactTitle}</h2>
          <p className="section-subtitle">{contactSubtitle}</p>
          <ContactForm />
        </div>
      </section>

      <footer className="footer">
        <div className="container">
          <div className="footer-inner">
            <div className="footer-brand">
              <span className="logo-text footer-logo">
                Cursuri
                <br />
                <em>la Pahar</em>
              </span>
              <p>Aducem educația în baruri.</p>
            </div>
            <div className="footer-links">
              <a href="/#cursuri">Cursuri</a>
              <a href="/#faq">FAQ</a>
              <a href="/#colaborare">Colaborare</a>
              <a href="/#contact">Contact</a>
            </div>
            <div className="footer-social">
              <a href="https://www.instagram.com/cursurilapahar" target="_blank" rel="noopener" aria-label="Instagram">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                </svg>
              </a>
              <a href="https://www.tiktok.com/@cursurilapahar" target="_blank" rel="noopener" aria-label="TikTok">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V9.13a8.19 8.19 0 004.79 1.53V7.19a4.85 4.85 0 01-1.02-.5z" />
                </svg>
              </a>
              <a href="https://www.facebook.com/profile.php?id=61585669450450" target="_blank" rel="noopener" aria-label="Facebook">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                </svg>
              </a>
            </div>
          </div>
          <div className="footer-bottom">
            <p>&copy; {year} Cursuri la Pahar. Toate drepturile rezervate.</p>
          </div>
        </div>
      </footer>
    </>
  );
}
