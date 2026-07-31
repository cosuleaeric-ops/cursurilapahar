"use client";

import { useMemo, useState } from "react";

export type Clip = {
  id: string;
  folder: string;
  titlu: string;
  tip: string;
  dur: number;
  loc: string;
  mis: string;
  cal: number | null;
  orient: string;
  stare: string;
  motiv: string;
  descr: string;
};

const CULOARE: Record<string, string> = {
  disponibil: "#16a34a",
  propus: "#0891b2",
  folosit: "#a16207",
  respins: "#dc2626",
};

export default function ListaClipuri({ clipuri, generat }: { clipuri: Clip[]; generat: string }) {
  const [q, setQ] = useState("");
  const [tip, setTip] = useState("");
  const [stare, setStare] = useState("");
  const [folder, setFolder] = useState("");
  const [limita, setLimita] = useState(60);

  const foldere = useMemo(() => [...new Set(clipuri.map((c) => c.folder))].sort(), [clipuri]);
  const tipuri = useMemo(() => [...new Set(clipuri.map((c) => c.tip))].sort(), [clipuri]);

  const rez = useMemo(() => {
    const t = q.trim().toLowerCase();
    return clipuri.filter(
      (c) =>
        (!tip || c.tip === tip) &&
        (!stare || c.stare === stare) &&
        (!folder || c.folder === folder) &&
        (!t ||
          c.titlu.toLowerCase().includes(t) ||
          c.descr.toLowerCase().includes(t) ||
          c.folder.toLowerCase().includes(t) ||
          c.id.includes(t)),
    );
  }, [clipuri, q, tip, stare, folder]);

  return (
    <div className="card">
      <div className="card-title">
        Toate clipurile scanate - {rez.length} din {clipuri.length}
      </div>
      <p className="form-desc" style={{ marginTop: 0 }}>
        Catalog generat {generat}. Caută după titlu, folder, descriere sau id.
      </p>

      <div style={{ display: "flex", gap: 8, flexWrap: "wrap", marginBottom: 12 }}>
        <input
          className="input"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          style={{ flex: "1 1 220px", minWidth: 180 }}
          aria-label="Caută"
        />
        <select className="input" value={folder} onChange={(e) => setFolder(e.target.value)} style={{ maxWidth: 260 }}>
          <option value="">Toate folderele</option>
          {foldere.map((f) => (
            <option key={f} value={f}>
              {f}
            </option>
          ))}
        </select>
        <select className="input" value={tip} onChange={(e) => setTip(e.target.value)}>
          <option value="">Orice tip</option>
          {tipuri.map((t) => (
            <option key={t} value={t}>
              {t}
            </option>
          ))}
        </select>
        <select className="input" value={stare} onChange={(e) => setStare(e.target.value)}>
          <option value="">Orice stare</option>
          <option value="disponibil">disponibil</option>
          <option value="propus">propus (văzut, neales)</option>
          <option value="folosit">folosit</option>
          <option value="respins">respins</option>
        </select>
      </div>

      <div style={{ overflowX: "auto" }}>
        <table className="wp-table" style={{ width: "100%", fontSize: 12.5 }}>
          <thead>
            <tr>
              <th>id</th>
              <th style={{ textAlign: "left" }}>Folder</th>
              <th style={{ textAlign: "left" }}>Titlu</th>
              <th>Tip</th>
              <th>Durată</th>
              <th>Local</th>
              <th>Mișcare</th>
              <th>Cal</th>
              <th style={{ textAlign: "left" }}>Stare</th>
            </tr>
          </thead>
          <tbody>
            {rez.slice(0, limita).map((c) => (
              <tr key={c.id} title={c.descr}>
                <td style={{ fontFamily: "monospace", opacity: 0.6 }}>{c.id}</td>
                <td style={{ maxWidth: 210, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                  {c.folder}
                </td>
                <td style={{ fontFamily: "monospace" }}>{c.titlu}</td>
                <td style={{ textAlign: "center" }}>{c.tip}</td>
                <td style={{ textAlign: "right" }}>{c.dur.toFixed(1)}s</td>
                <td style={{ textAlign: "center" }}>{c.loc}</td>
                <td style={{ textAlign: "center", opacity: 0.75 }}>{c.mis}</td>
                <td style={{ textAlign: "center" }}>{c.cal ?? "-"}</td>
                <td>
                  <span style={{ color: CULOARE[c.stare], fontWeight: 600 }}>{c.stare}</span>
                  {c.motiv && <div style={{ fontSize: 10.5, opacity: 0.6 }}>{c.motiv}</div>}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {rez.length > limita && (
        <button className="btn" style={{ marginTop: 12 }} onClick={() => setLimita((l) => l + 200)}>
          Arată încă 200 (din {rez.length - limita} rămase)
        </button>
      )}
    </div>
  );
}
