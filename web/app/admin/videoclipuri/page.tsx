import { redirect } from "next/navigation";
import { getRealSession } from "@/lib/auth";
import date from "@/data/videoclipuri.json";
import ListaClipuri, { type Clip } from "./ListaClipuri";
import Reguli, { type Regula } from "./Reguli";

export const dynamic = "force-dynamic";

type Folder = { nume: string; n: number; folosite: number; respinse: number; locatii: string[] };

export default async function VideoclipuriPage() {
  // getRealSession, nu getSession: pagina rămâne a lui Eric chiar și când impersonează alt user
  const real = await getRealSession();
  if (!real) redirect("/login");
  if (real.role !== "owner") redirect("/admin");

  const d = date as unknown as {
    generat: string;
    total_pe_disc: number;
    total_catalogate: number;
    necatalogate: number;
    folosite: number;
    respinse: number;
    disponibile: number;
    pe_tip: Record<string, number>;
    pe_locatie: Record<string, number>;
    pe_miscare: Record<string, number>;
    reguli: Regula[];
    foldere: Folder[];
    clipuri: Clip[];
  };

  const kpi = [
    { eticheta: "Pe disc", val: d.total_pe_disc, sub: "fișiere video în Drive" },
    { eticheta: "Scanate", val: d.total_catalogate, sub: "analizate cadru cu cadru" },
    { eticheta: "Disponibile", val: d.disponibile, sub: "gata de folosit" },
    { eticheta: "În montaje finale", val: d.folosite, sub: "blocate definitiv" },
    { eticheta: "Respinse", val: d.respinse, sub: "eliminate de tine" },
    { eticheta: "Necatalogate", val: d.necatalogate, sub: "cursuri noi, de scanat" },
  ];

  return (
    <>
      <h1 className="wp-page-title">Videoclipuri</h1>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(150px,1fr))", gap: 12, marginBottom: 18 }}>
        {kpi.map((k) => (
          <div key={k.eticheta} className="card" style={{ padding: "14px 16px" }}>
            <div style={{ fontSize: 28, fontWeight: 700, lineHeight: 1.1 }}>{k.val}</div>
            <div style={{ fontWeight: 600, fontSize: 13 }}>{k.eticheta}</div>
            <div style={{ fontSize: 11, opacity: 0.6 }}>{k.sub}</div>
          </div>
        ))}
      </div>

      <Reguli reguli={d.reguli} />

      <div className="card">
        <div className="card-title">Foldere ({d.foldere.length})</div>
        <div style={{ overflowX: "auto" }}>
          <table className="wp-table" style={{ width: "100%", fontSize: 13 }}>
            <thead>
              <tr>
                <th style={{ textAlign: "left" }}>Folder</th>
                <th>Clipuri</th>
                <th>Folosite</th>
                <th>Respinse</th>
                <th style={{ textAlign: "left" }}>Local</th>
              </tr>
            </thead>
            <tbody>
              {d.foldere.map((f) => (
                <tr key={f.nume}>
                  <td>{f.nume}</td>
                  <td style={{ textAlign: "center" }}>{f.n}</td>
                  <td style={{ textAlign: "center", opacity: f.folosite ? 1 : 0.3 }}>{f.folosite || "—"}</td>
                  <td style={{ textAlign: "center", opacity: f.respinse ? 1 : 0.3 }}>{f.respinse || "—"}</td>
                  <td style={{ fontSize: 12, opacity: 0.7 }}>{f.locatii.join(", ")}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="card">
        <div className="card-title">Distribuție</div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(220px,1fr))", gap: 18 }}>
          {([["Tip", d.pe_tip], ["Local", d.pe_locatie], ["Mișcare cameră", d.pe_miscare]] as const).map(
            ([titlu, obj]) => (
              <div key={titlu}>
                <div style={{ fontWeight: 600, marginBottom: 6 }}>{titlu}</div>
                {Object.entries(obj).map(([k, v]) => (
                  <div key={k} style={{ display: "flex", justifyContent: "space-between", fontSize: 13, padding: "2px 0" }}>
                    <span style={{ opacity: 0.75 }}>{k}</span>
                    <strong>{v}</strong>
                  </div>
                ))}
              </div>
            ),
          )}
        </div>
      </div>

      <ListaClipuri clipuri={d.clipuri} generat={d.generat} />
    </>
  );
}
