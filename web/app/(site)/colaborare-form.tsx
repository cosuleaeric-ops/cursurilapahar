"use client";

import { useState } from "react";
import { submitColaborare } from "./colaborare-action";
import { EMAIL_RE } from "./form-shared";

type Msg = { ok: boolean; text: string };

export function ColaborareForm({
  formType,
  children,
  buttonClassName = "btn btn-accent",
  buttonLabel = "Trimite",
}: {
  formType: "sustine" | "gazduieste" | "parteneriat" | "sponsorizare";
  children: React.ReactNode;
  buttonClassName?: string;
  buttonLabel?: string;
}) {
  const [msg, setMsg] = useState<Msg | null>(null);
  const [pending, setPending] = useState(false);
  // main.js:388-391: în `finally` eticheta e readusă hardcodat la „Trimite",
  // indiferent cum a pornit butonul (parteneri.php:269 = „Hai să vorbim").
  const [label, setLabel] = useState(buttonLabel);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const data = new FormData(form);

    // main.js:341-344 — grupurile de checkbox nebifate pleacă tot, ca listă goală.
    form.querySelectorAll<HTMLInputElement>('input[type="checkbox"]').forEach((cb) => {
      if (!data.has(cb.name)) data.append(cb.name, "");
    });

    // main.js:347-358 — singura validare de pe client e emailul; restul câmpurilor
    // pot pleca goale, iar mesajul e același și pentru email lipsă, și pentru format greșit.
    const email = String(data.get("email") ?? "").trim();
    if (!email || !EMAIL_RE.test(email)) {
      setMsg({ ok: false, text: "Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro)." });
      return;
    }

    setPending(true);
    setMsg(null);
    try {
      const res = await submitColaborare(formType, data);
      if (!res.success) throw new Error(res.message);
      setMsg({ ok: true, text: "Mulțumim! Te vom contacta în cel mai scurt timp." });
      form.reset();
    } catch (err) {
      // main.js:383-387 — la eroare datele completate rămân în formular.
      const m = err instanceof Error ? err.message : "";
      setMsg({ ok: false, text: m || "Ceva n-a mers bine. Scrie-ne direct la contact@cursurilapahar.ro" });
    } finally {
      setPending(false);
      setLabel("Trimite");
    }
  }

  return (
    // `novalidate` e pe toate formularele publice din PHP (ex. parteneri.php:244),
    // deci atributele `required` din markup nu blochează trimiterea.
    <form className="inner-page-form" data-form-type={formType} noValidate onSubmit={handleSubmit}>
      {children}
      <button type="submit" className={buttonClassName} disabled={pending}>
        {pending ? "Se trimite…" : label}
      </button>
      {/* CSS: .form-message e display:none; devine vizibil doar cu .success/.error */}
      <div className={`form-message${msg ? (msg.ok ? " success" : " error") : ""}`} aria-live="polite">
        {msg?.text ?? ""}
      </div>
    </form>
  );
}

export function BackLink() {
  return (
    <a
      href="/"
      onClick={(e) => {
        if (history.length > 1) {
          e.preventDefault();
          history.back();
        }
      }}
      className="page-hero-back"
      style={{ marginBottom: 16, display: "inline-flex" }}
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M19 12H5M12 5l-7 7 7 7" />
      </svg>
      Înapoi
    </a>
  );
}
