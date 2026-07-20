// Cron zilnic (Vercel) — echivalentul cron/recurring_tasks.php + cron/andy_course_tasks.php.
// Idempotent prin tabela cron_state (rec:<task>|<data>, postcourse:<event_id>, publish:<event_id>).

import { NextRequest, NextResponse } from "next/server";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

export const dynamic = "force-dynamic";

const TZ = "Europe/Bucharest";

function bucharestDate(offsetDays = 0): string {
  const d = new Date(Date.now() + offsetDays * 86400_000);
  return new Intl.DateTimeFormat("en-CA", { timeZone: TZ }).format(d); // YYYY-MM-DD
}

function courseShortName(t: string): string {
  return t
    .replace(/^\s*Curs la Pahar\s*[-–]\s*/u, "")
    .replace(/\s*\/\/.*$/u, "")
    .trim();
}

async function seen(key: string): Promise<boolean> {
  const rows = (await sql`SELECT 1 FROM cron_state WHERE key = ${key}`) as unknown[];
  return rows.length > 0;
}

async function mark(key: string): Promise<void> {
  await sql`INSERT INTO cron_state (key) VALUES (${key}) ON CONFLICT (key) DO NOTHING`;
}

async function addTodo(title: string, assignedTo: string): Promise<void> {
  await sql`
    INSERT INTO todos (title, assigned_to, created_by, completed)
    VALUES (${title}, ${assignedTo || "andy"}, 'system', false)
  `;
}

type RecTask = {
  legacy_id: string | null;
  id: number;
  type: string;
  system_key: string | null;
  assigned_to: string | null;
  title: string;
  days: number[];
};

export async function GET(req: NextRequest): Promise<NextResponse> {
  // Auth: header-ul Vercel Cron (CRON_SECRET) sau o sesiune de admin (test manual).
  const auth = req.headers.get("authorization");
  const secret = process.env.CRON_SECRET;
  const fromCron = !!secret && auth === `Bearer ${secret}`;
  if (!fromCron && !(await getSession())) {
    return new NextResponse("Forbidden", { status: 403 });
  }

  const qDate = req.nextUrl.searchParams.get("date");
  const today = qDate && /^\d{4}-\d{2}-\d{2}$/.test(qDate) ? qDate : bucharestDate();
  const dayOfMonth = Number(today.slice(8, 10));
  const yesterday = qDate ? qDate : bucharestDate(-1);

  const tasks = (await sql`
    SELECT id, legacy_id, type, system_key, assigned_to, title, days
    FROM recurring_tasks ORDER BY position, id
  `) as RecTask[];

  const added: string[] = [];

  // 1) Taskuri lunare — o dată pe zi calendaristică.
  for (const t of tasks.filter((t) => t.type === "monthly")) {
    if (!t.days.map(Number).includes(dayOfMonth)) continue;
    const title = t.title.trim();
    if (!title) continue;
    const key = `rec:${t.legacy_id ?? t.id}|${today}`;
    if (await seen(key)) continue;
    await addTodo(title, t.assigned_to ?? "eric6");
    await mark(key);
    added.push(title);
  }

  // 2) Taskuri post-curs — cursurile care au avut loc IERI (sau la ?date=).
  const postTpls = tasks.filter((t) => t.system_key === "post_course" && t.title.trim());
  if (postTpls.length) {
    const events = (await sql`
      SELECT id, title, speaker_name FROM events
      WHERE starts_at IS NOT NULL
        AND to_char(starts_at AT TIME ZONE ${TZ}, 'YYYY-MM-DD') = ${yesterday}
    `) as { id: number; title: string; speaker_name: string | null }[];
    for (const ev of events) {
      const key = `postcourse:${ev.id}`;
      if (await seen(key)) continue;
      const curs = courseShortName(ev.title);
      const speaker = ev.speaker_name?.trim() || "[speaker]";
      for (const tpl of postTpls) {
        await addTodo(tpl.title.replaceAll("{curs}", curs).replaceAll("{speaker}", speaker), tpl.assigned_to ?? "andy");
      }
      await mark(key);
      added.push(`${curs} (post-curs)`);
    }
  }

  // 3) Publicare pe site-uri partenere — când un curs capătă link LiveTickets.
  //    Prima rulare doar seed-uiește starea (fără taskuri), ca în PHP.
  const pubTpl = tasks.find((t) => t.system_key === "course_published" && t.title.trim());
  if (pubTpl) {
    const seeded = (await sql`SELECT 1 FROM cron_state WHERE key LIKE 'publish:%' LIMIT 1`) as unknown[];
    const firstRun = seeded.length === 0;
    const linked = (await sql`
      SELECT id, title FROM events
      WHERE livetickets_url IS NOT NULL AND livetickets_url <> ''
    `) as { id: number; title: string }[];
    for (const ev of linked) {
      const key = `publish:${ev.id}`;
      if (await seen(key)) continue;
      if (!firstRun) {
        const curs = courseShortName(ev.title);
        await addTodo(pubTpl.title.replaceAll("{curs}", curs), pubTpl.assigned_to ?? "andy");
        added.push(`${curs} (publicare)`);
      }
      await mark(key);
    }
  }

  const msg = added.length
    ? `Taskuri adăugate: ${added.join(" | ")}`
    : `Niciun task de adăugat pentru ${today}.`;
  return new NextResponse(msg, { headers: { "content-type": "text/plain; charset=utf-8" } });
}
