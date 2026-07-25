import Link from "next/link";
import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { ditlBase, vanduteForTarif, participantNameKey, type TicketType } from "@/lib/statistici";
import { updateParticipants, addVizaSubtip, deleteVizaSubtip, dedupVizaSubtips, deleteRaport, uploadRaport, uploadViza } from "./actions";

export const dynamic = "force-dynamic";

const TZ = "Europe/Bucharest";
const dateFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, weekday: "long", day: "numeric", month: "long", year: "numeric" });
const dtFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "2-digit", month: "2-digit", year: "numeric" });
const ron = (v: number) => `${Number(v).toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RON`;

export default async function CourseStatsPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ err?: string; serii?: string }>;
}) {
  const { id: idStr } = await params;
  const { err, serii } = await searchParams;
  const id = Number(idStr);
  if (!id) notFound();

  const [event] = (await sql`
    SELECT id, title, starts_at, location, speaker_name FROM events WHERE id = ${id}
  `) as { id: number; title: string; starts_at: string | null; location: string | null; speaker_name: string | null }[];
  if (!event) notFound();

  const [report] = (await sql`
    SELECT total_bilete, total_incasari, types_json, original_name, blob_url, uploaded_at
    FROM event_reports WHERE event_id = ${id}
  `) as {
    total_bilete: string;
    total_incasari: string;
    types_json: TicketType[] | null;
    original_name: string;
    blob_url: string | null;
    uploaded_at: string;
  }[];

  const subtips = (await sql`
    SELECT id, seria, tarif, nr_unitati, de_la, pana_la FROM viza_subtips
    WHERE event_id = ${id} ORDER BY tarif DESC, seria
  `) as { id: number; seria: string; tarif: string; nr_unitati: number; de_la: string | null; pana_la: string | null }[];

  const tickets = (await sql`
    SELECT participant_name FROM tickets WHERE event_id = ${id} ORDER BY participant_name
  `) as { participant_name: string }[];

  // participanți fideli: au fost și la alte cursuri (aceeași cheie normalizată de nume)
  const others = (await sql`
    SELECT t.participant_name, count(DISTINCT t.event_id)::int AS n
    FROM tickets t WHERE t.event_id <> ${id} GROUP BY t.participant_name
  `) as { participant_name: string; n: number }[];
  const otherCounts = new Map<string, number>();
  for (const o of others) {
    const k = participantNameKey(o.participant_name);
    otherCounts.set(k, (otherCounts.get(k) ?? 0) + Number(o.n));
  }
  const loyal = tickets
    .map((t) => ({ name: t.participant_name, others: otherCounts.get(participantNameKey(t.participant_name)) ?? 0 }))
    .filter((p) => p.others > 0)
    .sort((a, b) => b.others - a.others);

  const types = (report?.types_json ?? []) as TicketType[];
  const base = report ? ditlBase(types, Number(report.total_bilete)) : 0;

  return (
    <>
      <h1 className="wp-page-title">{event.title}</h1>
      <p style={{ color: "var(--text-muted)", fontSize: 13, marginTop: -6 }}>
        {event.starts_at ? dateFmt.format(new Date(event.starts_at)) : "fără dată"}
        {event.location ? ` · ${event.location}` : ""}
        {event.speaker_name ? ` · ${event.speaker_name}` : ""}
        {" · "}
        <Link href="/admin/cursuri" style={{ color: "var(--accent)" }}>
          ← Toate cursurile
        </Link>
      </p>

      {err && <div className="notice notice-error">{err}</div>}
      {serii && <div className="notice notice-success">Viza procesată: {serii} serii extrase din PDF.</div>}

      <div className="card">
        <div className="card-title">Raport eveniment</div>
        <form action={uploadRaport} style={{ marginBottom: 14 }}>
          <input type="hidden" name="id" value={id} />
          <div style={{ display: "flex", gap: 8, alignItems: "center", flexWrap: "wrap" }}>
            <input type="file" name="raport_file" accept=".xlsx,.xls" required style={{ border: "1px solid var(--border)", padding: "6px 10px", borderRadius: 4, fontSize: 13, background: "#fff" }} />
            <button type="submit" className="btn btn-primary btn-sm">Încarcă raportul</button>
          </div>
          <p className="form-desc">Exportul complet al evenimentului (foaia „Vanzari") sau decontul LiveTickets (foaia „Decont").</p>
        </form>
        {!report ? (
          <p style={{ color: "var(--text-muted)" }}>Niciun raport încărcat încă.</p>
        ) : (
          <>
            <div className="clp-summary-grid" style={{ marginBottom: 14 }}>
              <div className="clp-stat-box">
                <div className="lbl">Bilete</div>
                <div className="val">{Number(report.total_bilete).toLocaleString("ro-RO")}</div>
              </div>
              <div className="clp-stat-box">
                <div className="lbl">Încasări</div>
                <div className="val">{ron(Number(report.total_incasari))}</div>
              </div>
              <div className="clp-stat-box">
                <div className="lbl">Taxă DITL (2%)</div>
                <div className="val ditl">{ron(base * 0.02)}</div>
              </div>
            </div>
            <p style={{ fontSize: 12, color: "var(--text-muted)" }}>
              {report.original_name || "raport"} · încărcat {dtFmt.format(new Date(report.uploaded_at))}
              {report.blob_url && (
                <>
                  {" · "}
                  <a href={report.blob_url} target="_blank" rel="noopener" style={{ color: "var(--accent)" }}>
                    descarcă
                  </a>
                </>
              )}
            </p>
            <form action={deleteRaport} style={{ marginTop: 10 }}>
              <input type="hidden" name="id" value={id} />
              <button type="submit" className="btn btn-sm btn-danger">
                Șterge raportul
              </button>
            </form>
          </>
        )}
      </div>

      {types.length > 0 && (
        <div className="card">
          <div className="card-title">Distribuție bilete</div>
          <table className="wp-table">
            <thead>
              <tr>
                <th>Tip bilet</th>
                <th style={{ textAlign: "right" }}>Preț</th>
                <th style={{ textAlign: "right" }}>Vândute</th>
                <th style={{ textAlign: "right" }}>Valoare</th>
              </tr>
            </thead>
            <tbody>
              {types.map((t, i) => (
                <tr key={i}>
                  <td>{t.denumire ?? "—"}</td>
                  <td style={{ textAlign: "right" }}>{ron(Number(t.pret ?? 0))}</td>
                  <td style={{ textAlign: "right" }}>{Number(t.vandute ?? 0)}</td>
                  <td style={{ textAlign: "right", fontVariantNumeric: "tabular-nums" }}>
                    {ron(Number(t.pret ?? 0) * Number(t.vandute ?? 0))}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <div className="card">
        <div className="card-title" style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
          <span>Viză bilete ({subtips.length} serii)</span>
          {subtips.length > 1 && (
            <form action={dedupVizaSubtips} style={{ margin: 0 }}>
              <input type="hidden" name="id" value={id} />
              <button type="submit" className="btn btn-sm btn-secondary">
                Curăță duplicatele
              </button>
            </form>
          )}
        </div>

        {subtips.length > 0 && (
          <table className="wp-table" style={{ marginBottom: 14 }}>
            <thead>
              <tr>
                <th>Seria</th>
                <th style={{ textAlign: "right" }}>De la</th>
                <th style={{ textAlign: "right" }}>Până la</th>
                <th style={{ textAlign: "right" }}>Vândute</th>
                <th style={{ textAlign: "right" }}>Total</th>
                <th style={{ textAlign: "right" }}>Tarif</th>
                <th style={{ width: 60 }}></th>
              </tr>
            </thead>
            <tbody>
              {subtips.map((s) => {
                const vandute = vanduteForTarif(types, Number(s.tarif), Number(s.nr_unitati));
                return (
                  <tr key={s.id}>
                    <td>
                      <span className="clp-seria">{s.seria}</span>
                    </td>
                    <td style={{ textAlign: "right" }}>{s.de_la || "—"}</td>
                    <td style={{ textAlign: "right" }}>{s.pana_la || "—"}</td>
                    <td style={{ textAlign: "right" }}>{vandute != null ? <strong>{vandute}</strong> : "—"}</td>
                    <td style={{ textAlign: "right" }}>{s.nr_unitati}</td>
                    <td style={{ textAlign: "right" }}>{Number(s.tarif).toLocaleString("ro-RO", { maximumFractionDigits: 0 })} RON</td>
                    <td>
                      <form action={deleteVizaSubtip} style={{ margin: 0 }}>
                        <input type="hidden" name="id" value={id} />
                        <input type="hidden" name="subtip_id" value={s.id} />
                        <button type="submit" className="btn btn-sm btn-danger">
                          ✕
                        </button>
                      </form>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}

        <form action={uploadViza} style={{ marginBottom: 14 }}>
          <input type="hidden" name="id" value={id} />
          <div style={{ display: "flex", gap: 8, alignItems: "center", flexWrap: "wrap" }}>
            <input type="file" name="viza_file" accept=".pdf" required style={{ border: "1px solid var(--border)", padding: "6px 10px", borderRadius: 4, fontSize: 13, background: "#fff" }} />
            <button type="submit" className="btn btn-primary btn-sm">Încarcă PDF viză</button>
          </div>
          <p className="form-desc">Seriile se extrag automat din PDF și înlocuiesc lista de mai jos.</p>
        </form>

        <form action={addVizaSubtip} className="crm-form">
          <input type="hidden" name="id" value={id} />
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr 1fr 1fr auto", gap: 8, alignItems: "end" }}>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>Seria</label>
              <input type="text" name="seria" required />
            </div>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>Tarif</label>
              <input type="text" name="tarif" required />
            </div>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>Nr. unități</label>
              <input type="number" name="nr_unitati" min={1} required />
            </div>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>De la</label>
              <input type="text" name="de_la" />
            </div>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>Până la</label>
              <input type="text" name="pana_la" />
            </div>
            <button type="submit" className="btn btn-primary btn-sm">
              Adaugă seria
            </button>
          </div>
        </form>
      </div>

      <div className="card">
        <div className="card-title">Participanți ({tickets.length})</div>
        <form action={updateParticipants}>
          <input type="hidden" name="id" value={id} />
          <div className="form-group">
            <label>Un nume pe linie</label>
            <textarea name="participants" rows={10} defaultValue={tickets.map((t) => t.participant_name).join("\n")} />
          </div>
          <button type="submit" className="btn btn-primary btn-sm">
            Salvează lista
          </button>
        </form>
      </div>

      {loyal.length > 0 && (
        <div className="card">
          <div className="card-title">Participanți fideli ({loyal.length})</div>
          <div style={{ display: "flex", flexWrap: "wrap", gap: 6 }}>
            {loyal.map((p, i) => (
              <span
                key={i}
                style={{ background: "#dcfce7", color: "#16a34a", borderRadius: 20, fontSize: 12, fontWeight: 600, padding: "3px 10px" }}
              >
                {p.name} · +{p.others}
              </span>
            ))}
          </div>
        </div>
      )}
    </>
  );
}
