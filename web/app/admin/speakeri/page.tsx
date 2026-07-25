import { sql } from "@/lib/db";
import SpeakeriTable, { type Lead, type Speaker } from "./SpeakeriTable";

export const dynamic = "force-dynamic";

// Etichetele întrebărilor din formularul „Prezintă un curs" (clp_sustine_field_labels).
const SUSTINE_LABELS: Record<string, string> = {
  Name: "Nume și prenume",
  Email: "Email",
  Phone: "Număr de telefon",
  Social: "Link profil social media",
  "Course name": "Nume curs susținut",
  "Course desc": "Descrie cursul susținut",
  Motivation: "De ce îți dorești să susții acest curs?",
  Experience: "Ce experiențe sau competențe te califică?",
  "Previous presentations": "Ai mai susținut astfel de prezentări?",
  City: "În ce oraș ai vrea să susții cursul?",
  Other: "Mai e ceva ce vrei să ne transmiți?",
};

type SpeakerRow = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  status: string | null;
  notes: string | null;
  topics: string[] | null;
};

type MsgRow = {
  id: number;
  name: string | null;
  email: string | null;
  payload: Record<string, string>;
  contacted: boolean;
  created_at: string;
};

const norm = (s: string | null | undefined) => (s ?? "").trim().toLowerCase();
const digits = (s: string | null | undefined) => (s ?? "").replace(/\D/g, "");

export default async function SpeakeriPage() {
  const rows = (await sql`
    SELECT id, name, email, phone, status, notes, topics
    FROM speakers
    ORDER BY
      -- ca în clp_sort_speakers(): fără status => MID (3), status necunoscut => 2
      CASE
        WHEN status IS NULL OR status = '' THEN 3
        WHEN status = 'CONTACTAT' THEN 0
        WHEN status = 'URMEAZĂ' THEN 1
        WHEN status = 'RECURENT' THEN 2
        WHEN status = 'MID' THEN 3
        WHEN status = 'NOPE' THEN 4
        ELSE 2 END,
      lower(name)
  `) as SpeakerRow[];

  // Submisiile din formularul „Prezintă un curs", cele mai noi primele.
  const msgs = (await sql`
    SELECT id, name, email, payload, contacted, created_at
    FROM messages WHERE category = 'sustine'
    ORDER BY created_at DESC
  `) as MsgRow[];

  const phoneOf = (p: Record<string, string>) => p.Phone ?? p.Telefon ?? p.telefon ?? "";
  const matchSpeaker = (email: string, phone: string) =>
    rows.find(
      (s) =>
        (email && norm(s.email) === norm(email)) ||
        (phone && digits(s.phone).length >= 6 && digits(s.phone) === digits(phone)),
    );

  // speaker_id -> cea mai recentă submisie
  const formBySpeaker = new Map<number, MsgRow>();
  for (const m of msgs) {
    const sp = matchSpeaker(m.email ?? "", phoneOf(m.payload));
    if (sp && !formBySpeaker.has(sp.id)) formBySpeaker.set(sp.id, m);
  }

  const dFmt = new Intl.DateTimeFormat("ro-RO", {
    timeZone: "Europe/Bucharest",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

  const speakers: Speaker[] = rows.map((s) => {
    const sub = formBySpeaker.get(s.id);
    const form_rows = sub
      ? Object.entries(sub.payload)
          .filter(([k]) => !["trimis de pe", "data"].includes(k.toLowerCase()))
          .map(([k, v]) => ({ label: SUSTINE_LABELS[k] ?? k, value: String(v) }))
      : [];
    return {
      id: s.id,
      name: s.name,
      email: s.email,
      phone: s.phone,
      status: s.status,
      notes: s.notes,
      topics: s.topics ?? [],
      form_date: sub ? dFmt.format(new Date(sub.created_at)) : null,
      form_rows,
    };
  });

  // Leads marcați „contactat" în Mesaje care nu au încă fișă de speaker.
  const leads: Lead[] = msgs
    .filter((m) => m.contacted && !matchSpeaker(m.email ?? "", phoneOf(m.payload)))
    .map((m) => ({
      id: m.id,
      name: m.name || m.payload.Name || m.payload.Nume || "—",
      email: m.email,
      phone: phoneOf(m.payload) || null,
    }));

  return (
    <>
      <h1 className="wp-page-title">Speakeri</h1>
      <SpeakeriTable speakers={speakers} leads={leads} />
    </>
  );
}
