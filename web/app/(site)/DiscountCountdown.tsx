"use client";

import { useEffect, useState } from "react";

// Countdown-ul reducerii de pe card — port din main.js (HH:MM:SS, ore totale).
// La expirare se ascunde și ascunde și badge-ul −X% din același card.
export default function DiscountCountdown({ endsAt, code }: { endsAt: string; code: string }) {
  const [text, setText] = useState("--:--:--");
  const [expired, setExpired] = useState(false);

  useEffect(() => {
    const el = document.querySelector(`[data-ends-at="${endsAt}"]`);
    const tick = () => {
      const diff = Math.floor((new Date(endsAt).getTime() - Date.now()) / 1000);
      if (!isFinite(diff) || diff <= 0) {
        setExpired(true);
        const badge = el?.closest(".event-card")?.querySelector<HTMLElement>(".discount-badge");
        if (badge) badge.style.display = "none";
        return;
      }
      const h = Math.floor(diff / 3600);
      const m = Math.floor((diff % 3600) / 60);
      const s = diff % 60;
      setText(`${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`);
    };
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [endsAt]);

  if (expired) return null;
  return (
    <div className="discount-countdown" data-ends-at={endsAt}>
      <div className="discount-countdown-row">
        <span className="discount-countdown-label">Reducerea expiră în</span>
        <span className="discount-countdown-time">{text}</span>
      </div>
      <div className="discount-code">
        ✨ Folosește codul <strong>{code}</strong>
      </div>
    </div>
  );
}
