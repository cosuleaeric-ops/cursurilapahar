"use client";

import { useState } from "react";

// Port din admin/assets/js/admin-dashboard.js — calendarul „Urmatoarele cursuri"
// de pe dashboard: lună navigabilă, cursuri pe zi, postările IG marcate galben.
const DOW = ["Lu", "Ma", "Mi", "Jo", "Vi", "Sâ", "Du"];

const ymd = (d: Date) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;

export default function MiniCal({
  today,
  coursesByDay,
  igPosts,
  igPostTypes,
}: {
  today: string;
  coursesByDay: Record<string, string[]>;
  igPosts: Record<string, string[]>;
  igPostTypes: Record<string, { label: string }>;
}) {
  const [offset, setOffset] = useState(0);

  const base = new Date(`${today}T12:00:00`);
  const view = new Date(base.getFullYear(), base.getMonth() + offset, 1);
  const y = view.getFullYear();
  const m = view.getMonth();

  const lastDate = new Date(y, m + 1, 0).getDate();
  const firstDow = new Date(y, m, 1).getDay() === 0 ? 7 : new Date(y, m, 1).getDay();
  const weeks = Math.ceil((firstDow - 1 + lastDate) / 7);

  const cells: React.ReactNode[] = [];
  for (let i = 0; i < weeks * 7; i++) {
    const cur = new Date(y, m, 1 - (firstDow - 1) + i);
    const ds = ymd(cur);
    const isToday = ds === today;
    const isPast = ds < today;
    const cls = isToday ? "today" : isPast ? "past" : "";
    cells.push(
      <div className={`mini-cal-cell ${cls}`} key={ds}>
        <div className="mini-cal-day">{cur.getDate()}</div>
        {(coursesByDay[ds] ?? []).map((t, k) => (
          <div className={`mini-cal-event ${isToday ? "today-ev" : isPast ? "past" : "future"}`} title={t} key={k}>
            {t}
          </div>
        ))}
        {(igPosts[ds] ?? []).map((t, k) => {
          const label = igPostTypes[t]?.label ?? t;
          return (
            <div className="mini-cal-event ig-post" title={label} key={`ig${k}`}>
              {label}
            </div>
          );
        })}
      </div>,
    );
  }

  return (
    <div className="dash-section" style={{ marginBottom: 20 }}>
      <div className="dash-section-title" style={{ marginBottom: 10 }}>
        <div className="dash-cal-heading">
          <span>Urmatoarele cursuri</span>
          <button type="button" className="dash-cal-arrow" onClick={() => setOffset(offset - 1)} aria-label="Luna anterioară">
            ←
          </button>
          <button type="button" className="dash-cal-arrow" onClick={() => setOffset(offset + 1)} aria-label="Luna următoare">
            →
          </button>
        </div>
      </div>
      <div className="mini-cal">
        {DOW.map((d) => (
          <div className="mini-cal-dow" key={d}>
            {d}
          </div>
        ))}
        {cells}
      </div>
    </div>
  );
}
