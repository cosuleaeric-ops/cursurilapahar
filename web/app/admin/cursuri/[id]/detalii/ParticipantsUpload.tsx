"use client";

import { useEffect, useRef, useState } from "react";
import { updateParticipants } from "./actions";

// Port 1:1 al blocului „Actualizeaza lista" din admin/statistici/cursuri/view.php:
// drop zone XLSX/CSV, detectarea coloanei cu nume, selector manual când nu o
// nimerește, previzualizare cu bilete/comenzi, apoi trimiterea listei.

type Row = Record<string, unknown>;

declare global {
  interface Window {
    XLSX?: {
      read: (data: ArrayBuffer, opts: { type: string }) => { SheetNames: string[]; Sheets: Record<string, unknown> };
      utils: { sheet_to_json: (ws: unknown, opts: { defval: string }) => Row[] };
    };
  }
}

/** Aceleași tipare, în aceeași ordine, ca detectCol() din PHP. */
const DETECT = [
  /^prenume$/i, /prenume/i, /^nume complet$/i, /^participant/i, /^cump[aă]r[aă]tor/i,
  /^client/i, /^name$/i, /full.?name/i, /^nume$/i, /nume/i,
];

export default function ParticipantsUpload({ id, hasList }: { id: number; hasList: boolean }) {
  const [rows, setRows] = useState<Row[]>([]);
  const [headers, setHeaders] = useState<string[]>([]);
  const [pickCol, setPickCol] = useState(false);
  const [col, setCol] = useState("");
  const [names, setNames] = useState<string[] | null>(null);
  const [usedCol, setUsedCol] = useState("");
  const [drag, setDrag] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (window.XLSX) return;
    const s = document.createElement("script");
    s.src = "https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js";
    document.body.appendChild(s);
  }, []);

  /** Raport comisioane iaBilet: „Ion Pop - 2 bilete" în coloana „Comanda". */
  function parseIaBilet(list: Row[], hs: string[]): string[] | null {
    const c = hs.find((h) => /^comand[aă]$/i.test(h.trim()));
    if (!c) return null;
    const statusCol = hs.find((h) => /^status/i.test(h.trim()));
    const out: string[] = [];
    let matched = 0;
    for (const r of list) {
      const m = String(r[c] ?? "").trim().match(/^(.+)\s+-\s+(\d+)\s+bilete?$/i);
      if (!m) continue;
      matched++;
      if (statusCol && !/finalizat/i.test(String(r[statusCol] ?? ""))) continue;
      for (let i = 0; i < parseInt(m[2], 10); i++) out.push(m[1]);
    }
    return matched ? out : null;
  }

  function apply(list: string[], from: string) {
    setNames(list);
    setUsedCol(from);
  }

  function applyCol(c: string, list = rows) {
    const picked = list.map((r) => String(r[c] ?? "").trim()).filter(Boolean);
    if (!picked.length) {
      alert("Coloana selectată pare goală.");
      return;
    }
    apply(picked, c);
  }

  function handleFile(file: File) {
    setPickCol(false);
    setNames(null);
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const XLSX = window.XLSX!;
        const wb = XLSX.read(e.target!.result as ArrayBuffer, { type: "array" });
        const ws = wb.Sheets[wb.SheetNames[0]];
        const parsed = XLSX.utils.sheet_to_json(ws, { defval: "" });
        if (!parsed.length) {
          alert("Fișierul pare gol.");
          return;
        }
        const hs = Object.keys(parsed[0]);
        setRows(parsed);
        setHeaders(hs);

        const ia = parseIaBilet(parsed, hs);
        if (ia) {
          apply(ia, "Comanda");
          return;
        }
        const detected = DETECT.map((re) => hs.find((h) => re.test(h.trim()))).find(Boolean);
        if (detected) applyCol(detected, parsed);
        else {
          setCol(hs[0] ?? "");
          setPickCol(true);
        }
      } catch {
        alert("Nu am putut citi fișierul.");
      }
    };
    reader.readAsArrayBuffer(file);
  }

  const orders = names ? new Set(names).size : 0;

  return (
    <form action={updateParticipants} style={{ marginTop: 16, borderTop: "1px solid var(--border)", paddingTop: 14 }}>
      <input type="hidden" name="id" value={id} />
      <input type="hidden" name="participants_json" value={names ? JSON.stringify(names) : ""} />

      <div
        className={`update-drop${drag ? " dragover" : ""}`}
        style={{ padding: "10px 16px" }}
        onDragOver={(e) => {
          e.preventDefault();
          setDrag(true);
        }}
        onDragLeave={() => setDrag(false)}
        onDrop={(e) => {
          e.preventDefault();
          setDrag(false);
          if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
        }}
      >
        <input
          ref={fileRef}
          type="file"
          accept=".xlsx,.xls,.csv"
          onChange={(e) => e.target.files?.[0] && handleFile(e.target.files[0])}
        />
        <p style={{ fontSize: 12 }}>
          {hasList ? "Actualizeaza lista" : "Incarca lista"} — trage sau apasa pentru XLSX / CSV
        </p>
      </div>

      <div className="update-col-picker" style={{ display: pickCol ? "block" : "none" }}>
        <label>Selecteaza coloana cu nume participanti</label>
        <select value={col} onChange={(e) => setCol(e.target.value)}>
          {headers.map((h) => (
            <option key={h} value={h}>
              {h}
            </option>
          ))}
        </select>
        <button
          type="button"
          className="btn btn-ghost"
          style={{ marginTop: 8, width: "100%", justifyContent: "center" }}
          onClick={() => {
            setPickCol(false);
            applyCol(col);
          }}
        >
          Aplica
        </button>
      </div>

      <div className="update-preview" style={{ display: names ? "block" : "none" }}>
        <strong>{names?.length ?? 0}</strong> bilete · <strong>{orders}</strong> comenzi · coloana:{" "}
        <em>{usedCol}</em>
      </div>

      <div className="update-submit" style={{ display: names ? "block" : "none" }}>
        <button type="submit" className="btn btn-green" style={{ width: "100%", justifyContent: "center", padding: 10 }}>
          {hasList ? "Inlocuieste lista" : "Salveaza lista"}
        </button>
      </div>
    </form>
  );
}
