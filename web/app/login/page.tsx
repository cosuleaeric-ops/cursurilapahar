"use client";

import { useActionState } from "react";
import { login } from "./actions";

export default function LoginPage() {
  const [error, action, pending] = useActionState(login, null);

  return (
    <>
      <link rel="stylesheet" href="/assets/css/admin.css" />
      <div className="login-wrap">
        <form className="login-box" action={action}>
          <h1>Cursuri la Pahar — Admin</h1>

          <input type="text" name="username" autoComplete="username" aria-label="Utilizator" autoFocus />
          <input type="password" name="password" autoComplete="current-password" aria-label="Parolă" />

          {error && <div className="login-error">{error}</div>}

          <button type="submit" className="btn btn-primary" style={{ width: "100%", justifyContent: "center" }} disabled={pending}>
            {pending ? "Se verifică…" : "Intră"}
          </button>
        </form>
      </div>
    </>
  );
}
