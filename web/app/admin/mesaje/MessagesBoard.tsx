"use client";

import { useRef, useState } from "react";
import type { Cat, Comment, Msg } from "@/lib/messages";
import { addComment, deleteComment, deleteMessage, setEvaluation, setContacted, toggleRead } from "./actions";

// Port din admin/partials/messages-tab.php + clp_render_message_card()
// (lib/messages.php) + admin/assets/js/admin-mesaje.js.


const SUSTINE_TOOLTIPS: Record<string, string> = {
  Name: "Nume și prenume",
  Email: "Email",
  Phone: "Număr de telefon",
  Social: "Link profil social media",
  "Course name": "Nume curs susținut",
  "Course desc": "Descrie cursul susținut",
  Motivation: "De ce îți dorești să susții acest curs?",
  Experience: "Ce experiențe sau competențe te califică?",
  "Previous presentations": "Ai mai susținut astfel de prezentări?",
  City: "În ce oraș ai vrea să susții cursul?",
  Other: "Mai e ceva ce vrei să ne transmiți?",
};

function CopyBtn({ value }: { value: string }) {
  const [copied, setCopied] = useState(false);
  return (
    <button
      type="button"
      className={`msg-copy-btn${copied ? " copied" : ""}`}
      title="Copiază"
      onClick={(e) => {
        e.stopPropagation();
        // PHP (copyField): iconița devine bifă și butonul primește .copied 2 secunde.
        void navigator.clipboard.writeText(value).then(() => {
          setCopied(true);
          setTimeout(() => setCopied(false), 2000);
        });
      }}
    >
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
        {copied ? (
          <polyline points="20 6 9 17 4 12" />
        ) : (
          <>
            <rect x="9" y="9" width="13" height="13" rx="2" />
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
          </>
        )}
      </svg>
    </button>
  );
}

function Card({ m, isOwner, hidden }: { m: Msg; isOwner: boolean; hidden?: boolean }) {
  const [open, setOpen] = useState(false);
  const [commentOpen, setCommentOpen] = useState(false);
  const [text, setText] = useState("");
  const sustine = m.category === "sustine";

  const cls = ["msg-card"];
  if (!sustine && m.read) cls.push("is-read");
  if (sustine && m.evaluation) cls.push(`eval-${m.evaluation}`);
  if (m.contacted) cls.push("is-contacted");

  const call = (fn: (fd: FormData) => Promise<void>, extra: Record<string, string> = {}) => {
    const fd = new FormData();
    fd.set("id", String(m.id));
    for (const [k, v] of Object.entries(extra)) fd.set(k, v);
    void fn(fd);
  };

  return (
    <div
      className={cls.join(" ")}
      data-msg-id={m.id}
      style={hidden ? { display: "none" } : undefined}
      onClick={() => setOpen(!open)}
    >
      <div className="msg-card-head">
        <span className="msg-card-name">
          {m.name}
          {m.course_first && <span className="msg-card-course"> — {m.course_first}</span>}
        </span>
        <span className="msg-card-date">{m.date}</span>
      </div>
      <div className="msg-detail" style={{ display: open ? "block" : "none" }}>
        {m.fields.map(([lbl, val]) => (
          <div className="msg-detail-row" key={lbl}>
            <span className="msg-detail-lbl">
              {lbl}
              {sustine && SUSTINE_TOOLTIPS[lbl] && (
                <span className="msg-info" data-tooltip={SUSTINE_TOOLTIPS[lbl]}>
                  i
                </span>
              )}
            </span>
            <span className="msg-detail-val">
              {val}
              {val && <CopyBtn value={val} />}
            </span>
          </div>
        ))}

        <div className="msg-detail-actions">
          {sustine ? (
            <>
              {(["nope", "meh", "top"] as const).map((e) => (
                <button
                  key={e}
                  type="button"
                  className={`msg-eval-btn${m.evaluation === e ? " is-active" : ""}`}
                  data-eval={e}
                  onClick={(ev) => {
                    ev.stopPropagation();
                    call(setEvaluation, { evaluation: m.evaluation === e ? "" : e });
                  }}
                >
                  {e === "nope" ? "Nope" : e === "meh" ? "Meh" : "Top"}
                </button>
              ))}
              <button
                type="button"
                className="msg-comment-btn"
                onClick={(ev) => {
                  ev.stopPropagation();
                  setCommentOpen(!commentOpen);
                }}
              >
                💬 Comentariu
              </button>
              <button
                type="button"
                className={`msg-contact-btn${m.contacted ? " is-active" : ""}`}
                onClick={(ev) => {
                  ev.stopPropagation();
                  call(setContacted, { contacted: m.contacted ? "" : "1" });
                }}
              >
                {m.contacted ? "✓ Contactat" : "Contactat"}
              </button>
            </>
          ) : (
            <>
              <button
                type="button"
                className={`msg-read-btn${m.read ? " is-active" : ""}`}
                onClick={(ev) => {
                  ev.stopPropagation();
                  call(toggleRead);
                  // PHP (markRead): la marcarea ca citit cardul se pliază singur.
                  if (!m.read) setOpen(false);
                }}
              >
                {m.read ? "✓ Citit" : "Citit"}
              </button>
              <button
                type="button"
                className="msg-delete-btn"
                onClick={(ev) => {
                  ev.stopPropagation();
                  if (!confirm("Sigur vrei să ștergi acest mesaj?")) return;
                  call(deleteMessage);
                }}
              >
                Șterge
              </button>
            </>
          )}
        </div>

        {sustine && (
          <div className="msg-comments">
            <div className="msg-comments-title">Comentarii</div>
            <div className="msg-comments-list">
              {m.comments.map((c, i) => (
                <div className="msg-comment-item" key={i}>
                  <span className="msg-comment-when">
                    {c.at}
                    {c.by ? ` · ${c.by}` : ""}
                  </span>
                  {c.text}
                  {isOwner && (
                    <button
                      type="button"
                      className="msg-comment-del"
                      title="Șterge comentariu"
                      onClick={(ev) => {
                        ev.stopPropagation();
                        if (!confirm("Ștergi comentariul?")) return;
                        call(deleteComment, { index: String(i) });
                      }}
                    >
                      ×
                    </button>
                  )}
                </div>
              ))}
            </div>
            {/* PHP deschide formularul cu display:flex — textarea și „Adaugă" pe același rând. */}
            <div className="msg-comment-form" style={{ display: commentOpen ? "flex" : "none" }}>
              <textarea rows={2} value={text} onClick={(e) => e.stopPropagation()} onChange={(e) => setText(e.target.value)} />
              <button
                type="button"
                onClick={(ev) => {
                  ev.stopPropagation();
                  if (!text.trim()) return;
                  call(addComment, { text });
                  setText("");
                  setCommentOpen(false);
                }}
              >
                Adaugă
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default function MessagesBoard({
  cats,
  byCat,
  counts,
  isOwner,
}: {
  cats: Cat[];
  byCat: Record<string, Msg[]>;
  counts: Record<string, number>;
  isOwner: boolean;
}) {
  const [tab, setTab] = useState("sustine");
  const [evalFilter, setEvalFilter] = useState("all");
  const [hiddenIds, setHiddenIds] = useState<Record<number, boolean>>({});

  // PHP împarte cardurile în „De evaluat" / „Evaluați" o singură dată, când randează
  // pagina; o evaluare dată acum lasă cardul unde e, până la reîncărcare.
  const section = useRef<Record<number, boolean>>({});
  const wasEvaluated = (m: Msg) => {
    if (!(m.id in section.current)) section.current[m.id] = !!m.evaluation;
    return section.current[m.id];
  };

  const list = byCat[tab] ?? [];
  const pending = list.filter((m) => !wasEvaluated(m));
  const evaluated = list.filter((m) => wasEvaluated(m));

  // PHP (filterEval) fixează vizibilitatea cardurilor la click pe filtru; ratingul
  // sau „Contactat" schimbate după aceea nu mai re-filtrează lista.
  const applyFilter = (f: string) => {
    setEvalFilter(f);
    const h: Record<number, boolean> = {};
    for (const m of evaluated) {
      const show = f === "all" ? true : f === "contactat" ? m.contacted : m.evaluation === f && !m.contacted;
      if (!show) h[m.id] = true;
    }
    setHiddenIds(h);
  };

  return (
    <>
      <div className="msg-tabs">
        {cats.map((c) => (
          <button key={c.key} className={`msg-tab${tab === c.key ? " active" : ""}`} onClick={() => setTab(c.key)}>
            {c.icon} {c.label}
            <span className="msg-count" style={counts[c.key] ? undefined : { display: "none" }}>
              {counts[c.key] ?? 0}
            </span>
          </button>
        ))}
      </div>

      <div className="msg-panel active">
        {list.length === 0 ? (
          <div className="card">
            <p className="msg-empty">Niciun mesaj în această categorie.</p>
          </div>
        ) : tab === "sustine" ? (
          <>
            <div className="msg-section">
              <h3 className="msg-section-title">🤔 De evaluat ({pending.length})</h3>
              {pending.length === 0 ? (
                <p className="msg-empty">Nimic de evaluat.</p>
              ) : (
                <div className="msg-cards">
                  {pending.map((m) => (
                    <Card key={m.id} m={m} isOwner={isOwner} />
                  ))}
                </div>
              )}
            </div>
            <div className="msg-section">
              <h3 className="msg-section-title">✅ Evaluați ({evaluated.length})</h3>
              {evaluated.length === 0 ? (
                <p className="msg-empty">Niciun candidat evaluat încă.</p>
              ) : (
                <>
                  <div className="msg-eval-filter">
                    {[
                      ["all", "Toți"],
                      ["nope", "⛔ Nope"],
                      ["meh", "🤔 Meh"],
                      ["top", "✅ Top"],
                      ["contactat", "📋 Contactați"],
                    ].map(([k, lbl]) => (
                      <button
                        key={k}
                        type="button"
                        data-filter={k}
                        className={`msg-eval-filter-btn${evalFilter === k ? " active" : ""}`}
                        onClick={() => applyFilter(k)}
                      >
                        {lbl}
                      </button>
                    ))}
                  </div>
                  <div className="msg-cards">
                    {evaluated.map((m) => (
                      <Card key={m.id} m={m} isOwner={isOwner} hidden={hiddenIds[m.id]} />
                    ))}
                  </div>
                </>
              )}
            </div>
          </>
        ) : (
          <div className="msg-cards">
            {list.map((m) => (
              <Card key={m.id} m={m} isOwner={isOwner} />
            ))}
          </div>
        )}
      </div>
    </>
  );
}
