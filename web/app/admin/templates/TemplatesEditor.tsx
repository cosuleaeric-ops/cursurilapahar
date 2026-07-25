"use client";

import { useRef, useState } from "react";
import { saveTemplates, type Template } from "./actions";

type Row = Template & { _key: number };

export function CopyButton({
  text,
  className = "tpl-copy-btn",
  label,
  icon,
}: {
  text: string;
  className?: string;
  label?: string;
  icon?: string;
}) {
  const [done, setDone] = useState(false);
  const copy = (e: React.MouseEvent) => {
    e.stopPropagation();
    navigator.clipboard?.writeText(text).then(
      () => {
        setDone(true);
        setTimeout(() => setDone(false), 1200);
      },
      () => {}
    );
  };
  // pe dashboard butonul arată emoji + eticheta template-ului
  if (label !== undefined) {
    return (
      <button type="button" className={className} onClick={copy}>
        <span style={{ fontSize: 15 }}>{icon ?? "📋"}</span>
        {done ? "✓ Copiat" : label}
      </button>
    );
  }
  return (
    <button type="button" className={className} onClick={copy}>
      {done ? "✓ Copiat" : "📋 Copiază"}
    </button>
  );
}

function Card({ row, onChange, onDelete }: { row: Row; onChange: (patch: Template) => void; onDelete: () => void }) {
  const [open, setOpen] = useState(false);
  const icon = row.icon ?? "📋";
  const label = row.label ?? "";
  const text = row.text ?? "";

  return (
    <div className={`tpl-card${open ? " open" : ""}`}>
      <div className="tpl-view" onClick={() => setOpen(!open)}>
        <span className="tpl-chevron">▸</span>
        <span className="tpl-view-icon">{icon}</span>
        <div className="tpl-view-main">
          <div className="tpl-view-title">{label || "Template fără titlu"}</div>
          <div className="tpl-view-preview">{text || "gol"}</div>
        </div>
        <CopyButton text={text} />
      </div>
      {open && (
        <div className="tpl-edit">
          <label className="tpl-lbl">Emoji &amp; titlu</label>
          <div style={{ display: "flex", gap: 8 }}>
            <input
              type="text"
              name="tpl_icon"
              value={icon}
              onChange={(e) => onChange({ icon: e.target.value })}
              style={{ width: 56, textAlign: "center", fontSize: 18 }}
            />
            <input
              type="text"
              name="tpl_label"
              value={label}
              onChange={(e) => onChange({ label: e.target.value })}
              style={{ flex: 1, fontWeight: 600 }}
            />
          </div>
          <label className="tpl-lbl">Text mesaj</label>
          <textarea name="tpl_text" rows={6} value={text} onChange={(e) => onChange({ text: e.target.value })} />
          <div className="tpl-edit-actions">
            <button type="button" className="btn btn-secondary btn-sm" onClick={() => setOpen(false)}>
              Închide
            </button>
            <button type="button" className="btn btn-danger btn-sm tpl-del" onClick={onDelete}>
              Șterge
            </button>
          </div>
        </div>
      )}
      {!open && (
        <>
          <input type="hidden" name="tpl_icon" value={icon} />
          <input type="hidden" name="tpl_label" value={label} />
          <input type="hidden" name="tpl_text" value={text} />
        </>
      )}
    </div>
  );
}

export default function TemplatesEditor({ templates }: { templates: Template[] }) {
  const nextKey = useRef(templates.length);
  const [rows, setRows] = useState<Row[]>(templates.map((t, i) => ({ ...t, _key: i })));

  return (
    <form action={saveTemplates}>
      <div>
        {rows.map((r) => (
          <Card
            key={r._key}
            row={r}
            onChange={(patch) => setRows(rows.map((x) => (x._key === r._key ? { ...x, ...patch } : x)))}
            onDelete={() => setRows(rows.filter((x) => x._key !== r._key))}
          />
        ))}
      </div>
      <div style={{ display: "flex", gap: 8, flexWrap: "wrap", marginTop: 14 }}>
        <button
          type="button"
          className="btn btn-secondary btn-sm"
          onClick={() => setRows([...rows, { icon: "📋", label: "", text: "", _key: nextKey.current++ }])}
        >
          + Adaugă template
        </button>
        <button type="submit" className="btn btn-primary btn-sm">
          Salvează
        </button>
      </div>
    </form>
  );
}
