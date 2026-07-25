"use client";

import { useEffect, useRef, useState } from "react";
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
  meet: Record<string, string>;
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

function CheckIcon() {
  return (
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="20 6 9 17 4 12" />
    </svg>
  );
}

function Contact({ value }: { value: string }) {
  // spCopy(): bifă verde (text + bordură) timp de 2000 ms, apoi iconița inițială
  const [copied, setCopied] = useState(false);
  return (
    <div>
      {value}{" "}
      <button
        type="button"
        className="sp-copy-btn"
        title="Copiază"
        style={copied ? { color: "#27ae60", borderColor: "#27ae60" } : undefined}
        onClick={() =>
          navigator.clipboard.writeText(value).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
          })
        }
      >
        {copied ? <CheckIcon /> : <CopyIcon />}
      </button>
    </div>
  );
}

export default function SpeakeriTable({ speakers, leads }: { speakers: Speaker[]; leads: Lead[] }) {
  const [filter, setFilter] = useState<string>("all");
  const [edit, setEdit] = useState<Speaker | null>(null);
  // spContactatEdit() forțează titlul/butonul de editare deși fișa n-are încă id
  const [leadEdit, setLeadEdit] = useState(false);
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
                  // toată colorarea din CSS se agață de [data-status], nu de .active
                  data-status={f}
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
                            onClick={() => {
                              setLeadEdit(true);
                              setEdit({
                                id: 0,
                                name: c.name,
                                email: c.email,
                                phone: c.phone,
                                // spResetForm() lasă selectul pe MID; spContactatEdit() nu-l mai atinge
                                status: "MID",
                                notes: "",
                                topics: [],
                                form_date: null,
                                form_rows: [],
                                meet: {},
                              });
                            }}
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
                        {/* fără display inline: clasa .sp-status-popover dă flex-direction:column */}
                        {statusFor === sp.id && (
                          <span className="sp-status-popover" style={{ position: "absolute", top: "100%", left: 0, zIndex: 60 }}>
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

      {(edit || adding) && (
        <SpeakerModal
          sp={edit}
          editLabels={!!edit?.id || leadEdit}
          onClose={() => { setEdit(null); setAdding(false); setLeadEdit(false); }}
        />
      )}
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

const MEET_FIELDS: [string, string][] = [
  ["auzit", "Cum ai auzit de Cursuri la Pahar?"],
  ["ocupatie", "Cu ce te ocupi?"],
  ["pasiune", "Ce te pasionează cel mai mult la subiectul ăsta și crezi că ar fi valoros pentru oameni?"],
  ["teme", "Ai mai avea alte idei de teme?"],
  ["dinamica", "Cum vezi tu dinamica cu publicul? Cum ți-ar plăcea să arate?"],
  ["experienta", "Unde ai mai ținut cursuri și cum s-au desfășurat? Ai vreo prezentare pe care ai folosit-o?"],
  ["contract", "Contract (prezentare, durata, onorariu)"],
  ["curiozitati", "Curiozități?"],
  ["program", "Program pe perioada următoare"],
];

const modalTabBtn = (on: boolean): React.CSSProperties => ({
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

function SpeakerModal({ sp, editLabels, onClose }: { sp: Speaker | null; editLabels: boolean; onClose: () => void }) {
  const [tab, setTab] = useState<"contact" | "meet">("contact");
  return (
    <div style={overlay} onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="card crm-form" style={{ width: "min(640px,95vw)", maxHeight: "90vh", overflowY: "auto", margin: 0, position: "relative" }}>
        <div className="card-title">{editLabels ? "Editează speaker" : "Adaugă speaker"}</div>
        <form action={saveSpeaker}>
          <input type="hidden" name="id" value={sp?.id ?? ""} />
          <div style={{ display: "flex", gap: 4, background: "#f1f5f9", borderRadius: 8, padding: 3, marginBottom: 20, width: "fit-content" }}>
            <button type="button" style={modalTabBtn(tab === "contact")} onClick={() => setTab("contact")}>
              Contact
            </button>
            <button type="button" style={modalTabBtn(tab === "meet")} onClick={() => setTab("meet")}>
              Meet
            </button>
          </div>
          <div style={tab === "contact" ? undefined : { display: "none" }}>
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
          </div>
          <div style={tab === "meet" ? undefined : { display: "none" }}>
            {MEET_FIELDS.map(([k, lbl]) => (
              <div className="form-group" key={k}>
                <label>{lbl}</label>
                <textarea name={`meet_${k}`} rows={2} defaultValue={sp?.meet?.[k] ?? ""} />
              </div>
            ))}
          </div>
          <div style={{ display: "flex", gap: 8, marginTop: 16 }}>
            <button type="submit" className="btn btn-primary btn-sm">
              {editLabels ? "Salvează" : "Adaugă speakerul"}
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
  const listRef = useRef<HTMLDivElement>(null);
  const focusNew = useRef(false);

  // spDtAddCourse() dă focus pe inputul nou adăugat
  useEffect(() => {
    if (!focusNew.current) return;
    focusNew.current = false;
    const inputs = listRef.current?.querySelectorAll<HTMLInputElement>("input");
    inputs?.[inputs.length - 1]?.focus();
  }, [topics.length]);

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
        <div className="card-title">Detalii: {sp.name}</div>
        <div style={{ display: "flex", gap: 4, background: "#f1f5f9", borderRadius: 8, padding: 3, marginBottom: 20, width: "fit-content" }}>
          <button type="button" style={tabBtn(tab === "formular")} onClick={() => setTab("formular")}>
            Formular
          </button>
          <button type="button" style={tabBtn(tab === "cursuri")} onClick={() => setTab("cursuri")}>
            Cursuri
          </button>
        </div>

        {/* ambele taburi rămân în DOM, se comută doar display-ul (ca în speakeri-tab.php) */}
        <div style={tab === "formular" ? undefined : { display: "none" }}>
          <div style={{ fontSize: 12, color: "var(--text-muted)", margin: "-8px 0 16px" }}>
            {sp.form_date ? `Trimis pe ${sp.form_date}` : ""}
          </div>
          {sp.form_rows.length === 0 ? (
            <div style={{ fontSize: 13, color: "#9ca3af" }}>Fără formular trimis.</div>
          ) : (
            sp.form_rows.map((r, i) => (
              <div key={i} style={{ marginBottom: 12 }}>
                <div style={{ fontSize: 11, fontWeight: 600, color: "#6b7280", textTransform: "uppercase", letterSpacing: ".02em", marginBottom: 2 }}>
                  {r.label}
                </div>
                {/* câmp trimis gol => liniuță em */}
                <div style={{ fontSize: 13, whiteSpace: "pre-wrap" }}>{r.value || "—"}</div>
              </div>
            ))
          )}
        </div>
        <div style={tab === "cursuri" ? undefined : { display: "none" }}>
          <form action={saveTopics}>
            <input type="hidden" name="id" value={sp.id} />
            <label style={{ fontSize: 12, fontWeight: 600, color: "#374151", display: "block", marginBottom: 6 }}>
              Cursuri pe care le poate susține
            </label>
            <div ref={listRef} style={{ display: "flex", flexDirection: "column", gap: 4 }}>
              {topics.map((t, i) => (
                <div key={i} style={{ display: "flex", gap: 4, alignItems: "center" }}>
                  <input
                    type="text"
                    name="topics"
                    value={t}
                    onChange={(e) => setTopics(topics.map((v, j) => (j === i ? e.target.value : v)))}
                    style={{ flex: 1, padding: "5px 9px", fontSize: 12, border: "1px solid #e5e7eb", borderRadius: 8 }}
                  />
                  {/* butonul × scoate rândul, ca `this.closest('div').remove()` din PHP */}
                  <button
                    type="button"
                    onClick={() => setTopics(topics.filter((_, j) => j !== i))}
                    style={{ background: "none", border: "1px solid #d1d5db", borderRadius: 6, padding: "0 7px", height: 28, cursor: "pointer", color: "#9ca3af", fontSize: 14, lineHeight: 1 }}
                  >
                    ×
                  </button>
                </div>
              ))}
            </div>
            <button
              type="button"
              onClick={() => { focusNew.current = true; setTopics([...topics, ""]); }}
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
        </div>

        <div style={{ marginTop: 16, borderTop: "1px solid #eef2f7", paddingTop: 12 }}>
          <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>
            Închide
          </button>
        </div>
      </div>
    </div>
  );
}
