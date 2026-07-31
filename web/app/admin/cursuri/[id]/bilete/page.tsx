import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { DEFAULT_TYPES, formatNumar, getTypes } from "@/lib/bilete";
import { VIEW_CSS } from "../detalii/styles";
import ConfirmButton from "../detalii/ConfirmButton";
import {
  addDefaultTypes,
  addDiscountCode,
  addType,
  caseazaLibere,
  deleteDiscountCode,
  deleteType,
  setVandute,
  toggleDiscountCode,
  toggleVizat,
  updateCote,
  updateType,
} from "./actions";
import { BILETE_CSS, COD_CSS } from "./styles";

export const dynamic = "force-dynamic";

const TZ = "Europe/Bucharest";
const roDate = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "numeric", month: "long", year: "numeric" });
const money = (v: number) => Number(v).toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const luna = (s: string | null) => new Intl.DateTimeFormat("en-CA", { timeZone: TZ }).format(new Date(s ?? "")).slice(0, 7);

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }) {
  const { id: idStr } = await params;
  const [event] = (await sql`SELECT title FROM events WHERE id = ${Number(idStr) || 0}`) as { title: string }[];
  return { title: `Bilete — ${event?.title ?? "Curs"} — Admin` };
}

export default async function BiletePage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ err?: string }>;
}) {
  const { id: idStr } = await params;
  const { err } = await searchParams;
  const id = Number(idStr);
  if (!id) notFound();

  const [event] = (await sql`
    SELECT id, title, starts_at, location, impozit_cota, timbru_cota, vizat_at
    FROM events WHERE id = ${id}
  `) as {
    id: number;
    title: string;
    starts_at: string | null;
    location: string | null;
    impozit_cota: string;
    timbru_cota: string;
    vizat_at: string | null;
  }[];
  if (!event) notFound();

  const types = await getTypes(id);
  const coduri = (await sql`
    SELECT c.id, c.code, c.percent, c.active,
           to_char(c.valid_until AT TIME ZONE 'Europe/Bucharest', 'DD.MM.YYYY HH24:MI') AS pana_la,
           c.valid_until > now() AS in_termen,
           COUNT(t.id)::int AS nr_tipuri
    FROM discount_codes c LEFT JOIN ticket_types t ON t.discount_code_id = c.id
    WHERE c.event_id = ${id}
    GROUP BY c.id ORDER BY c.id
  `) as {
    id: number;
    code: string;
    percent: string;
    active: boolean;
    pana_la: string | null;
    in_termen: boolean | null;
    nr_tipuri: number;
  }[];
  const codById = new Map(coduri.map((c) => [c.id, c]));
  // un bilet oarecare, ca să poți vedea cum arată ce primește participantul
  const [exemplu] = (await sql`
    SELECT qr_token FROM ticket_pool WHERE event_id = ${id} ORDER BY status = 'vandut' DESC, id LIMIT 1
  `) as { qr_token: string | null }[];
  const totalPool = types.reduce((s, t) => s + t.stock, 0);
  const totalVandute = types.reduce((s, t) => s + t.vandute, 0);
  const totalLibere = types.reduce((s, t) => s + t.libere, 0);
  const totalCasate = types.reduce((s, t) => s + t.casate, 0);
  const incasari = types.reduce((s, t) => s + Number(t.price) * t.vandute, 0);
  const timbre = incasari * (Number(event.timbru_cota) / 100);
  const impozit = (incasari - timbre) * (Number(event.impozit_cota) / 100);
  const vizat = !!event.vizat_at;

  return (
    <>
      <link rel="stylesheet" href="/admin/statistici/style.css" />
      <style dangerouslySetInnerHTML={{ __html: VIEW_CSS + BILETE_CSS + COD_CSS }} />

      <div className="course-wrap">
        {err && <div className="error-msg" style={{ display: "block", marginBottom: 16 }}>{err}</div>}

        <div className="course-hero">
          <a
            href={`/admin/cursuri/${id}/detalii`}
            style={{ fontSize: 12, color: "var(--muted)", textDecoration: "none", display: "inline-flex", alignItems: "center", gap: 4, marginBottom: 10 }}
          >
            ← Inapoi
          </a>
          <h2>{event.title}</h2>
          <div className="meta">
            {event.starts_at ? roDate.format(new Date(event.starts_at)) : ""}
            {event.location ? ` · ${event.location}` : ""}
          </div>
        </div>

        <div className="section-card">
          <h3>Tipuri de bilete</h3>

          {types.length === 0 ? (
            <div style={{ marginBottom: 16 }}>
              <p style={{ color: "var(--muted)", fontSize: 14, marginBottom: 10 }}>
                Cursurile create de acum înainte pornesc cu tipurile implicite. Ăsta e mai vechi — pune-le cu un clic:
              </p>
              <form action={addDefaultTypes} style={{ margin: 0 }}>
                <input type="hidden" name="id" value={id} />
                <button type="submit" className="add-btn">
                  Adauga tipurile implicite
                </button>
              </form>
              <p className="hint">
                {DEFAULT_TYPES.map((t) => `${t.name} — ${t.price} lei × ${t.stock}`).join(" · ")}
              </p>
            </div>
          ) : (
            <table className="bilete-table">
              <thead>
                <tr>
                  <th>Tip</th>
                  <th>Seria</th>
                  <th className="num">Tarif</th>
                  <th className="num">Stoc</th>
                  <th className="num">Vândute</th>
                  <th className="num">Libere</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {types.map((t) => (
                  <tr key={t.id}>
                    <td>
                      <form action={updateType} className="row-form">
                        <input type="hidden" name="id" value={id} />
                        <input type="hidden" name="type_id" value={t.id} />
                        <input name="name" defaultValue={t.name} className="in-name" />
                        <input name="price" type="number" step="0.01" defaultValue={Number(t.price)} className="in-num" />
                        <input name="stock" type="number" min={t.stock} defaultValue={t.stock} className="in-num" />
                        <button type="submit" className="mini-btn">Salveaza</button>
                      </form>
                      {t.discount_code_id && codById.has(t.discount_code_id) && (
                        <span className="cod-tag">cod {codById.get(t.discount_code_id)!.code}</span>
                      )}
                    </td>
                    <td>
                      <span className="seria-badge">{t.serie}</span>
                      <div className="serie-range">
                        {formatNumar(1)} – {formatNumar(t.stock)}
                      </div>
                    </td>
                    <td className="num">{money(Number(t.price))}</td>
                    <td className="num">{t.stock}</td>
                    <td className="num">
                      <form action={setVandute} className="row-form">
                        <input type="hidden" name="id" value={id} />
                        <input type="hidden" name="type_id" value={t.id} />
                        <input name="vandute" type="number" min={0} max={t.stock} defaultValue={t.vandute} className="in-num" />
                        <button type="submit" className="mini-btn">OK</button>
                      </form>
                    </td>
                    <td className="num">
                      {t.libere}
                      {t.casate > 0 && <span className="casate-tag">{t.casate} casate</span>}
                    </td>
                    <td>
                      {t.vandute === 0 && (
                        <form action={deleteType} style={{ margin: 0 }}>
                          <input type="hidden" name="id" value={id} />
                          <input type="hidden" name="type_id" value={t.id} />
                          <ConfirmButton message={`Stergi tipul «${t.name}»?`} className="x-btn" title="Sterge">
                            ×
                          </ConfirmButton>
                        </form>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}

          <form action={addType} className="add-form">
            <input type="hidden" name="id" value={id} />
            <div className="f">
              <label>Tip bilet</label>
              <input name="name" required style={{ width: 200 }} />
            </div>
            <div className="f">
              <label>Tarif (RON)</label>
              <input name="price" type="number" step="0.01" required style={{ width: 90 }} />
            </div>
            <div className="f">
              <label>Stoc de vizat</label>
              <input name="stock" type="number" min={1} required style={{ width: 90 }} />
            </div>
            <button type="submit" className="add-btn">Adauga tip</button>
          </form>
          <p className="hint">
            Seria de 3 litere se generează automat, iar biletele se numerotează de la 0001. Stocul poate doar să
            crească — ce e vizat la primărie nu se retrage, se casează la final.
          </p>
        </div>

        {totalPool > 0 && (
          <div className="section-card">
            <h3>Situatie</h3>
            <div className="raport-grid">
              <div className="raport-stat">
                <div className="label">Vandute</div>
                <div className="value">{totalVandute}</div>
              </div>
              <div className="raport-stat">
                <div className="label">Incasari</div>
                <div className="value">
                  {money(incasari)} <small style={{ fontSize: 14, fontWeight: 400 }}>RON</small>
                </div>
              </div>
              <div className="raport-stat">
                <div className="label">Impozit ({Number(event.impozit_cota)}%)</div>
                <div className="value ditl">
                  {money(impozit)} <small style={{ fontSize: 14, fontWeight: 400 }}>RON</small>
                </div>
              </div>
            </div>
            <div className="raport-meta">
              {totalPool} bilete vizate · {totalLibere} libere · {totalCasate} casate
            </div>

            <form action={updateCote} className="add-form" style={{ borderTop: "1px solid var(--border)", paddingTop: 16 }}>
              <input type="hidden" name="id" value={id} />
              <div className="f">
                <label>Cota impozit (%)</label>
                <input name="impozit_cota" type="number" step="0.01" defaultValue={Number(event.impozit_cota)} style={{ width: 90 }} />
              </div>
              <div className="f">
                <label>Timbre (%)</label>
                <input name="timbru_cota" type="number" step="0.01" defaultValue={Number(event.timbru_cota)} style={{ width: 90 }} />
              </div>
              <button type="submit" className="add-btn">Salveaza cotele</button>
            </form>
            <p className="hint">
              Art. 481 Cod fiscal: 2% la spectacolele de la alin. (1), 5% la cele „cu caracter ocazional" de la alin.
              (2). Timbrele se scad din baza impozabilă — 0 dacă nu se aplică.
            </p>
          </div>
        )}

        {types.some((t) => !t.discount_code_id) && (
          <div className="section-card">
            <h3>Coduri de reducere</h3>

            {coduri.length > 0 && (
              <table className="bilete-table">
                <thead>
                  <tr>
                    <th>Cod</th>
                    <th className="num">Reducere</th>
                    <th>Valabil</th>
                    <th className="num">Bilete generate</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {coduri.map((c) => (
                    <tr key={c.id}>
                      <td>
                        <span className="seria-badge">{c.code}</span>
                      </td>
                      <td className="num">−{Number(c.percent)}%</td>
                      <td>
                        {c.pana_la ? `până ${c.pana_la}` : "fără termen"}
                        {c.pana_la && c.in_termen === false && <span className="casate-tag">expirat</span>}
                      </td>
                      <td className="num">{c.nr_tipuri} tipuri</td>
                      <td style={{ display: "flex", gap: 6, alignItems: "center" }}>
                        <form action={toggleDiscountCode} style={{ margin: 0 }}>
                          <input type="hidden" name="id" value={id} />
                          <input type="hidden" name="code_id" value={c.id} />
                          <button type="submit" className="mini-btn">
                            {c.active ? "Opreste" : "Porneste"}
                          </button>
                        </form>
                        <form action={deleteDiscountCode} style={{ margin: 0 }}>
                          <input type="hidden" name="id" value={id} />
                          <input type="hidden" name="code_id" value={c.id} />
                          <ConfirmButton
                            message={`Stergi codul ${c.code} si biletele generate de el?`}
                            className="x-btn"
                            title="Sterge"
                          >
                            ×
                          </ConfirmButton>
                        </form>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}

            <form action={addDiscountCode} className="add-form">
              <input type="hidden" name="id" value={id} />
              <div className="f">
                <label>Cod</label>
                <input name="code" required style={{ width: 140, textTransform: "uppercase" }} />
              </div>
              <div className="f">
                <label>Reducere (%)</label>
                <input name="percent" type="number" min={1} max={99} step="0.01" required style={{ width: 100 }} />
              </div>
              <div className="f">
                <label>Valabil până (optional)</label>
                <input name="valid_until" type="datetime-local" style={{ width: 210 }} />
              </div>
              <button type="submit" className="add-btn">Genereaza biletele</button>
            </form>
            <p className="hint">
              Codul nu scade prețul biletelor existente — generează bilete noi, cu serie și tarif propriu, pentru
              fiecare tip normal. Altfel ai vinde la 32,50 lei un bilet dintr-o serie declarată la primărie cu 50 de
              lei. Fă codurile <strong>înainte</strong> de a depune cererea de vizare, ca seriile reduse să intre în
              ea.
            </p>
          </div>
        )}

        {totalPool > 0 && (
          <div className="section-card">
            <h3>La intrare</h3>
            <div className="doc-list">
              <a className="doc" href={`/admin/cursuri/${id}/checkin`}>
                <strong>Scaneaza biletele</strong>
                <span>Deschide camera telefonului · {totalVandute} bilete vandute</span>
              </a>
              {exemplu?.qr_token && (
                <a className="doc" href={`/bilet/${exemplu.qr_token}`} target="_blank" rel="noopener">
                  <strong>Vezi un bilet</strong>
                  <span>Exact ce primeste participantul</span>
                </a>
              )}
            </div>
          </div>
        )}

        {totalPool > 0 && (
          <div className="section-card">
            <h3>Documente pentru primarie</h3>
            <div className="doc-list">
              <a className="doc" href={`/admin/cursuri/${id}/bilete/vizare`} target="_blank" rel="noopener">
                <strong>Cerere de inregistrare/vizare</strong>
                <span>Inainte de curs · tot pool-ul de {totalPool} bilete</span>
              </a>
              <a className="doc" href={`/admin/bilete/decont?luna=${luna(event.starts_at)}`} target="_blank" rel="noopener">
                <strong>Decont impozit pe spectacole</strong>
                <span>Lunar, pana pe 10 · toate cursurile din luna</span>
              </a>
              <a className="doc" href={`/admin/cursuri/${id}/bilete/casare`} target="_blank" rel="noopener">
                <strong>Proces-verbal de casare</strong>
                <span>Dupa curs · {totalLibere} bilete nevandute</span>
              </a>
            </div>

            <div className="doc-actions">
              <form action={toggleVizat} style={{ margin: 0 }}>
                <input type="hidden" name="id" value={id} />
                <button type="submit" className={`state-btn${vizat ? " on" : ""}`}>
                  {vizat ? `✓ Vizat ${roDate.format(new Date(event.vizat_at!))}` : "Marcheaza ca vizat"}
                </button>
              </form>
              {totalLibere > 0 && (
                <form action={caseazaLibere} style={{ margin: 0 }}>
                  <input type="hidden" name="id" value={id} />
                  <ConfirmButton
                    message={`Casezi cele ${totalLibere} bilete nevandute? Nu mai pot fi vandute dupa.`}
                    className="state-btn"
                  >
                    Caseaza cele {totalLibere} nevandute
                  </ConfirmButton>
                </form>
              )}
            </div>
          </div>
        )}
      </div>
    </>
  );
}
