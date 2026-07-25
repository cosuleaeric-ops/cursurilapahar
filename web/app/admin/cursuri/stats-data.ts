import { sql } from "@/lib/db";
import { ditlBase, vanduteForTarif, type TicketType } from "@/lib/statistici";
import type { MonthCourse } from "./StatsPanels";

const TZ = "Europe/Bucharest";

export const RO_MONTHS = [
  "",
  "ianuarie",
  "februarie",
  "martie",
  "aprilie",
  "mai",
  "iunie",
  "iulie",
  "august",
  "septembrie",
  "octombrie",
  "noiembrie",
  "decembrie",
];

/** clp_ig_post_types() (lib/instagram_posts.php:10-14) — tipurile din dropdown-ul de zi. */
export const IG_POST_TYPES: Record<string, { label: string }> = {
  postare_cursuri: { label: "POSTARE CURSURI" },
};

const dateRoFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "numeric", month: "long", year: "numeric" });
const dtFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "2-digit", month: "2-digit", year: "numeric" });

/** Cursurile lunii + raportul, viza și baza DITL — echivalentul /api/cursuri_month.php. */
export async function fetchMonthStats(year: number, month: number) {
  const prefix = `${year}-${String(month).padStart(2, "0")}`;

  // Vizibilitate ca în clp_fetch_statistici_courses_for_month() (lib/courses.php:436-460):
  // se arată doar cursurile venite din courses.json (aici: legacy_card_id) sau cele fără
  // external_id care au statistici (raport / bilete / viză) și nu cad în aceeași zi cu un card.
  const allRows = (await sql`
    SELECT e.id, e.title, e.starts_at, e.external_id,
           to_char(e.starts_at AT TIME ZONE ${TZ}, 'YYYY-MM-DD') AS date,
           (SELECT count(*)::int FROM tickets t WHERE t.event_id = e.id) AS total_tickets,
           (SELECT count(*)::int FROM event_files f WHERE f.event_id = e.id AND f.file_type = 'viza') AS viza_files,
           r.total_bilete, r.total_incasari, r.types_json
    FROM events e
    LEFT JOIN event_reports r ON r.event_id = e.id
    WHERE to_char(e.starts_at AT TIME ZONE ${TZ}, 'YYYY-MM') = ${prefix}
      AND (
        e.legacy_card_id IS NOT NULL
        OR (
          coalesce(e.external_id, '') = ''
          AND (
            EXISTS (SELECT 1 FROM event_reports r2 WHERE r2.event_id = e.id)
            OR EXISTS (SELECT 1 FROM tickets t2 WHERE t2.event_id = e.id)
            OR EXISTS (SELECT 1 FROM event_files f2 WHERE f2.event_id = e.id AND f2.file_type = 'viza')
          )
          AND NOT EXISTS (
            SELECT 1 FROM events e2
            WHERE e2.legacy_card_id IS NOT NULL
              AND (e2.starts_at AT TIME ZONE ${TZ})::date = (e.starts_at AT TIME ZONE ${TZ})::date
          )
        )
      )
    ORDER BY e.starts_at ASC
  `) as {
    id: number;
    title: string;
    starts_at: string;
    external_id: string | null;
    date: string;
    total_tickets: number;
    viza_files: number;
    total_bilete: string | null;
    total_incasari: string | null;
    types_json: TicketType[] | null;
  }[];

  // clp_dedupe_statistici_course_rows() (lib/courses.php:363-389): pe fiecare dată rămâne
  // un singur curs — cel cu scorul cel mai mare (external_id 8 + raport 4 + viză 2 +
  // bilete 3) — apoi lista se re-sortează crescător după dată.
  const score = (r: (typeof allRows)[number]) =>
    ((r.external_id ?? "") !== "" ? 8 : 0) +
    (r.total_incasari != null ? 4 : 0) +
    (Number(r.viza_files) > 0 ? 2 : 0) +
    (Number(r.total_tickets) > 0 ? 3 : 0);
  const byDate = new Map<string, (typeof allRows)[number]>();
  for (const row of allRows) {
    if (!row.date) continue;
    const kept = byDate.get(row.date);
    if (!kept || score(row) > score(kept)) byDate.set(row.date, row);
  }
  const rows = [...byDate.values()].sort((a, b) => (a.date < b.date ? -1 : a.date > b.date ? 1 : 0));

  const ids = rows.map((r) => Number(r.id));
  const subtipsByEvent = new Map<number, MonthCourse["subtips"]>();
  if (ids.length) {
    const subs = (await sql`
      SELECT event_id, seria, tarif, nr_unitati, de_la, pana_la
      FROM viza_subtips WHERE event_id = ANY(${ids})
      ORDER BY event_id, tarif DESC
    `) as { event_id: number; seria: string; tarif: string; nr_unitati: number; de_la: string; pana_la: string }[];
    for (const s of subs) {
      const eid = Number(s.event_id);
      const row = rows.find((r) => Number(r.id) === eid);
      const types = (row?.types_json ?? []) as TicketType[];
      const list = subtipsByEvent.get(eid) ?? [];
      list.push({
        seria: s.seria,
        de_la: s.de_la ?? "",
        pana_la: s.pana_la ?? "",
        vandute: vanduteForTarif(types, Number(s.tarif), Number(s.nr_unitati)),
        nr_unitati: Number(s.nr_unitati),
        tarif: Number(s.tarif),
      });
      subtipsByEvent.set(eid, list);
    }
  }

  let sumIncasari = 0;
  let sumDitlBase = 0;
  const courses: MonthCourse[] = rows.map((r) => {
    const hasReport = r.total_incasari != null;
    const types = (r.types_json ?? []) as TicketType[];
    const base = hasReport ? ditlBase(types, Number(r.total_bilete ?? 0)) : null;
    if (hasReport) {
      sumIncasari += Number(r.total_incasari);
      sumDitlBase += base ?? 0;
    }
    return {
      id: Number(r.id),
      name: r.title,
      date_ro: r.starts_at ? dateRoFmt.format(new Date(r.starts_at)) : "",
      total_tickets: Number(r.total_tickets),
      has_report: hasReport,
      // lib/courses.php:473 — bifa de viză depinde exclusiv de fișierul încărcat.
      has_viza: Number(r.viza_files) > 0,
      total_incasari: hasReport ? Number(r.total_incasari) : null,
      ditl_base: base,
      subtips: subtipsByEvent.get(Number(r.id)) ?? [],
    };
  });

  return { courses, sumIncasari, sumDitlBase };
}

/**
 * Cursurile pentru calendar. Ca în clp_courses_stats_js_config() (lib/courses_admin.php:175):
 * sursa e exclusiv courses.json (aici: events cu legacy_card_id), iar titlul e cel brut.
 */
export async function fetchCalendarCourses(year: number, month: number) {
  const prefix = `${year}-${String(month).padStart(2, "0")}`;
  const rows = (await sql`
    SELECT title, to_char(starts_at AT TIME ZONE ${TZ}, 'YYYY-MM-DD') AS date
    FROM events
    WHERE legacy_card_id IS NOT NULL
      AND to_char(starts_at AT TIME ZONE ${TZ}, 'YYYY-MM') = ${prefix}
    ORDER BY starts_at
  `) as { title: string; date: string }[];
  return rows.map((r) => ({ date: r.date, title: r.title }));
}
