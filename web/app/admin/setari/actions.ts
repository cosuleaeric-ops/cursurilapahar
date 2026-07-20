"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import bcrypt from "bcryptjs";
import { sql } from "@/lib/db";
import { getSession, type Session } from "@/lib/auth";

async function requireOwner(): Promise<Session> {
  const s = await getSession();
  if (!s) redirect("/login");
  if (s.role !== "owner") redirect("/admin");
  return s;
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

async function setSetting(key: string, value: unknown): Promise<void> {
  await sql`
    INSERT INTO settings (key, value) VALUES (${key}, ${JSON.stringify(value)})
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()
  `;
}

export async function saveQuickLinks(formData: FormData): Promise<void> {
  await requireOwner();
  const icons = formData.getAll("ql_icon").map(String);
  const labels = formData.getAll("ql_label").map(String);
  const urls = formData.getAll("ql_url").map(String);
  const links = [];
  for (let i = 0; i < labels.length; i++) {
    const label = labels[i].trim();
    const url = (urls[i] ?? "").trim();
    if (label && url) links.push({ label, url, icon: (icons[i] ?? "🔗").trim() || "🔗" });
  }
  await setSetting("quick_links", links);
  revalidatePath("/admin");
  redirect("/admin/setari?saved=1");
}

export async function saveKit(formData: FormData): Promise<void> {
  await requireOwner();
  await setSetting("kit_api_key", g(formData, "kit_api_key"));
  await setSetting("kit_form_id", g(formData, "kit_form_id"));
  redirect("/admin/setari?saved=1");
}

export async function saveBrevo(formData: FormData): Promise<void> {
  await requireOwner();
  await setSetting("brevo_api_key", g(formData, "brevo_api_key"));
  redirect("/admin/setari?saved=1");
}

export async function saveHeadScripts(formData: FormData): Promise<void> {
  await requireOwner();
  await setSetting("head_scripts", String(formData.get("head_scripts") ?? ""));
  revalidatePath("/", "layout");
  redirect("/admin/setari?saved=1");
}

export async function addRecurring(): Promise<void> {
  await requireOwner();
  await sql`
    INSERT INTO recurring_tasks (legacy_id, type, title, assigned_to, days, position)
    VALUES (${"rec_" + Math.random().toString(16).slice(2, 14)}, 'monthly', 'Task nou', 'eric6', '{}',
            (SELECT COALESCE(MAX(position), 0) + 1 FROM recurring_tasks))
  `;
  redirect("/admin/setari?rec=ok#rec");
}

export async function saveRecurring(formData: FormData): Promise<void> {
  await requireOwner();
  const id = Number(g(formData, "id"));
  const title = g(formData, "title");
  if (!id || !title) redirect("/admin/setari#rec");
  let assigned = g(formData, "assigned_to");
  const valid = (await sql`SELECT username FROM users`) as { username: string }[];
  if (!valid.some((u) => u.username === assigned)) assigned = "eric6";
  const days = [...new Set(formData.getAll("days").map(Number).filter((d) => d >= 1 && d <= 31))].sort((a, b) => a - b);
  await sql`
    UPDATE recurring_tasks SET title = ${title}, assigned_to = ${assigned}, days = ${days}
    WHERE id = ${id} AND type = 'monthly'
  `;
  redirect("/admin/setari?rec=ok#rec");
}

export async function saveRecurringSystemTitle(formData: FormData): Promise<void> {
  await requireOwner();
  const id = Number(g(formData, "id"));
  const title = g(formData, "title");
  if (!id || !title) redirect("/admin/setari#rec");
  await sql`UPDATE recurring_tasks SET title = ${title} WHERE id = ${id} AND type = 'system'`;
  redirect("/admin/setari?rec=ok#rec");
}

export async function deleteRecurring(formData: FormData): Promise<void> {
  await requireOwner();
  const id = Number(g(formData, "id"));
  if (!id) redirect("/admin/setari#rec");
  await sql`DELETE FROM recurring_tasks WHERE id = ${id} AND type = 'monthly'`;
  redirect("/admin/setari?rec=ok#rec");
}

export async function changePassword(formData: FormData): Promise<void> {
  const s = await requireOwner();
  const pw = g(formData, "new_password");
  const confirm = g(formData, "confirm_password");
  if (!pw || pw !== confirm || pw.length < 6) redirect("/admin/setari?error=1");
  const hash = await bcrypt.hash(pw, 10);
  await sql`UPDATE users SET password_hash = ${hash} WHERE username = ${s.username}`;
  redirect("/admin/setari?saved=1");
}
