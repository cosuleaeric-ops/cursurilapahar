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

  // Un user poate avea mai multe adrese (ex. cea de firmă și cea personală).
  const rows = (await sql`
    SELECT username, email, emails FROM users
    WHERE lower(username) = ${input}
       OR lower(email) = ${input}
       OR EXISTS (SELECT 1 FROM unnest(emails) e WHERE lower(e) = ${input})
  `) as { username: string; email: string | null; emails: string[] | null }[];
  const user = rows[0];
  if (!user) return generic;

  // Linkul pleacă la adresa scrisă de el, dacă e una dintre ale lui.
  const own = (user.emails ?? []).find((e) => e.toLowerCase() === input);
  const target = own ?? user.email;
  if (!target) return generic;

  const h = await headers();
  const host = h.get("x-forwarded-host") ?? h.get("host") ?? "cursuri-la-pahar.vercel.app";
  const proto = host.startsWith("localhost") ? "http" : "https";
  const token = await createMagicToken(user.username);

  const NAME: Record<string, string> = { eric6: "Eric", andy: "Andy" };
  await sendMagicLinkEmail(target, NAME[user.username] ?? user.username, `${proto}://${host}/login/magic?token=${token}`);
  return generic;
}
