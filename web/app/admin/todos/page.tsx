import type { Metadata } from "next";
import { sql } from "@/lib/db";
import { TODOS_CSS } from "./styles";
import TodosList, { type DoneGroup, type Todo, type User } from "./TodosList";

export const dynamic = "force-dynamic";

// <title>To-dos – Admin</title> (admin/todos/index.php:111).
export const metadata: Metadata = { title: "To-dos - Admin" };

type Row = Todo & { done_day: string | null };

const DISPLAY: Record<string, string> = { eric6: "Eric", andy: "Andy" };
const COLORS: Record<string, string> = { eric6: "#2563eb", andy: "#16a34a" };
const INITIALS: Record<string, string> = { eric6: "E", andy: "A" };
const RO_MONTHS = ["", "ianuarie", "februarie", "martie", "aprilie", "mai", "iunie", "iulie", "august", "septembrie", "octombrie", "noiembrie", "decembrie"];

const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);
const ymd = (d: Date) => new Intl.DateTimeFormat("en-CA", { timeZone: "Europe/Bucharest" }).format(d);

export default async function TodosPage() {
  // Ordinea din todos.json: inserare, cel mai vechi primul (lib/todos.php:42,
  // fără nicio sortare în admin/todos/index.php). Gruparea zilei se face STRICT
  // după completed_at (index.php:60-61); fără el, ziua e goală → „Mai demult".
  const rows = (await sql`
    SELECT id, title, assigned_to, completed,
      to_char(completed_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS done_day
    FROM todos ORDER BY id
  `) as Row[];
  const userRows = (await sql`SELECT username FROM users ORDER BY id`) as { username: string }[];

  const users: User[] = userRows.map((u) => ({
    username: u.username,
    name: DISPLAY[u.username] ?? cap(u.username),
    color: COLORS[u.username] ?? "#6b7280",
    initial: INITIALS[u.username] ?? u.username.charAt(0).toUpperCase(),
    avatar: `/assets/images/avatars/${u.username}.png`,
  }));

  const pending = rows.filter((t) => !t.completed);
  const done = rows.filter((t) => t.completed);

  const today = ymd(new Date());
  const yesterday = ymd(new Date(Date.now() - 86400000));
  const dayLabel = (day: string | null) => {
    if (!day) return "Mai demult";
    if (day === today) return "Azi";
    if (day === yesterday) return "Ieri";
    const [y, m, d] = day.split("-");
    return `${Number(d)} ${RO_MONTHS[Number(m)]} ${y}`;
  };

  // Grupare pe ziua finalizării, cele mai recente primele (krsort în PHP).
  const map = new Map<string, Todo[]>();
  for (const t of done) {
    const k = t.done_day ?? "";
    if (!map.has(k)) map.set(k, []);
    map.get(k)!.push(t);
  }
  const doneGroups: DoneGroup[] = [...map.entries()]
    .sort((a, b) => b[0].localeCompare(a[0]))
    .map(([day, items]) => ({ label: dayLabel(day || null), items }));

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: TODOS_CSS }} />
      <TodosList pending={pending} doneGroups={doneGroups} doneCount={done.length} users={users} />
    </>
  );
}
