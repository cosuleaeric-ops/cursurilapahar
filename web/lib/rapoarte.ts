// Parsare rapoarte iaBilet/LiveTickets (XLSX) și extragere serii din PDF-ul de viză.
// Port din JS-ul de upload (view.php) + parse_viza_subtips() din admin/statistici/db.php.
// Rulează pe server (Node), nu în browser ca în PHP.

import * as XLSX from "xlsx";

export type ParsedType = { bilet: string; pret: number; vandute: number; refund: number };
export type ParsedReport = { totalBilete: number; totalIncasari: number; types: ParsedType[] };

const num = (v: unknown): number => {
  const n = Number(String(v ?? "0").replace(/\s/g, "").replace(",", "."));
  return Number.isFinite(n) ? n : 0;
};

// Preț net per bilet → denumirea seriei (ca SERII din JS-ul vechi)
const SERII: Record<number, string> = { 50: "Bilet standard", 40: "Bilet standard", 30: "Bilet student", 25: "Bilet student", 20: "Bilet student" };

/** Acceptă exportul complet al evenimentului (foaia „Vanzari") sau decontul LiveTickets („Decont"). */
export function parseReportXlsx(buf: ArrayBuffer): ParsedReport | { error: string } {
  const wb = XLSX.read(buf, { type: "array" });
  const vanzari = wb.SheetNames.find((n) => /vanzari/i.test(n));
  const decont = wb.SheetNames.find((n) => /decont/i.test(n));
  if (!vanzari && !decont) {
    return {
      error:
        'Fișierul nu are foaia „Vanzari" sau „Decont". Încarcă exportul complet al evenimentului (Curs la Pahar - ....xlsx) sau decontul LiveTickets.',
    };
  }

  let totalBilete = 0;
  let totalIncasari = 0;
  const types: ParsedType[] = [];

  if (vanzari) {
    const rows = XLSX.utils.sheet_to_json<Record<string, unknown>>(wb.Sheets[vanzari], { defval: 0 });
    for (const row of rows) {
      const tb = num(row["Total bilete"] ?? row["total_bilete"]);
      const refund = num(row["Valoare retururi"] ?? row["valoare_retururi"]);
      const ti = num(row["Total incasari"] ?? row["total_incasari"]);
      const pret = num(row["Pret"] ?? row["pret"]);
      const vandute = num(row["Vandute"] ?? row["vandute"]);
      const bilet = String(row["Bilet"] ?? row["bilet"] ?? "").trim();
      totalBilete += tb - refund;
      totalIncasari += ti;
      if (bilet && pret > 0) types.push({ bilet, pret, vandute, refund });
    }
  } else if (decont) {
    // Decont LiveTickets: un rând per comandă; seria se deduce din prețul net per bilet.
    const rows = XLSX.utils.sheet_to_json<Record<string, unknown>>(wb.Sheets[decont], { defval: 0 });
    const byType = new Map<string, ParsedType>();
    for (const row of rows) {
      const id = String(row["ID Comanda"] ?? "").trim();
      if (/comision tranzactie/i.test(id)) {
        totalIncasari -= num(row["De transferat"]);
        continue;
      }
      const nr = num(row["Nr Bilete"]);
      if (!/^\d+$/.test(id) || !(nr > 0)) continue;
      const gross = num(row["Total bilete"]);
      const comision = num(row["Comision"]);
      const transf = num(row["De transferat"]);
      totalBilete += gross;
      totalIncasari += transf;
      // biletele cu discount rămân în seria de bază (prețul nominal), nu serie separată
      const basePret = Math.round(((gross - comision) / nr) * 100) / 100;
      const bilet = SERII[basePret] ?? `Tarif ${basePret} lei`;
      const key = `${bilet}|${basePret}`;
      const cur = byType.get(key) ?? { bilet, pret: basePret, vandute: 0, refund: 0 };
      cur.vandute += nr;
      byType.set(key, cur);
    }
    types.push(...byType.values());
  }

  return { totalBilete, totalIncasari, types };
}

export type VizaSubtip = { seria: string; tarif: number; nr_unitati: number; de_la: string; pana_la: string };

/** Extrage seriile din textul PDF-ului de viză — cele 3 formate din parse_viza_subtips(). */
export function parseVizaSubtips(raw: string): VizaSubtip[] {
  const text = raw.replace(/\r\n?/g, "\n");
  const out: VizaSubtip[] = [];
  const seen = new Set<string>();
  const toNum = (s: string) => Number(s.replace(/,/g, "."));

  // 1) Format vechi, ancorat pe antet („Tariful pe bucată (lei)" … „Seria De la nr. La nr.")
  const oldRe =
    /Tariful\s+pe\s+buc[aă]t[aă]\s*\(lei\)[^\n]*\s+(\d+)\s+([\d,.]+)\s+[\d,.]+\s+Seria\s+De\s+la\s+nr\.\s+La\s+nr\.[^\n]*\s+([A-Z]+)\s+(\d+)\s+(\d+)/gu;
  for (const m of text.matchAll(oldRe)) {
    out.push({ nr_unitati: Number(m[1]), tarif: toNum(m[2]), seria: m[3].trim(), de_la: m[4], pana_la: m[5] });
  }
  if (out.length) return out;

  // 2) Rânduri inline: „Bilet standard - ONLINE 57 50.00 2,850.00 SSR 0001 - SSR 0057"
  const inlineRe = /^.+?\s+(\d+)\s+([\d,.]+)\s+[\d,.]+\s+([A-Z]{2,})\s+(\d+)\s+-\s+[A-Z]{2,}\s+(\d+)/gmu;
  for (const m of text.matchAll(inlineRe)) {
    // cheia include tariful: două produse pot împărți aceeași serie+de_la fără să fie duplicate
    const key = `${m[3].trim()}_${m[4]}_${toNum(m[2])}`;
    if (seen.has(key)) continue;
    seen.add(key);
    out.push({ nr_unitati: Number(m[1]), tarif: toNum(m[2]), seria: m[3].trim(), de_la: m[4], pana_la: m[5] });
  }

  // 3) Format iaBilet (cerere vizare DITL): serie numerică lungă, fără litere
  const numRe = /^.+?\s+(\d+)\s+([\d,.]+)\s+[\d,.]+\s+(\d{8,})\s*-\s*(\d{8,})\s*$/gmu;
  for (const m of text.matchAll(numRe)) {
    const seria = m[3].slice(0, 6); // primele 6 cifre = ID-ul evenimentului iaBilet
    const key = `${seria}_${m[3]}_${toNum(m[2])}`;
    if (seen.has(key)) continue;
    seen.add(key);
    out.push({ nr_unitati: Number(m[1]), tarif: toNum(m[2]), seria, de_la: m[3], pana_la: m[4] });
  }

  return out;
}

/** Textul unui PDF, cu pdfjs în Node (echivalentul PDF.js din browser + pdf_to_text). */
export async function pdfToText(data: Uint8Array): Promise<string> {
  const pdfjs = await import("pdfjs-dist/legacy/build/pdf.js");
  const doc = await pdfjs.getDocument({ data, useSystemFonts: true, isEvalSupported: false }).promise;
  const pages: string[] = [];
  for (let i = 1; i <= doc.numPages; i++) {
    const page = await doc.getPage(i);
    const content = await page.getTextContent();
    // grupăm pe rânduri după coordonata Y, ca să păstrăm structura tabelului
    const lines = new Map<number, string[]>();
    for (const item of content.items as { str: string; transform: number[] }[]) {
      if (!("str" in item)) continue;
      const y = Math.round(item.transform[5]);
      lines.set(y, [...(lines.get(y) ?? []), item.str]);
    }
    pages.push(
      [...lines.entries()]
        .sort((a, b) => b[0] - a[0])
        .map(([, parts]) => parts.join(" ").replace(/\s+/g, " ").trim())
        .filter(Boolean)
        .join("\n")
    );
  }
  return pages.join("\n");
}
