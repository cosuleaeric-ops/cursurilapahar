"use client";

import { useState } from "react";
import type { Participant } from "@/lib/statistici";
import { courseMeta } from "./meta";

export type MonthCourse = {
  id: number;
  name: string;
  speaker_name: string | null;
  location: string | null;
  date_ro: string;
  total_tickets: number;
  has_report: boolean;
  has_viza: boolean;
  total_incasari: number | null;
  ditl_base: number | null;
  subtips: { seria: string; de_la: string; pana_la: string; vandute: number | null; nr_unitati: number; tarif: number }[];
};

const fmtRON = (v: number) => Number(v).toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const VIZA_HEADERS = ["Seria", "De la", "Până la", "Vândute", "Total", "Tarif"];

/** Tabelul lunii: bilete, raport, viză, încasări, DITL; rândul de viză se deschide la click pe nume. */
export function CoursesPanel({ courses, sumIncasari, sumDitlBase }: { courses: MonthCourse[]; sumIncasari: number; sumDitlBase: number }) {
  const [open, setOpen] = useState<Set<number>>(new Set());
  const toggle = (id: number) =>
    setOpen((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });

  if (!courses.length) return <p style={{ color: "var(--text-muted)" }}>Niciun curs pentru perioada selectată.</p>;

  return (
    <>
      {sumIncasari > 0 && (
        <div className="clp-summary-grid" style={{ marginBottom: 16 }}>
          <div className="clp-stat-box">
            <div className="lbl">Total încasări</div>
            <div className="val">
              {fmtRON(sumIncasari)} <small style={{ fontSize: 14, fontWeight: 400 }}>RON</small>
            </div>
          </div>
          <div className="clp-stat-box">
            <div className="lbl">Taxă DITL (2%)</div>
            <div className="val ditl">
              {fmtRON(sumDitlBase * 0.02)} <small style={{ fontSize: 14, fontWeight: 400 }}>RON</small>
            </div>
          </div>
        </div>
      )}

      <table className="wp-table">
        <thead>
          <tr>
            <th>Curs</th>
            <th>Dată</th>
            <th style={{ textAlign: "right" }}>Bilete</th>
            <th style={{ textAlign: "center" }}>Raport</th>
            <th style={{ textAlign: "center" }}>Viză</th>
            <th style={{ textAlign: "right" }}>Încasări</th>
            <th style={{ textAlign: "right" }}>DITL (2%)</th>
          </tr>
        </thead>
        <tbody>
          {courses.map((c) => (
            <FragmentRow key={c.id} c={c} open={open.has(c.id)} onToggle={() => toggle(c.id)} />
          ))}
        </tbody>
      </table>
    </>
  );
}

function FragmentRow({ c, open, onToggle }: { c: MonthCourse; open: boolean; onToggle: () => void }) {
  const dash = <span style={{ color: "#d1d5db" }}>-</span>;
  const meta = courseMeta(c.speaker_name, c.location);
  return (
    <>
      <tr style={{ cursor: "pointer" }} onClick={() => (location.href = `/admin/cursuri/${c.id}/detalii`)}>
        <td style={{ fontWeight: 600 }}>
          {c.subtips.length ? (
            <span
              className="clp-toggle"
              onClick={(e) => {
                e.stopPropagation();
                onToggle();
              }}
            >
              {c.name}
            </span>
          ) : (
            c.name
          )}
          {meta && <span style={{ fontWeight: 400, color: "var(--text-muted)" }}> · {meta}</span>}
        </td>
        <td style={{ color: "var(--text-muted)", whiteSpace: "nowrap" }}>{c.date_ro}</td>
        <td style={{ textAlign: "right" }}>{c.total_tickets}</td>
        <td style={{ textAlign: "center" }}>
          {c.has_report ? <span style={{ color: "#16a34a", fontSize: 16 }}>✓</span> : <span style={{ color: "#d1d5db", fontSize: 16 }}>-</span>}
        </td>
        <td style={{ textAlign: "center" }}>
          {c.has_viza ? <span style={{ color: "#16a34a", fontSize: 16 }}>✓</span> : <span style={{ color: "#d1d5db", fontSize: 16 }}>-</span>}
        </td>
        <td style={{ textAlign: "right", fontVariantNumeric: "tabular-nums" }}>
          {c.total_incasari != null ? `${fmtRON(c.total_incasari)} RON` : dash}
        </td>
        <td style={{ textAlign: "right", fontVariantNumeric: "tabular-nums" }}>
          {c.ditl_base != null ? <span className="clp-ditl-cell">{fmtRON(c.ditl_base * 0.02)} RON</span> : dash}
        </td>
      </tr>
      {open && c.subtips.length > 0 && (
        <tr className="clp-viza-row open">
          <td colSpan={7} style={{ padding: 0, background: "#f8fafc" }}>
            <div style={{ padding: "6px 16px 12px 32px" }}>
              <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 12 }}>
                <thead>
                  <tr>
                    {VIZA_HEADERS.map((h) => (
                      <th
                        key={h}
                        style={{
                          padding: "5px 10px",
                          fontSize: 10,
                          fontWeight: 700,
                          textTransform: "uppercase",
                          color: "var(--text-muted)",
                          borderBottom: "1px solid var(--border)",
                          textAlign: h === "Seria" ? "left" : "right",
                        }}
                      >
                        {h}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {c.subtips.map((s, i) => (
                    <tr key={i}>
                      <td style={{ padding: "5px 10px", borderBottom: "1px solid #f1f5f9" }}>
                        <span className="clp-seria">{s.seria}</span>
                      </td>
                      <td style={{ padding: "5px 10px", textAlign: "right", borderBottom: "1px solid #f1f5f9" }}>{s.de_la}</td>
                      <td style={{ padding: "5px 10px", textAlign: "right", borderBottom: "1px solid #f1f5f9" }}>{s.pana_la}</td>
                      <td style={{ padding: "5px 10px", textAlign: "right", borderBottom: "1px solid #f1f5f9" }}>
                        {s.vandute != null ? <strong>{s.vandute}</strong> : "-"}
                      </td>
                      <td style={{ padding: "5px 10px", textAlign: "right", borderBottom: "1px solid #f1f5f9" }}>{s.nr_unitati}</td>
                      <td style={{ padding: "5px 10px", textAlign: "right", borderBottom: "1px solid #f1f5f9" }}>
                        {Number(s.tarif).toLocaleString("ro-RO", { maximumFractionDigits: 0 })} RON
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </td>
        </tr>
      )}
    </>
  );
}

const RO_MONTHS = ["", "ianuarie", "februarie", "martie", "aprilie", "mai", "iunie", "iulie", "august", "septembrie", "octombrie", "noiembrie", "decembrie"];

/** Lista de participanți, cu căutare live (ca `clpFilter` din JS-ul vechi). */
export function ParticipantsPanel({
  participants,
  stats,
  evolution,
}: {
  participants: Participant[];
  stats: { unique: number; returning: number; tickets: number };
  evolution: { m: string; unici: number; bilete: number }[];
}) {
  const [q, setQ] = useState("");
  if (!participants.length) return <p style={{ color: "var(--text-muted)" }}>Niciun participant înregistrat încă.</p>;

  const needle = q.trim().toLowerCase();
  const shown = needle ? participants.filter((p) => p.participant_name.toLowerCase().includes(needle)) : participants;

  return (
    <>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(3,1fr)", gap: 14, marginBottom: 20 }}>
        <div className="clp-stat-box">
          <div className="lbl">Participanți unici</div>
          <div className="val">{stats.unique}</div>
        </div>
        <div className="clp-stat-box">
          <div className="lbl">Revin la 2+ cursuri</div>
          <div className="val" style={{ color: "#16a34a" }}>
            {stats.returning}
          </div>
        </div>
        <div className="clp-stat-box">
          <div className="lbl">Total bilete vândute</div>
          <div className="val">{stats.tickets}</div>
        </div>
      </div>

      {evolution.length > 0 && (
        <div className="dash-section" style={{ marginBottom: 20 }}>
          <div className="dash-section-title">
            <span>Evoluție participanți</span>
          </div>
          <table className="dash-table">
            <tbody>
              <tr style={{ fontSize: 11, fontWeight: 700, textTransform: "uppercase", letterSpacing: ".5px", color: "var(--text-muted)" }}>
                <td>Luna</td>
                <td style={{ textAlign: "right" }}>Unici</td>
                <td style={{ textAlign: "right" }}>Bilete</td>
              </tr>
              {evolution.map((e) => {
                const mi = parseInt(e.m.slice(5, 7), 10);
                const mn = (RO_MONTHS[mi] ?? "").charAt(0).toUpperCase() + (RO_MONTHS[mi] ?? "").slice(1);
                return (
                  <tr key={e.m}>
                    <td>
                      {mn} {e.m.slice(0, 4)}
                    </td>
                    <td style={{ textAlign: "right", fontWeight: 600 }}>{e.unici}</td>
                    <td style={{ textAlign: "right" }} className="muted">
                      {e.bilete}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      <div style={{ marginBottom: 12 }}>
        <input
          type="text"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          aria-label="Caută participant"
          style={{ width: "100%", padding: "9px 12px", border: "1px solid var(--border)", borderRadius: 8, fontSize: 13, background: "#fff" }}
        />
      </div>

      <table className="wp-table">
        <thead>
          <tr>
            <th>Participant</th>
            <th style={{ textAlign: "right", width: 90 }}># Cursuri</th>
            <th style={{ textAlign: "right", width: 90 }}># Bilete</th>
            <th>Cursuri</th>
          </tr>
        </thead>
        <tbody>
          {shown.map((p, i) => (
            <tr key={i}>
              <td>
                <strong>{p.participant_name}</strong>
                {p.num_courses > 1 && (
                  <span style={{ background: "#dcfce7", color: "#16a34a", padding: "2px 8px", borderRadius: 20, fontSize: 11, fontWeight: 600, marginLeft: 6 }}>
                    revine
                  </span>
                )}
              </td>
              <td style={{ textAlign: "right" }}>{p.num_courses}</td>
              <td style={{ textAlign: "right" }}>{p.total_tickets}</td>
              <td>
                <div style={{ display: "flex", flexWrap: "wrap", gap: 4 }}>
                  {p.courses.filter(Boolean).map((c, j) => {
                    const parts = c.split(" (");
                    const name = parts[0].trim();
                    const date = parts.length > 1 ? parts[1].replace(/\)$/, "").slice(0, 7) : "";
                    return (
                      <span
                        key={j}
                        style={{ background: "#f1f5f9", border: "1px solid var(--border)", borderRadius: 4, fontSize: 11, color: "var(--text-muted)", padding: "2px 6px" }}
                      >
                        {name}
                        {date && <span style={{ opacity: 0.6 }}> ({date})</span>}
                      </span>
                    );
                  })}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  );
}
