import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { jwtVerify } from "jose";

// Paginile publice sunt cache-uite la CDN, deci serverul nu mai citește cookie-uri
// la fiecare afișare. Bara de admin se randează acum pe client, iar aici ținem
// sincron cu sesiunea reală un marcaj NEsecret (`clp_bar`) pe care îl poate citi.
//
// Matcher-ul de mai jos face ca proxy-ul să nu ruleze deloc pentru vizitatorii
// anonimi (fără cookie de sesiune și fără marcaj): niciun Set-Cookie pe răspuns,
// deci homepage-ul rămâne cache-uibil. Varianta testului A/B se atribuie pe client.
const BAR_COOKIE = "clp_bar";
const BAR_MAX_AGE = 60 * 60 * 24 * 7; // cât sesiunea (lib/auth.ts)

async function isOwner(token: string | undefined): Promise<boolean> {
  if (!token || !process.env.AUTH_SECRET) return false;
  try {
    const { payload } = await jwtVerify(token, new TextEncoder().encode(process.env.AUTH_SECRET));
    return payload.role === "owner";
  } catch {
    return false;
  }
}

export async function proxy(request: NextRequest) {
  const res = NextResponse.next();
  const owner = await isOwner(request.cookies.get("clp_session")?.value);
  const marked = request.cookies.get(BAR_COOKIE)?.value === "1";

  if (owner && !marked) {
    res.cookies.set(BAR_COOKIE, "1", { maxAge: BAR_MAX_AGE, path: "/", sameSite: "lax" });
  } else if (!owner && marked) {
    res.cookies.delete(BAR_COOKIE);
  }
  return res;
}

export const config = {
  matcher: [
    {
      source: "/((?!api|_next|assets|favicon.ico).*)",
      has: [{ type: "cookie", key: "clp_session" }],
    },
    {
      source: "/((?!api|_next|assets|favicon.ico).*)",
      has: [{ type: "cookie", key: "clp_bar" }],
    },
  ],
};
