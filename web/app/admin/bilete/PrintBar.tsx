"use client";

export default function PrintBar({ back }: { back: string }) {
  return (
    <div className="toolbar">
      <button type="button" onClick={() => window.print()}>
        Listeaza / Salveaza PDF
      </button>
      <a href={back}>← Inapoi</a>
    </div>
  );
}
