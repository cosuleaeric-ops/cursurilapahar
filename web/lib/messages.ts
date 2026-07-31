// Port din clp_load_grouped_messages() / clp_message_categories() (lib/messages.php).
// Folosit și de pagina Mesaje, și de cardul de pe Dashboard, ca să nu se
// contrazică la numărători.

import { sql } from "@/lib/db";

export type Cat = { key: string; label: string; icon: string };

/** Exact cele 4 categorii de pe live — orice alt tip cade pe „contact". */
export const CATS: Cat[] = [
  { key: "sustine", label: "Speakeri", icon: "🎤" },
  { key: "contact", label: "Contact", icon: "💬" },
  { key: "gazduieste", label: "Locații", icon: "📍" },
  { key: "parteneriat", label: "Parteneriate", icon: "🤝" },
];

export type Comment = { at?: string; by?: string; text?: string };

export type Msg = {
  id: number;
  category: string;
  name: string;
  date: string;
  fields: [string, string][];
  read: boolean;
  evaluation: string;
  contacted: boolean;
  comments: Comment[];
  course_first: string;
};

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

const dtFmt = new Intl.DateTimeFormat("en-GB", {
  timeZone: "Europe/Bucharest",
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
  hour: "2-digit",
  minute: "2-digit",
  second: "2-digit",
  hour12: false,
});

/**
 * Data se afișează brută, exact cum o scrie PHP-ul cu date('Y-m-d H:i:s')
 * în antetul blocului de log (api/contact.php:57, lib/messages.php:111+223).
 */
export function phpDate(d: Date): string {
  const p: Record<string, string> = {};
  for (const part of dtFmt.formatToParts(d)) p[part.type] = part.value;
  return `${p.year}-${p.month}-${p.day} ${p.hour}:${p.minute}:${p.second}`;
}

const norm = (s: string | null | undefined) => (s ?? "").trim().toLowerCase();
const digits = (s: string | null | undefined) => (s ?? "").replace(/\D/g, "");

export async function loadGroupedMessages(): Promise<{
  byCat: Record<string, Msg[]>;
  tabCounts: Record<string, number>;
}> {
  const rows = (await sql`
    SELECT id, category, name, email, payload, read, rating, contacted, comments, created_at
    FROM messages ORDER BY created_at DESC
  `) as Row[];
  const speakers = (await sql`SELECT email, phone FROM speakers`) as { email: string | null; phone: string | null }[];

  const isSpeaker = (email: string, phone: string) =>
    speakers.some(
      (s) =>
        (email && norm(s.email) === norm(email)) ||
        (phone && digits(phone).length >= 6 && digits(s.phone) === digits(phone)),
    );

  const byCat: Record<string, Msg[]> = Object.fromEntries(CATS.map((c) => [c.key, [] as Msg[]]));

  for (const r of rows) {
    const p = r.payload ?? {};
    const cat = byCat[r.category] ? r.category : "contact";

    // Candidații care au deja fișă de speaker nu mai apar în triaj.
    if (cat === "sustine") {
      const em = String(p.Email ?? p.email ?? r.email ?? "");
      const ph = String(p.Phone ?? p.Telefon ?? p.telefon ?? "");
      if (isSpeaker(em, ph)) continue;
    }

    const courseName = String(p["Course name"] ?? "").trim();
    byCat[cat].push({
      id: r.id,
      category: cat,
      name:
        String(p.Nume ?? p.nume ?? p.Name ?? p["Organizație"] ?? p.organizatie ?? r.name ?? "") || "-",
      date: phpDate(new Date(r.created_at)),
      fields: Object.entries(p)
        .filter(([k]) => !["trimis de pe", "data"].includes(k.toLowerCase()))
        .map(([k, v]) => [k, String(v ?? "")] as [string, string]),
      read: r.read,
      evaluation: r.rating ?? "",
      contacted: r.contacted,
      comments: Array.isArray(r.comments) ? r.comments : [],
      course_first: courseName ? courseName.split(/(?<=[.!?])\s+|\s*[\r\n]+\s*/u)[0] : "",
    });
  }

  // Speakeri = neevaluați; restul = necitite.
  const tabCounts: Record<string, number> = {};
  for (const c of CATS) {
    tabCounts[c.key] =
      c.key === "sustine"
        ? byCat.sustine.filter((m) => !m.evaluation).length
        : byCat[c.key].filter((m) => !m.read).length;
  }

  return { byCat, tabCounts };
}
