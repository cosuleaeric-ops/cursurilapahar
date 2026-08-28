"use client";

import { useRef, useState } from "react";
import type { MarketingCompetitor } from "@/lib/marketing-competitors";
import { saveMarketingCompetitors } from "./actions";

type Row = MarketingCompetitor & { _key: number };

function move<T>(items: T[], from: number, to: number): T[] {
  const next = [...items];
  const [item] = next.splice(from, 1);
  next.splice(to, 0, item);
  return next;
}

export default function CompetitorsEditor({ competitors }: { competitors: MarketingCompetitor[] }) {
  const nextKey = useRef(competitors.length);
  const [rows, setRows] = useState<Row[]>(competitors.map((c, i) => ({ ...c, _key: i })));

  return (
    <form action={saveMarketingCompetitors} className="competitors-editor">
      <div className="competitors-editor-head" aria-hidden="true">
        <span>Nume</span>
        <span>Instagram</span>
        <span>TikTok</span>
        <span>Website</span>
        <span>Ordine</span>
      </div>
      <div className="competitors-editor-list">
        {rows.map((competitor, index) => (
          <div className="competitors-editor-row" key={competitor._key}>
            <label>
              <span>Nume</span>
              <input type="text" name="comp_name" defaultValue={competitor.name} />
            </label>
            <label>
              <span>Instagram</span>
              <input type="url" name="comp_ig" defaultValue={competitor.ig} />
            </label>
            <label>
              <span>TikTok</span>
              <input type="url" name="comp_tt" defaultValue={competitor.tt} />
            </label>
            <label>
              <span>Website</span>
              <input type="url" name="comp_web" defaultValue={competitor.web} />
            </label>
            <div className="competitors-editor-actions">
              <button
                type="button"
                className="btn btn-secondary btn-sm"
                onClick={() => setRows(move(rows, index, index - 1))}
                disabled={index === 0}
              >
                ↑
              </button>
              <button
                type="button"
                className="btn btn-secondary btn-sm"
                onClick={() => setRows(move(rows, index, index + 1))}
                disabled={index === rows.length - 1}
              >
                ↓
              </button>
              <button
                type="button"
                className="btn btn-danger btn-sm"
                onClick={() => setRows(rows.filter((r) => r._key !== competitor._key))}
              >
                Șterge
              </button>
            </div>
          </div>
        ))}
      </div>
      <div className="competitors-editor-footer">
        <button
          type="button"
          className="btn btn-secondary btn-sm"
          onClick={() => setRows([...rows, { name: "", ig: "", tt: "", web: "", _key: nextKey.current++ }])}
        >
          + Adaugă competitor
        </button>
        <button type="submit" className="btn btn-primary btn-sm">
          Salvează competitorii
        </button>
      </div>
    </form>
  );
}
