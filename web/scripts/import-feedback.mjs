import { neon } from "@neondatabase/serverless";
import { readFileSync } from "fs";

const sql = neon(process.env.DATABASE_URL);

// CSV parser simplu care respectă ghilimelele
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

const csv = readFileSync(process.argv[2], "utf8");
const rows = parseCsv(csv);
const header = rows.shift();
console.log("header:", header.join(","), "| rânduri:", rows.length);

await sql`DROP TABLE IF EXISTS feedback`;
await sql`
  CREATE TABLE feedback (
    id INT PRIMARY KEY,
    curs TEXT NOT NULL,
    data_curs DATE,
    tema TEXT NOT NULL,
    tip TEXT NOT NULL,
    completat_la TEXT,
    experienta INT, speaker INT, continut INT, locatie INT, durata INT,
    pret TEXT, revenire TEXT, intrebare TEXT, text TEXT
  )
`;

const num = (v) => {
  const n = parseInt(String(v).trim(), 10);
  return Number.isFinite(n) ? n : null;
};
const str = (v) => (String(v ?? "").trim() === "" ? null : String(v).trim());

let ok = 0;
for (const r of rows) {
  const [id, curs, data, tema, tip, ts, exp, spk, cont, loc, dur, pret, rev, intrebareRaw, text] = r;
  // Mesajele pentru speakeri (ex. „Mesaj speaker (Delia)") intră la Altele — nu au categorie proprie pe pagină.
  const intrebare = /^Mesaj speaker/i.test(String(intrebareRaw).trim()) ? "Altele" : intrebareRaw;
  // data: "23.01.2026" sau "~04.04.2026" (aproximativă) → ISO
  const m = String(data).replace("~", "").trim().match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
  const iso = m ? `${m[3]}-${m[2]}-${m[1]}` : null;
  await sql`
    INSERT INTO feedback (id, curs, data_curs, tema, tip, completat_la,
      experienta, speaker, continut, locatie, durata, pret, revenire, intrebare, text)
    VALUES (${num(id)}, ${str(curs)}, ${iso}, ${str(tema)}, ${str(tip)}, ${str(ts)},
      ${num(exp)}, ${num(spk)}, ${num(cont)}, ${num(loc)}, ${num(dur)},
      ${str(pret)}, ${str(rev)}, ${str(intrebare)}, ${str(text)})
  `;
  ok++;
}
console.log("importate:", ok);

const check = await sql`SELECT tip, count(*) c FROM feedback GROUP BY tip`;
console.log(check);
