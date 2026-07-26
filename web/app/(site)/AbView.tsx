"use client";

import { useEffect } from "react";

/**
 * Contorizează o afișare de homepage pentru testul A/B. Înainte incrementa
 * `page.tsx` la render; homepage-ul fiind acum cache-uit, serverul nu mai vede
 * fiecare afișare, așa că o anunță browserul. Filtrele (boți, prefetch, sesiune
 * de admin) au rămas pe server, în ruta care primește semnalul.
 */
export default function AbView() {
  useEffect(() => {
    fetch("/api/ab/view", { method: "POST", keepalive: true }).catch(() => {});
  }, []);
  return null;
}
