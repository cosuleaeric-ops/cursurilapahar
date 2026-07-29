"use client";

import { useActionState } from "react";
import { intra } from "./actions";

export default function PasswordGate() {
  const [msg, action, pending] = useActionState(intra, null);

  return (
    <div className="fb-gate">
      <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Anton&family=Rubik:wght@300;400;500;600;700&display=swap"
      />
      <style>{`
        .fb-gate {
          min-height: 100vh; display: flex; align-items: center; justify-content: center;
          background: #0D0D0D; font-family: 'Rubik', sans-serif; padding: 24px;
        }
        .fb-gate form {
          background: #161616; border: 1px solid #2a2a2a; border-radius: 14px;
          padding: 36px 32px; width: 100%; max-width: 380px; text-align: center;
        }
        .fb-gate h1 {
          font-family: 'Anton', sans-serif; text-transform: uppercase; line-height: 1.2;
          color: #F5F0E6; font-size: 26px; font-weight: 400; margin-bottom: 6px;
        }
        .fb-gate p { color: #9a9a9a; font-size: 14px; margin-bottom: 20px; }
        .fb-gate input {
          width: 100%; padding: 12px 14px; border: 1px solid #333; border-radius: 8px;
          background: #0D0D0D; color: #F5F0E6; font-size: 15px; margin-bottom: 12px;
        }
        .fb-gate input:focus { outline: none; border-color: #C9A84C; }
        .fb-gate button {
          width: 100%; padding: 12px; border-radius: 8px; background: #C9A84C; color: #0D0D0D;
          font-weight: 600; font-size: 15px; border: none; cursor: pointer;
        }
        .fb-gate button:hover { background: #d9b95e; }
        .fb-gate button:disabled { opacity: .6; cursor: default; }
        .fb-gate .err {
          background: rgba(220, 68, 68, .12); color: #e07a7a; border: 1px solid rgba(220, 68, 68, .3);
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
