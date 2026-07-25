import { sql } from "@/lib/db";
import SpeakeriTable, { type Lead, type Speaker } from "./SpeakeriTable";
import { STATUS_RANK } from "./statuses";

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
  meet: Record<string, string> | null;
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

// clp_normalize_speaker_phone(): doar cifrele, fără prefixul 40 (min. 11 cifre) sau 0 (min. 10).
const normPhone = (s: string | null | undefined) => {
  let d = digits(s);
  if (d.startsWith("40") && d.length >= 11) d = d.slice(2);
  if (d.startsWith("0") && d.length >= 10) d = d.slice(1);
  return d;
};

// clp_speaker_dedupe_key(): email, altfel telefon, altfel nume.
const dedupeKey = (s: SpeakerRow) => {
  const email = norm(s.email);
  if (email) return `e:${email}`;
  const phone = normPhone(s.phone);
  if (phone) return `p:${phone}`;
  return `n:${(s.name ?? "").trim().toLowerCase()}`;
};

// aceleași ranguri ca în ORDER BY-ul de mai jos (fără status => MID, necunoscut => 2)
const rank = (s: string | null) => {
  const v = (s ?? "").trim();
  if (v === "") return 3;
  return STATUS_RANK[v] ?? 2;
};

// clp_merge_speaker_entries(): reunește cursurile, ține statusul cu rangul cel
// mai mic și completează doar câmpurile goale ale fișei păstrate.
function mergeSpeakers(keep: SpeakerRow, other: SpeakerRow): SpeakerRow {
  const meet = { ...(keep.meet ?? {}) };
  for (const [k, v] of Object.entries(other.meet ?? {})) {
    if (String(v).trim() !== "" && (meet[k] ?? "").trim() === "") meet[k] = String(v).trim();
  }
  const out: SpeakerRow = {
    ...keep,
    topics: [...new Set([...(keep.topics ?? []), ...(other.topics ?? [])].filter((t) => t.trim() !== ""))],
    meet,
    status: rank(keep.status) <= rank(other.status) ? (keep.status ?? "MID") : (other.status ?? "MID"),
  };
  if (out.name.trim() === "" && (other.name ?? "").trim() !== "") out.name = other.name.trim();
  if ((out.email ?? "").trim() === "" && (other.email ?? "").trim() !== "") out.email = other.email!.trim();
  if ((out.phone ?? "").trim() === "" && (other.phone ?? "").trim() !== "") out.phone = other.phone!.trim();
  if ((out.notes ?? "").trim() === "" && (other.notes ?? "").trim() !== "") out.notes = other.notes!.trim();
  return out;
}

export default async function SpeakeriPage({
  searchParams,
}: {
  searchParams: Promise<{ saved?: string }>;
}) {
  const params = await searchParams;
  const dbRows = (await sql`
    SELECT id, name, email, phone, status, notes, topics, meet
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

  // clp_deduplicate_speakers(): fișele fără nume nu se afișează, iar cele cu
  // același email/telefon/nume se contopesc într-un singur rând.
  const byKey = new Map<string, SpeakerRow>();
  for (const r of dbRows) {
    if ((r.name ?? "").trim() === "") continue;
    const key = dedupeKey(r);
    const prev = byKey.get(key);
    byKey.set(key, prev ? mergeSpeakers(prev, r) : r);
  }
  // contopirea poate schimba statusul, deci se reașază pe rang; sortarea e
  // stabilă, așa că ordinea alfabetică din SQL rămâne în interiorul grupei
  const rows = [...byKey.values()].sort((a, b) => rank(a.status) - rank(b.status));

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

  // PHP afișează timestamp-ul brut din antetul blocului de log: „2026-01-15 14:30:22".
  const dFmt = new Intl.DateTimeFormat("sv-SE", {
    timeZone: "Europe/Bucharest",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
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
      meet: s.meet ?? {},
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
      {params.saved !== undefined && <div className="notice notice-success">Speakerul a fost salvat.</div>}
      <SpeakeriTable speakers={speakers} leads={leads} />
    </>
  );
}
