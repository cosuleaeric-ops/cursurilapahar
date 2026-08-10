import Link from "next/link";
import { sql } from "@/lib/db";
import { saveCourse } from "./actions";
import CourseAddForm, { type CourseEdit } from "./CourseAddForm";
import CoursesTable, { type CourseRow } from "./CoursesTable";
import { CoursesPanel, ParticipantsPanel } from "./StatsPanels";
import Calendar from "./Calendar";
import { fetchMonthStats, fetchCalendarCourses, IG_POST_TYPES, RO_MONTHS } from "./stats-data";
import { fetchParticipants } from "@/lib/statistici";

export const dynamic = "force-dynamic";

type Row = {
  id: number;
  title: string;
  speaker_id: number | null;
  speaker_name: string | null;
  location: string | null;
  image_url: string | null;
  livetickets_url: string | null;
  active: boolean;
  clicks: number;
  date_display: string | null;
  date_raw: string | null;
  time_str: string | null;
  discount_percent: number | null;
  discount_ends_local: string | null;
  discount_active: boolean | null;
  upcoming: boolean;
};

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

  // Toate patru sunt independente, deci un singur dus-întors ca durată, nu patru.
  const [rows, speakers, locations, settingRows] = (await Promise.all([
    sql`
    SELECT id, title, speaker_id, speaker_name, location, image_url, livetickets_url, active, clicks,
      date_display,
      to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS date_raw,
      to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'HH24:MI') AS time_str,
      discount_percent,
      to_char(discount_ends_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD"T"HH24:MI') AS discount_ends_local,
      discount_ends_at > now() AS discount_active,
      to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') >=
        to_char(now() AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS upcoming
    FROM events
    -- lista vine exclusiv din cardurile de site (courses.json), ca
    -- clp_load_courses_for_admin(); evenimentele doar-din-statistici n-au card
    WHERE legacy_card_id IS NOT NULL
    ORDER BY starts_at ASC
  `,
    // load_speakers_for_picker() (lib/speakers.php:138-141): aceeași ordine ca în tabul
    // Speakeri — întâi rangul statusului, apoi numele case-insensitive; fără nume gol.
    sql`
    SELECT id, name, status FROM speakers
    WHERE trim(name) <> ''
    ORDER BY
      CASE
        WHEN status IS NULL OR status = '' THEN 3
        WHEN status = 'CONTACTAT' THEN 0
        WHEN status = 'URMEAZĂ' THEN 1
        WHEN status = 'RECURENT' THEN 2
        WHEN status = 'MID' THEN 3
        WHEN status = 'NOPE' THEN 4
        ELSE 2 END,
      lower(name)
  `,
    sql`SELECT id, name FROM locations ORDER BY name`,
    // clp_load_ig_posts() — postările Instagram marcate pe zile, pentru chipurile din calendar.
    sql`SELECT value FROM settings WHERE key = 'instagram_posts'`,
  ])) as [
    Row[],
    { id: number; name: string; status: string | null }[],
    { id: number; name: string }[],
    { value: unknown }[],
  ];

  const upcoming: CourseRow[] = rows
    .filter((c) => c.upcoming)
    .map((c) => ({
      id: c.id,
      title: c.title,
      speaker_name: c.speaker_name,
      location: c.location,
      // lib/courses_admin.php:107 — `$c['date_display'] ?? $c['date_raw'] ?? ''`: când lipsește
      // textul STOCAT se arată data BRUTĂ („2026-08-11"), nu una reformatată.
      date_display: c.date_display ?? c.date_raw ?? "",
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
  const src = editId ? rows.find((c) => Number(c.id) === editId) : null;
  const edit: CourseEdit | null = src
    ? {
        id: src.id,
        title: src.title,
        date_raw: src.date_raw ?? "",
        time: src.time_str ?? "",
        // cursuri-tab.php:40 — hidden-ul pornește din `speaker_id`-ul salvat, nu din nume.
        speaker_id: src.speaker_id ?? 0,
        speaker_name: src.speaker_name ?? "",
        location: src.location ?? "",
        livetickets_url: src.livetickets_url ?? "",
        image_url: src.image_url ?? "",
      }
    : null;

  const igValue = settingRows[0]?.value;
  const igPosts = igValue && typeof igValue === "object" ? (igValue as Record<string, string[]>) : {};

  return (
    <>
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 16, flexWrap: "wrap" }}>
        <h1 className="wp-page-title" style={{ marginBottom: 0 }}>Cursuri</h1>
        <a
          href="/admin/cursuri/nou"
          style={{ padding: "10px 20px", background: "var(--accent)", color: "#fff", borderRadius: 8, fontSize: 14, fontWeight: 600, textDecoration: "none" }}
        >
          + Curs nou
        </a>
      </div>

      {sp.saved && <div className="notice notice-success">Curs salvat.</div>}

      {/* cursuri-tab.php randează câmpurile server-side la fiecare request: `key`-ul
          remontează formularul când se schimbă cursul editat sau după o salvare,
          ca să vină iar populat din ?edit=… și gol după adăugare. */}
      <CourseAddForm
        key={`${editId}|${sp.saved ?? ""}`}
        action={saveCourse}
        speakers={speakers}
        locations={locations}
        edit={edit}
        error={sp.course_error}
        year={year}
        month={month}
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
          <Calendar
            year={year}
            month={month}
            courses={await fetchCalendarCourses(year, month)}
            today={todayStr}
            igPosts={igPosts}
            igPostTypes={IG_POST_TYPES}
          />
        )}
        {ctab === "participanti" && <ParticipantsPanel {...(await fetchParticipants())} />}
      </div>
    </>
  );
}
