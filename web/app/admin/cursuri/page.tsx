import Link from "next/link";
import { sql } from "@/lib/db";
import { deleteCourse, toggleActive } from "./actions";
import styles from "./cursuri.module.css";

export const dynamic = "force-dynamic";

type Course = {
  id: number;
  title: string;
  location: string | null;
  image_url: string | null;
  active: boolean;
  sold_out: boolean;
  date_str: string | null;
  upcoming: boolean;
  ticket_count: number;
};

const dFmt = new Intl.DateTimeFormat("ro-RO", { day: "numeric", month: "short", year: "numeric" });
const fmtDate = (s: string | null) => (s ? dFmt.format(new Date(`${s}T12:00:00`)) : "—");

function cardTitle(t: string) {
  return t.replace(/\s+\/\/\s+.+$/u, "");
}

function Row({ c }: { c: Course }) {
  return (
    <div className={styles.item}>
      {c.image_url ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img className={styles.thumb} src={c.image_url} alt="" />
      ) : (
        <div className={styles.thumbPlaceholder} />
      )}
      <div className={styles.itemMain}>
        <div className={styles.itemTop}>
          <span className={styles.name}>{cardTitle(c.title)}</span>
          {c.sold_out && <span className={`${styles.badge} ${styles.badgeSold}`}>SOLD OUT</span>}
        </div>
        <div className={styles.meta}>
          {fmtDate(c.date_str)}
          {c.location ? ` · ${c.location}` : ""}
          {c.ticket_count > 0 ? ` · ${c.ticket_count} bilete` : ""}
        </div>
      </div>
      <div className={styles.itemActions}>
        <form action={toggleActive}>
          <input type="hidden" name="id" value={c.id} />
          <button className={`${styles.toggle} ${c.active ? styles.on : styles.off}`} type="submit">
            {c.active ? "Activ" : "Inactiv"}
          </button>
        </form>
        <Link className={styles.btnGhost} href={`/admin/cursuri/${c.id}`}>
          Editează
        </Link>
        {c.ticket_count === 0 && (
          <form action={deleteCourse}>
            <input type="hidden" name="id" value={c.id} />
            <button className={styles.btnDanger} type="submit">
              Șterge
            </button>
          </form>
        )}
      </div>
    </div>
  );
}

export default async function CursuriPage() {
  const rows = (await sql`
    SELECT id, title, location, image_url, active, sold_out,
      to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS date_str,
      starts_at >= now() AS upcoming,
      (SELECT count(*)::int FROM tickets WHERE event_id = e.id) AS ticket_count
    FROM events e
    ORDER BY starts_at DESC
  `) as Course[];

  const upcoming = rows.filter((c) => c.upcoming).sort((a, b) => (a.date_str ?? "").localeCompare(b.date_str ?? ""));
  const past = rows.filter((c) => !c.upcoming);

  return (
    <>
      <div className={styles.head}>
        <h1 className={styles.h1}>Cursuri ({rows.length})</h1>
        <Link className={styles.btnPrimary} href="/admin/cursuri/nou">
          + Adaugă curs
        </Link>
      </div>

      <h2 className={styles.subhead}>Viitoare ({upcoming.length})</h2>
      <div className={styles.list}>
        {upcoming.length === 0 ? <p className={styles.empty}>Niciun curs viitor.</p> : upcoming.map((c) => <Row key={c.id} c={c} />)}
      </div>

      <h2 className={styles.subhead}>Trecute ({past.length})</h2>
      <div className={styles.list}>
        {past.map((c) => (
          <Row key={c.id} c={c} />
        ))}
      </div>
    </>
  );
}
