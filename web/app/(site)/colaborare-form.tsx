"use client";

import { useActionState } from "react";
import { submitColaborare } from "./colaborare-action";

export function ColaborareForm({
  formType,
  children,
}: {
  formType: "sustine" | "gazduieste" | "parteneriat";
  children: React.ReactNode;
}) {
  const [msg, action, pending] = useActionState(submitColaborare, null);
  return (
    <form className="inner-page-form" action={action}>
      <input type="hidden" name="form_type" value={formType} />
      {children}
      <button type="submit" className="btn btn-accent" disabled={pending}>
        {pending ? "Se trimite…" : "Trimite"}
      </button>
      {msg && (
        <div className="form-message" aria-live="polite">
          {msg}
        </div>
      )}
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
