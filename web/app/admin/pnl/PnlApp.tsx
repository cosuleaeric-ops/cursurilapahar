"use client";

import { useEffect, useRef } from "react";

export type PnlConfig = {
  csrf: string;
  api: string;
  cheltuialaEmoji: Record<string, string>;
  cheltuialaEmojiKeywords: Record<string, string>;
};

const CHART_SRC = "https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js";
const APP_SRC = "/admin/statistici/pnl/app.js";

// Modulele sunt evaluate o singură dată per URL, deci fiecare intrare pe pagină
// primește un query nou ca app.js să se execute din nou pe DOM-ul proaspăt.
let loadCount = 0;

function loadScript(src: string, type?: "module") {
  return new Promise<void>((resolve, reject) => {
    const s = document.createElement("script");
    s.src = src;
    if (type) s.type = type;
    s.onload = () => resolve();
    s.onerror = () => reject(new Error(`nu s-a încărcat ${src}`));
    document.body.appendChild(s);
  });
}

// app.js (portat 1:1 din PHP) leagă listenerii o singură dată, la execuție, și
// mută overlay-urile modalelor în <body>. Ca <script defer> în markup rula
// înainte de hidratare: React re-crea nodurile, listenerii se pierdeau și
// butoanele de cheltuială/venit nu mai făceau nimic. Îl încărcăm de aici, adică
// după hidratare, și ca modul ca să nu intre în coliziune cu propriile const-uri
// globale la a doua vizită.
export default function PnlApp({ config }: { config: PnlConfig }) {
  const cfg = useRef(config);
  const started = useRef(false);

  useEffect(() => {
    if (!started.current) {
      started.current = true;
      (window as unknown as { PNL: PnlConfig }).PNL = cfg.current;
      (async () => {
        if (!("Chart" in window)) await loadScript(CHART_SRC).catch(() => {});
        await loadScript(`${APP_SRC}?i=${++loadCount}`, "module").catch(() => {});
      })();
    }
    return () => {
      // Overlay-urile mutate în <body> ies din arborele React, deci le curățăm
      // manual: altfel rămân în DOM și la revenire getElementById le-ar nimeri
      // pe cele vechi, fără listeneri.
      document.querySelectorAll("body > .pnl-modal-overlay").forEach((el) => el.remove());
      document.querySelectorAll(`script[src^="${APP_SRC}?"]`).forEach((el) => el.remove());
      document.documentElement.classList.remove("pnl-modal-open");
    };
  }, []);

  return null;
}
