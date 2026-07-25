"use client";

import { useEffect, useRef, useState } from "react";
import { saveCourseIdeas, type IdeaCategory } from "./actions";

type Row = { emoji: string; title: string; topics: string; _key: number };

export default function IdeasEditor({ intro, categories }: { intro: string; categories: IdeaCategory[] }) {
  const nextKey = useRef(categories.length);
  const [rows, setRows] = useState<Row[]>(
    categories.map((c, i) => ({
      emoji: c.emoji ?? "",
      title: c.title ?? "",
      topics: (c.topics ?? []).join("\n"),
      _key: i,
    }))
  );

  const blocksRef = useRef<HTMLDivElement>(null);
  const focusLast = useRef(false);

  // ciAdd(): după adăugare, cursorul intră în titlul ultimului bloc
  // (cursuri-posibile-tab.php:59-65).
  useEffect(() => {
    if (!focusLast.current) return;
    focusLast.current = false;
    const inputs = blocksRef.current?.querySelectorAll<HTMLInputElement>('.ci-block input[name="cat_title"]');
    inputs?.[inputs.length - 1]?.focus();
  }, [rows.length]);

  const patch = (key: number, p: Partial<Row>) => setRows(rows.map((r) => (r._key === key ? { ...r, ...p } : r)));

  const move = (key: number, dir: -1 | 1) => {
    const i = rows.findIndex((r) => r._key === key);
    const j = i + dir;
    if (i < 0 || j < 0 || j >= rows.length) return;
    const next = [...rows];
    [next[i], next[j]] = [next[j], next[i]];
    setRows(next);
  };

  const SaveBtn = () => (
    <button type="submit" className="btn btn-sm btn-primary">
      Salvează tot
    </button>
  );

  return (
    <form action={saveCourseIdeas}>
      <div className="card">
        <div className="card-title" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          {/* Contorul e randat server-side în PHP, deci nu se mișcă la adăugare/ștergere
              până la salvare (cursuri-posibile-tab.php:10). */}
          <span>Cursuri posibile ({categories.length} categorii)</span>
          <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
            <a href="/cursuri-posibile" target="_blank" className="btn btn-sm btn-secondary">
              Vezi pagina ↗
            </a>
            <SaveBtn />
          </div>
        </div>
        <div className="form-group">
          <label>Text introductiv</label>
          <textarea name="ideas_intro" rows={3} defaultValue={intro} />
        </div>
      </div>

      <div ref={blocksRef} style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 14 }}>
        {rows.map((r) => (
          <div className="card ci-block" style={{ margin: 0 }} key={r._key}>
            <div style={{ display: "flex", gap: 8, alignItems: "center", marginBottom: 8 }}>
              <input
                type="text"
                name="cat_emoji"
                value={r.emoji}
                onChange={(e) => patch(r._key, { emoji: e.target.value })}
                style={{ width: 52, textAlign: "center" }}
                title="Emoji"
              />
              <input
                type="text"
                name="cat_title"
                value={r.title}
                onChange={(e) => patch(r._key, { title: e.target.value })}
                style={{ flex: 1, fontWeight: 700 }}
                required
              />
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => move(r._key, -1)} title="Mută sus">
                ↑
              </button>
              <button type="button" className="btn btn-sm btn-secondary" onClick={() => move(r._key, 1)} title="Mută jos">
                ↓
              </button>
              <button
                type="button"
                className="btn btn-sm btn-danger"
                title="Șterge"
                onClick={() => {
                  if (confirm("Ștergi categoria?")) setRows(rows.filter((x) => x._key !== r._key));
                }}
              >
                ✕
              </button>
            </div>
            <textarea
              name="cat_topics"
              rows={7}
              style={{ width: "100%" }}
              title="O temă pe linie"
              value={r.topics}
              onChange={(e) => patch(r._key, { topics: e.target.value })}
            />
          </div>
        ))}
      </div>

      <div style={{ display: "flex", gap: 8, marginTop: 14 }}>
        <button
          type="button"
          className="btn btn-sm btn-secondary"
          onClick={() => {
            focusLast.current = true;
            setRows([...rows, { emoji: "", title: "", topics: "", _key: nextKey.current++ }]);
          }}
        >
          + Adaugă categorie
        </button>
        <SaveBtn />
      </div>
    </form>
  );
}
