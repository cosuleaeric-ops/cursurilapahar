import { sql } from "@/lib/db";

export const dynamic = "force-dynamic";

const VARIANTS: Record<string, string> = {
  off: "cardurile ca înainte (fără buton)",
  on: "cardurile cu butonul „Vreau să vin”",
};

export default async function AbPage() {
  const rows = (await sql`
    SELECT variant, views, conversions FROM ab_experiments WHERE experiment = 'button'
  `) as { variant: string; views: number; conversions: number }[];
  const stats = Object.fromEntries(rows.map((r) => [r.variant, r]));
  const ctr = (v: string) => {
    const s = stats[v];
    return s && s.views > 0 ? (s.conversions / s.views) * 100 : 0;
  };
  const totalViews = rows.reduce((a, r) => a + r.views, 0);
  const leader = totalViews > 0 && ctr("on") !== ctr("off") ? (ctr("on") > ctr("off") ? "on" : "off") : "";

  return (
    <>
      <h1 className="wp-page-title">Test A/B — Buton „Vreau să vin"</h1>
      <p style={{ color: "var(--text-muted)", fontSize: 13, marginBottom: 20 }}>
        Jumătate din vizitatori (aleatoriu, cookie 90 de zile) văd un buton galben „Vreau să vin" pe fiecare card de
        curs, jumătate nu. Click = ajungere pe pagina de bilete prin card sau buton. Boții și prefetch-urile nu sunt
        numărate.
      </p>

      <div className="card">
        <div style={{ overflowX: "auto" }}>
          <table className="wp-table" style={{ maxWidth: 980 }}>
            <thead>
              <tr>
                <th>Variantă</th>
                <th>Afișări</th>
                <th>Click-uri</th>
                <th>CTR</th>
              </tr>
            </thead>
            <tbody>
              {Object.entries(VARIANTS).map(([v, label]) => (
                <tr key={v} style={leader === v ? { fontWeight: 700 } : undefined}>
                  <td>
                    <strong>{v.toUpperCase()}</strong>{" "}
                    <span style={{ color: "var(--text-muted)", fontWeight: 400 }}>— {label}</span>
                    {leader === v && " 🏆"}
                  </td>
                  <td>{stats[v]?.views ?? 0}</td>
                  <td>{stats[v]?.conversions ?? 0}</td>
                  <td>{ctr(v).toFixed(1)}%</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {totalViews === 0 && (
          <p style={{ color: "var(--text-muted)", fontSize: 13, marginTop: 10 }}>Nu există date încă.</p>
        )}
      </div>
    </>
  );
}
