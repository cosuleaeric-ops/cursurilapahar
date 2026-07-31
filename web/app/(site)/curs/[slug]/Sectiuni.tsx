"use client";

import { useState } from "react";

// Secțiunile de sub descriere: „Bine de știut", locația cu hartă, întrebări
// frecvente. Harta se încarcă doar după clic — un iframe Google pe fiecare
// afișare ar încetini pagina și ar pune un cookie fără să fie nevoie.

export function Harta({ nume, adresa, mapsLink }: { nume: string; adresa: string; mapsLink: string | null }) {
  const [vizibila, setVizibila] = useState(false);
  const q = encodeURIComponent(adresa || nume);

  return (
    <div className="loc-harta">
      {vizibila ? (
        <iframe
          title={`Harta — ${nume}`}
          src={`https://maps.google.com/maps?q=${q}&output=embed`}
          loading="lazy"
          referrerPolicy="no-referrer-when-downgrade"
        />
      ) : (
        <button type="button" onClick={() => setVizibila(true)}>
          Arată harta
        </button>
      )}
      {mapsLink && (
        <a className="loc-maps" href={mapsLink} target="_blank" rel="noopener">
          Deschide în Google Maps →
        </a>
      )}
    </div>
  );
}

export function Intrebari({ items }: { items: { q: string; a: string }[] }) {
  const [deschis, setDeschis] = useState<number | null>(null);
  return (
    <div className="faq-list">
      {items.map((it, i) => (
        <div key={i} className={`faq-item${deschis === i ? " on" : ""}`}>
          <button type="button" onClick={() => setDeschis(deschis === i ? null : i)}>
            <span>{it.q}</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
              <path d="M6 9l6 6 6-6" />
            </svg>
          </button>
          {deschis === i && <p>{it.a}</p>}
        </div>
      ))}
    </div>
  );
}
