// TEMPORAR — diagnostic pentru sold-out: rulează pe Vercel exact lanțul de
// apeluri către LiveTickets, o dată în cerere și o dată în after(), ca să vedem
// dacă diferența e contextul. Protejat cu CRON_SECRET. De șters după.

import { NextRequest, NextResponse } from "next/server";
import { after } from "next/server";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { ltGetEventByUrl, ltIsSoldOut } from "@/lib/livetickets";

export const dynamic = "force-dynamic";

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

  const probe = async () => {
    try {
      const parsed = await ltGetEventByUrl(ev.livetickets_url);
      if (!parsed) return { rezultat: "ltGetEventByUrl null" };
      return {
        items: Array.isArray(parsed.items) ? (parsed.items as unknown[]).length : null,
        ticket_count: parsed.ticket_count ?? null,
        soldOut: ltIsSoldOut(parsed),
      };
    } catch (e) {
      return { eroare: String(e) };
    }
  };

  const inRequest = await probe();

  // Aceeași verificare, dar în after() — exact contextul din homepage.
  after(async () => {
    const r = await probe();
    await sql`
      INSERT INTO settings (key, value) VALUES ('debug_soldout_after', ${JSON.stringify(r)}::jsonb)
      ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value
    `;
  });

  const [prev] = (await sql`SELECT value FROM settings WHERE key = 'debug_soldout_after'`) as { value: unknown }[];
  return NextResponse.json({ event: ev.id, inRequest, inAfterPrecedent: prev?.value ?? null });
}
