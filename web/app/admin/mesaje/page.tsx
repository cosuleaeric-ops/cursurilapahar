import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import MessagesBoard, { type Cat, type Comment, type Msg } from "./MessagesBoard";

export const dynamic = "force-dynamic";

type Row = {
  id: number;
  category: string;
  name: string | null;
  email: string | null;
  payload: Record<string, unknown>;
  read: boolean;
  rating: string | null;
  contacted: boolean;
  comments: Comment[] | null;
  created_at: string;
};

// Aceleași categorii ca clp_message_categories().
const CATS: Cat[] = [
  { key: "sustine", label: "Speakeri", icon: "🎤" },
  { key: "contact", label: "Contact", icon: "💬" },
  { key: "gazduieste", label: "Locații", icon: "📍" },
  { key: "parteneriat", label: "Parteneriate", icon: "🤝" },
  { key: "sponsorizare", label: "Sponsorizări", icon: "⭐" },
];

const dtFmt = new Intl.DateTimeFormat("ro-RO", {
  timeZone: "Europe/Bucharest",
  day: "2-digit",
  month: "2-digit",
  year: "numeric",
  hour: "2-digit",
  minute: "2-digit",
});

export default async function MesajePage() {
  const session = await getSession();
  const rows = (await sql`
    SELECT id, category, name, email, payload, read, rating, contacted, comments, created_at
    FROM messages ORDER BY created_at DESC
  `) as Row[];

  const byCat: Record<string, Msg[]> = {};
  const counts: Record<string, number> = {};

  for (const r of rows) {
    const p = r.payload ?? {};
    const fields = Object.entries(p)
      .filter(([k]) => !["trimis de pe", "data"].includes(k.toLowerCase()))
      .map(([k, v]) => [k, String(v ?? "")] as [string, string]);

    const name =
      String(p.Nume ?? p.nume ?? p.Name ?? p["Organizație"] ?? p.organizatie ?? r.name ?? "") || "—";
    const courseName = String(p["Course name"] ?? "").trim();

    const msg: Msg = {
      id: r.id,
      category: r.category,
      name,
      date: dtFmt.format(new Date(r.created_at)),
      fields,
      read: r.read,
      evaluation: r.rating ?? "",
      contacted: r.contacted,
      comments: Array.isArray(r.comments) ? r.comments : [],
      course_first: courseName ? courseName.split(/(?<=[.!?])\s+|\s*[\r\n]+\s*/u)[0] : "",
    };

    (byCat[r.category] ??= []).push(msg);
    // Badge-ul de pe tab = mesajele care încă cer o acțiune.
    const pendingHere = r.category === "sustine" ? !r.rating : !r.read;
    if (pendingHere) counts[r.category] = (counts[r.category] ?? 0) + 1;
  }

  return (
    <>
      <h1 className="wp-page-title">Mesaje</h1>
      <MessagesBoard cats={CATS} byCat={byCat} counts={counts} isOwner={session?.role === "owner"} />
    </>
  );
}
