"use server";

import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";

const CATEGORIES = new Set(["sustine", "gazduieste", "parteneriat"]);

export async function submitColaborare(_prev: string | null, formData: FormData): Promise<string> {
  const category = String(formData.get("form_type") ?? "");
  if (!CATEGORIES.has(category)) return "Formular invalid.";

  const email = String(formData.get("email") ?? "").trim();
  if (!email) return "Completează adresa de email.";
  const name = String(formData.get("name") ?? formData.get("contact_person") ?? "").trim();

  const payload: Record<string, string> = {};
  for (const key of Array.from(new Set(formData.keys()))) {
    if (key === "form_type" || key.startsWith("$ACTION")) continue;
    const vals = formData
      .getAll(key)
      .map((v) => String(v).trim())
      .filter(Boolean);
    if (vals.length) payload[key] = vals.join(", ");
  }

  await sql`
    INSERT INTO messages (category, name, email, payload)
    VALUES (${category}, ${name || null}, ${email}, ${JSON.stringify(payload)})
  `;
  revalidatePath("/admin/mesaje");
  return "✓ Trimis! Îți răspundem cât putem de repede.";
}
