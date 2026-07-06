import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

export const dynamic = "force-dynamic";

export default async function AdminHome() {
  const session = await getSession();
  const [stats] = (await sql`
    SELECT
      (SELECT count(*) FROM events)        AS events,
      (SELECT count(*) FROM tickets)       AS tickets,
      (SELECT count(*) FROM vote_courses)  AS votes,
      (SELECT count(*) FROM speakers)      AS speakers
  `) as { events: string; tickets: string; votes: string; speakers: string }[];

  const cards = [
    { label: "Evenimente", value: stats.events, cls: "accent-blue" },
    { label: "Bilete", value: stats.tickets, cls: "accent-green" },
    { label: "Cursuri la vot", value: stats.votes, cls: "accent-gold" },
    { label: "Speakeri", value: stats.speakers, cls: "accent-red" },
  ];

  return (
    <>
      <h1 className="wp-page-title">Salut, {session?.username} 👋</h1>
      <div className="dash-grid" style={{ gridTemplateColumns: "repeat(auto-fit, minmax(170px, 1fr))" }}>
        {cards.map((c) => (
          <div key={c.label} className={`dash-card ${c.cls}`}>
            <div className="dash-label">{c.label}</div>
            <div className="dash-value">{c.value}</div>
          </div>
        ))}
      </div>
    </>
  );
}
