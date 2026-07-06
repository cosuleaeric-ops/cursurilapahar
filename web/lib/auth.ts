import { cookies } from "next/headers";
import { SignJWT, jwtVerify } from "jose";
import bcrypt from "bcryptjs";
import { sql } from "@/lib/db";

const COOKIE = "clp_session";
const MAX_AGE = 60 * 60 * 24 * 7; // 7 zile
const MAGIC_AUD = "clp-magic";

export const SESSION_COOKIE = COOKIE;
export const sessionCookieOpts = {
  httpOnly: true,
  secure: process.env.NODE_ENV === "production",
  sameSite: "lax" as const,
  path: "/",
  maxAge: MAX_AGE,
};

function secretKey(): Uint8Array {
  const s = process.env.AUTH_SECRET;
  if (!s) throw new Error("AUTH_SECRET lipsește (vezi .env.local)");
  return new TextEncoder().encode(s);
}

export type Session = { username: string; role: string };

/** Verifică user + parolă în Neon (bcrypt; hash-urile PHP $2y$ sunt compatibile). */
export async function verifyLogin(username: string, password: string): Promise<Session | null> {
  const rows = (await sql`
    SELECT username, password_hash, role FROM users WHERE username = ${username}
  `) as { username: string; password_hash: string; role: string }[];
  const u = rows[0];
  if (!u) return null;
  const ok = await bcrypt.compare(password, u.password_hash);
  if (!ok) return null;
  return { username: u.username, role: u.role };
}

/** Semnează un JWT de sesiune (fără a seta cookie-ul). */
export async function signSession(s: Session): Promise<string> {
  return new SignJWT({ role: s.role })
    .setProtectedHeader({ alg: "HS256" })
    .setSubject(s.username)
    .setIssuedAt()
    .setExpirationTime("7d")
    .sign(secretKey());
}

/** Emite JWT httpOnly. */
export async function createSession(s: Session): Promise<void> {
  const token = await signSession(s);
  const store = await cookies();
  store.set(COOKIE, token, sessionCookieOpts);
}

/** Verifică un magic-link token semnat; întoarce username-ul sau null. */
export async function verifyMagicToken(token: string): Promise<string | null> {
  try {
    const { payload } = await jwtVerify(token, secretKey(), { audience: MAGIC_AUD });
    return payload.sub ? String(payload.sub) : null;
  } catch {
    return null;
  }
}

/** Citește sesiunea din cookie (null dacă lipsește/invalid/expirat). */
export async function getSession(): Promise<Session | null> {
  const store = await cookies();
  const token = store.get(COOKIE)?.value;
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, secretKey());
    return { username: String(payload.sub), role: String(payload.role) };
  } catch {
    return null;
  }
}

export async function destroySession(): Promise<void> {
  const store = await cookies();
  store.delete(COOKIE);
}
