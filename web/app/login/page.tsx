"use client";

import { useActionState } from "react";
import { requestMagicLink } from "./actions";

export default function LoginPage() {
  const [msg, action, pending] = useActionState(requestMagicLink, null);

  return (
    <>
      <link rel="stylesheet" href="/assets/css/admin.css" />
      <style>{`.login-box input[type="email"] {
  width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: var(--radius);
  font-size: 14px; margin-bottom: 12px; background: var(--surface); color: var(--text);
  transition: border-color .15s, box-shadow .15s; box-sizing: border-box;
}
.login-box input[type="email"]:focus {
  outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}`}</style>
      <div className="login-wrap">
        <form className="login-box" action={action}>
          <h1>Cursuri la Pahar — Admin</h1>
          <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 14, textAlign: "center" }}>
            Scrie-ți adresa de email și îți trimitem un link de acces.
          </p>

          <input type="email" name="email" autoComplete="email" aria-label="Email" autoFocus />

          {msg && <div className="login-error" style={{ background: "#eff6ff", color: "#2563eb", borderColor: "#bfdbfe" }}>{msg}</div>}

          <button type="submit" className="btn btn-primary" style={{ width: "100%", justifyContent: "center" }} disabled={pending}>
            {pending ? "Se trimite…" : "Trimite-mi linkul"}
          </button>
        </form>
      </div>
    </>
  );
}
