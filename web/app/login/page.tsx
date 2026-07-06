"use client";

import { useActionState } from "react";
import { login } from "./actions";
import styles from "./login.module.css";

export default function LoginPage() {
  const [error, action, pending] = useActionState(login, null);

  return (
    <main className={styles.main}>
      <form className={styles.card} action={action}>
        <h1 className={styles.title}>Admin · Curs la Pahar</h1>

        <label className={styles.label}>
          Utilizator
          <input className={styles.input} name="username" type="text" autoComplete="username" autoFocus />
        </label>

        <label className={styles.label}>
          Parolă
          <input className={styles.input} name="password" type="password" autoComplete="current-password" />
        </label>

        {error && <p className={styles.error}>{error}</p>}

        <button className={styles.btn} type="submit" disabled={pending}>
          {pending ? "Se verifică…" : "Intră"}
        </button>
      </form>
    </main>
  );
}
