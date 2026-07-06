import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { addVenit, addCheltuiala, deleteVenit, deleteCheltuiala } from "./actions";

export const dynamic = "force-dynamic";

const lei0 = (n: number) => new Intl.NumberFormat("ro-RO", { maximumFractionDigits: 0 }).format(n) + " lei";
const lei2 = (n: number) => new Intl.NumberFormat("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + " lei";
const roMonth = (ym: string) => {
  const [y, m] = ym.split("-");
  const d = new Date(Number(y), Number(m) - 1, 1);
  const s = new Intl.DateTimeFormat("ro-RO", { month: "long", year: "numeric" }).format(d);
  return s.charAt(0).toUpperCase() + s.slice(1);
};

type Row = Record<string, string | number | null>;

export default async function PnlPage() {
  const session = await getSession();
  if (!session) redirect("/login");
  if (session.role !== "owner") redirect("/admin");

  const [tot] = (await sql`
    SELECT
      (SELECT COALESCE(sum(suma),0)::float FROM venituri) AS venituri,
      (SELECT COALESCE(sum(ch.suma),0)::float FROM cheltuieli ch JOIN cheltuiala_categorii cc ON cc.id=ch.categorie_id WHERE lower(cc.nume) <> 'dividende') AS chelt_op,
      (SELECT COALESCE(sum(ch.suma),0)::float FROM cheltuieli ch JOIN cheltuiala_categorii cc ON cc.id=ch.categorie_id WHERE lower(cc.nume) = 'dividende') AS dividende
  `) as Row[];
  const venituri = Number(tot.venituri);
  const cheltOp = Number(tot.chelt_op);
  const dividende = Number(tot.dividende);
  const profit = venituri - cheltOp;

  const vLuni = (await sql`SELECT to_char(data,'YYYY-MM') m, sum(suma)::float s FROM venituri GROUP BY 1`) as Row[];
  const cLuni = (await sql`
    SELECT to_char(ch.data,'YYYY-MM') m, sum(ch.suma)::float s
    FROM cheltuieli ch JOIN cheltuiala_categorii cc ON cc.id=ch.categorie_id
    WHERE lower(cc.nume) <> 'dividende' GROUP BY 1`) as Row[];
  const months = new Map<string, { v: number; c: number }>();
  for (const r of vLuni) months.set(String(r.m), { v: Number(r.s), c: 0 });
  for (const r of cLuni) {
    const cur = months.get(String(r.m)) ?? { v: 0, c: 0 };
    cur.c = Number(r.s);
    months.set(String(r.m), cur);
  }
  const monthly = [...months.entries()].sort((a, b) => b[0].localeCompare(a[0]));

  const cats = (await sql`
    SELECT cc.nume, COALESCE(sum(ch.suma),0)::float s
    FROM cheltuiala_categorii cc LEFT JOIN cheltuieli ch ON ch.categorie_id=cc.id
    GROUP BY cc.nume HAVING COALESCE(sum(ch.suma),0) > 0 ORDER BY s DESC`) as Row[];

  const catList = (await sql`SELECT id, nume FROM cheltuiala_categorii ORDER BY nume`) as Row[];
  const recentV = (await sql`SELECT id, data, descriere, suma::float FROM venituri ORDER BY data DESC, id DESC LIMIT 12`) as Row[];
  const recentC = (await sql`
    SELECT ch.id, ch.data, ch.descriere, ch.suma::float, cc.nume categorie
    FROM cheltuieli ch JOIN cheltuiala_categorii cc ON cc.id=ch.categorie_id
    ORDER BY ch.data DESC, ch.id DESC LIMIT 12`) as Row[];

  const section: React.CSSProperties = { display: "grid", gridTemplateColumns: "1fr 1fr", gap: 20 };
  const td: React.CSSProperties = { textAlign: "right", fontVariantNumeric: "tabular-nums" };
  const delBtn = { border: "none", background: "none", color: "var(--danger)", cursor: "pointer", fontSize: 12, fontWeight: 600 };

  return (
    <>
      <h1 className="wp-page-title">P&amp;L Cursuri</h1>

      <div className="dash-grid" style={{ gridTemplateColumns: "repeat(auto-fit, minmax(170px, 1fr))" }}>
        <div className="dash-card accent-green">
          <div className="dash-label">Venituri</div>
          <div className="dash-value">{lei0(venituri)}</div>
        </div>
        <div className="dash-card accent-red">
          <div className="dash-label">Cheltuieli operaționale</div>
          <div className="dash-value">{lei0(cheltOp)}</div>
        </div>
        <div className="dash-card accent-blue">
          <div className="dash-label">Profit</div>
          <div className={`dash-value ${profit >= 0 ? "positive" : "negative"}`}>{lei0(profit)}</div>
        </div>
        <div className="dash-card accent-gold">
          <div className="dash-label">Dividende</div>
          <div className="dash-value">{lei0(dividende)}</div>
        </div>
      </div>

      <div style={section}>
        <div className="dash-section">
          <div className="dash-section-title">Lunar</div>
          <table className="dash-table">
            <tbody>
              <tr style={{ fontSize: 11, color: "var(--text-muted)", textTransform: "uppercase" }}>
                <td>Luna</td>
                <td style={td}>Venituri</td>
                <td style={td}>Cheltuieli</td>
                <td style={td}>Profit</td>
              </tr>
              {monthly.map(([m, x]) => (
                <tr key={m}>
                  <td>{roMonth(m)}</td>
                  <td style={td}>{lei0(x.v)}</td>
                  <td style={td}>{lei0(x.c)}</td>
                  <td style={{ ...td, color: x.v - x.c >= 0 ? "var(--success)" : "var(--danger)", fontWeight: 600 }}>
                    {lei0(x.v - x.c)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="dash-section">
          <div className="dash-section-title">Cheltuieli pe categorii</div>
          <table className="dash-table">
            <tbody>
              {cats.map((r) => (
                <tr key={String(r.nume)}>
                  <td>{String(r.nume)}</td>
                  <td style={td}>{lei0(Number(r.s))}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div style={section}>
        <div className="dash-section">
          <div className="dash-section-title">Adaugă venit</div>
          <form action={addVenit}>
            <div style={{ display: "flex", gap: 10 }}>
              <div className="form-group" style={{ flex: "0 0 140px", marginBottom: 10 }}>
                <label>Data</label>
                <input name="data" type="date" required />
              </div>
              <div className="form-group" style={{ flex: 1, marginBottom: 10 }}>
                <label>Descriere</label>
                <input name="descriere" type="text" required />
              </div>
              <div className="form-group" style={{ flex: "0 0 110px", marginBottom: 10 }}>
                <label>Sumă</label>
                <input name="suma" type="number" step="0.01" required />
              </div>
            </div>
            <button type="submit" className="btn btn-primary btn-sm">
              Adaugă venit
            </button>
          </form>
        </div>

        <div className="dash-section">
          <div className="dash-section-title">Adaugă cheltuială</div>
          <form action={addCheltuiala}>
            <div style={{ display: "flex", gap: 10, flexWrap: "wrap" }}>
              <div className="form-group" style={{ flex: "0 0 130px", marginBottom: 10 }}>
                <label>Data</label>
                <input name="data" type="date" required />
              </div>
              <div className="form-group" style={{ flex: 1, minWidth: 120, marginBottom: 10 }}>
                <label>Descriere</label>
                <input name="descriere" type="text" required />
              </div>
              <div className="form-group" style={{ flex: "0 0 100px", marginBottom: 10 }}>
                <label>Sumă</label>
                <input name="suma" type="number" step="0.01" required />
              </div>
              <div className="form-group" style={{ flex: "0 0 150px", marginBottom: 10 }}>
                <label>Categorie</label>
                <select name="categorie_id" required defaultValue="">
                  <option value="" disabled>
                    —
                  </option>
                  {catList.map((c) => (
                    <option key={String(c.id)} value={String(c.id)}>
                      {String(c.nume)}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <button type="submit" className="btn btn-primary btn-sm">
              Adaugă cheltuială
            </button>
          </form>
        </div>
      </div>

      <div style={section}>
        <div className="dash-section">
          <div className="dash-section-title">Ultimele venituri</div>
          <table className="dash-table">
            <tbody>
              {recentV.map((r) => (
                <tr key={String(r.id)}>
                  <td style={{ whiteSpace: "nowrap", color: "var(--text-muted)", fontSize: 12 }}>{String(r.data)}</td>
                  <td>{String(r.descriere)}</td>
                  <td style={td}>{lei2(Number(r.suma))}</td>
                  <td style={{ textAlign: "right", width: 1 }}>
                    <form action={deleteVenit} style={{ margin: 0 }}>
                      <input type="hidden" name="id" value={String(r.id)} />
                      <button type="submit" style={delBtn}>
                        ✕
                      </button>
                    </form>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="dash-section">
          <div className="dash-section-title">Ultimele cheltuieli</div>
          <table className="dash-table">
            <tbody>
              {recentC.map((r) => (
                <tr key={String(r.id)}>
                  <td style={{ whiteSpace: "nowrap", color: "var(--text-muted)", fontSize: 12 }}>{String(r.data)}</td>
                  <td>
                    {String(r.descriere)}
                    <span style={{ color: "var(--text-muted)", fontSize: 11 }}> · {String(r.categorie)}</span>
                  </td>
                  <td style={td}>{lei2(Number(r.suma))}</td>
                  <td style={{ textAlign: "right", width: 1 }}>
                    <form action={deleteCheltuiala} style={{ margin: 0 }}>
                      <input type="hidden" name="id" value={String(r.id)} />
                      <button type="submit" style={delBtn}>
                        ✕
                      </button>
                    </form>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}
