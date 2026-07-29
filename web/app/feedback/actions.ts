"use server";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { SignJWT } from "jose";
import { getSettings } from "@/lib/settings";

const AUD = "clp-feedback";
const MAX_AGE = 60 * 60 * 24 * 90; // 90 zile

/** Verifică parola din settings (feedback_password) și emite un cookie semnat. */
export async function intra(_prev: string | null, formData: FormData): Promise<string> {
  const parola = String(formData.get("parola") || "").trim();
  const expected = String((await getSettings()).feedback_password ?? "");
  if (!parola || !expected || parola !== expected) return "Parolă greșită.";

  const secret = process.env.AUTH_SECRET;
  if (!secret) throw new Error("AUTH_SECRET lipsește (vezi .env.local)");
  // Cheie derivată, ca token-ul să nu poată fi refolosit drept clp_session.
  const token = await new SignJWT({})
    .setProtectedHeader({ alg: "HS256" })
    .setAudience(AUD)
    .setIssuedAt()
    .setExpirationTime("90d")
    .sign(new TextEncoder().encode(`${secret}:feedback`));

  (await cookies()).set("clp_feedback", token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/feedback",
    maxAge: MAX_AGE,
  });
  redirect("/feedback");
}
