"use client";

import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import type { PickerType } from "./TicketPicker";

// Selecția biletelor stă într-un modal, ca pe paginile de eveniment: butonul de
// pe cardul din dreapta (bara fixă, pe telefon) îl deschide. Cantitățile pleacă
// în URL-ul coșului — prețurile se recalculează pe server.

const money = (v: number) => v.toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function BileteModal({
  eventId,
  slug,
  titlu,
  cand,
  imagine,
  types,
  pretMin,
  soldOut,
  codAplicat,
  codGresit,
  ltUrl,
}: {
  eventId: number;
  slug: string;
  titlu: string;
  cand: string;
  imagine: string | null;
  types: PickerType[];
  pretMin: number | null;
  soldOut: boolean;
  codAplicat: string | null;
  codGresit?: string;
  ltUrl: string | null;
}) {
  const [deschis, setDeschis] = useState(!!codGresit || !!codAplicat);
  // Pe telefon, cardul devine bară fixă cu z-index propriu, ceea ce deschide un
  // context de stivuire: modalul randat înăuntru ar rămâne sub navbar oricât de
  // mare i-ar fi z-index-ul. De aceea se montează direct în <body>.
  const [montat, setMontat] = useState(false);
  useEffect(() => setMontat(true), []);
  const [qty, setQty] = useState<Record<number, number>>({});
  const [cod, setCod] = useState(codAplicat ?? codGresit ?? "");

  // Cât e modalul deschis, pagina din spate nu se mai mișcă.
  useEffect(() => {
    if (!deschis) return;
    const inainte = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const esc = (e: KeyboardEvent) => e.key === "Escape" && setDeschis(false);
    document.addEventListener("keydown", esc);
    return () => {
      document.body.style.overflow = inainte;
      document.removeEventListener("keydown", esc);
    };
  }, [deschis]);

  const set = (id: number, n: number, max: number) =>
    setQty((q) => ({ ...q, [id]: Math.max(0, Math.min(n, max)) }));

  const linii = types.map((t) => ({ t, n: qty[t.id] ?? 0 })).filter((x) => x.n > 0);
  const total = linii.reduce((s, x) => s + x.n * x.t.price, 0);
  const bucati = linii.reduce((s, x) => s + x.n, 0);
  const href = `/cos?e=${eventId}&t=${types
    .filter((t) => (qty[t.id] ?? 0) > 0)
    .map((t) => `${t.id}x${qty[t.id]}`)
    .join(",")}`;

  return (
    <>
      <div className="eb-card">
        <div className="eb-card-pret">
          {pretMin != null ? (
            <>
              <strong>de la {money(pretMin)} lei</strong>
              <span>{cand}</span>
            </>
          ) : (
            <strong>{soldOut ? "Sold out" : "În curând"}</strong>
          )}
        </div>
        <button
          type="button"
          className="eb-cta"
          disabled={soldOut}
          onClick={() => setDeschis(true)}
        >
          {soldOut ? "S-au epuizat" : "Cumpără bilete"}
        </button>
      </div>

      {deschis && montat && createPortal(
        <div className="mb-overlay" onClick={() => setDeschis(false)}>
          <div className="mb-panel" onClick={(e) => e.stopPropagation()} role="dialog" aria-modal="true">
            <button type="button" className="mb-x" onClick={() => setDeschis(false)} aria-label="Închide">
              ×
            </button>

            <div className="mb-main">
              <div className="mb-head">
                <h2>{titlu}</h2>
                <p>{cand}</p>
              </div>

              <div className="mb-body">
                <form className="mb-promo" action={`/curs/${slug}`} method="get">
                  <label htmlFor="mb-cod">Cod de reducere</label>
                  <input id="mb-cod" name="cod" value={cod} onChange={(e) => setCod(e.target.value)} />
                  <button type="submit">Aplică</button>
                </form>
                {codGresit && <p className="mb-err">Codul {codGresit} nu e valid pentru cursul ăsta.</p>}
                {codAplicat && (
                  <p className="mb-ok">
                    Cod <strong>{codAplicat}</strong> aplicat.{" "}
                    <a href={`/curs/${slug}`}>Renunță</a>
                  </p>
                )}

                {types.map((t) => {
                  const n = qty[t.id] ?? 0;
                  const pas = Math.max(1, t.bundle);
                  const maxim = Math.floor(Math.min(t.libere, t.maxPerOrder * pas) / pas) * pas;
                  const blocat = t.libere === 0 || !!t.dinData;
                  return (
                    <div key={t.id} className={`mb-tip${blocat ? " mb-tip--out" : ""}`}>
                      <div className="mb-tip-sus">
                        <h3>
                          {t.name}
                          {t.bundle > 1 && <span className="bt-pachet">se iau câte {t.bundle}</span>}
                        </h3>
                        <div className="mb-step">
                          <button type="button" onClick={() => set(t.id, n - pas, maxim)} disabled={blocat || n === 0} aria-label="Mai puține">
                            −
                          </button>
                          <span>{n}</span>
                          <button type="button" onClick={() => set(t.id, n + pas, maxim)} disabled={blocat || n + pas > maxim} aria-label="Mai multe">
                            +
                          </button>
                        </div>
                      </div>
                      <div className="mb-tip-jos">
                        {/* La un pachet, tăiat e cât ar costa biletele la preț
                            întreg, iar cifra mare e prețul pe bilet cu ofertă. */}
                        <div className="mb-pret">
                          {t.bundle > 1 && <s>{money(t.price * t.bundle)} lei</s>}
                          <b>{money(t.price)} lei</b>
                        </div>
                        {t.bundle > 1 && (
                          <div className="mb-perbilet">
                            Prețul e pentru fiecare bilet. Adaugă {t.bundle} în coș ca să prinzi oferta.
                          </div>
                        )}
                        {t.dinData ? (
                          <div className="mb-nota">{t.dinData}</div>
                        ) : t.libere === 0 ? (
                          <div className="mb-nota">Epuizat</div>
                        ) : (
                          t.libere <= 10 && <div className="mb-nota">Au mai rămas {t.libere}</div>
                        )}
                        {t.description && <p>{t.description}</p>}
                      </div>
                    </div>
                  );
                })}
              </div>

              <div className="mb-foot">
                <div className="mb-foot-total">
                  {bucati > 0 && (
                    <>
                      <span>
                        {bucati} {bucati === 1 ? "bilet" : "bilete"}
                      </span>
                      <strong>{money(total)} lei</strong>
                    </>
                  )}
                </div>
                {bucati > 0 ? (
                  <a href={href} className="eb-cta mb-cta">
                    Continuă
                  </a>
                ) : ltUrl ? (
                  <a href={`/go/course?id=${eventId}`} className="eb-cta mb-cta" target="_blank" rel="noopener">
                    Cumpără pe LiveTickets
                  </a>
                ) : (
                  <span className="eb-cta mb-cta mb-cta--off">Continuă</span>
                )}
              </div>
            </div>

            <aside className="mb-sumar">
              {imagine && (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={imagine} alt="" />
              )}
              <div className="mb-sumar-in">
                {linii.length === 0 ? (
                  <div className="mb-cos-gol">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.4" aria-hidden="true">
                      <path d="M3 3h2l2.4 12.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 7H6" />
                      <circle cx="10" cy="20" r="1.5" />
                      <circle cx="18" cy="20" r="1.5" />
                    </svg>
                    <p>Coșul e gol</p>
                  </div>
                ) : (
                  <>
                    <h4>Comanda ta</h4>
                    <ul>
                      {linii.map((x) => (
                        <li key={x.t.id}>
                          <span>
                            {x.n} × {x.t.name}
                          </span>
                          <span>{money(x.n * x.t.price)} lei</span>
                        </li>
                      ))}
                    </ul>
                    <div className="mb-sumar-total">
                      <span>Total</span>
                      <strong>{money(total)} lei</strong>
                    </div>
                  </>
                )}
              </div>
            </aside>
          </div>
        </div>,
        document.body,
      )}
    </>
  );
}
