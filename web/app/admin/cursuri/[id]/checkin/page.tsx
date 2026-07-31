import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { VIEW_CSS } from "../detalii/styles";
import Scanner from "./Scanner";
import { CHECKIN_CSS } from "./styles";

export const dynamic = "force-dynamic";

const TZ = "Europe/Bucharest";
const roDate = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "numeric", month: "long", year: "numeric" });

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }) {
  const { id: idStr } = await params;
  const [event] = (await sql`SELECT title FROM events WHERE id = ${Number(idStr) || 0}`) as { title: string }[];
  return { title: `Check-in — ${event?.title ?? "Curs"}` };
}

export default async function CheckinPage({ params }: { params: Promise<{ id: string }> }) {
  const { id: idStr } = await params;
  const id = Number(idStr);
  if (!id) notFound();

  const [event] = (await sql`SELECT id, title, starts_at FROM events WHERE id = ${id}`) as {
    id: number;
    title: string;
    starts_at: string | null;
  }[];
  if (!event) notFound();

  const [n] = (await sql`
    SELECT COUNT(*) FILTER (WHERE status = 'vandut')::int AS vandute,
           COUNT(*) FILTER (WHERE status = 'vandut' AND used_at IS NOT NULL)::int AS intrati
    FROM ticket_pool WHERE event_id = ${id}
  `) as { vandute: number; intrati: number }[];

  return (
    <>
      <link rel="stylesheet" href="/admin/statistici/style.css" />
      <style dangerouslySetInnerHTML={{ __html: VIEW_CSS + CHECKIN_CSS }} />

      <div className="course-wrap" style={{ maxWidth: 520 }}>
        <div className="course-hero">
          <a
            href={`/admin/cursuri/${id}/bilete`}
            style={{ fontSize: 12, color: "var(--muted)", textDecoration: "none", display: "inline-block", marginBottom: 10 }}
          >
            ← Inapoi
          </a>
          <h2>{event.title}</h2>
          <div className="meta">{event.starts_at ? roDate.format(new Date(event.starts_at)) : ""}</div>
        </div>

        <div className="section-card">
          <div className="checkin-count">
            <strong>{n.intrati}</strong> din {n.vandute} au intrat
          </div>
          <Scanner eventId={id} />
        </div>
      </div>
    </>
  );
}
