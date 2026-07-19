"use server";

import { sql } from "@/lib/db";

export async function subscribeNewsletter(_prev: string | null, formData: FormData): Promise<string> {
  const email = String(formData.get("email") ?? "").trim();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return "Email invalid.";

  const rows = (await sql`
    SELECT key, value FROM settings WHERE key IN ('kit_api_key', 'kit_form_id')
  `) as { key: string; value: unknown }[];
  const s = Object.fromEntries(rows.map((r) => [r.key, String(r.value ?? "").replace(/\s+/g, "")]));
  if (!s.kit_api_key || !s.kit_form_id) return "Newsletterul nu e configurat încă.";

  try {
    const res = await fetch(`https://api.convertkit.com/v3/forms/${encodeURIComponent(s.kit_form_id)}/subscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ api_key: s.kit_api_key, email }),
    });
    const data = (await res.json().catch(() => null)) as { subscription?: unknown; message?: string; error?: string } | null;
    if (res.ok && data?.subscription) {
      return "Mulțumim! Te vom anunța cu 2 săptămâni înainte de fiecare eveniment.";
    }
    return data?.message ?? data?.error ?? `Eroare (HTTP ${res.status}). Încearcă din nou.`;
  } catch {
    return "Eroare de conexiune. Încearcă din nou.";
  }
}
