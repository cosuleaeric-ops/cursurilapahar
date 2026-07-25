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

const dateRoFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "numeric", month: "long", year: "numeric" });
const dtFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "2-digit", month: "2-digit", year: "numeric" });

/** Cursurile lunii + raportul, viza și baza DITL — echivalentul /api/cursuri_month.php. */
export async function fetchMonthStats(year: number, month: number) {
  const prefix = `${year}-${String(month).padStart(2, "0")}`;

  const rows = (await sql`
    SELECT e.id, e.title, e.starts_at,
           (SELECT count(*)::int FROM tickets t WHERE t.event_id = e.id) AS total_tickets,
           (SELECT count(*)::int FROM event_files f WHERE f.event_id = e.id AND f.file_type = 'viza') AS viza_files,
           r.total_bilete, r.total_incasari, r.types_json
    FROM events e
    LEFT JOIN event_reports r ON r.event_id = e.id
    WHERE to_char(e.starts_at AT TIME ZONE ${TZ}, 'YYYY-MM') = ${prefix}
    ORDER BY e.starts_at DESC
  `) as {
    id: number;
    title: string;
    starts_at: string;
    total_tickets: number;
    viza_files: number;
    total_bilete: string | null;
    total_incasari: string | null;
    types_json: TicketType[] | null;
  }[];

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
      has_viza: Number(r.viza_files) > 0 || (subtipsByEvent.get(Number(r.id))?.length ?? 0) > 0,
      total_incasari: hasReport ? Number(r.total_incasari) : null,
      ditl_base: base,
      subtips: subtipsByEvent.get(Number(r.id)) ?? [],
    };
  });

  return { courses, sumIncasari, sumDitlBase };
}

/** Cursurile lunii pentru calendar (dată + titlu scurt). */
export async function fetchCalendarCourses(year: number, month: number) {
  const prefix = `${year}-${String(month).padStart(2, "0")}`;
  const rows = (await sql`
    SELECT title, to_char(starts_at AT TIME ZONE ${TZ}, 'YYYY-MM-DD') AS date
    FROM events
    WHERE to_char(starts_at AT TIME ZONE ${TZ}, 'YYYY-MM') = ${prefix}
    ORDER BY starts_at
  `) as { title: string; date: string }[];
  return rows.map((r) => ({ date: r.date, title: r.title.replace(/\s*\/\/.*$/u, "").replace(/^\s*Curs la Pahar\s*[-–]\s*/u, "") }));
}
