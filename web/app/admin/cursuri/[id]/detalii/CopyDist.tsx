"use client";

import { useState } from "react";

// Butonul „Copiaza" de lângă Distributie bilete (id="distCopyBtn" în PHP).
export default function CopyDist({ text }: { text: string }) {
  const [done, setDone] = useState(false);
  return (
    <button
      type="button"
      className="btn btn-ghost"
      style={{ fontSize: 12, padding: "5px 12px" }}
      onClick={() => {
        navigator.clipboard.writeText(text);
        setDone(true);
        setTimeout(() => setDone(false), 1500);
      }}
    >
      {done ? "Copiat ✓" : "Copiaza"}
    </button>
  );
}
