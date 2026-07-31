"use client";

import { useState } from "react";

/**
 * Codul ajunge în URL (`?cod=...`), iar pagina se re-randează pe server cu
 * biletele codului. Nu se calculează nimic în browser.
 */
export default function CodReducere({ slug, gresit }: { slug: string; gresit?: string }) {
  const [deschis, setDeschis] = useState(!!gresit);

  if (!deschis)
    return (
      <button type="button" className="cod-link" onClick={() => setDeschis(true)}>
        Ai un cod de reducere?
      </button>
    );

  return (
    <form className="cod-form" action={`/curs/${slug}`} method="get">
      <input name="cod" defaultValue={gresit ?? ""} autoFocus aria-label="Cod de reducere" />
      <button type="submit" className="btn btn-secondary">
        Aplică
      </button>
      {gresit && <span className="cod-err">Codul {gresit} nu e valid pentru cursul ăsta.</span>}
    </form>
  );
}
