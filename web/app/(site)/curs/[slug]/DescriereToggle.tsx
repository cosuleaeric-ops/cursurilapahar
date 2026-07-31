"use client";

import { useState } from "react";

/** Descrierea pornește scurtată, ca pe paginile de bilete: câteva rânduri și „Mai multe detalii". */
export default function DescriereToggle({ html }: { html: string }) {
  const [deschis, setDeschis] = useState(false);

  return (
    <div className="curs-desc">
      <div
        className={`curs-desc-html${deschis ? "" : " curs-desc-html--scurt"}`}
        dangerouslySetInnerHTML={{ __html: html }}
      />
      <button type="button" className="curs-desc-toggle" onClick={() => setDeschis((v) => !v)}>
        {deschis ? "Mai puține detalii" : "Mai multe detalii"}
      </button>
    </div>
  );
}
