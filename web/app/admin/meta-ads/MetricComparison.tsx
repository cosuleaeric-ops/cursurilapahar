"use client";

import { useEffect, useRef, useState } from "react";
import type { CampaignMetricComparison, CampaignMetricKey } from "@/lib/meta";

type Props = {
  campaignId: string;
  comparisons: CampaignMetricComparison[];
  metrics: CampaignMetricKey[];
};

const DEFINITIONS: Record<CampaignMetricKey, { label: string; better: "high" | "low"; format: (v: number) => string }> = {
  roas: { label: "ROAS", better: "high", format: (v) => `${v.toFixed(2)}x` },
  costPerPurchase: { label: "Cost / achiziție", better: "low", format: (v) => `${v.toFixed(2)} lei` },
  cpm: { label: "CPM", better: "low", format: (v) => `${v.toFixed(2)} lei` },
  cpcLink: { label: "CPC pe link", better: "low", format: (v) => `${v.toFixed(2)} lei` },
  cpcAll: { label: "CPC toate clicurile", better: "low", format: (v) => `${v.toFixed(2)} lei` },
  costPerLandingView: { label: "Cost / vizualizare", better: "low", format: (v) => `${v.toFixed(2)} lei` },
  frequency: { label: "Frecvență", better: "low", format: (v) => v.toFixed(2) },
  ctrLink: { label: "CTR pe link", better: "high", format: (v) => `${v.toFixed(2)}%` },
  ctrAll: { label: "CTR total", better: "high", format: (v) => `${v.toFixed(2)}%` },
};

function MetricButton({ metric, value, onClick }: { metric: CampaignMetricKey; value: number | null; onClick: () => void }) {
  const definition = DEFINITIONS[metric];
  return (
    <button
      type="button"
      onClick={onClick}
      aria-label={`Compară ${definition.label}`}
      style={{
        border: "1px solid var(--border)",
        borderRadius: 8,
        padding: "12px 14px",
        background: "var(--bg-warm)",
        textAlign: "left",
        width: "100%",
        cursor: "pointer",
        font: "inherit",
      }}
    >
      <div style={{ fontSize: 10, textTransform: "uppercase", letterSpacing: ".05em", color: "var(--text-muted)", fontWeight: 700 }}>
        {definition.label}
      </div>
      <div style={{ fontSize: 20, fontWeight: 700, marginTop: 4 }}>{value == null ? "-" : definition.format(value)}</div>
      <div style={{ fontSize: 11, color: "var(--text-muted)", marginTop: 2 }}>Compară cu restul campaniilor →</div>
    </button>
  );
}

function average(values: number[]): number {
  return values.reduce((sum, value) => sum + value, 0) / values.length;
}

function median(values: number[]): number {
  const sorted = [...values].sort((a, b) => a - b);
  const middle = Math.floor(sorted.length / 2);
  return sorted.length % 2 ? sorted[middle] : (sorted[middle - 1] + sorted[middle]) / 2;
}

export default function MetricComparison({ campaignId, comparisons, metrics }: Props) {
  const [selected, setSelected] = useState<CampaignMetricKey | null>(null);
  const closeRef = useRef<HTMLButtonElement>(null);
  const current = comparisons.find((campaign) => campaign.id === campaignId);
  const definition = selected ? DEFINITIONS[selected] : null;
  const rows = selected
    ? comparisons
        .filter((campaign) => campaign.metrics[selected] != null)
        .sort((a, b) => {
          const direction = definition?.better === "high" ? -1 : 1;
          return (a.metrics[selected]! - b.metrics[selected]!) * direction;
        })
    : [];
  const values = rows.map((row) => row.metrics[selected!]!);
  const currentValue = selected && current ? current.metrics[selected] : null;
  const rank = currentValue == null ? null : rows.findIndex((row) => row.id === campaignId) + 1;

  useEffect(() => {
    if (!selected) return;
    closeRef.current?.focus();
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setSelected(null);
    };
    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [selected]);

  return (
    <>
      <div style={{ display: "contents" }}>
        {metrics.map((metric) => (
          <MetricButton key={metric} metric={metric} value={current?.metrics[metric] ?? null} onClick={() => setSelected(metric)} />
        ))}
      </div>

      {selected && definition && (
        <div
          role="presentation"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) setSelected(null);
          }}
          style={{ position: "fixed", inset: 0, zIndex: 1000, background: "rgba(0,0,0,.58)", padding: 20, overflowY: "auto" }}
        >
          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="metric-comparison-title"
            style={{ maxWidth: 720, margin: "6vh auto", background: "var(--bg-warm)", border: "1px solid var(--border)", borderRadius: 12, padding: 20, boxShadow: "0 18px 60px rgba(0,0,0,.3)" }}
          >
            <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", gap: 16 }}>
              <div>
                <div className="card-title" id="metric-comparison-title">Compară {definition.label}</div>
                <div style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 4 }}>Toate campaniile cu date disponibile</div>
              </div>
              <button ref={closeRef} type="button" onClick={() => setSelected(null)} className="btn btn-sm" aria-label="Închide comparația">Închide</button>
            </div>

            <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 10, margin: "18px 0" }}>
              <div><div style={{ fontSize: 11, color: "var(--text-muted)" }}>Campania curentă</div><strong style={{ fontSize: 20 }}>{currentValue == null ? "-" : definition.format(currentValue)}</strong></div>
              <div><div style={{ fontSize: 11, color: "var(--text-muted)" }}>Media</div><strong style={{ fontSize: 20 }}>{values.length ? definition.format(average(values)) : "-"}</strong></div>
              <div><div style={{ fontSize: 11, color: "var(--text-muted)" }}>Poziție</div><strong style={{ fontSize: 20 }}>{rank ? `${rank} din ${rows.length}` : "-"}</strong></div>
            </div>

            <div style={{ overflowX: "auto" }}>
              <table className="wp-table">
                <thead><tr><th>Campanie</th><th style={{ textAlign: "right" }}>Valoare</th><th style={{ textAlign: "right" }}>Față de medie</th></tr></thead>
                <tbody>
                  {rows.map((row) => {
                    const value = row.metrics[selected]!;
                    const diff = values.length ? value - average(values) : 0;
                    return (
                      <tr key={row.id} style={row.id === campaignId ? { background: "var(--bg)" } : undefined}>
                        <td style={{ fontWeight: row.id === campaignId ? 700 : undefined }}>{row.name}{row.id === campaignId ? " (aceasta)" : ""}</td>
                        <td style={{ textAlign: "right", whiteSpace: "nowrap" }}>{definition.format(value)}</td>
                        <td style={{ textAlign: "right", whiteSpace: "nowrap", color: diff === 0 ? "var(--text-muted)" : diff * (definition.better === "high" ? 1 : -1) > 0 ? "#1a7f37" : "#d63638" }}>{diff === 0 ? "media" : `${diff > 0 ? "+" : ""}${definition.format(diff)}`}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            {values.length > 0 && <p style={{ fontSize: 12, color: "var(--text-muted)", margin: "14px 0 0" }}>Mediana: {definition.format(median(values))}. Pentru costuri mai mic este mai bine; pentru ROAS și CTR mai mare este mai bine.</p>}
          </div>
        </div>
      )}
    </>
  );
}
