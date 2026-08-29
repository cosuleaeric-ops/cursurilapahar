import type { Metadata } from "next";
import { sql } from "@/lib/db";

export const dynamic = "force-dynamic";

// layout_header.php:15 randează „<$__page_title> — Admin”, cu $__page_title = 'Test A/B Buton'.
export const metadata: Metadata = { title: "Test A/B Buton - Admin" };

// Port din admin/statistici/ab_headline.php.
const VARIANTS: [string, string][] = [
  ["off", "cardurile ca înainte (fără buton)"],
  // ab_headline.php:12 închide citatul cu ghilimea dreaptă ASCII, nu cu una curbă.
  ["on", 'cardurile cu butonul „Vezi detalii & bilete"'],
];
const LABEL: Record<string, string> = { on: "Cu buton", off: "Fără buton" };

const nf = new Intl.NumberFormat("en-US");
const pct = (n: number) => n.toFixed(2);

export default async function AbPage() {
  const rows = (await sql`
    SELECT variant, views, conversions FROM ab_experiments WHERE experiment = 'button'
  `) as { variant: string; views: number; conversions: number }[];
  const stats = Object.fromEntries(rows.map((r) => [r.variant, r]));
  const views = (v: string) => stats[v]?.views ?? 0;
  const clicks = (v: string) => stats[v]?.conversions ?? 0;
  const ctr = (v: string) => (views(v) > 0 ? (clicks(v) / views(v)) * 100 : 0);

  const totalViews = views("on") + views("off");
  const leader = totalViews > 0 && ctr("on") !== ctr("off") ? (ctr("on") > ctr("off") ? "on" : "off") : "";
  const other = leader === "on" ? "off" : "on";

  return (
    <>
      <h1 className="wp-page-title">Test A/B - Buton „Vezi detalii &amp; bilete&quot;</h1>
      <p style={{ color: "var(--text-muted)", fontSize: 13, marginBottom: 20 }}>
        Jumătate din vizitatori (aleatoriu, cookie 90 de zile) văd un buton galben „Vezi detalii &amp; bilete&quot; pe fiecare card
        de curs, jumătate nu. Click = ajungere pe pagina de bilete prin card sau buton. Boții și prefetch-urile nu sunt
        numărate.
      </p>

      <div style={{ overflowX: "auto" }}>
        <table className="table" style={{ maxWidth: 980 }}>
          <thead>
            <tr>
              <th>Variantă</th>
              <th>Descriere</th>
              <th style={{ textAlign: "right" }}>Afișări</th>
              <th style={{ textAlign: "right" }}>Click-uri cursuri</th>
              <th style={{ textAlign: "right" }}>CTR</th>
            </tr>
          </thead>
          <tbody>
            {VARIANTS.map(([v, desc]) => (
              <tr key={v}>
                <td style={{ fontWeight: 700 }}>
                  {LABEL[v]}
                  {v === leader ? " 🏆" : ""}
                </td>
                <td style={{ fontSize: 12, color: "var(--text-muted)" }}>{desc}</td>
                <td style={{ textAlign: "right" }}>{nf.format(views(v))}</td>
                <td style={{ textAlign: "right" }}>{nf.format(clicks(v))}</td>
                <td style={{ textAlign: "right", fontWeight: 600 }}>{pct(ctr(v))}%</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {totalViews === 0 ? (
        <p style={{ color: "var(--text-muted)", fontSize: 13, marginTop: 16 }}>
          Nu există date încă - testul pornește la primele vizite pe pagina principală.
        </p>
      ) : leader !== "" ? (
        <p style={{ fontSize: 13, marginTop: 16 }}>
          Varianta <strong>{leader === "on" ? "cu buton" : "fără buton"}</strong> conduce cu un CTR de{" "}
          <strong>{pct(ctr(leader))}%</strong> (față de {pct(ctr(other))}% cealaltă variantă).{" "}
          {totalViews < 750 && (
            <span style={{ color: "var(--text-muted)" }}>
              Sub ~750 de afișări totale diferența poate fi zgomot - mai lasă testul să ruleze.
            </span>
          )}
        </p>
      ) : null}
    </>
  );
}
