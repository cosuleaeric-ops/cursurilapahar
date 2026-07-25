"use client";

import { useState } from "react";
import { SPEAKER_STATUSES, STATUS_COLOR } from "./statuses";
import { deleteSpeaker, saveSpeaker, saveTopics, setStatus, unmarkContacted } from "./actions";

// Port din admin/partials/speakeri-tab.php + admin/assets/js/admin-speakeri.js.

export type Speaker = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  status: string | null;
  notes: string | null;
  topics: string[];
  form_date: string | null;
  form_rows: { label: string; value: string }[];
};

export type Lead = { id: number; name: string; email: string | null; phone: string | null };

const FILTERS = ["all", "URMEAZĂ", "RECURENT", "MID", "NOPE", "CONTACTAT"] as const;

function CopyIcon() {
  return (
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <rect x="9" y="9" width="13" height="13" rx="2" />
      <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
    </svg>
  );
}

function Contact({ value }: { value: string }) {
  return (
    <div>
      {value}{" "}
      <button type="button" className="sp-copy-btn" title="Copiază" onClick={() => navigator.clipboard.writeText(value)}>
        <CopyIcon />
      </button>
    </div>
  );
}

export default function SpeakeriTable({ speakers, leads }: { speakers: Speaker[]; leads: Lead[] }) {
  const [filter, setFilter] = useState<string>("all");
  const [edit, setEdit] = useState<Speaker | null>(null);
  const [adding, setAdding] = useState(false);
  const [details, setDetails] = useState<Speaker | null>(null);
  const [statusFor, setStatusFor] = useState<number | null>(null);

  const showLeads = filter === "all" || filter === "CONTACTAT";
  const visible = speakers.filter((s) => filter === "all" || (s.status ?? "MID") === filter);

  return (
    <>
      <div className="card">
        <div className="card-title" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <span>Speakeri ({speakers.length})</span>
          <button type="button" onClick={() => setAdding(true)} className="btn btn-sm btn-primary">
            + Adaugă speaker
          </button>
        </div>

        {speakers.length === 0 && leads.length === 0 ? (
          <p style={{ color: "var(--text-muted)" }}>Nu există speakeri adăugați încă.</p>
        ) : (
          <>
            <div className="sp-filter-bar">
              {FILTERS.map((f) => (
                <button
                  key={f}
                  className={`sp-filter-btn${filter === f ? " active" : ""}`}
                  onClick={() => setFilter(f)}
                  type="button"
                >
                  {f === "all" ? "Toți" : f}
                </button>
              ))}
            </div>

            <table className="wp-table crm-table">
              <thead>
                <tr>
                  <th>Nume</th>
                  <th>Contact</th>
                  <th style={{ width: 90 }}>Status</th>
                  <th style={{ width: 150 }}>Acțiuni</th>
                </tr>
              </thead>
              <tbody>
                {showLeads &&
                  leads.map((c) => (
                    <tr key={`lead-${c.id}`}>
                      <td style={{ fontWeight: 600 }}>{c.name}</td>
                      <td style={{ fontSize: 13 }}>
                        {c.email && <Contact value={c.email} />}
                        {c.phone && <Contact value={c.phone} />}
                      </td>
                      <td>
                        <span className="crm-status-badge" style={{ background: "#2271b1" }}>
                          CONTACTAT
                        </span>
                      </td>
                      <td>
                        <div className="row-actions">
                          <button
                            type="button"
                            className="btn btn-sm btn-secondary"
                            onClick={() =>
                              setEdit({
                                id: 0,
                                name: c.name,
                                email: c.email,
                                phone: c.phone,
                                status: "CONTACTAT",
                                notes: "",
                                topics: [],
                                form_date: null,
                                form_rows: [],
                              })
                            }
                          >
                            Editează
                          </button>
                          <form action={unmarkContacted} style={{ display: "inline" }}>
                            <input type="hidden" name="id" value={c.id} />
                            <button type="submit" className="btn btn-sm btn-danger">
                              Scoate
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  ))}

                {visible.map((sp) => (
                  <tr key={sp.id}>
                    <td style={{ fontWeight: 600 }}>
                      <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
                        <button
                          type="button"
                          className="btn btn-sm btn-secondary"
                          style={{ padding: 5, display: "inline-flex", alignItems: "center", justifyContent: "center", flexShrink: 0, lineHeight: 0 }}
                          onClick={() => setDetails(sp)}
                          title="Detalii"
                        >
                          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="16" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12.01" y2="8" />
                          </svg>
                        </button>
                        <div>
                          {sp.name}
                          {sp.notes && (
                            <div style={{ fontSize: 11, color: "var(--text-muted)", fontWeight: 400, marginTop: 2 }}>
                              {sp.notes.slice(0, 60)}
                              {sp.notes.length > 60 ? "…" : ""}
                            </div>
                          )}
                        </div>
                      </div>
                    </td>
                    <td style={{ fontSize: 13 }}>
                      {sp.email && <Contact value={sp.email} />}
                      {sp.phone && <Contact value={sp.phone} />}
                    </td>
                    <td>
                      <span
                        className="crm-status-badge"
                        style={{ background: STATUS_COLOR[sp.status ?? "MID"] ?? "#6b7280", cursor: "pointer", userSelect: "none", position: "relative" }}
                        onClick={() => setStatusFor(statusFor === sp.id ? null : sp.id)}
                      >
                        {sp.status ?? "MID"}
                        {statusFor === sp.id && (
                          <span className="sp-status-popover" style={{ display: "block", position: "absolute", top: "100%", left: 0, zIndex: 60 }}>
                            {SPEAKER_STATUSES.map((s) => (
                              <button
                                key={s}
                                type="button"
                                style={{ color: STATUS_COLOR[s] }}
                                onClick={(e) => {
                                  e.stopPropagation();
                                  setStatusFor(null);
                                  const fd = new FormData();
                                  fd.set("id", String(sp.id));
                                  fd.set("status", s);
                                  void setStatus(fd);
                                }}
                              >
                                {s}
                              </button>
                            ))}
                          </span>
                        )}
                      </span>
                    </td>
                    <td>
                      <div className="row-actions">
                        <button type="button" className="btn btn-sm btn-secondary" onClick={() => setEdit(sp)}>
                          Editează
                        </button>
                        <form
                          action={deleteSpeaker}
                          onSubmit={(e) => {
                            if (!confirm("Ștergi speakerul?")) e.preventDefault();
                          }}
                          style={{ display: "inline" }}
                        >
                          <input type="hidden" name="id" value={sp.id} />
                          <button type="submit" className="btn btn-sm btn-danger">
                            Șterge
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </>
        )}
      </div>

      {(edit || adding) && <SpeakerModal sp={edit} onClose={() => { setEdit(null); setAdding(false); }} />}
      {details && <DetailsModal sp={details} onClose={() => setDetails(null)} />}
    </>
  );
}

const overlay: React.CSSProperties = {
  display: "flex",
  position: "fixed",
  inset: 0,
  zIndex: 9999,
  alignItems: "center",
  justifyContent: "center",
  background: "rgba(0,0,0,.45)",
};

function SpeakerModal({ sp, onClose }: { sp: Speaker | null; onClose: () => void }) {
  return (
    <div style={overlay} onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="card crm-form" style={{ width: "min(640px,95vw)", maxHeight: "90vh", overflowY: "auto", margin: 0, position: "relative" }}>
        <div className="card-title">{sp?.id ? "Editează speaker" : "Adaugă speaker"}</div>
        <form action={saveSpeaker}>
          <input type="hidden" name="id" value={sp?.id ?? ""} />
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 8 }}>
            <div className="form-group">
              <label>Nume *</label>
              <input type="text" name="name" defaultValue={sp?.name ?? ""} required />
            </div>
            <div className="form-group">
              <label>Email</label>
              <input type="email" name="email" defaultValue={sp?.email ?? ""} />
            </div>
            <div className="form-group">
              <label>Telefon</label>
              <input type="text" name="phone" defaultValue={sp?.phone ?? ""} />
            </div>
          </div>
          <div className="form-group" style={{ maxWidth: 200 }}>
            <label>Status</label>
            <select name="status" defaultValue={sp?.status ?? "MID"}>
              {SPEAKER_STATUSES.map((s) => (
                <option key={s} value={s}>
                  {s}
                </option>
              ))}
            </select>
          </div>
          <div className="form-group">
            <label>Note</label>
            <textarea name="notes" rows={2} defaultValue={sp?.notes ?? ""} />
          </div>
          <div style={{ display: "flex", gap: 8, marginTop: 16 }}>
            <button type="submit" className="btn btn-primary btn-sm">
              {sp?.id ? "Salvează" : "Adaugă speakerul"}
            </button>
            <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>
              Anulează
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function DetailsModal({ sp, onClose }: { sp: Speaker; onClose: () => void }) {
  const [tab, setTab] = useState<"formular" | "cursuri">("formular");
  const [topics, setTopics] = useState<string[]>(sp.topics.length ? sp.topics : [""]);
  const tabBtn = (on: boolean): React.CSSProperties => ({
    padding: "5px 16px",
    border: "none",
    borderRadius: 6,
    fontSize: 12,
    fontWeight: 600,
    cursor: "pointer",
    background: on ? "#fff" : "none",
    color: on ? "#1f2937" : "#6b7280",
    boxShadow: on ? "0 1px 3px rgba(0,0,0,.1)" : undefined,
  });

  return (
    <div style={overlay} onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="card" style={{ width: "min(640px,95vw)", maxHeight: "90vh", overflowY: "auto", margin: 0, position: "relative" }}>
        <div className="card-title">{sp.name}</div>
        <div style={{ display: "flex", gap: 4, background: "#f1f5f9", borderRadius: 8, padding: 3, marginBottom: 20, width: "fit-content" }}>
          <button type="button" style={tabBtn(tab === "formular")} onClick={() => setTab("formular")}>
            Formular
          </button>
          <button type="button" style={tabBtn(tab === "cursuri")} onClick={() => setTab("cursuri")}>
            Cursuri
          </button>
        </div>

        {tab === "formular" ? (
          <div>
            <div style={{ fontSize: 12, color: "var(--text-muted)", margin: "-8px 0 16px" }}>{sp.form_date ?? ""}</div>
            {sp.form_rows.length === 0 ? (
              <p style={{ color: "var(--text-muted)", fontSize: 13 }}>Nu există o submisie de formular pentru speakerul ăsta.</p>
            ) : (
              sp.form_rows.map((r, i) => (
                <div key={i} style={{ marginBottom: 12 }}>
                  <div style={{ fontSize: 12, fontWeight: 600, color: "#374151" }}>{r.label}</div>
                  <div style={{ fontSize: 13, whiteSpace: "pre-wrap" }}>{r.value}</div>
                </div>
              ))
            )}
          </div>
        ) : (
          <form action={saveTopics}>
            <input type="hidden" name="id" value={sp.id} />
            <label style={{ fontSize: 12, fontWeight: 600, color: "#374151", display: "block", marginBottom: 6 }}>
              Cursuri pe care le poate susține
            </label>
            <div style={{ display: "flex", flexDirection: "column", gap: 4 }}>
              {topics.map((t, i) => (
                <input
                  key={i}
                  type="text"
                  name="topics"
                  defaultValue={t}
                  style={{ width: "100%" }}
                />
              ))}
            </div>
            <button
              type="button"
              onClick={() => setTopics([...topics, ""])}
              style={{ marginTop: 6, background: "none", border: "1px solid #d1d5db", borderRadius: 6, padding: "2px 8px", cursor: "pointer", fontSize: 11, color: "#6b7280" }}
            >
              + curs
            </button>
            <div style={{ marginTop: 16 }}>
              <button type="submit" className="btn btn-primary btn-sm">
                Salvează cursurile
              </button>
            </div>
          </form>
        )}

        <div style={{ marginTop: 16, borderTop: "1px solid #eef2f7", paddingTop: 12 }}>
          <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>
            Închide
          </button>
        </div>
      </div>
    </div>
  );
}
