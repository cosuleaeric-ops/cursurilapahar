"use server";

import { sql } from "@/lib/db";
import { EMAIL_RE, type FormResult } from "./form-shared";

export async function subscribeNewsletter(rawEmail: string): Promise<FormResult> {
  const email = String(rawEmail ?? "").trim();
  // api/subscribe.php:7-8
  if (!EMAIL_RE.test(email)) return { success: false, message: "Email invalid." };

  const rows = (await sql`
    SELECT key, value FROM settings WHERE key IN ('kit_api_key', 'kit_form_id')
  `) as { key: string; value: unknown }[];
  const s = Object.fromEntries(rows.map((r) => [r.key, String(r.value ?? "").replace(/\s+/g, "")]));
  // api/subscribe.php:15-16 — mesaj distinct pentru fiecare setare lipsă.
  if (!s.kit_api_key) return { success: false, message: "API key lipsă în setări Kit." };
  if (!s.kit_form_id) return { success: false, message: "Form ID lipsă în setări Kit." };

  try {
    const res = await fetch(`https://api.convertkit.com/v3/forms/${encodeURIComponent(s.kit_form_id)}/subscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ api_key: s.kit_api_key, email }),
    });
    const text = await res.text();
    const data = (() => {
      try {
        return JSON.parse(text) as { subscription?: unknown; message?: string; error?: string };
      } catch {
        return null;
      }
    })();

    if (res.ok && data?.subscription) return { success: true };
    // api/subscribe.php:42 — mesajul de la Kit, altfel „HTTP <cod>: <primii 200 de caractere>".
    return {
      success: false,
      message: data?.message ?? data?.error ?? `HTTP ${res.status}: ${text.substring(0, 200)}`,
    };
  } catch (err) {
    // api/subscribe.php:35
    return { success: false, message: `Eroare conexiune: ${err instanceof Error ? err.message : ""}` };
  }
}
