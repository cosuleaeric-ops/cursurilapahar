import Link from "next/link";
import { sql } from "@/lib/db";

export const dynamic = "force-dynamic";

type EventRow = { id: number; title: string; starts_at: string | null };
type QuickLink = { url: string; icon?: string; label?: string };

const dFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: "Europe/Bucharest", day: "numeric", month: "long", year: "numeric" });
const cardTitle = (t: string) => t.replace(/\s+\/\/\s+.+$/u, "");

export default async function AdminHome() {
  const upcoming = (await sql`
    SELECT id, title, starts_at FROM events
    WHERE starts_at >= now() ORDER BY starts_at ASC LIMIT 4
  `) as EventRow[];

  const [qlRow] = (await sql`SELECT value FROM settings WHERE key = 'quick_links'`) as { value: unknown }[];
  const quickLinks: QuickLink[] = Array.isArray(qlRow?.value) ? (qlRow.value as QuickLink[]) : [];
  const canva = quickLinks.filter((q) => (q.url ?? "").includes("canva.com"));
  const general = quickLinks.filter((q) => !(q.url ?? "").includes("canva.com"));

  return (
    <>
      <h1 className="wp-page-title">Dashboard</h1>

      <div className="bc-home-grid">
        {/* To-dos */}
        <div className="bc-card">
          <div className="bc-card-head">
            <span className="bc-card-icon">✅</span>
            <span className="bc-card-title">To-dos</span>
          </div>
          <p className="bc-card-empty">Niciun to-do.</p>
        </div>

        {/* Cursuri */}
        <Link className="bc-card" href="/admin/cursuri">
          <div className="bc-card-head">
            <span className="bc-card-icon">📋</span>
            <span className="bc-card-title">Cursuri</span>
          </div>
          {upcoming.length === 0 ? (
            <p className="bc-card-empty">Niciun curs programat.</p>
          ) : (
            <ul className="bc-card-list">
              {upcoming.map((c) => (
                <li key={c.id}>
                  <span className="bc-li-dot" style={{ background: "#2563eb" }}></span>
                  <span>
                    {cardTitle(c.title)}
                    {c.starts_at && <span className="bc-li-meta"> · {dFmt.format(new Date(c.starts_at))}</span>}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </Link>

        {/* Mesaje */}
        <div className="bc-card">
          <div className="bc-card-head">
            <span className="bc-card-icon">💬</span>
            <span className="bc-card-title">Mesaje</span>
          </div>
          <p className="bc-card-empty">Toate mesajele sunt citite.</p>
        </div>
      </div>

      {quickLinks.length > 0 && (
        <div className="ql-grid">
          {general.length > 0 && (
            <div className="dash-section" style={{ margin: 0 }}>
              <div className="dash-section-title">
                <span>Linkuri utile</span>
              </div>
              <div style={{ display: "flex", flexWrap: "wrap", gap: 10 }}>
                {general.map((q, i) => (
                  <a key={i} href={q.url} target="_blank" rel="noopener" className="ql-btn">
                    <span style={{ fontSize: 17 }}>{q.icon ?? "🔗"}</span>
                    {q.label}
                  </a>
                ))}
              </div>
            </div>
          )}
          {canva.length > 0 && (
            <div className="dash-section" style={{ margin: 0 }}>
              <div className="dash-section-title">
                <span>Canva</span>
              </div>
              <div style={{ display: "flex", flexWrap: "wrap", gap: 10 }}>
                {canva.map((q, i) => (
                  <a key={i} href={q.url} target="_blank" rel="noopener" className="ql-btn">
                    <span style={{ fontSize: 17 }}>{q.icon ?? "🔗"}</span>
                    {q.label}
                  </a>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </>
  );
}
