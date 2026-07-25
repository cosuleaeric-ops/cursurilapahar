import Link from "next/link";
import { sql } from "@/lib/db";
import { deleteCourse, toggleActive } from "./actions";
import { CoursesPanel, ParticipantsPanel } from "./StatsPanels";
import Calendar from "./Calendar";
import { fetchMonthStats, fetchCalendarCourses, RO_MONTHS } from "./stats-data";
import { fetchParticipants } from "@/lib/statistici";

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
const cardTitle = (t: string) => t.replace(/\s+\/\/\s+.+$/u, "");

const rowStyle: React.CSSProperties = {
  display: "flex",
  alignItems: "center",
  gap: 14,
  padding: "12px 0",
  borderBottom: "1px solid var(--border)",
};

function Row({ c }: { c: Course }) {
  return (
    <div style={rowStyle}>
      {c.image_url ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img className="course-thumb" src={c.image_url} alt="" />
      ) : (
        <div className="course-thumb-empty" />
      )}
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <span className="course-title-line" style={{ overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
            {cardTitle(c.title)}
          </span>
          {c.sold_out && (
            <span className="crm-status-badge" style={{ background: "#6b7280" }}>
              SOLD OUT
            </span>
          )}
        </div>
        <div style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 2 }}>
          {fmtDate(c.date_str)}
          {c.location ? ` · ${c.location}` : ""}
          {c.ticket_count > 0 ? ` · ${c.ticket_count} bilete` : ""}
        </div>
      </div>
      <div style={{ display: "flex", alignItems: "center", gap: 8, flexShrink: 0 }}>
        <form action={toggleActive} style={{ margin: 0 }}>
          <input type="hidden" name="id" value={c.id} />
          <button type="submit" className={`btn btn-sm ${c.active ? "status-active" : "status-inactive"}`}>
            {c.active ? "Activ" : "Inactiv"}
          </button>
        </form>
        <Link className="btn btn-sm btn-secondary" href={`/admin/cursuri/${c.id}`}>
          Editează
        </Link>
        {c.ticket_count === 0 && (
          <form action={deleteCourse} style={{ margin: 0 }}>
            <input type="hidden" name="id" value={c.id} />
            <button type="submit" className="btn btn-sm btn-danger">
              Șterge
            </button>
          </form>
        )}
      </div>
    </div>
  );
}

export default async function CursuriPage({
  searchParams,
}: {
  searchParams: Promise<{ ctab?: string; year?: string; month?: string }>;
}) {
  const sp = await searchParams;
  const ctab = sp.ctab === "calendar" || sp.ctab === "participanti" ? sp.ctab : "cursuri";
  const todayStr = new Intl.DateTimeFormat("en-CA", { timeZone: "Europe/Bucharest" }).format(new Date());
  const year = Number(sp.year) || Number(todayStr.slice(0, 4));
  const month = Number(sp.month) || Number(todayStr.slice(5, 7));
  const prevM = month === 1 ? { y: year - 1, m: 12 } : { y: year, m: month - 1 };
  const nextM = month === 12 ? { y: year + 1, m: 1 } : { y: year, m: month + 1 };
  const tabHref = (t: string, y = year, m = month) => `/admin/cursuri?ctab=${t}&year=${y}&month=${m}`;

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

  const subhead: React.CSSProperties = {
    fontSize: 11,
    fontWeight: 700,
    letterSpacing: ".05em",
    textTransform: "uppercase",
    color: "var(--text-muted)",
    margin: "24px 0 6px",
  };

  return (
    <>
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
        <h1 className="wp-page-title" style={{ marginBottom: 0 }}>
          Cursuri ({rows.length})
        </h1>
        <Link className="btn btn-primary" href="/admin/cursuri/nou">
          + Adaugă curs
        </Link>
      </div>

      <div style={subhead}>Viitoare ({upcoming.length})</div>
      {upcoming.length === 0 ? (
        <p style={{ color: "var(--text-muted)", fontSize: 13 }}>Niciun curs viitor.</p>
      ) : (
        upcoming.map((c) => <Row key={c.id} c={c} />)
      )}

      <div style={subhead}>Trecute ({past.length})</div>
      {past.map((c) => (
        <Row key={c.id} c={c} />
      ))}

      <div className="card" id="clp-stats-card">
        <div className="clp-tabs" style={{ marginBottom: 16 }}>
          <Link href={tabHref("cursuri")} className={`clp-tab-btn${ctab === "cursuri" ? " active" : ""}`}>
            Cursuri
          </Link>
          <Link href={tabHref("calendar")} className={`clp-tab-btn${ctab === "calendar" ? " active" : ""}`}>
            Calendar
          </Link>
          <Link href={tabHref("participanti")} className={`clp-tab-btn${ctab === "participanti" ? " active" : ""}`}>
            Participanți
          </Link>
          <span className="clp-tabs-sep" aria-hidden="true"></span>
          <Link href={tabHref(ctab, prevM.y, prevM.m)} className="clp-tab-btn" style={{ padding: "7px 12px", lineHeight: 1 }} aria-label="Luna anterioară">
            ←
          </Link>
          <span className="clp-tab-btn active" style={{ cursor: "default", minWidth: 96, textAlign: "center", pointerEvents: "none" }}>
            {RO_MONTHS[month].charAt(0).toUpperCase() + RO_MONTHS[month].slice(1)} {year}
          </span>
          <Link href={tabHref(ctab, nextM.y, nextM.m)} className="clp-tab-btn" style={{ padding: "7px 12px", lineHeight: 1 }} aria-label="Luna următoare">
            →
          </Link>
        </div>

        {ctab === "cursuri" && <CoursesPanel {...(await fetchMonthStats(year, month))} />}
        {ctab === "calendar" && (
          <Calendar year={year} month={month} courses={await fetchCalendarCourses(year, month)} today={todayStr} />
        )}
        {ctab === "participanti" && <ParticipantsPanel {...(await fetchParticipants())} />}
      </div>
    </>
  );
}
