"use server";

import { headers } from "next/headers";
import { sql } from "@/lib/db";
import { createMagicToken } from "@/lib/auth";
import { sendMagicLinkEmail } from "@/lib/brevo";

/**
 * Login prin magic link: primești pe email un link semnat, valabil 15 minute.
 * Răspunsul e mereu același, ca să nu dezvăluie ce adrese există în sistem.
 */
export async function requestMagicLink(_prev: string | null, formData: FormData): Promise<string> {
  const input = String(formData.get("email") || "").trim().toLowerCase();
  const generic = "Dacă adresa e în sistem, ți-am trimis linkul de acces. Verifică-ți emailul.";
  if (!input) return "Scrie adresa ta de email.";

  const rows = (await sql`
    SELECT username, email FROM users WHERE lower(email) = ${input} OR lower(username) = ${input}
  `) as { username: string; email: string | null }[];
  const user = rows[0];
  if (!user?.email) return generic;

  const h = await headers();
  const host = h.get("x-forwarded-host") ?? h.get("host") ?? "cursuri-la-pahar.vercel.app";
  const proto = host.startsWith("localhost") ? "http" : "https";
  const token = await createMagicToken(user.username);

  await sendMagicLinkEmail(user.email, user.username === "eric6" ? "Eric" : user.username, `${proto}://${host}/login/magic?token=${token}`);
  return generic;
}
