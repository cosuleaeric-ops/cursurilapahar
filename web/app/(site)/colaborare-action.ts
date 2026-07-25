"use server";

import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";
import { sendConfirmationEmail } from "@/lib/brevo";
import { sendTeamNotification } from "./team-email";
import { EMAIL_RE, phpPayload, phpValue, type FormResult } from "./form-shared";

const CATEGORIES = new Set(["sustine", "gazduieste", "parteneriat", "sponsorizare"]);

export async function submitColaborare(formType: string, formData: FormData): Promise<FormResult> {
  if (!CATEGORIES.has(formType)) return { success: false, message: "Formular invalid." };

  // api/contact.php:23-27 — singura validare de pe server e emailul; restul câmpurilor
  // se salvează exact cum au venit, chiar și goale.
  const email = String(formData.get("email") ?? "").trim();
  if (!EMAIL_RE.test(email)) return { success: false, message: "Email invalid." };

  // api/contact.php:75 — numele vizitatorului: name, apoi contact_person, apoi partner_name.
  const rawName = formData.get("name") ?? formData.get("contact_person") ?? formData.get("partner_name") ?? "";
  const name = phpValue(String(rawName));

  const payload = phpPayload(formData);

  try {
    await sql`
      INSERT INTO messages (category, name, email, payload)
      VALUES (${formType}, ${name || null}, ${email}, ${JSON.stringify(payload)})
    `;
  } catch {
    // main.js:383-387 — clientul afișează caseta roșie și păstrează datele completate.
    return { success: false, message: "" };
  }

  await sendTeamNotification(formType, email, payload);
  await sendConfirmationEmail(formType, email, name);
  revalidatePath("/admin/mesaje");
  return { success: true };
}
