"use client";

import { useActionState } from "react";
import { submitContact } from "./contact-action";
import { subscribeNewsletter } from "./newsletter-action";

export function NewsletterForm() {
  const [msg, action, pending] = useActionState(subscribeNewsletter, null);
  return (
    <form className="newsletter-form" action={action}>
      <div className="newsletter-fields">
        <input type="email" name="email" required autoComplete="email" aria-label="Email" />
        <button type="submit" className="btn btn-accent" disabled={pending}>
          {pending ? "Se trimite…" : "Anunță-mă"}
        </button>
      </div>
      <p className="newsletter-note">100% gratuit. Te poți dezabona oricând.</p>
      <div className="form-message" aria-live="polite">{msg}</div>
    </form>
  );
}

export function ContactForm() {
  const [msg, action, pending] = useActionState(submitContact, null);
  return (
    <form className="contact-form" action={action}>
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
      {msg && <div className="form-message" aria-live="polite">{msg}</div>}
    </form>
  );
}
