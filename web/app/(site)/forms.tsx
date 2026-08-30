"use client";

import { useState } from "react";
import { submitContact } from "./contact-action";
import { subscribeNewsletter } from "./newsletter-action";
import { EMAIL_RE } from "./form-shared";

type Msg = { ok: boolean; text: string };

const msgClass = (msg: Msg | null) => `form-message${msg ? (msg.ok ? " success" : " error") : ""}`;

export function NewsletterForm() {
  const [msg, setMsg] = useState<Msg | null>(null);
  const [pending, setPending] = useState(false);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const email = String(new FormData(form).get("email") ?? "").trim();

    // main.js:141 — email gol: nu se afișează niciun mesaj, doar se iese.
    if (!email) return;
    if (!EMAIL_RE.test(email)) {
      setMsg({ ok: false, text: "Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro)." });
      return;
    }

    setPending(true);
    setMsg(null);
    try {
      const res = await subscribeNewsletter(email);
      if (!res.success) throw new Error(res.message || "Eroare necunoscută");
      setMsg({ ok: true, text: "Mulțumim! Te vom anunța cu 2 săptămâni înainte de fiecare eveniment." });
      form.reset();
    } catch (err) {
      // main.js:169-173 — mesajul de la server, altfel textul generic.
      const m = err instanceof Error ? err.message : "";
      setMsg({
        ok: false,
        text:
          m && m !== "Eroare necunoscută"
            ? m
            : "Ceva n-a mers bine. Încearcă din nou sau scrie-ne la contact@cursurilapahar.ro",
      });
    } finally {
      setPending(false);
    }
  }

  return (
    // index.php:343 — formularul are `novalidate`, deci `required` nu blochează trimiterea.
    <form className="newsletter-form" noValidate onSubmit={handleSubmit}>
      <div className="newsletter-fields">
        <input type="email" name="email" required autoComplete="email" aria-label="Email" />
        <button type="submit" className="btn btn-accent" disabled={pending}>
          {pending ? "Se trimite…" : "Anunță-mă"}
        </button>
      </div>
      <p className="newsletter-note">
        <strong>100% gratuit.</strong> Te poți dezabona oricând.
      </p>
      <div className={msgClass(msg)} aria-live="polite">
        {msg?.text ?? ""}
      </div>
    </form>
  );
}

export function ContactForm() {
  const [msg, setMsg] = useState<Msg | null>(null);
  const [pending, setPending] = useState(false);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const data = new FormData(form);
    const val = (k: string) => String(data.get(k) ?? "").trim();

    // main.js:199-209 — întâi toate cele trei câmpuri, apoi formatul emailului.
    if (!val("name") || !val("email") || !val("message")) {
      setMsg({ ok: false, text: "Te rugăm completează toate câmpurile." });
      return;
    }
    if (!EMAIL_RE.test(val("email"))) {
      setMsg({ ok: false, text: "Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro)." });
      return;
    }

    setPending(true);
    setMsg(null);
    try {
      const res = await submitContact(data);
      if (!res.success) throw new Error(res.message);
      setMsg({ ok: true, text: "Mesaj trimis! Îți răspundem în cel mai scurt timp." });
      form.reset();
    } catch {
      // main.js:226-228 — la contact, orice eroare arată același text.
      setMsg({ ok: false, text: "Ceva n-a mers bine. Scrie-ne direct la contact@cursurilapahar.ro" });
    } finally {
      setPending(false);
    }
  }

  return (
    // index.php:458 — `novalidate`; validarea e cea din main.js, replicată mai sus.
    <form className="contact-form" noValidate onSubmit={handleSubmit}>
      <div className="form-row">
        <div className="form-group">
          <label htmlFor="contactName">Nume</label>
          <input type="text" id="contactName" name="name" required />
        </div>
        <div className="form-group">
          <label htmlFor="contactEmail">Email</label>
          <input type="email" id="contactEmail" name="email" required />
        </div>
      </div>
      <div className="form-group">
        <label htmlFor="contactMsg">Mesaj</label>
        <textarea id="contactMsg" name="message" rows={5} required></textarea>
      </div>
      <button type="submit" className="btn btn-accent" disabled={pending}>
        {pending ? "Se trimite…" : "Trimite mesajul"}
      </button>
      <div className={msgClass(msg)} aria-live="polite">
        {msg?.text ?? ""}
      </div>
    </form>
  );
}
