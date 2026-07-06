import { sql } from "@/lib/db";
import HeroCarousel from "./HeroCarousel";

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
  const settingsRows = (await sql`SELECT key, value FROM settings`) as { key: string; value: unknown }[];
  const s = Object.fromEntries(settingsRows.map((r) => [r.key, r.value]));
  const str = (k: string, d = "") => (typeof s[k] === "string" ? (s[k] as string) : d);

  const rawHero =
    Array.isArray(s.hero_images) && s.hero_images.length ? (s.hero_images as string[]) : ["/assets/images/hero1.jpg"];
  // servim webp (ca site-ul real via img_webp) — mult mai mici decât jpg
  const heroImages = rawHero.map((p) => p.replace(/\.jpe?g$/i, ".webp"));
  const heroTitle = str("hero_title", "Curs la Pahar");
  const coursesTitle = str("courses_title", "PROGRAM CURSURI");

  const events = (await sql`
    SELECT id, title, starts_at, location, image_url, livetickets_url, sold_out
    FROM events
    WHERE active = true
    ORDER BY starts_at ASC
  `) as EventRow[];

  return (
    <>
      <section className="hero" id="hero">
        <HeroCarousel images={heroImages} />
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
                const linkProps = e.sold_out
                  ? {}
                  : { href: e.livetickets_url ?? "#", target: "_blank", rel: "noopener" };
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
                    </div>
                  </a>
                );
              })}
            </div>
          )}
        </div>
      </section>
    </>
  );
}
