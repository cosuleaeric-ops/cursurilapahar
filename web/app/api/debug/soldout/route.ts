// TEMPORAR — diagnostic pentru sold-out: rulează pe Vercel exact lanțul de
// apeluri către LiveTickets și arată ce vede serverul, nu laptopul.
// Protejat cu CRON_SECRET, ca /api/cron/daily. De șters după investigație.

import { NextRequest, NextResponse } from "next/server";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { ltGetEventByUrl, ltIsSoldOut } from "@/lib/livetickets";

export const dynamic = "force-dynamic";

const UA =
  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36";

export async function GET(req: NextRequest): Promise<NextResponse> {
  const auth = req.headers.get("authorization");
  const secret = process.env.CRON_SECRET;
  const fromCron = !!secret && auth === `Bearer ${secret}`;
  if (!fromCron && !(await getSession())) return new NextResponse("Forbidden", { status: 403 });

  // Același filtru ca homepage-ul: doar cursurile care încă nu au trecut.
  const [ev] = (await sql`
    SELECT id, title, livetickets_url, sold_out, sold_out_checked_at
    FROM events
    WHERE livetickets_url IS NOT NULL AND active = true
      AND to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD')
          >= to_char(now() AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD')
    ORDER BY starts_at ASC LIMIT 1
  `) as { id: number; title: string; livetickets_url: string; sold_out: boolean; sold_out_checked_at: string }[];
  if (!ev) return NextResponse.json({ error: "niciun eveniment cu link" });

  const slug = new URL(ev.livetickets_url).pathname.split("/").filter(Boolean).pop() ?? "";
  const probe = async (url: string) => {
    try {
      const r = await fetch(url, { headers: { "User-Agent": UA, Accept: "*/*" }, cache: "no-store", signal: AbortSignal.timeout(12_000) });
      const body = await r.text();
      return { status: r.status, ok: r.ok, len: body.length, head: body.slice(0, 160) };
    } catch (e) {
      return { error: String(e) };
    }
  };

  const parsed = await ltGetEventByUrl(ev.livetickets_url);
  return NextResponse.json({
    event: { id: ev.id, title: ev.title, sold_out: ev.sold_out, checked_at: ev.sold_out_checked_at },
    getbyurl: await probe(`https://api.livetickets.ro/public/events/getbyurl?url=${encodeURIComponent(slug)}`),
    getTickets: await probe(`https://api.livetickets.ro/public/events/get-tickets?url=${encodeURIComponent(slug)}`),
    parsedItems: Array.isArray(parsed?.items) ? (parsed.items as unknown[]).length : null,
    ltIsSoldOut: parsed ? ltIsSoldOut(parsed) : "ltGetEventByUrl a intors null",
  });
}
