"use client";

import { useRef } from "react";
import { regenerateSyncToken } from "./actions";

// Cardul „🔄 Sync Token" din admin/partials/config-tab.php.
export default function SyncToken({ token }: { token: string }) {
  const inputRef = useRef<HTMLInputElement>(null);

  return (
    <div className="card">
      <div className="card-title">🔄 Sync Token</div>
      <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 12 }}>
        Folosit de scriptul <code>./sync.sh</code> pentru a sincroniza datele din producție în mediul local. Pune
        valoarea într-un fișier <code>.sync-token</code> în root-ul proiectului local.
      </p>
      <div style={{ display: "flex", gap: 8, alignItems: "center", marginBottom: 10 }}>
        <input
          ref={inputRef}
          type="text"
          value={token}
          readOnly
          style={{ fontFamily: "monospace", fontSize: 12, flex: 1 }}
          onFocus={(e) => e.currentTarget.select()}
        />
        {/* PHP (admin-common.js:12-17): butonul selectează inputul și copiază, fără feedback vizual */}
        <button
          type="button"
          className="btn btn-secondary btn-sm"
          onClick={() => {
            inputRef.current?.select();
            navigator.clipboard.writeText(token);
          }}
        >
          Copiază
        </button>
        <form
          action={regenerateSyncToken}
          style={{ margin: 0 }}
          onSubmit={(e) => {
            if (!confirm("Regenerezi tokenul? Va trebui să-l actualizezi local.")) e.preventDefault();
          }}
        >
          <button type="submit" className="btn btn-secondary btn-sm">
            Regenerează
          </button>
        </form>
      </div>
      <p className="form-desc" style={{ margin: 0 }}>
        Conținut <code>.sync-token</code>:
      </p>
      <pre style={{ background: "#f5f5f5", padding: 10, borderRadius: 4, fontSize: 12, margin: "6px 0 0", userSelect: "all" }}>
        {`SYNC_URL=https://cursurilapahar.ro/admin/sync-export.php\nSYNC_TOKEN=${token}`}
      </pre>
    </div>
  );
}
