"use client";

import { useState } from "react";

export default function FaqList({ items }: { items: { q: string; a: string }[] }) {
  const [open, setOpen] = useState<number | null>(null);

  return (
    <div className="faq-list">
      {items.map((f, i) => (
        <div className="faq-item" key={i}>
          <button
            className="faq-question"
            aria-expanded={open === i}
            onClick={() => setOpen((o) => (o === i ? null : i))}
          >
            <span>{f.q}</span>
            <span className="faq-icon" aria-hidden="true"></span>
          </button>
          <div className={`faq-answer${open === i ? " open" : ""}`}>
            <div>
              <p>{f.a}</p>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
