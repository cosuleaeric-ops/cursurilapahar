"use client";

import { useState } from "react";

import CodReducere from "./CodReducere";

export type PickerType = {
  id: number;
  name: string;
  description: string | null;
  price: number;
  libere: number;
  maxPerOrder: number;
  bundle: number;
  /** Textul „în vânzare din …" când biletul e programat mai târziu. */
  dinData: string | null;
};

const money = (v: number) => v.toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/**
 * Selecția nu se ține nici în localStorage, nici pe server: pleacă în URL-ul
 * coșului, iar /cos recalculează tot din baza de date. Nimic din prețuri nu
 * trece prin browser, deci nu are ce fi falsificat.
 */
export default function TicketPicker({
  eventId,
  types,
  slug,
  codAplicat,
  codGresit,
}: {
  eventId: number;
  types: PickerType[];
  slug: string;
  codAplicat: boolean;
  codGresit?: string;
}) {
  const [qty, setQty] = useState<Record<number, number>>({});

  const set = (id: number, n: number, max: number) =>
    setQty((q) => ({ ...q, [id]: Math.max(0, Math.min(n, max)) }));

  const total = types.reduce((s, t) => s + (qty[t.id] ?? 0) * t.price, 0);
  const bucati = types.reduce((s, t) => s + (qty[t.id] ?? 0), 0);
  const href = `/cos?e=${eventId}&t=${types
    .filter((t) => (qty[t.id] ?? 0) > 0)
    .map((t) => `${t.id}x${qty[t.id]}`)
    .join(",")}`;

  return (
    <>
      <div className="bt-list">
        {types.map((t) => {
          const n = qty[t.id] ?? 0;
          const epuizat = t.libere === 0 || !!t.dinData;
          const maxim = Math.min(t.libere, t.maxPerOrder);
          return (
            <div key={t.id} className={`bt-row${epuizat ? " bt-row--out" : ""}`}>
              <div className="bt-main">
                <h3>
                  {t.name}
                  {t.bundle > 1 && <span className="bt-pachet">pentru {t.bundle} persoane</span>}
                </h3>
                {t.description && <p>{t.description}</p>}
              </div>
              <div className="bt-price">
                {money(t.price)} lei
                {t.dinData ? (
                  <span className="bt-left">{t.dinData}</span>
                ) : epuizat ? (
                  <span className="bt-left bt-left--out">epuizat</span>
                ) : (
                  t.libere <= 10 && <span className="bt-left">ultimele {t.libere}</span>
                )}
              </div>
              <div className="bt-stepper">
                <button type="button" onClick={() => set(t.id, n - 1, maxim)} disabled={epuizat || n === 0} aria-label="Mai puține">
                  −
                </button>
                <span>{n}</span>
                <button type="button" onClick={() => set(t.id, n + 1, maxim)} disabled={epuizat || n >= maxim} aria-label="Mai multe">
                  +
                </button>
              </div>
            </div>
          );
        })}
      </div>

      <div className="bt-foot">
        <div className="bt-total">
          {!codAplicat && bucati === 0 && <CodReducere slug={slug} gresit={codGresit} />}
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
          <a href={href} className="btn btn-primary bt-cta">
            Comandă bilete
          </a>
        ) : (
          <span className="btn btn-primary bt-cta bt-cta--off">Comandă bilete</span>
        )}
      </div>
    </>
  );
}
