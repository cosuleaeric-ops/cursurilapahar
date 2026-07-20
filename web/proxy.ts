import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

// Test A/B buton „Vreau să vin": atribuie varianta (cookie 90 zile, 50/50)
// înainte de render, ca homepage-ul (server component) să o poată citi.
const AB_COOKIE = "clp_ab_btn";
const VARIANTS = ["off", "on"];

export function proxy(request: NextRequest) {
  const res = NextResponse.next();
  const v = request.cookies.get(AB_COOKIE)?.value;
  if (!v || !VARIANTS.includes(v)) {
    const assigned = VARIANTS[Math.floor(Math.random() * VARIANTS.length)];
    res.cookies.set(AB_COOKIE, assigned, {
      maxAge: 90 * 86400,
      path: "/",
      sameSite: "lax",
    });
    // request-ul curent încă nu are cookie-ul — pasează varianta prin header
    res.headers.set("x-ab-btn", assigned);
  }
  return res;
}

export const config = {
  matcher: "/",
};
