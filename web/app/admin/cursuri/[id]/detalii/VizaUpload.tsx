"use client";

import { useRef, useState } from "react";
import { uploadViza } from "./actions";

// Ca în view.php: cardul de viză n-are buton de submit — uploadul pornește
// singur la alegerea fișierului, iar eticheta zonei arată progresul.
export default function VizaUpload({ id, hasFile }: { id: number; hasFile: boolean }) {
  const [busy, setBusy] = useState(false);
  const formRef = useRef<HTMLFormElement>(null);

  return (
    <form ref={formRef} action={uploadViza}>
      <input type="hidden" name="id" value={id} />
      <div className="upload-zone">
        <input
          type="file"
          name="viza_file"
          accept=".pdf"
          disabled={busy}
          onChange={(e) => {
            if (!e.target.files?.[0]) return;
            // submit înainte de setBusy, ca fișierul să intre în FormData
            formRef.current?.requestSubmit();
            setBusy(true);
          }}
        />
        <p>{busy ? "⏳ Incarc PDF…" : hasFile ? "Inlocuieste viză" : "Trage sau apasa pentru a incarca Viză PDF"}</p>
      </div>
    </form>
  );
}
