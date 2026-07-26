// Contorizarea afișărilor pentru testul A/B — fostul `trackAb(ab, "views")` din
// page.tsx. Aceleași filtre ca înainte (shouldCountClick: boți, prefetch, sesiune
// de admin), doar că semnalul vine de la browser, nu din render.

import { NextResponse } from "next/server";
import { cookies } from "next/headers";
import { AB_COOKIE, AB_VARIANTS, shouldCountClick, trackAb, type AbVariant } from "@/lib/ab";

export const dynamic = "force-dynamic";

export async function POST(): Promise<NextResponse> {
  const v = (await cookies()).get(AB_COOKIE)?.value ?? "";
  if ((AB_VARIANTS as readonly string[]).includes(v) && (await shouldCountClick())) {
    await trackAb(v as AbVariant, "views");
  }
  return new NextResponse(null, { status: 204 });
}
