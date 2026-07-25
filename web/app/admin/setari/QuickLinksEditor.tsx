"use client";

import { useRef, useState } from "react";
import { saveQuickLinks } from "./actions";

export type QuickLink = { icon?: string; label?: string; url?: string };

type Row = QuickLink & { _key: number };

export default function QuickLinksEditor({ links }: { links: QuickLink[] }) {
  const nextKey = useRef(links.length);
  const [rows, setRows] = useState<Row[]>(links.map((l, i) => ({ ...l, _key: i })));

  return (
    <form action={saveQuickLinks}>
      <div style={{ display: "flex", flexDirection: "column", gap: 8, marginBottom: 14 }}>
        {rows.map((ql) => (
          <div
            key={ql._key}
            style={{ display: "grid", gridTemplateColumns: "60px 1fr 3fr auto", gap: 8, alignItems: "center" }}
          >
            {/* PHP (config-tab.php:23-25): inputurile nu sunt în .form-group, deci rămân pe stilul implicit */}
            <input type="text" name="ql_icon" defaultValue={ql.icon ?? "🔗"} style={{ textAlign: "center", fontSize: 18 }} />
            <input type="text" name="ql_label" defaultValue={ql.label ?? ""} />
            <input type="text" name="ql_url" defaultValue={ql.url ?? ""} />
            <button
              type="button"
              onClick={() => setRows(rows.filter((r) => r._key !== ql._key))}
              className="btn btn-danger btn-sm"
              style={{ whiteSpace: "nowrap" }}
            >
              ✕
            </button>
          </div>
        ))}
      </div>
      <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
        <button
          type="button"
          onClick={() => setRows([...rows, { icon: "🔗", label: "", url: "", _key: nextKey.current++ }])}
          className="btn btn-secondary btn-sm"
        >
          + Adaugă link
        </button>
        <button type="submit" className="btn btn-primary btn-sm">
          Salvează
        </button>
      </div>
    </form>
  );
}
