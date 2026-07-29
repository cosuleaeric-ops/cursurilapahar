// Importă răspunsurile unui formular de feedback (CSV base64) în tabela feedback.
// Mapează coloanele după textul întrebării (formatele vechi și noi diferă).
// Șterge întâi rândurile cursului, ca importul să fie idempotent.
// Rulare: node --env-file=.env.local scripts/import-form-sheet.mjs <fisier.b64> <cod> <data ISO> <tema>
import { neon } from "@neondatabase/serverless";
import { readFileSync } from "fs";

const sql = neon(process.env.DATABASE_URL);

function parseCsv(txt) {
  const rows = [];
  let row = [], cell = "", inQ = false;
  for (let i = 0; i < txt.length; i++) {
    const c = txt[i];
    if (inQ) {
      if (c === '"') {
        if (txt[i + 1] === '"') { cell += '"'; i++; }
        else inQ = false;
      } else cell += c;
    } else if (c === '"') inQ = true;
    else if (c === ",") { row.push(cell); cell = ""; }
    else if (c === "\n" || c === "\r") {
      if (c === "\r" && txt[i + 1] === "\n") i++;
      row.push(cell); cell = "";
      if (row.some((x) => x !== "")) rows.push(row);
      row = [];
    } else cell += c;
  }
  if (cell !== "" || row.length) { row.push(cell); if (row.some((x) => x !== "")) rows.push(row); }
  return rows;
}

const [file, curs, dataIso, tema] = process.argv.slice(2);
const b64 = readFileSync(file, "utf8").trim();
const rows = parseCsv(Buffer.from(b64, "base64").toString("utf8"));
const header = rows.shift().map((h) => h.toLowerCase());

// Maparea coloanelor după întrebare; "Teme dorite" și "Speakeri" se ignoră (nu apar pe pagină).
function col(re) {
  const i = header.findIndex((h) => re.test(h));
  return i === -1 ? null : i;
}
const map = {
  ts: col(/timestamp|marcaj/),
  experienta: col(/experien/),
  speaker: col(/speakerul din|interacțiunea|interactiunea/),
  continut: col(/conținutul|continutul/),
  locatie: col(/locația|locatia/),
  durata: col(/durata/),
  pret: col(/prețul|pretul/),
  revenire: col(/mai vii|mai veni/),
  text: col(/mai plăcute|mai placute/),
  altele: col(/nu te-am întrebat|nu te-am intrebat/),
  delia: col(/mesaj-feedback/),
};

const num = (v) => { const n = parseInt(String(v ?? "").trim(), 10); return Number.isFinite(n) ? n : null; };
const str = (v) => (String(v ?? "").trim() === "" ? null : String(v).trim());

await sql`DELETE FROM feedback WHERE curs = ${curs}`;
const [{ m }] = await sql`SELECT coalesce(max(id), 0) m FROM feedback`;
let id = Number(m);
let n = 0;
for (const r of rows) {
  const get = (k) => (map[k] == null ? null : r[map[k]]);
  const textParts = [str(get("text")), str(get("delia")), str(get("altele"))].filter(Boolean);
  id++;
  await sql`
    INSERT INTO feedback (id, curs, data_curs, tema, tip, completat_la,
      experienta, speaker, continut, locatie, durata, pret, revenire, intrebare, text)
    VALUES (${id}, ${curs}, ${dataIso}, ${tema}, 'raspuns', ${str(get("ts"))},
      ${num(get("experienta"))}, ${num(get("speaker"))}, ${num(get("continut"))}, ${num(get("locatie"))},
      ${num(get("durata"))}, ${str(get("pret"))}, ${str(get("revenire"))}, NULL,
      ${textParts.length ? textParts.join(" | ") : null})
  `;
  n++;
}
console.log(`${curs}: ${n} rânduri importate`);
