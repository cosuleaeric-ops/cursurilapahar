"use client";

import { useEffect, useRef, useState } from "react";
import { switchUser } from "./actions";

const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);

export default function UserSwitcher({
  realUsername,
  viewUsername,
  users,
}: {
  realUsername: string;
  viewUsername: string;
  users: string[];
}) {
  const impersonating = viewUsername !== realUsername;
  const [open, setOpen] = useState(false);
  const wrap = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const close = (e: MouseEvent) => {
      if (wrap.current && !wrap.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener("click", close);
    return () => document.removeEventListener("click", close);
  }, [open]);

  if (impersonating) {
    return (
      <>
        <span style={{ fontSize: 11, background: "#fef3c7", color: "#92400e", padding: "3px 8px", borderRadius: 12, fontWeight: 600 }}>
          Vizualizezi ca: {cap(viewUsername)}
        </span>
        <form action={switchUser} style={{ margin: 0 }}>
          <input type="hidden" name="target_username" value={realUsername} />
          <button
            type="submit"
            style={{ fontSize: 11, padding: "3px 8px", border: "1px solid #d1d5db", borderRadius: 6, background: "#fff", cursor: "pointer", color: "#374151" }}
          >
            Înapoi la {cap(realUsername)}
          </button>
        </form>
      </>
    );
  }

  return (
    <>
      <span style={{ fontSize: 12, color: "#a0aec0" }}>{cap(realUsername)}</span>
      <div style={{ position: "relative" }} ref={wrap}>
        <button
          type="button"
          onClick={() => setOpen(!open)}
          style={{ padding: "2px 5px", border: "none", background: "none", cursor: "pointer", color: "#c0c8d4", fontSize: 10, lineHeight: 1 }}
          title="Schimbă cont"
        >
          ▾
        </button>
        {open && (
          <div
            style={{
              position: "absolute",
              right: 0,
              top: "calc(100% + 4px)",
              background: "#fff",
              border: "1px solid #e5e7eb",
              borderRadius: 8,
              boxShadow: "0 4px 16px rgba(0,0,0,.1)",
              minWidth: 140,
              zIndex: 999,
            }}
          >
            {users
              .filter((u) => u !== realUsername)
              .map((u) => (
                <form key={u} action={switchUser} style={{ margin: 0 }}>
                  <input type="hidden" name="target_username" value={u} />
                  <button
                    type="submit"
                    style={{ display: "block", width: "100%", textAlign: "left", padding: "8px 14px", border: "none", background: "none", cursor: "pointer", fontSize: 13, color: "#374151" }}
                  >
                    {cap(u)}
                  </button>
                </form>
              ))}
          </div>
        )}
      </div>
    </>
  );
}
