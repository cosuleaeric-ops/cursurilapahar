"use client";

import { useState, type ReactNode } from "react";
import { addTodo, toggleTodo, deleteTodo } from "./actions";

// Port din admin/todos/index.php — titlu + „+", formular ascuns cu pastile de
// atribuire, lista simplă și blocul <details> „N completate" grupat pe zi.

export type Todo = { id: number; title: string; assigned_to: string | null; completed: boolean };
export type User = { username: string; name: string; color: string; initial: string; avatar: string };
export type DoneGroup = { label: string; items: Todo[] };

/** Aceleași linkuri ca clp_todo_render_title(). */
function renderTitle(title: string): ReactNode[] {
  const parts: ReactNode[] = [];
  const re = /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g;
  let last = 0;
  let m: RegExpExecArray | null;
  let i = 0;
  while ((m = re.exec(title)) !== null) {
    if (m.index > last) parts.push(title.slice(last, m.index));
    parts.push(
      <a key={i++} href={m[2]} target="_blank" rel="noopener" className="todo-link">
        {m[1]}
      </a>,
    );
    last = m.index + m[0].length;
  }
  if (last < title.length) parts.push(title.slice(last));
  return parts;
}

function Assign({ u }: { u: User }) {
  return (
    <span className={`todo-assign todo-assign--${u.username}`}>
      <span className="todo-av" style={{ background: u.color }}>
        {u.initial}
        {u.avatar && (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={u.avatar} alt="" style={{ objectPosition: u.username === "eric6" ? "top" : "center" }} />
        )}
      </span>
      <span className="todo-assign-name">{u.name}</span>
    </span>
  );
}

function Item({ t, users, done }: { t: Todo; users: Record<string, User>; done?: boolean }) {
  const u = t.assigned_to ? users[t.assigned_to] : undefined;
  return (
    <li className={`todo-item a-${t.assigned_to ?? ""}`}>
      <form action={toggleTodo} className="todo-check">
        <input type="hidden" name="id" value={t.id} />
        <input
          type="checkbox"
          defaultChecked={done}
          title={done ? "Marchează incomplet" : "Marchează completat"}
          onChange={(e) => e.currentTarget.form?.requestSubmit()}
        />
      </form>
      <span className={`todo-text${done ? " done" : ""}`}>{renderTitle(t.title)}</span>
      {u && <Assign u={u} />}
      <form
        action={deleteTodo}
        onSubmit={(e) => {
          if (!confirm("Sigur ștergi?")) e.preventDefault();
        }}
      >
        <input type="hidden" name="id" value={t.id} />
        <button type="submit" className="todo-del" title="Șterge">
          ×
        </button>
      </form>
    </li>
  );
}

export default function TodosList({
  pending,
  doneGroups,
  doneCount,
  users,
}: {
  pending: Todo[];
  doneGroups: DoneGroup[];
  doneCount: number;
  users: User[];
}) {
  const [open, setOpen] = useState(false);
  const byName = Object.fromEntries(users.map((u) => [u.username, u]));

  return (
    <>
      <div className="todos-head">
        <h1 className="wp-page-title">To-dos</h1>
        <button
          type="button"
          className="todo-add-icon"
          onClick={() => setOpen(!open)}
          title="Adaugă o sarcină"
          aria-label="Adaugă o sarcină"
        >
          +
        </button>
      </div>

      <form action={addTodo} className={`todo-add-form${open ? " open" : ""}`}>
        <input type="text" name="title" className="todo-add-input" required autoFocus={open} />
        <div className="todo-add-assign">
          <span className="todo-add-assign-label">Atribuie:</span>
          {users.map((u) => (
            <label className="todo-assign-pick" key={u.username}>
              <input type="radio" name="assigned_to" value={u.username} required />
              <Assign u={u} />
            </label>
          ))}
        </div>
        <div className="todo-add-actions">
          <button type="submit" className="todo-add-submit">
            Adaugă
          </button>
          <button type="button" className="todo-add-cancel" onClick={() => setOpen(false)}>
            Anulează
          </button>
        </div>
      </form>

      <div className="todos-single">
        <ul className="todo-items">
          {pending.map((t) => (
            <Item key={t.id} t={t} users={byName} />
          ))}
          {pending.length === 0 && doneCount === 0 && <li className="todo-empty">Nicio sarcină.</li>}
        </ul>

        {doneCount > 0 && (
          <details className="todo-completed">
            <summary>
              <span className="todo-completed-caret">▸</span> {doneCount} completat{doneCount === 1 ? "" : "e"}
            </summary>
            {doneGroups.map((g) => (
              <div key={g.label}>
                <div className="todo-done-day">{g.label}</div>
                <ul className="todo-items todo-completed-items">
                  {g.items.map((t) => (
                    <Item key={t.id} t={t} users={byName} done />
                  ))}
                </ul>
              </div>
            ))}
          </details>
        )}
      </div>
    </>
  );
}
