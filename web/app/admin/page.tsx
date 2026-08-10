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
export default async function AdminHome() {
  const session = await getSession();

  // Clientul Neon merge pe HTTP, deci fiecare interogare e un drum dus-întors.
  // Rulate în serie însumau latențele; în paralel costă cât cea mai lentă.
  const [upcoming, todos, msgData, settingRows, calRows] = await Promise.all([
    // Dashboard-ul PHP citește doar cardurile din courses.json (clp_load_courses_for_admin),
    // nu și cursurile venite din statistici → în Neon acelea sunt rândurile fără legacy_card_id.
    sql`
      SELECT id, title, starts_at, date_display FROM events
      WHERE legacy_card_id IS NOT NULL
        AND to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD')
            >= to_char(now() AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD')
      ORDER BY starts_at ASC LIMIT 4
    `,
    // PHP ia primele 5 din todos.json (ordine de inserare) = cele mai VECHI 5 necompletate.
    sql`
      SELECT id, title FROM todos
      WHERE completed = false AND assigned_to = ${session?.username ?? ""}
      ORDER BY id ASC LIMIT 5
    `,
    // Aceleași numere ca tab_counts din Mesaje: Speakeri = neevaluați (fără cei
    // care au deja fișă de speaker), restul = necitite.
    loadGroupedMessages(),
    // Cele trei setări într-o singură interogare, nu trei.
    sql`
      SELECT key, value FROM settings
      WHERE key = ANY(${["quick_links", "templates", "instagram_posts"]})
    `,
    // Calendarul de pe dashboard: doar cardurile din courses.json (legacy_card_id), grupate pe zi (ora București).
    sql`
      SELECT title, to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS d
      FROM events WHERE starts_at IS NOT NULL AND legacy_card_id IS NOT NULL
    `,
  ]) as [EventRow[], TodoRow[], Awaited<ReturnType<typeof loadGroupedMessages>>, { key: string; value: unknown }[], { title: string; d: string }[]];
  const { tabCounts } = msgData;

  const todoDot = session?.username === "andy" ? "#16a34a" : "#2563eb";
  const msgLines = CATS.filter((c) => (tabCounts[c.key] ?? 0) > 0).map((c) => ({ label: c.label, n: tabCounts[c.key] }));

  const setting = (k: string) => settingRows.find((r) => r.key === k)?.value;
  const qlValue = setting("quick_links");
  const tplValue = setting("templates");
  const igValue = setting("instagram_posts");
  const coursesByDay: Record<string, string[]> = {};
  for (const r of calRows) (coursesByDay[r.d] ??= []).push(r.title);
  const igPosts = igValue && typeof igValue === "object" ? (igValue as Record<string, string[]>) : {};
  const todayStr = ymdFmt.format(new Date());
  const templates = Array.isArray(tplValue) ? (tplValue as { icon?: string; label?: string; text?: string }[]) : [];
  const quickLinks: QuickLink[] = Array.isArray(qlValue) ? (qlValue as QuickLink[]) : [];
  const isCanva = (q: QuickLink) => (q.url ?? "").includes("canva.com");
  const isDrive = (q: QuickLink) => /(drive|docs)\.google\.com/.test(q.url ?? "");
  const canva = quickLinks.filter(isCanva);
  const drive = quickLinks.filter((q) => !isCanva(q) && isDrive(q));
  const general = quickLinks.filter((q) => !isCanva(q) && !isDrive(q));

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
                const disp = c.date_display ?? "";
                return (
                  <li key={c.id}>
                    <span className="bc-li-dot" style={{ background: "#2563eb" }}></span>
                    <span>
                      {c.title}
                      {/* dashboard-tab.php:53 randează mereu span-ul cu „ · ", și când data lipsește */}
                      <span className="bc-li-meta"> · {disp}</span>
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

      {/* Panourile stau într-o singură grilă, ca să umple lățimea. Drive e
          subsecțiune în „Linkuri utile", nu panou separat. */}
      <div className="ql-grid">
        {(general.length > 0 || drive.length > 0) && (
          <div className="dash-section" style={{ margin: 0 }}>
            <div className="dash-section-title">
              <span>Linkuri utile</span>
            </div>
            {general.length > 0 && (
              <div className="ql-btns">
                {general.map((q, i) => (
                  <a key={i} href={q.url} target="_blank" rel="noopener" className="ql-btn">
                    <span style={{ fontSize: 17 }}>{q.icon ?? "🔗"}</span>
                    {q.label}
                  </a>
                ))}
              </div>
            )}
            {drive.length > 0 && (
              <>
                <div className="ql-subtitle">Drive</div>
                <div className="ql-btns">
                  {drive.map((q, i) => (
                    <a key={i} href={q.url} target="_blank" rel="noopener" className="ql-btn">
                      <span style={{ fontSize: 17 }}>{q.icon ?? "🔗"}</span>
                      {q.label}
                    </a>
                  ))}
                </div>
              </>
            )}
          </div>
        )}

        {canva.length > 0 && (
          <div className="dash-section" style={{ margin: 0 }}>
            <div className="dash-section-title">
              <span>Canva</span>
            </div>
            <div className="ql-btns">
              {canva.map((q, i) => (
                <a key={i} href={q.url} target="_blank" rel="noopener" className="ql-btn">
                  <span style={{ fontSize: 17 }}>{q.icon ?? "🔗"}</span>
                  {q.label}
                </a>
              ))}
            </div>
          </div>
        )}

        <div className="dash-section" style={{ margin: 0 }}>
          <div className="dash-section-title">
            <span>Templates</span>
            <Link href="/admin/templates">Editează →</Link>
          </div>
          {templates.length === 0 ? (
            <p className="bc-card-empty">
              Niciun template încă. <Link href="/admin/templates" style={{ color: "var(--accent)" }}>Adaugă unul</Link>.
            </p>
          ) : (
            <div className="ql-btns">
              {templates.map((t, i) => (
                <CopyButton key={i} className="ql-btn" text={t.text ?? ""} label={t.label ?? ""} icon={t.icon ?? "📋"} />
              ))}
            </div>
          )}
        </div>

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
