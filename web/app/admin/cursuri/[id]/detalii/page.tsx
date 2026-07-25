import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { ditlBase, vanduteForTarif, participantNameKey, type TicketType } from "@/lib/statistici";
import { VIEW_CSS } from "./styles";
import {
  addVizaSubtip,
  dedupVizaSubtips,
  deleteCourse,
  deleteRaport,
  deleteViza,
  deleteVizaSubtip,
  updateParticipants,
  uploadRaport,
  uploadViza,
} from "./actions";
import CopyDist from "./CopyDist";

export const dynamic = "force-dynamic";

// Port din admin/statistici/cursuri/view.php — aceeași structură (course-hero,
// section-card, actions-grid, subtip-table) și același stylesheet.

const TZ = "Europe/Bucharest";
const roDate = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, weekday: "long", day: "numeric", month: "long", year: "numeric" });
const shortDate = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "numeric", month: "long", year: "numeric" });
const money = (v: number) => Number(v).toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const ymd = (s: string) => new Intl.DateTimeFormat("en-CA", { timeZone: TZ }).format(new Date(s));

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
    SELECT id, title, starts_at FROM events WHERE id = ${id}
  `) as { id: number; title: string; starts_at: string | null }[];
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

  const [vizaFile] = (await sql`
    SELECT id, original_name, blob_url, uploaded_at FROM event_files
    WHERE event_id = ${id} ORDER BY uploaded_at DESC LIMIT 1
  `) as { id: number; original_name: string; blob_url: string; uploaded_at: string }[];

  const tickets = (await sql`
    SELECT participant_name FROM tickets WHERE event_id = ${id} ORDER BY participant_name
  `) as { participant_name: string }[];

  // Distribuție: câte comenzi au cumpărat N bilete (o comandă = un nume).
  const nameCounts = new Map<string, number>();
  for (const t of tickets) nameCounts.set(t.participant_name, (nameCounts.get(t.participant_name) ?? 0) + 1);
  const groups = new Map<number, number>();
  for (const n of nameCounts.values()) groups.set(n, (groups.get(n) ?? 0) + 1);
  const groupList = [...groups.entries()].sort((a, b) => b[0] - a[0]);
  const totalTickets = tickets.length;
  const totalOrders = nameCounts.size;
  const distCopyText =
    totalTickets > 0
      ? [
          `Sunt ${totalTickets} ${totalTickets === 1 ? "bilet" : "bilete"}, dintre care:`,
          "",
          ...groupList.map(([n, o]) => `- ${o} ${o === 1 ? "comanda" : "comenzi"} x ${n} ${n === 1 ? "bilet" : "bilete"}`),
        ].join("\n")
      : "";

  // Participanți fideli: aceeași cheie normalizată de nume, la alte cursuri.
  const others = (await sql`
    SELECT t.participant_name, e.title, to_char(e.starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS d
    FROM tickets t JOIN events e ON e.id = t.event_id
    WHERE t.event_id <> ${id}
  `) as { participant_name: string; title: string; d: string | null }[];
  const otherByKey = new Map<string, Set<string>>();
  for (const o of others) {
    const k = participantNameKey(o.participant_name);
    if (!otherByKey.has(k)) otherByKey.set(k, new Set());
    otherByKey.get(k)!.add(`${o.title}|||${o.d ?? ""}`);
  }
  const returning = [...nameCounts.keys()]
    .map((name) => ({ name, list: [...(otherByKey.get(participantNameKey(name)) ?? [])] }))
    .filter((p) => p.list.length > 0)
    .sort((a, b) => b.list.length - a.list.length);

  const types = (report?.types_json ?? []) as TicketType[];
  const base = report ? ditlBase(types, Number(report.total_bilete)) : 0;

  const dupKeys = new Set<string>();
  let hasDupes = false;
  for (const s of subtips) {
    const k = `${s.seria}_${s.de_la}_${s.pana_la}`;
    if (dupKeys.has(k)) hasDupes = true;
    dupKeys.add(k);
  }

  const sortedNames = [...nameCounts.entries()].sort((a, b) => b[1] - a[1]);

  return (
    <>
      <link rel="stylesheet" href="/admin/statistici/style.css" />
      <style dangerouslySetInnerHTML={{ __html: VIEW_CSS }} />

      <div className="course-wrap">
        {err && <div className="error-msg" style={{ display: "block", marginBottom: 16 }}>{err}</div>}
        {serii && <div className="notice notice-success">Viza procesată: {serii} serii extrase din PDF.</div>}

        <div className="course-hero">
          <a
            href="/admin/cursuri"
            style={{ fontSize: 12, color: "var(--muted)", textDecoration: "none", display: "inline-flex", alignItems: "center", gap: 4, marginBottom: 10 }}
          >
            ← Inapoi
          </a>
          <h2>{event.title}</h2>
          <div className="meta">{event.starts_at ? roDate.format(new Date(event.starts_at)) : ""}</div>
        </div>

        {report && (
          <div className="section-card">
            <h3>Raport eveniment</h3>
            <div className="raport-grid">
              <div className="raport-stat">
                <div className="label">Total incasari</div>
                <div className="value">
                  {money(Number(report.total_incasari))} <small style={{ fontSize: 14, fontWeight: 400 }}>RON</small>
                </div>
              </div>
              <div className="raport-stat">
                <div className="label">Taxa DITL (2%)</div>
                <div className="value ditl">
                  {money(base * 0.02)} <small style={{ fontSize: 14, fontWeight: 400 }}>RON</small>
                </div>
              </div>
            </div>
            <div className="raport-meta">
              {report.original_name ? `${report.original_name} · ` : ""}
              Actualizat {ymd(report.uploaded_at)}
            </div>
            <form
              action={deleteRaport}
              onSubmit={undefined}
              style={{ marginTop: 4 }}
            >
              <input type="hidden" name="id" value={id} />
              <button type="submit" className="btn btn-ghost" style={{ fontSize: 12, padding: "4px 12px", color: "var(--muted)" }}>
                Sterge raportul
              </button>
            </form>
          </div>
        )}

        <div className="section-card">
          <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 12, marginBottom: 16 }}>
            <h3 style={{ marginBottom: 0 }}>Distributie bilete</h3>
            {totalTickets > 0 && <CopyDist text={distCopyText} />}
          </div>
          {totalTickets === 0 ? (
            <p style={{ color: "var(--muted)", fontSize: 14 }}>Niciun bilet inregistrat.</p>
          ) : (
            <>
              <div className="dist-total">
                {totalTickets} {totalTickets === 1 ? "bilet" : "bilete"}
              </div>
              <div className="dist-sub">
                {totalOrders} {totalOrders === 1 ? "comanda" : "comenzi"}
              </div>
              <ul className="dist-list">
                {groupList.map(([n, o]) => (
                  <li key={n}>
                    <span className="dist-bullet"></span>
                    <strong>{o}</strong>&nbsp;{o === 1 ? "comanda" : "comenzi"} ×{" "}
                    <strong>
                      {n} {n === 1 ? "bilet" : "bilete"}
                    </strong>
                  </li>
                ))}
              </ul>
            </>
          )}
        </div>

        {returning.length > 0 && (
          <div className="section-card">
            <h3>Participanti fideli ({returning.length})</h3>
            <ul className="returning-list">
              {returning.map((rp) => (
                <li key={rp.name}>
                  <span className="returning-badge">×{rp.list.length}</span>
                  <strong>{rp.name}</strong>
                  <span className="returning-courses">
                    {rp.list.map((c, i) => {
                      const [title, d] = c.split("|||");
                      return (
                        <span key={i}>
                          {i > 0 ? ", " : ""}
                          {title}{" "}
                          <span style={{ color: "var(--muted)" }}>({d ? shortDate.format(new Date(`${d}T12:00:00`)) : ""})</span>
                        </span>
                      );
                    })}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        )}

        <div className="section-card">
          <h3>Participanti{sortedNames.length > 0 ? ` (${sortedNames.length} comenzi)` : ""}</h3>
          {sortedNames.length > 0 ? (
            <ul className="participants-list">
              {sortedNames.map(([name, cnt]) => (
                <li key={name}>
                  {name}
                  {cnt > 1 && <span>×{cnt}</span>}
                </li>
              ))}
            </ul>
          ) : (
            <p style={{ fontSize: 13, color: "var(--muted)", marginBottom: 12 }}>Niciun participant inregistrat.</p>
          )}
          <form action={updateParticipants} style={{ marginTop: 16, borderTop: "1px solid var(--border)", paddingTop: 14 }}>
            <input type="hidden" name="id" value={id} />
            <div className="form-group">
              <label style={{ fontSize: 12, color: "var(--muted)" }}>Un nume pe linie</label>
              <textarea name="participants" rows={8} defaultValue={tickets.map((t) => t.participant_name).join("\n")} />
            </div>
            <div className="update-submit" style={{ display: "block" }}>
              <button type="submit" className="btn btn-green" style={{ width: "100%", justifyContent: "center", padding: 10 }}>
                {sortedNames.length === 0 ? "Salveaza lista" : "Inlocuieste lista"}
              </button>
            </div>
          </form>
        </div>

        <div className="actions-grid">
          <div className="section-card" style={{ marginBottom: 0 }}>
            <h3>Raport eveniment</h3>
            <form action={uploadRaport}>
              <input type="hidden" name="id" value={id} />
              <div className="upload-zone">
                <input type="file" name="raport_file" accept=".xlsx,.xls" />
                <p>{report ? "Inlocuieste raportul" : "Trage sau apasa pentru XLSX"}</p>
              </div>
              <button type="submit" className="btn btn-ghost" style={{ marginTop: 8, width: "100%", justifyContent: "center" }}>
                Incarca
              </button>
            </form>
          </div>

          <div className="section-card" style={{ marginBottom: 0 }}>
            <h3>Viză bilete</h3>
            <form action={uploadViza}>
              <input type="hidden" name="id" value={id} />
              <div className="upload-zone">
                <input type="file" name="viza_file" accept=".pdf" />
                <p>{vizaFile ? "Inlocuieste viză" : "Trage sau apasa pentru a incarca Viză PDF"}</p>
              </div>
              <button type="submit" className="btn btn-ghost" style={{ marginTop: 8, width: "100%", justifyContent: "center" }}>
                Incarca
              </button>
            </form>
          </div>
        </div>

        {vizaFile && (
          <div className="section-card">
            <div className="viza-file" style={{ marginBottom: 12 }}>
              <div>
                <a className="viza-name" href={vizaFile.blob_url} target="_blank" rel="noopener">
                  {vizaFile.original_name}
                </a>
                <div className="viza-date">Incarcat {ymd(vizaFile.uploaded_at)}</div>
              </div>
              <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
                <form action={deleteViza} style={{ margin: 0 }}>
                  <input type="hidden" name="id" value={id} />
                  <input type="hidden" name="file_id" value={vizaFile.id} />
                  <button type="submit" className="icon-btn danger" title="Sterge">
                    ×
                  </button>
                </form>
              </div>
            </div>

            {subtips.length > 0 ? (
              <>
                {hasDupes && (
                  <form action={dedupVizaSubtips} style={{ marginBottom: 8 }}>
                    <input type="hidden" name="id" value={id} />
                    <button
                      type="submit"
                      style={{ fontSize: 12, color: "#c0392b", background: "none", border: "1px solid #c0392b", borderRadius: 6, padding: "3px 10px", cursor: "pointer" }}
                    >
                      Sterge duplicate
                    </button>
                  </form>
                )}
                <table className="subtip-table">
                  <thead>
                    <tr>
                      <th>Seria</th>
                      <th>De la</th>
                      <th>Pana la</th>
                      {types.length > 0 && <th>Vandute</th>}
                      <th>Nr. bilete</th>
                      <th>Tarif</th>
                    </tr>
                  </thead>
                  <tbody>
                    {subtips.map((s) => {
                      const vandute = vanduteForTarif(types, Number(s.tarif), Number(s.nr_unitati));
                      return (
                        <tr key={s.id}>
                          <td>
                            <span className="seria-badge">{s.seria}</span>
                          </td>
                          <td className="num">{s.de_la ?? ""}</td>
                          <td className="num">{s.pana_la ?? ""}</td>
                          {types.length > 0 && (
                            <td className={`num ${vandute != null ? "sold-match" : "no-match"}`}>
                              {vandute != null ? `${vandute} vandute` : "—"}
                            </td>
                          )}
                          <td className="num">{s.nr_unitati}</td>
                          <td className="num">{Number(s.tarif).toLocaleString("ro-RO", { maximumFractionDigits: 0 })} RON</td>
                          <td>
                            <form action={deleteVizaSubtip} style={{ margin: 0 }}>
                              <input type="hidden" name="id" value={id} />
                              <input type="hidden" name="subtip_id" value={s.id} />
                              <button
                                type="submit"
                                style={{ background: "none", border: "none", cursor: "pointer", color: "var(--muted)", fontSize: 14, padding: "2px 6px" }}
                                title="Sterge"
                              >
                                ×
                              </button>
                            </form>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </>
            ) : (
              <>
                <p style={{ fontSize: 13, color: "var(--muted)", margin: "8px 0 0" }}>
                  Nu s-au putut extrage datele automat. Introdu manual:
                </p>
                <form action={addVizaSubtip} style={{ marginTop: 10, display: "flex", gap: 8, flexWrap: "wrap", alignItems: "flex-end" }}>
                  <input type="hidden" name="id" value={id} />
                  {[
                    { name: "seria", label: "Seria", type: "text", w: 70 },
                    { name: "tarif", label: "Tarif (RON)", type: "number", w: 80 },
                    { name: "nr_unitati", label: "Nr. bilete", type: "number", w: 80 },
                    { name: "de_la", label: "De la nr.", type: "text", w: 70 },
                    { name: "pana_la", label: "Pana la nr.", type: "text", w: 70 },
                  ].map((f) => (
                    <div key={f.name} style={{ display: "flex", flexDirection: "column", gap: 3 }}>
                      <label style={{ fontSize: 11, color: "var(--muted)" }}>{f.label}</label>
                      <input
                        type={f.type}
                        name={f.name}
                        step={f.type === "number" && f.name === "tarif" ? "0.01" : undefined}
                        required
                        style={{ width: f.w, padding: "5px 8px", border: "1px solid var(--border)", borderRadius: 6, fontSize: 13 }}
                      />
                    </div>
                  ))}
                  <button
                    type="submit"
                    style={{ padding: "6px 14px", background: "var(--accent)", color: "#fff", border: "none", borderRadius: 6, fontSize: 13, cursor: "pointer" }}
                  >
                    Adauga
                  </button>
                </form>
              </>
            )}
          </div>
        )}

        <div className="section-card" style={{ borderColor: "#f5c6c7" }}>
          <div className="danger-zone">
            <form action={deleteCourse}>
              <input type="hidden" name="id" value={id} />
              <button type="submit" className="btn btn-red" style={{ fontSize: 12, padding: "5px 14px" }}>
                Sterge cursul
              </button>
            </form>
          </div>
        </div>
      </div>
    </>
  );
}
