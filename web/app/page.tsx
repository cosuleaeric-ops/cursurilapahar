import { sql } from "@/lib/db";
import styles from "./page.module.css";

// Date live din Neon la fiecare request (scaffold — caching-ul îl reglăm ulterior).
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

const dateFmt = new Intl.DateTimeFormat("ro-RO", {
  timeZone: "Europe/Bucharest",
  weekday: "long",
  day: "numeric",
  month: "long",
  hour: "2-digit",
  minute: "2-digit",
});

function formatDate(iso: string | null): string {
  if (!iso) return "";
  const s = dateFmt.format(new Date(iso));
  return s.charAt(0).toUpperCase() + s.slice(1);
}

export default async function Home() {
  const settingsRows = (await sql`SELECT key, value FROM settings`) as { key: string; value: unknown }[];
  const settings = Object.fromEntries(settingsRows.map((r) => [r.key, r.value]));

  const events = (await sql`
    SELECT id, title, starts_at, location, image_url, livetickets_url, sold_out
    FROM events
    WHERE active = true
    ORDER BY starts_at ASC
  `) as EventRow[];

  const heroTitle = typeof settings.hero_title === "string" ? settings.hero_title : "Curs la Pahar";
  const announcement = typeof settings.announcement === "string" ? settings.announcement : "";

  return (
    <main className={styles.main}>
      {announcement && <div className={styles.announcement}>{announcement}</div>}

      <h1 className={styles.hero} dangerouslySetInnerHTML={{ __html: heroTitle }} />

      <section>
        <h2 className={styles.sectionTitle}>Cursuri active ({events.length})</h2>
        <div className={styles.grid}>
          {events.map((e) => (
            <article key={e.id} className={styles.card}>
              {e.image_url && <img className={styles.cardImg} src={e.image_url} alt={e.title} />}
              <div className={styles.cardBody}>
                <h3 className={styles.cardTitle}>{e.title}</h3>
                <p className={styles.cardMeta}>{formatDate(e.starts_at)}</p>
                {e.location && <p className={styles.cardMeta}>{e.location}</p>}
                {e.sold_out ? (
                  <span className={styles.soldOut}>SOLD OUT</span>
                ) : (
                  e.livetickets_url && (
                    <a className={styles.btn} href={e.livetickets_url} target="_blank" rel="noreferrer">
                      Bilete
                    </a>
                  )
                )}
              </div>
            </article>
          ))}
        </div>
      </section>

      <p className={styles.footnote}>Date live din Neon Postgres · scaffold migrare Next.js</p>
    </main>
  );
}
