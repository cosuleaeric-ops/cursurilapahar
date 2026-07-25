"use client";

import { useEffect, useRef, useState } from "react";
import { uploadRaport } from "./actions";

// Port 1:1 al cardului „Raport eveniment" din admin/statistici/raport_upload_form.inc.php
// + parserul XLSX din view.php: zona de drop are text fix, iar previzualizarea verde
// și butonul verde apar abia după ce fișierul e citit în browser.

type XlsxLib = {
  read: (data: ArrayBuffer, opts: { type: string }) => { SheetNames: string[]; Sheets: Record<string, unknown> };
  utils: { sheet_to_json: (ws: unknown, opts: { defval: number }) => Record<string, unknown>[] };
};

const XLSX_SRC = "https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js";
const num = (v: unknown): number => {
  const n = Number(v ?? 0);
  return Number.isNaN(n) ? 0 : n;
};

export default function RaportUpload({ id }: { id: number }) {
  const [totals, setTotals] = useState<{ bilete: number; incasari: number } | null>(null);
  const [drag, setDrag] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if ((window as unknown as { XLSX?: XlsxLib }).XLSX) return;
    if (document.querySelector(`script[src="${XLSX_SRC}"]`)) return;
    const s = document.createElement("script");
    s.src = XLSX_SRC;
    document.body.appendChild(s);
  }, []);

  async function handleFile(file: File) {
    const XLSX = (window as unknown as { XLSX?: XlsxLib }).XLSX;
    if (!XLSX) return;
    try {
      const wb = XLSX.read(await file.arrayBuffer(), { type: "array" });
      const vanzari = wb.SheetNames.find((n) => /vanzari/i.test(n));
      const decont = wb.SheetNames.find((n) => /decont/i.test(n));
      if (!vanzari && !decont) {
        alert(
          'Fișierul nu are foaia „Vanzari" sau „Decont". Încarcă exportul complet al evenimentului (Curs la Pahar - ....xlsx) sau decontul LiveTickets.'
        );
        return;
      }

      let totalBilete = 0;
      let totalIncasari = 0;
      if (vanzari) {
        for (const row of XLSX.utils.sheet_to_json(wb.Sheets[vanzari], { defval: 0 })) {
          totalBilete += num(row["Total bilete"] || row["total_bilete"]) - num(row["Valoare retururi"] || row["valoare_retururi"]);
          totalIncasari += num(row["Total incasari"] || row["total_incasari"]);
        }
      } else {
        // Decont LiveTickets: un rând per comandă; rândul de comision scade din încasări
        for (const row of XLSX.utils.sheet_to_json(wb.Sheets[decont!], { defval: 0 })) {
          const orderId = String(row["ID Comanda"] || "").trim();
          if (/comision tranzactie/i.test(orderId)) {
            totalIncasari -= num(row["De transferat"]);
            continue;
          }
          if (!/^\d+$/.test(orderId) || !(num(row["Nr Bilete"]) > 0)) continue;
          totalBilete += num(row["Total bilete"]);
          totalIncasari += num(row["De transferat"]);
        }
      }
      setTotals({ bilete: totalBilete, incasari: totalIncasari });
    } catch {
      alert("Nu am putut citi fișierul XLSX.");
    }
  }

  return (
    <form action={uploadRaport} className="raport-form">
      <input type="hidden" name="id" value={id} />

      <div
        className={`raport-drop${drag ? " dragover" : ""}`}
        onDragOver={(e) => {
          e.preventDefault();
          setDrag(true);
        }}
        onDragLeave={() => setDrag(false)}
        onDrop={(e) => {
          e.preventDefault();
          setDrag(false);
          const f = e.dataTransfer.files[0];
          if (!f) return;
          // fișierul tras trebuie să ajungă și în input, altfel formularul se trimite gol
          if (fileRef.current) fileRef.current.files = e.dataTransfer.files;
          handleFile(f);
        }}
      >
        <input
          ref={fileRef}
          type="file"
          name="raport_file"
          accept=".xlsx,.xls"
          onChange={(e) => e.target.files?.[0] && handleFile(e.target.files[0])}
        />
        <p>📊 Trage sau apasa pentru a incarca raportul XLSX</p>
      </div>

      <div className="raport-preview" style={{ display: totals ? "block" : "none" }}>
        <strong>Total încasări:</strong> {(totals?.incasari ?? 0).toFixed(2)} RON{" \u00a0·\u00a0 "}
        <strong>Total bilete:</strong> {(totals?.bilete ?? 0).toFixed(2)} RON{" \u00a0·\u00a0 "}
        <strong>DITL (2%):</strong> <span style={{ color: "#c0392b" }}>{((totals?.bilete ?? 0) * 0.02).toFixed(2)} RON</span>
      </div>

      <div className="raport-submit" style={{ display: totals ? "block" : "none" }}>
        <button type="submit" className="btn btn-green" style={{ width: "100%", justifyContent: "center", padding: 9 }}>
          Salveaza raportul
        </button>
      </div>
    </form>
  );
}
