"use client";

import { useActionState } from "react";
import { intra } from "./actions";

export default function PasswordGate() {
  const [msg, action, pending] = useActionState(intra, null);

  return (
    <div className="fb-gate">
      <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
      />
      <style>{`
        .fb-gate {
          min-height: 100vh; display: flex; align-items: center; justify-content: center;
          background: #ffffff; font-family: 'Poppins', sans-serif; padding: 24px;
        }
        .fb-gate form {
          background: #fafafa; border: 1px solid #e5e5e5; border-radius: 14px;
          padding: 36px 32px; width: 100%; max-width: 380px; text-align: center;
        }
        .fb-gate h1 {
          text-transform: uppercase; line-height: 1.2;
          color: #171717; font-size: 24px; font-weight: 800; margin-bottom: 6px;
        }
        .fb-gate p { color: #6b6b6b; font-size: 14px; margin-bottom: 20px; }
        .fb-gate input {
          width: 100%; padding: 12px 14px; border: 1px solid #d4d4d4; border-radius: 8px;
          background: #ffffff; color: #171717; font-size: 15px; margin-bottom: 12px;
          font-family: inherit;
        }
        .fb-gate input:focus { outline: none; border-color: #C9A84C; }
        .fb-gate button {
          width: 100%; padding: 12px; border-radius: 8px; background: #C9A84C; color: #171717;
          font-weight: 600; font-size: 15px; border: none; cursor: pointer; font-family: inherit;
        }
        .fb-gate button:hover { background: #b9983c; }
        .fb-gate button:disabled { opacity: .6; cursor: default; }
        .fb-gate .err {
          background: #fdeaea; color: #c04040; border: 1px solid #f2c5c5;
          border-radius: 8px; padding: 9px 12px; font-size: 13px; margin-bottom: 12px;
        }
      `}</style>
      <form action={action}>
        <h1>Feedback participanți</h1>
        <p>Pagina e protejată. Scrie parola ca să vezi feedback-ul de la toate cursurile.</p>
        <input type="password" name="parola" aria-label="Parolă" autoFocus />
        {msg && <div className="err">{msg}</div>}
        <button type="submit" disabled={pending}>{pending ? "Se verifică…" : "Intră"}</button>
      </form>
    </div>
  );
}
