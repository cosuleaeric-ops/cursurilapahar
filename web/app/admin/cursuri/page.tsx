import Link from "next/link";
import { sql } from "@/lib/db";
import { saveCourse } from "./actions";
import CourseAddForm, { type CourseEdit } from "./CourseAddForm";
import CoursesTable, { type CourseRow } from "./CoursesTable";
import { CoursesPanel, ParticipantsPanel } from "./StatsPanels";
import Calendar from "./Calendar";
import { fetchMonthStats, fetchCalendarCourses, RO_MONTHS } from "./stats-data";
import { fetchParticipants } from "@/lib/statistici";

export const dynamic = "force-dynamic";

type Row = {
  id: number;
  title: string;
  speaker_name: string | null;
  location: string | null;
  image_url: string | null;
  livetickets_url: string | null;
  active: boolean;
  clicks: number;
  date_raw: string | null;
  time_str: string | null;
  discount_percent: number | null;
  discount_ends_local: string | null;
  discount_active: boolean | null;
  upcoming: boolean;
};

const dFmt = new Intl.DateTimeFormat("ro-RO", { day: "numeric", month: "long", year: "numeric" });
const dateDisplay = (raw: string | null) => (raw ? dFmt.format(new Date(`${raw}T12:00:00`)) : "");

export default async function CursuriPage({
  searchParams,
}: {
  searchParams: Promise<{ ctab?: string; year?: string; month?: string; edit?: string; saved?: string; course_error?: string }>;
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
    SELECT id, title, speaker_name, location, image_url, livetickets_url, active, clicks,
      to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS date_raw,
      to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'HH24:MI') AS time_str,
      discount_percent,
      to_char(discount_ends_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD"T"HH24:MI') AS discount_ends_local,
      discount_ends_at > now() AS discount_active,
      starts_at >= now() AS upcoming
    FROM events
    ORDER BY starts_at ASC
  `) as Row[];

  const upcoming: CourseRow[] = rows
    .filter((c) => c.upcoming)
    .map((c) => ({
      id: c.id,
      title: c.title,
      speaker_name: c.speaker_name,
      date_display: dateDisplay(c.date_raw),
      date_raw: c.date_raw,
      livetickets_url: c.livetickets_url,
      image_url: c.image_url,
      active: c.active,
      clicks: c.clicks,
      discount_percent: c.discount_percent,
      discount_ends_local: c.discount_ends_local,
      discount_active: c.discount_active === true,
    }));

  const editId = Number(sp.edit) || 0;
  const src = editId ? rows.find((c) => c.id === editId) : null;
  const edit: CourseEdit | null = src
    ? {
        id: src.id,
        title: src.title,
        date_raw: src.date_raw ?? "",
        time: src.time_str ?? "",
        speaker_name: src.speaker_name ?? "",
        location: src.location ?? "",
        livetickets_url: src.livetickets_url ?? "",
        image_url: src.image_url ?? "",
      }
    : null;

  const speakers = (await sql`SELECT id, name, status FROM speakers ORDER BY name`) as {
    id: number;
    name: string;
    status: string | null;
  }[];
  const locations = (await sql`SELECT id, name FROM locations ORDER BY name`) as { id: number; name: string }[];

  return (
    <>
      <h1 className="wp-page-title">Cursuri</h1>

      {sp.saved && <div className="notice notice-success">Curs salvat.</div>}

      <CourseAddForm
        action={saveCourse}
        speakers={speakers}
        locations={locations}
        edit={edit}
        error={sp.course_error}
      />

      <div className="card">
        <div className="card-title">Viitoarele cursuri ({upcoming.length})</div>
        {upcoming.length === 0 ? (
          <p style={{ color: "var(--text-muted)" }}>Nu există cursuri adăugate încă.</p>
        ) : (
          <CoursesTable list={upcoming} />
        )}
      </div>

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
          <Link
            href={tabHref(ctab, prevM.y, prevM.m)}
            className="clp-tab-btn"
            style={{ padding: "7px 12px", lineHeight: 1 }}
            aria-label="Luna anterioară"
          >
            ←
          </Link>
          <span
            className="clp-tab-btn active"
            style={{ cursor: "default", minWidth: 96, textAlign: "center", pointerEvents: "none" }}
          >
            {RO_MONTHS[month].charAt(0).toUpperCase() + RO_MONTHS[month].slice(1)} {year}
          </span>
          <Link
            href={tabHref(ctab, nextM.y, nextM.m)}
            className="clp-tab-btn"
            style={{ padding: "7px 12px", lineHeight: 1 }}
            aria-label="Luna următoare"
          >
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
