import Link from "next/link";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { CopyButton } from "./templates/TemplatesEditor";
import MiniCal from "./MiniCal";
import { CATS, loadGroupedMessages } from "@/lib/messages";

export const dynamic = "force-dynamic";

type EventRow = { id: number; title: string; starts_at: string | null; date_display: string | null };
type TodoRow = { id: number; title: string };
type QuickLink = { url: string; icon?: string; label?: string };
const todoPlain = (t: string) => t.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, "$1");

// dashboard-tab.php:53 afișează textul STOCAT în `date_display`; funcția de mai jos e doar
// fallback-ul, cu aceeași regulă ca clp_date_display_from_raw() (luna cu majusculă).
const RO_MONTHS = ["", "Ianuarie", "Februarie", "Martie", "Aprilie", "Mai", "Iunie", "Iulie", "August", "Septembrie", "Octombrie", "Noiembrie", "Decembrie"];
const ymdFmt = new Intl.DateTimeFormat("en-CA", { timeZone: "Europe/Bucharest" });
const dateDisplay = (iso: string) => {
  const [y, m, d] = ymdFmt.format(new Date(iso)).split("-");
  return `${Number(d)} ${RO_MONTHS[Number(m)]} ${y}`;
};

export default async function AdminHome() {
  const session = await getSession();
  // Dashboard-ul PHP citește doar cardurile din courses.json (clp_load_courses_for_admin),
  // nu și cursurile venite din statistici → în Neon acelea sunt rândurile fără legacy_card_id.
  const upcoming = (await sql`
    SELECT id, title, starts_at, date_display FROM events
    WHERE legacy_card_id IS NOT NULL
      AND to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD')
          >= to_char(now() AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD')
    ORDER BY starts_at ASC LIMIT 4
  `) as EventRow[];

  // PHP ia primele 5 din todos.json (ordine de inserare) = cele mai VECHI 5 necompletate.
  const todos = (await sql`
    SELECT id, title FROM todos
    WHERE completed = false AND assigned_to = ${session?.username ?? ""}
    ORDER BY id ASC LIMIT 5
  `) as TodoRow[];
  const todoDot = session?.username === "andy" ? "#16a34a" : "#2563eb";

  // Aceleași numere ca tab_counts din Mesaje: Speakeri = neevaluați (fără cei
  // care au deja fișă de speaker), restul = necitite.
  const { tabCounts } = await loadGroupedMessages();
  const msgLines = CATS.filter((c) => (tabCounts[c.key] ?? 0) > 0).map((c) => ({ label: c.label, n: tabCounts[c.key] }));

  const [qlRow] = (await sql`SELECT value FROM settings WHERE key = 'quick_links'`) as { value: unknown }[];
  const [tplRow] = (await sql`SELECT value FROM settings WHERE key = 'templates'`) as { value: unknown }[];
  const [igRow] = (await sql`SELECT value FROM settings WHERE key = 'instagram_posts'`) as { value: unknown }[];

  // Calendarul de pe dashboard: doar cardurile din courses.json (legacy_card_id), grupate pe zi (ora București).
  const calRows = (await sql`
    SELECT title, to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS d
    FROM events WHERE starts_at IS NOT NULL AND legacy_card_id IS NOT NULL
  `) as { title: string; d: string }[];
  const coursesByDay: Record<string, string[]> = {};
  for (const r of calRows) (coursesByDay[r.d] ??= []).push(r.title);
  const igPosts =
    igRow?.value && typeof igRow.value === "object" ? (igRow.value as Record<string, string[]>) : {};
  const todayStr = ymdFmt.format(new Date());
  const templates = Array.isArray(tplRow?.value) ? (tplRow.value as { icon?: string; label?: string; text?: string }[]) : [];
  const quickLinks: QuickLink[] = Array.isArray(qlRow?.value) ? (qlRow.value as QuickLink[]) : [];
  const canva = quickLinks.filter((q) => (q.url ?? "").includes("canva.com"));
  const general = quickLinks.filter((q) => !(q.url ?? "").includes("canva.com"));

  return (
    <>
      <h1 className="wp-page-title">Dashboard</h1>

      <div className="bc-home-grid">
        {/* To-dos */}
        <Link className="bc-card" href="/admin/todos">
          <div className="bc-card-head">
            <span className="bc-card-icon">✅</span>
            <span className="bc-card-title">To-dos</span>
          </div>
          {todos.length > 0 && (
            <ul className="bc-card-list">
              {todos.map((t) => (
                <li key={t.id}>
                  <span className="bc-li-dot" style={{ background: todoDot }}></span>
                  <span>{todoPlain(t.title)}</span>
                </li>
              ))}
            </ul>
          )}
        </Link>

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
              {upcoming.map((c) => {
                const disp = c.date_display ?? (c.starts_at ? dateDisplay(c.starts_at) : "");
                return (
                  <li key={c.id}>
                    <span className="bc-li-dot" style={{ background: "#2563eb" }}></span>
                    <span>
                      {c.title}
                      {disp && <span className="bc-li-meta"> · {disp}</span>}
                    </span>
                  </li>
                );
              })}
            </ul>
          )}
        </Link>

        {/* Mesaje */}
        <Link className="bc-card" href="/admin/mesaje">
          <div className="bc-card-head">
            <span className="bc-card-icon">💬</span>
            <span className="bc-card-title">Mesaje</span>
          </div>
          {msgLines.length === 0 ? (
            <p className="bc-card-empty">Toate mesajele sunt citite.</p>
          ) : (
            <ul className="bc-card-list">
              {msgLines.slice(0, 4).map((m) => (
                <li key={m.label}>
                  <span className="bc-li-dot" style={{ background: "#e8a317" }}></span>
                  <span>
                    {m.label}
                    <span className="bc-li-meta"> · {m.n} noi</span>
                  </span>
                </li>
              ))}
            </ul>
          )}
        </Link>
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

      <div className="dash-section" style={{ marginBottom: 24 }}>
        <div className="dash-section-title">
          <span>Templates</span>
          <Link href="/admin/templates">Editează →</Link>
        </div>
        {templates.length === 0 ? (
          <p className="bc-card-empty">
            Niciun template încă. <Link href="/admin/templates" style={{ color: "var(--accent)" }}>Adaugă unul</Link>.
          </p>
        ) : (
          <div style={{ display: "flex", flexWrap: "wrap", gap: 10 }}>
            {templates.map((t, i) => (
              <CopyButton key={i} className="ql-btn" text={t.text ?? ""} label={t.label ?? ""} icon={t.icon ?? "📋"} />
            ))}
          </div>
        )}
      </div>

      <MiniCal
        today={todayStr}
        coursesByDay={coursesByDay}
        igPosts={igPosts}
        igPostTypes={{ postare_cursuri: { label: "POSTARE CURSURI" } }}
      />
    </>
  );
}
