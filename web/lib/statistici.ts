// Statistici cursuri — port din lib/statistici.php (participanți, DITL, viza).
// Sursa de date e acum Neon (tabelele events/tickets/event_reports/viza_subtips).

import { sql } from "@/lib/db";

/** Cheie normalizată de nume: fără diacritice/punctuație, tokeni sortați. */
export function participantNameKey(name: string): string {
  const n = name
    .trim()
    .toLowerCase()
    .replace(/[ăâ]/g, "a")
    .replace(/î/g, "i")
    .replace(/[șş]/g, "s")
    .replace(/[țţ]/g, "t")
    .replace(/[-.]/g, " ");
  return n.split(/\s+/).filter(Boolean).sort().join(" ");
}

function levenshtein(a: string, b: string): number {
  const m = a.length;
  const n = b.length;
  let prev = Array.from({ length: n + 1 }, (_, j) => j);
  for (let i = 1; i <= m; i++) {
    const cur = [i, ...Array(n).fill(0)];
    for (let j = 1; j <= n; j++) {
      cur[j] = Math.min(prev[j] + 1, cur[j - 1] + 1, prev[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
    }
    prev = cur;
  }
  return prev[n];
}

/**
 * Unește cheile apropiate ca să absoarbă typo-urile („ionesc" vs „ionescu").
 * Cu gardă: distanță ≤1 doar peste 10 caractere, ≤2 peste 15, mereu aceeași
 * primă literă — ca numele scurte să nu fuzioneze niciodată.
 */
export function mergeParticipantKeys(keys: string[]): Map<string, string> {
  const sorted = [...keys].sort();
  const reps: string[] = [];
  const map = new Map<string, string>();
  for (const k of sorted) {
    let target = k;
    const len = k.length;
    if (len > 10) {
      const max = len > 15 ? 2 : 1;
      for (const r of reps) {
        if (r[0] !== k[0] || Math.abs(r.length - len) > max) continue;
        if (levenshtein(k, r) <= max) {
          target = r;
          break;
        }
      }
    }
    if (target === k) reps.push(k);
    map.set(k, target);
  }
  return map;
}

export type Participant = {
  participant_name: string;
  num_courses: number;
  total_tickets: number;
  courses: string[];
};

export type ParticipantsData = {
  participants: Participant[];
  stats: { unique: number; returning: number; tickets: number };
  evolution: { m: string; unici: number; bilete: number }[];
};

export async function fetchParticipants(): Promise<ParticipantsData> {
  const rows = (await sql`
    SELECT t.participant_name, t.event_id, e.title AS course_name,
           to_char(e.starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS course_date,
           to_char(e.starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM') AS m
    FROM tickets t JOIN events e ON e.id = t.event_id
  `) as { participant_name: string; event_id: number; course_name: string; course_date: string; m: string }[];

  const keyMap = mergeParticipantKeys([...new Set(rows.map((r) => participantNameKey(r.participant_name)))]);

  type Group = { names: Map<string, number>; courseIds: Set<number>; total: number; courses: Set<string> };
  const groups = new Map<string, Group>();
  const evo = new Map<string, { keys: Set<string>; bilete: number }>();

  for (const r of rows) {
    const key = keyMap.get(participantNameKey(r.participant_name)) ?? participantNameKey(r.participant_name);
    let g = groups.get(key);
    if (!g) {
      g = { names: new Map(), courseIds: new Set(), total: 0, courses: new Set() };
      groups.set(key, g);
    }
    g.names.set(r.participant_name, (g.names.get(r.participant_name) ?? 0) + 1);
    g.courseIds.add(Number(r.event_id));
    g.total++;
    g.courses.add(`${r.course_name} (${r.course_date})`);

    let e = evo.get(r.m);
    if (!e) {
      e = { keys: new Set(), bilete: 0 };
      evo.set(r.m, e);
    }
    e.keys.add(key);
    e.bilete++;
  }

  const participants: Participant[] = [...groups.values()].map((g) => {
    // numele cel mai frecvent din grup devine cel afișat
    const topName = [...g.names.entries()].sort((a, b) => b[1] - a[1])[0][0];
    return {
      participant_name: topName,
      num_courses: g.courseIds.size,
      total_tickets: g.total,
      courses: [...g.courses],
    };
  });

  participants.sort(
    (a, b) =>
      b.num_courses - a.num_courses ||
      b.total_tickets - a.total_tickets ||
      a.participant_name.localeCompare(b.participant_name, "ro")
  );

  const evolution = [...evo.entries()]
    .sort((a, b) => b[0].localeCompare(a[0]))
    .slice(0, 12)
    .map(([m, e]) => ({ m, unici: e.keys.size, bilete: e.bilete }));

  return {
    participants,
    stats: {
      unique: participants.length,
      returning: participants.filter((p) => p.num_courses > 1).length,
      tickets: participants.reduce((s, p) => s + p.total_tickets, 0),
    },
    evolution,
  };
}

export type TicketType = { pret?: number; vandute?: number; denumire?: string };

/**
 * Baza impozabilă DITL: valoarea nominală a biletelor vândute (preț × vândute).
 * Retururile, discounturile și comisionul platformei NU reduc baza. Fallback pe
 * total_bilete când nu există defalcare pe tipuri.
 */
export function ditlBase(types: TicketType[], fallback: number): number {
  if (!types?.length) return fallback;
  return types.reduce((s, t) => s + Number(t.pret ?? 0) * Number(t.vandute ?? 0), 0);
}

/** Câte bilete s-au vândut la un anumit tarif (pentru rândurile de viza). */
export function vanduteForTarif(types: TicketType[], tarif: number, nrUnitati?: number): number | null {
  const cands = types.filter((t) => Number(t.pret ?? 0) === Number(tarif));
  if (!cands.length) return null;
  if (cands.length === 1) return Number(cands[0].vandute ?? 0);
  // mai multe tipuri cu același preț: alege-l pe cel care se potrivește cu seria
  const exact = nrUnitati != null ? cands.find((c) => Number(c.vandute ?? 0) === nrUnitati) : undefined;
  return Number((exact ?? cands[0]).vandute ?? 0);
}
