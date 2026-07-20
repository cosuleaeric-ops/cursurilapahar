// Redirect către pagina de bilete + contorizare — port din go/course.php.
// Click pe card SAU pe butonul „Vreau să vin" = același redirect (aceeași metrică).

import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";
import { sql } from "@/lib/db";
import { AB_COOKIE, AB_VARIANTS, shouldCountClick, trackAb, type AbVariant } from "@/lib/ab";

export const dynamic = "force-dynamic";

export async function GET(req: NextRequest): Promise<NextResponse> {
  const id = Number(req.nextUrl.searchParams.get("id") ?? "");
  const rows = id
    ? ((await sql`
        SELECT id, livetickets_url, active FROM events WHERE id = ${id}
      `) as { id: number; livetickets_url: string | null; active: boolean }[])
    : [];
  const ev = rows[0];
  const url = ev?.livetickets_url?.trim() ?? "";

  if (!url || !ev.active) {
    return NextResponse.redirect(new URL("/#cursuri", req.url), 302);
  }

  if (await shouldCountClick()) {
    await sql`UPDATE events SET clicks = clicks + 1 WHERE id = ${ev.id}`;
    const v = (await cookies()).get(AB_COOKIE)?.value ?? "";
    if ((AB_VARIANTS as readonly string[]).includes(v)) {
      await trackAb(v as AbVariant, "conversions");
    }
  }

  return NextResponse.redirect(url, 302);
}
