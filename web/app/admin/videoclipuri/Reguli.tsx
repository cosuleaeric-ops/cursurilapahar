"use client";

import { useState } from "react";

export type Regula = {
  regula: string;
  detaliu: string;
  unde: string;
  categorie?: string;
  icon?: string;
};

const CULOARE_CAT: Record<string, string> = {
  Stil: "#2563eb",
  Selecție: "#7c3aed",
  Livrare: "#059669",
};

export default function Reguli({ reguli }: { reguli: Regula[] }) {
  const [deschis, setDeschis] = useState<string | null>(null);

  const categorii = [...new Set(reguli.map((r) => r.categorie ?? "Altele"))];

  return (
    <div className="card">
      <div className="card-title">Regulile tale ({reguli.length})</div>
      <p className="form-desc" style={{ marginTop: 0, marginBottom: 16 }}>
        Se aplică automat la fiecare montaj. Apasă pe una ca să vezi unde e implementată.
      </p>

      {categorii.map((cat) => (
        <div key={cat} style={{ marginBottom: 20 }}>
          <div
            style={{
              fontSize: 11,
              fontWeight: 700,
              letterSpacing: 0.8,
              textTransform: "uppercase",
              color: CULOARE_CAT[cat] ?? "#6b7280",
              marginBottom: 8,
            }}
          >
            {cat}
          </div>
          <div style={{ display: "grid", gap: 8 }}>
            {reguli
              .filter((r) => (r.categorie ?? "Altele") === cat)
              .map((r) => {
                const activ = deschis === r.regula;
                return (
                  <button
                    key={r.regula}
                    onClick={() => setDeschis(activ ? null : r.regula)}
                    style={{
                      display: "block",
                      width: "100%",
                      textAlign: "left",
                      background: activ ? "#f8fafc" : "#fff",
                      border: "1px solid",
                      borderColor: activ ? (CULOARE_CAT[cat] ?? "#6b7280") : "#e5e7eb",
                      borderLeftWidth: 3,
                      borderLeftColor: CULOARE_CAT[cat] ?? "#6b7280",
                      borderRadius: 8,
                      padding: "11px 14px",
                      cursor: "pointer",
                      font: "inherit",
                      transition: "background .12s, border-color .12s",
                    }}
                  >
                    <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
                      <span style={{ fontSize: 17, lineHeight: 1, flexShrink: 0 }}>{r.icon ?? "•"}</span>
                      <span style={{ fontWeight: 600, fontSize: 14, flex: 1 }}>{r.regula}</span>
                      <span style={{ opacity: 0.35, fontSize: 11 }}>{activ ? "▲" : "▼"}</span>
                    </div>
                    {activ && (
                      <div style={{ paddingLeft: 27, marginTop: 8 }}>
                        <div style={{ fontSize: 13, lineHeight: 1.55, opacity: 0.85 }}>{r.detaliu}</div>
                        <code
                          style={{
                            display: "inline-block",
                            marginTop: 8,
                            fontSize: 11,
                            background: "#f1f5f9",
                            padding: "3px 7px",
                            borderRadius: 4,
                            opacity: 0.8,
                          }}
                        >
                          {r.unde}
                        </code>
                      </div>
                    )}
                  </button>
                );
              })}
          </div>
        </div>
      ))}
    </div>
  );
}
