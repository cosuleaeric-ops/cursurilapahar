import { NextRequest, NextResponse } from "next/server";
import { sql } from "@/lib/db";
import { signSession, verifyMagicToken, SESSION_COOKIE, sessionCookieOpts } from "@/lib/auth";

export async function GET(req: NextRequest) {
  const token = req.nextUrl.searchParams.get("token") ?? "";
  const username = await verifyMagicToken(token);
  if (!username) return NextResponse.redirect(new URL("/login", req.url));

  const rows = (await sql`SELECT username, role FROM users WHERE username = ${username}`) as
    { username: string; role: string }[];
  if (!rows[0]) return NextResponse.redirect(new URL("/login", req.url));

  const sessionToken = await signSession({ username: rows[0].username, role: rows[0].role });
  const res = NextResponse.redirect(new URL("/admin", req.url));
  res.cookies.set(SESSION_COOKIE, sessionToken, sessionCookieOpts);
  return res;
}
