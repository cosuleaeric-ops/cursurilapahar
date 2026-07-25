"use client";

// Calendarul lunii cu cursurile marcate — port din calRender() + openDayMenu()
// (admin-cursuri-stats.js). Luna vine din searchParams; postările Instagram se
// bifează dintr-un dropdown pe zi.

import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { toggleIgPost } from "./actions";

const DOW = ["Lu", "Ma", "Mi", "Jo", "Vi", "Sâ", "Du"];
const RO_MONTHS = ["", "ianuarie", "februarie", "martie", "aprilie", "mai", "iunie", "iulie", "august", "septembrie", "octombrie", "noiembrie", "decembrie"];

// roDateLabel() din admin-cursuri-stats.js:265-268 — „14 mai 2026”.
const roDateLabel = (ds: string) => {
  const [y, m, d] = ds.split("-").map(Number);
  return `${d} ${RO_MONTHS[m]} ${y}`;
};

export default function Calendar({
  year,
  month,
  courses,
  today,
  igPosts,
  igPostTypes,
}: {
  year: number;
  month: number;
  courses: { date: string; title: string }[];
  today: string;
  igPosts: Record<string, string[]>;
  igPostTypes: Record<string, { label: string }>;
}) {
  const [posts, setPosts] = useState(igPosts);
  const [menu, setMenu] = useState<{ date: string; top: number; left: number } | null>(null);

  useEffect(() => setPosts(igPosts), [igPosts]);

  useEffect(() => {
    if (!menu) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setMenu(null);
    };
    const onAway = (e: MouseEvent) => {
      const t = e.target as HTMLElement;
      if (!t.closest(".cal-daymenu") && !t.closest(".cal-cell--pick")) setMenu(null);
    };
    document.addEventListener("keydown", onKey);
    document.addEventListener("click", onAway, true);
    return () => {
      document.removeEventListener("keydown", onKey);
      document.removeEventListener("click", onAway, true);
    };
  }, [menu]);

  const openDayMenu = (e: React.MouseEvent<HTMLDivElement>, ds: string) => {
    if (menu && menu.date === ds) {
      setMenu(null); // al doilea click pe aceeași zi închide meniul
      return;
    }
    const r = e.currentTarget.getBoundingClientRect();
    setMenu({ date: ds, top: r.bottom + window.scrollY + 4, left: Math.max(8, r.left + window.scrollX) });
  };

  const onToggle = async (ds: string, type: string) => {
    const on = !(posts[ds] ?? []).includes(type);
    try {
      const types = await toggleIgPost(ds, type, on);
      setPosts((prev) => {
        const next = { ...prev };
        if (types.length) next[ds] = types;
        else delete next[ds];
        return next;
      });
    } catch {
      alert("Nu am putut salva. Încearcă din nou.");
    }
  };

  const firstDow = (new Date(Date.UTC(year, month - 1, 1)).getUTCDay() + 6) % 7;
  const daysInMonth = new Date(Date.UTC(year, month, 0)).getUTCDate();
  const numRows = Math.ceil((firstDow + daysInMonth) / 7);

  const byDay = new Map<string, string[]>();
  for (const c of courses) {
    if (!c.date) continue;
    byDay.set(c.date, [...(byDay.get(c.date) ?? []), c.title]);
  }

  const cells = [];
  for (let i = 0; i < firstDow; i++) cells.push(<div className="cal-cell other-month" key={`e${i}`} />);
  for (let day = 1; day <= daysInMonth; day++) {
    const ds = `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
    const isToday = ds === today;
    const events = byDay.get(ds) ?? [];
    cells.push(
      <div
        className={`cal-cell cal-cell--pick${isToday ? " today" : ""}`}
        data-date={ds}
        key={ds}
        onClick={(e) => openDayMenu(e, ds)}
      >
        <div className="cal-day-num">{isToday ? <span className="cal-circle">{day}</span> : day}</div>
        {events.map((t, i) => (
          <div className={`cal-event ${isToday ? "today-ev" : ds < today ? "past" : "future"}`} title={t} key={i}>
            {t}
          </div>
        ))}
        {(posts[ds] ?? []).map((t, i) => {
          const label = igPostTypes[t]?.label ?? t;
          return (
            <div className="cal-event ig-post" title={label} key={`ig${i}`}>
              {label}
            </div>
          );
        })}
      </div>
    );
  }
  // celulele de umplere de la finalul ultimului rând (admin-cursuri-stats.js:249-250)
  const trailing = (7 - ((firstDow + daysInMonth) % 7)) % 7;
  for (let i = 0; i < trailing; i++) cells.push(<div className="cal-cell other-month" key={`t${i}`} />);

  return (
    <>
      <div
        id="calGrid"
        style={{ height: `calc(36px + ${numRows} * 120px)`, gridTemplateRows: `36px repeat(${numRows}, 1fr)` }}
      >
        {DOW.map((d) => (
          <div className="cal-dow" key={d}>
            {d}
          </div>
        ))}
        {cells}
      </div>
      {menu &&
        createPortal(
          <div className="cal-daymenu" style={{ top: menu.top, left: menu.left }}>
            <div className="cal-daymenu-title">{roDateLabel(menu.date)}</div>
            {Object.keys(igPostTypes).map((t) => {
              const on = (posts[menu.date] ?? []).includes(t);
              return (
                <button
                  type="button"
                  className={`cal-daymenu-opt${on ? " on" : ""}`}
                  data-type={t}
                  key={t}
                  onClick={() => onToggle(menu.date, t)}
                >
                  <span className="cal-daymenu-check">{on ? "✓" : ""}</span>
                  {igPostTypes[t].label}
                </button>
              );
            })}
          </div>,
          document.body,
        )}
      <div style={{ display: "flex", gap: 16, marginTop: 12, fontSize: 12, color: "#6b7280", flexWrap: "wrap" }}>
        <span style={{ display: "flex", alignItems: "center", gap: 6 }}>
          <span style={{ width: 10, height: 10, borderRadius: 3, background: "#dbeafe", border: "1px solid #bfdbfe", display: "inline-block" }} /> Curs
          viitor
        </span>
        <span style={{ display: "flex", alignItems: "center", gap: 6 }}>
          <span style={{ width: 10, height: 10, borderRadius: 3, background: "#1d4ed8", display: "inline-block" }} /> Curs azi
        </span>
        <span style={{ display: "flex", alignItems: "center", gap: 6 }}>
          <span style={{ width: 10, height: 10, borderRadius: 3, background: "#f1f5f9", border: "1px solid #e5e7eb", display: "inline-block" }} /> Curs
          trecut
        </span>
      </div>
    </>
  );
}
