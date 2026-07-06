"use server";

import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";

export async function submitContact(_prev: string | null, formData: FormData): Promise<string> {
  const name = String(formData.get("name") ?? "").trim();
  const email = String(formData.get("email") ?? "").trim();
  const message = String(formData.get("message") ?? "").trim();
  if (!email || !message) return "Completează email și mesaj.";

  await sql`
    INSERT INTO messages (category, name, email, payload)
    VALUES ('contact', ${name || null}, ${email}, ${JSON.stringify({ message })})
  `;
  revalidatePath("/admin/mesaje");
  return "✓ Mesaj trimis! Îți răspundem cât putem de repede.";
}
