"use client";

import { useState } from "react";

export type PickerType = { id: number; name: string; price: number; libere: number };

const money = (v: number) => v.toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/**
 * Selecția nu se ține nici în localStorage, nici pe server: pleacă în URL-ul
 * coșului, iar /cos recalculează tot din baza de date. Nimic din prețuri nu
 * trece prin browser, deci nu are ce fi falsificat.
 */
export default function TicketPicker({ eventId, types }: { eventId: number; types: PickerType[] }) {
  const [qty, setQty] = useState<Record<number, number>>({});

  const set = (id: number, n: number, max: number) =>
    setQty((q) => ({ ...q, [id]: Math.max(0, Math.min(n, Math.min(max, 10))) }));

  const total = types.reduce((s, t) => s + (qty[t.id] ?? 0) * t.price, 0);
  const bucati = types.reduce((s, t) => s + (qty[t.id] ?? 0), 0);
  const href = `/cos?e=${eventId}&t=${types
    .filter((t) => (qty[t.id] ?? 0) > 0)
    .map((t) => `${t.id}x${qty[t.id]}`)
    .join(",")}`;

  return (
    <div className="tp">
      {types.map((t) => {
        const n = qty[t.id] ?? 0;
        const epuizat = t.libere === 0;
        return (
          <div key={t.id} className={`tp-row${epuizat ? " tp-row--out" : ""}`}>
            <div className="tp-info">
              <span className="tp-name">{t.name}</span>
              {epuizat ? (
                <span className="tp-left tp-left--out">epuizat</span>
              ) : (
                t.libere <= 10 && <span className="tp-left">ultimele {t.libere}</span>
              )}
            </div>
            <div className="tp-price">{money(t.price)} lei</div>
            <div className="tp-stepper">
              <button type="button" onClick={() => set(t.id, n - 1, t.libere)} disabled={epuizat || n === 0} aria-label="Mai puține">
                −
              </button>
              <span>{n}</span>
              <button type="button" onClick={() => set(t.id, n + 1, t.libere)} disabled={epuizat || n >= Math.min(t.libere, 10)} aria-label="Mai multe">
                +
              </button>
            </div>
          </div>
        );
      })}

      <div className="tp-foot">
        <div className="tp-total">
          <span>Total</span>
          <strong>{money(total)} lei</strong>
        </div>
        {bucati > 0 ? (
          <a href={href} className="btn btn-primary tp-cta">
            Comandă {bucati} {bucati === 1 ? "bilet" : "bilete"}
          </a>
        ) : (
          <span className="btn btn-primary tp-cta tp-cta--off">Alege câte bilete vrei</span>
        )}
      </div>
    </div>
  );
}
