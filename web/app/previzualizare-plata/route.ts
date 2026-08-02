// Linkul care deschide fluxul de plată cuiva din afară, fără să-l vadă și
// cumpărătorii: verificatorul de la procesatorul de plăți intră o dată pe acest
// URL, primește un cookie și de atunci vede coșul cu plata pe site.
//
// Cheia se regenerează din Admin → Setări, iar linkurile date până atunci mor.

import { NextResponse } from "next/server";
import { getSettings } from "@/lib/settings";
import { COOKIE_PREVIZUALIZARE } from "@/lib/checkout";

export const dynamic = "force-dynamic";

const SITE = "https://cursurilapahar.ro";
const ZILE = 30;

export async function GET(req: Request) {
  const url = new URL(req.url);
  const cheie = (await getSettings()).checkout_preview_token;
  const primit = url.searchParams.get("cheie") ?? "";

  if (typeof cheie !== "string" || !cheie || primit !== cheie) {
    return new NextResponse("Link de previzualizare invalid sau expirat.", {
      status: 404,
      headers: { "content-type": "text/plain; charset=utf-8" },
    });
  }

  // `catre` rămâne intern: fără el, linkul ar putea trimite pe orice domeniu.
  const catre = url.searchParams.get("catre");
  const destinatie = catre && catre.startsWith("/") ? `${SITE}${catre}` : `${SITE}/curs/test-plata`;

  const res = NextResponse.redirect(destinatie, 303);
  res.cookies.set(COOKIE_PREVIZUALIZARE, cheie, {
    maxAge: 60 * 60 * 24 * ZILE,
    path: "/",
    sameSite: "lax",
    httpOnly: true,
  });
  return res;
}
