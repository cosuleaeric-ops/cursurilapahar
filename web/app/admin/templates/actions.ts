"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

export type Template = { icon?: string; label?: string; text?: string };

export async function saveTemplates(formData: FormData): Promise<void> {
  if (!(await getSession())) redirect("/login");
  const icons = formData.getAll("tpl_icon").map(String);
  const labels = formData.getAll("tpl_label").map(String);
  const texts = formData.getAll("tpl_text").map(String);
  const out: Template[] = [];
  for (let i = 0; i < labels.length; i++) {
    const label = labels[i].trim();
    const text = (texts[i] ?? "").trim();
    if (label && text) out.push({ icon: (icons[i] ?? "").trim() || "📋", label, text });
  }
  await sql`
    INSERT INTO settings (key, value) VALUES ('templates', ${JSON.stringify(out)})
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()
  `;
  revalidatePath("/admin");
  redirect("/admin/templates?saved=1");
}
