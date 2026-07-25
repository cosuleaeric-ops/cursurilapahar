// Calendarul lunii cu cursurile marcate — port din calRender() (admin-cursuri-stats.js).
// Server component: luna vine din searchParams, deci nu are nevoie de JS.

const DOW = ["Lu", "Ma", "Mi", "Jo", "Vi", "Sâ", "Du"];

export default function Calendar({
  year,
  month,
  courses,
  today,
}: {
  year: number;
  month: number;
  courses: { date: string; title: string }[];
  today: string;
}) {
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
      <div className={`cal-cell${isToday ? " today" : ""}`} data-date={ds} key={ds}>
        <div className="cal-day-num">{isToday ? <span className="cal-circle">{day}</span> : day}</div>
        {events.map((t, i) => (
          <div className={`cal-event${ds < today ? " past" : ""}`} title={t} key={i}>
            {t}
          </div>
        ))}
      </div>
    );
  }

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
