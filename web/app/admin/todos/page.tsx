import { sql } from "@/lib/db";
import { addTodo, toggleTodo, deleteTodo } from "./actions";
import type { ReactNode } from "react";

export const dynamic = "force-dynamic";

type Todo = {
  id: number;
  title: string;
  assigned_to: string | null;
  completed: boolean;
};

const USERS: Record<string, { name: string; color: string }> = {
  eric6: { name: "Eric", color: "#2563eb" },
  andy: { name: "Andy", color: "#16a34a" },
};

// Render [text](url) ca linkuri; restul ca text.
function renderTitle(title: string): ReactNode[] {
  const parts: ReactNode[] = [];
  const re = /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g;
  let last = 0;
  let m: RegExpExecArray | null;
  let i = 0;
  while ((m = re.exec(title)) !== null) {
    if (m.index > last) parts.push(title.slice(last, m.index));
    parts.push(
      <a key={i++} href={m[2]} target="_blank" rel="noopener" style={{ color: "var(--accent)" }}>
        {m[1]}
      </a>
    );
    last = m.index + m[0].length;
  }
  if (last < title.length) parts.push(title.slice(last));
  return parts;
}

function Assignee({ user }: { user: string | null }) {
  const u = user ? USERS[user] : null;
  if (!u) return null;
  return (
    <span
      style={{
        display: "inline-flex",
        alignItems: "center",
        gap: 6,
        fontSize: 11,
        fontWeight: 700,
        color: "#fff",
        background: u.color,
        borderRadius: 999,
        padding: "2px 9px",
        flexShrink: 0,
      }}
    >
      {u.name}
    </span>
  );
}

function TodoRow({ t }: { t: Todo }) {
  return (
    <div style={{ display: "flex", alignItems: "center", gap: 11, padding: "8px 0", borderBottom: "1px solid var(--border)", opacity: t.completed ? 0.5 : 1 }}>
      <form action={toggleTodo} style={{ margin: 0, display: "flex" }}>
        <input type="hidden" name="id" value={t.id} />
        <button
          type="submit"
          aria-label={t.completed ? "Redeschide" : "Finalizează"}
          style={{
            width: 20,
            height: 20,
            borderRadius: 6,
            border: `1.5px solid ${t.completed ? "var(--success)" : "var(--border-strong)"}`,
            background: t.completed ? "var(--success)" : "transparent",
            color: "#fff",
            cursor: "pointer",
            fontSize: 12,
            lineHeight: 1,
            flexShrink: 0,
          }}
        >
          {t.completed ? "✓" : ""}
        </button>
      </form>
      <span style={{ flex: 1, fontSize: 14, textDecoration: t.completed ? "line-through" : "none" }}>{renderTitle(t.title)}</span>
      <Assignee user={t.assigned_to} />
      <form action={deleteTodo} style={{ margin: 0 }}>
        <input type="hidden" name="id" value={t.id} />
        <button type="submit" style={{ border: "none", background: "none", color: "var(--danger)", cursor: "pointer", fontSize: 13, fontWeight: 600 }}>
          ✕
        </button>
      </form>
    </div>
  );
}

export default async function TodosPage() {
  const all = (await sql`SELECT id, title, assigned_to, completed FROM todos ORDER BY created_at DESC`) as Todo[];
  const pending = all.filter((t) => !t.completed);
  const done = all.filter((t) => t.completed);

  return (
    <>
      <h1 className="wp-page-title">To-dos</h1>

      <div className="card crm-form">
        <div className="card-title">Adaugă to-do</div>
        <form action={addTodo}>
          <div style={{ display: "flex", gap: 8, alignItems: "flex-end", flexWrap: "wrap" }}>
            <div className="form-group" style={{ flex: 1, minWidth: 200, marginBottom: 0 }}>
              <label>Descriere</label>
              <input name="title" type="text" required />
            </div>
            <div className="form-group" style={{ flex: "0 0 140px", marginBottom: 0 }}>
              <label>Pentru</label>
              <select name="assigned_to" defaultValue="eric6">
                <option value="eric6">Eric</option>
                <option value="andy">Andy</option>
              </select>
            </div>
            <button type="submit" className="btn btn-primary">
              Adaugă
            </button>
          </div>
        </form>
      </div>

      <div className="card">
        <div className="card-title">De făcut ({pending.length})</div>
        {pending.length === 0 ? (
          <p style={{ color: "var(--text-muted)", fontSize: 13 }}>Niciun to-do. 🎉</p>
        ) : (
          pending.map((t) => <TodoRow key={t.id} t={t} />)
        )}
      </div>

      {done.length > 0 && (
        <div className="card">
          <div className="card-title">Finalizate ({done.length})</div>
          {done.map((t) => (
            <TodoRow key={t.id} t={t} />
          ))}
        </div>
      )}
    </>
  );
}
