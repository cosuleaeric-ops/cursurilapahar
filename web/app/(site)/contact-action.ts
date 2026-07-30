"use server";

import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";
import { sendConfirmationEmail } from "@/lib/brevo";
import { EMAIL_RE, phpPayload, phpValue, type FormResult } from "./form-shared";

export async function submitContact(formData: FormData): Promise<FormResult> {
  const email = String(formData.get("email") ?? "").trim();
  // api/contact.php:23-27 — pe server se verifică doar emailul.
  if (!EMAIL_RE.test(email)) return { success: false, message: "Email invalid." };

  const name = phpValue(String(formData.get("name") ?? ""));
  const payload = phpPayload(formData);

  try {
    await sql`
      INSERT INTO messages (category, name, email, payload)
      VALUES ('contact', ${name || null}, ${email}, ${JSON.stringify(payload)})
    `;
  } catch {
    // main.js:226-228 — orice eșec devine caseta roșie generică pe client.
    return { success: false, message: "" };
  }

  // Notificarea internă către contact@cursurilapahar.ro a fost scoasă; mesajele
  // se citesc doar din /admin/mesaje.
  await sendConfirmationEmail("contact", email, name);
  revalidatePath("/admin/mesaje");
  return { success: true };
}
