"use client";

import { useState } from "react";
import { saveRecurring, saveRecurringSystemTitle, deleteRecurring, addRecurring } from "./actions";

export type RecTask = {
  id: number;
  type: string;
  system_key: string | null;
  assigned_to: string | null;
  title: string;
  schedule: string | null;
  description: string | null;
  days: number[];
};

const REC_CSS = `
.rec-card { position:relative; border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:14px; background:var(--bg-warm); }
.rec-top { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; padding-right:80px; }
.rec-top .rec-title { flex:1; min-width:220px; }
.rec-assignee { font-weight:600; border:none; border-radius:999px; padding:6px 14px; cursor:pointer; font-size:13px; }
.rec-assignee.a-eric6 { background:#eff6ff; color:#2563eb; }
.rec-assignee.a-andy  { background:#f0fdf4; color:#16a34a; }
.rec-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); margin-bottom:8px; }
.rec-days { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:14px; }
.rec-day-sel { padding:7px 10px; }
.rec-add-day { background:none; border:1px dashed var(--border-strong); border-radius:8px; padding:7px 12px; font-size:13px; font-weight:600; color:var(--accent); cursor:pointer; }
.rec-del { position:absolute; top:14px; right:14px; margin:0; }
.rec-auto { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); background:#fff; border:1px solid var(--border); border-radius:6px; padding:3px 8px; flex-shrink:0; }
.rec-pill { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 13px; font-size:13px; font-weight:600; white-space:nowrap; flex-shrink:0; }
.rec-pill.a-eric6 { background:#eff6ff; color:#2563eb; }
.rec-pill.a-andy  { background:#f0fdf4; color:#16a34a; }
.rec-pill .dot { width:8px; height:8px; border-radius:50%; background:currentColor; }
.rec-sys-badge { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#92400e; background:#fef3c7; border-radius:6px; padding:3px 8px; white-space:nowrap; }
.rec-sys-desc { font-size:12px; color:var(--text-muted); }
.rec-view-top { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px; }
.rec-view-title { font-size:15px; font-weight:600; color:var(--text); }
.rec-card--auto .rec-view-top { flex-wrap:nowrap; }
.rec-card--auto .rec-view-title { flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rec-edit-btn { background:none; border:none; cursor:pointer; color:var(--text-muted); padding:5px; border-radius:7px; display:inline-flex; flex-shrink:0; transition:color .12s, background .12s; }
.rec-edit-btn:hover { color:var(--accent); background:var(--accent-soft); }
.rec-edit-btn svg { width:17px; height:17px; display:block; }
.rec-view-meta { font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.rec-view-days { font-weight:600; color:var(--text); }
.rec-edit-actions { display:flex; gap:8px; }
.rec-title-input { width:100%; padding:8px 10px; border:1px solid var(--border); border-radius:8px; font-size:14px; background:#fff; box-sizing:border-box; }
`;

const USER_LABEL = (u: string) => (u === "eric6" ? "Eric" : u.charAt(0).toUpperCase() + u.slice(1));
const plainTitle = (t: string) => t.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/gu, "$1");

function EditIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M12 20h9" />
      <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
    </svg>
  );
}

function MonthlyCard({ t, users }: { t: RecTask; users: string[] }) {
  const [editing, setEditing] = useState(false);
  const [days, setDays] = useState<number[]>(t.days.length ? t.days : [0]);
  const [assignee, setAssignee] = useState(t.assigned_to ?? "eric6");
  const realDays = [...t.days].sort((a, b) => a - b);

  return (
    <div className="rec-card">
      {!editing ? (
        <div className="rec-view">
          <div className="rec-view-top">
            <span className="rec-view-title">{t.title}</span>
            <span className={`rec-pill a-${t.assigned_to ?? "eric6"}`}>
              <span className="dot"></span>
              {USER_LABEL(t.assigned_to ?? "eric6")}
            </span>
            <button type="button" className="rec-edit-btn" style={{ marginLeft: "auto" }} title="Editează" onClick={() => setEditing(true)}>
              <EditIcon />
            </button>
          </div>
          <div className="rec-view-meta">
            Lunar ·{" "}
            {realDays.length ? (
              <>
                zilele <span className="rec-view-days">{realDays.join(", ")}</span>
              </>
            ) : (
              <em>nicio zi aleasă</em>
            )}
          </div>
        </div>
      ) : (
        <div className="rec-edit">
          <form action={saveRecurring}>
            <input type="hidden" name="id" value={t.id} />
            <div className="rec-top">
              <input type="text" name="title" defaultValue={t.title} className="rec-title rec-title-input" required />
              <select
                name="assigned_to"
                className={`rec-assignee a-${assignee}`}
                value={assignee}
                onChange={(e) => setAssignee(e.target.value)}
              >
                {users.map((u) => (
                  <option key={u} value={u}>
                    {USER_LABEL(u)}
                  </option>
                ))}
              </select>
            </div>
            <div className="rec-label">Zile din lună</div>
            <div className="rec-days">
              {days.map((sel, i) => (
                <select
                  key={i}
                  name="days"
                  className="rec-day-sel"
                  value={sel || ""}
                  onChange={(e) => setDays(days.map((d, j) => (j === i ? Number(e.target.value) : d)))}
                >
                  <option value="">— zi —</option>
                  {Array.from({ length: 31 }, (_, d) => d + 1).map((d) => (
                    <option key={d} value={d}>
                      {d}
                    </option>
                  ))}
                </select>
              ))}
              <button type="button" className="rec-add-day" onClick={() => setDays([...days, 0])}>
                + zi
              </button>
            </div>
            <div className="rec-edit-actions">
              <button type="submit" className="btn btn-primary btn-sm">
                Salvează
              </button>
              <button type="button" className="btn btn-secondary btn-sm" onClick={() => setEditing(false)}>
                Anulează
              </button>
            </div>
          </form>
          <form
            action={deleteRecurring}
            style={{ marginTop: 10 }}
            onSubmit={(e) => {
              if (!confirm("Ștergi taskul recurent?")) e.preventDefault();
            }}
          >
            <input type="hidden" name="id" value={t.id} />
            <button type="submit" className="btn btn-danger btn-sm">
              Șterge taskul
            </button>
          </form>
        </div>
      )}
    </div>
  );
}

function SystemCard({ t }: { t: RecTask }) {
  const [editing, setEditing] = useState(false);
  return (
    <div className="rec-card rec-card--auto">
      {!editing ? (
        <div className="rec-view">
          <div className="rec-view-top">
            <span className="rec-view-title">{plainTitle(t.title)}</span>
            <span className="rec-auto">⚙︎ automat</span>
            <button type="button" className="rec-edit-btn" title="Editează numele" onClick={() => setEditing(true)}>
              <EditIcon />
            </button>
          </div>
          <div className="rec-view-meta">
            <span className={`rec-pill a-${t.assigned_to ?? "andy"}`}>
              <span className="dot"></span>
              {USER_LABEL(t.assigned_to ?? "andy")}
            </span>
            <span className="rec-sys-badge">{t.schedule ?? "auto"}</span>
            <span className="rec-sys-desc">{t.description ?? ""}</span>
          </div>
        </div>
      ) : (
        <div className="rec-edit">
          <form action={saveRecurringSystemTitle}>
            <input type="hidden" name="id" value={t.id} />
            <div className="rec-label">Nume task</div>
            <input type="text" name="title" defaultValue={t.title} className="rec-title-input" style={{ marginBottom: 14 }} />
            <div className="rec-edit-actions">
              <button type="submit" className="btn btn-primary btn-sm">
                Salvează
              </button>
              <button type="button" className="btn btn-secondary btn-sm" onClick={() => setEditing(false)}>
                Anulează
              </button>
            </div>
          </form>
        </div>
      )}
    </div>
  );
}

export default function RecurringEditor({ tasks, users, notice }: { tasks: RecTask[]; users: string[]; notice?: string }) {
  return (
    <div className="card" id="rec">
      <style>{REC_CSS}</style>
      <div className="card-title">🔁 Taskuri recurente</div>
      {notice === "ok" && <div className="notice notice-success" style={{ marginBottom: 14 }}>Salvat ✓</div>}
      <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 18 }}>
        Apar automat în To-dos la persoana aleasă. Cele lunare au zilele alese de tine; cele marcate „automat" au
        programare fixă (poți schimba doar numele).
      </p>

      {tasks.map((t) => (t.type === "monthly" ? <MonthlyCard key={t.id} t={t} users={users} /> : <SystemCard key={t.id} t={t} />))}

      <form action={addRecurring} style={{ marginTop: 4 }}>
        <button type="submit" className="btn btn-secondary btn-sm">
          + Adaugă task recurent
        </button>
      </form>
    </div>
  );
}
