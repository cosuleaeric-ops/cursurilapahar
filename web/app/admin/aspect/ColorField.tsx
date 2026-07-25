"use client";

import { useEffect } from "react";

// Aceleași câmpuri ca în aspect-tab.php: un singur input text cu `data-coloris`,
// inițializat de Coloris cu paleta din admin/assets/js/admin-aspect.js.
const SWATCHES = ["#0D0D0D", "#161616", "#1A1A1A", "#ffffff", "#C9A84C", "#b8922e", "#FFB000", "#E8E4DC", "#9CA3AF"];

declare global {
  interface Window {
    Coloris?: (opts: Record<string, unknown>) => void;
  }
}

/** Încarcă scriptul Coloris și îl pornește pe câmpurile [data-coloris]. */
export function ColorisInit() {
  useEffect(() => {
    const start = () =>
      window.Coloris?.({
        el: "[data-coloris]",
        format: "hex",
        forceAlpha: false,
        focusInput: false,
        selectInput: true,
        clearButton: false,
        swatches: SWATCHES,
      });

    if (window.Coloris) {
      start();
      return;
    }
    const s = document.createElement("script");
    s.src = "/assets/js/coloris.min.js";
    s.onload = start;
    document.body.appendChild(s);
  }, []);

  return null;
}

export default function ColorField({ name, label, value }: { name: string; label: string; value: string }) {
  return (
    <div className="form-group" style={{ margin: 0 }}>
      <label>{label}</label>
      <input type="text" name={name} defaultValue={value} data-coloris="" />
    </div>
  );
}
