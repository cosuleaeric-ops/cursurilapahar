"use client";

import { useMemo, useState } from "react";

export type Row = {
  id: number;
  curs: string;
  data_curs: string | null;
  tema: string;
  tip: string;
  experienta: number | null;
  speaker: number | null;
  continut: number | null;
  locatie: number | null;
  durata: number | null;
  pret: string | null;
  revenire: string | null;
  intrebare: string | null;
  text: string | null;
};

const LUNI = ["ian", "feb", "mar", "apr", "mai", "iun", "iul", "aug", "sep", "oct", "nov", "dec"];
function dataScurta(iso: string | null): string {
  if (!iso) return "";
  const [y, m, d] = iso.split("-").map(Number);
  return `${d} ${LUNI[m - 1]} ${y}`;
}

function norm(s: string): string {
  return s.toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
}

// Teme recurente în comentarii — chip-urile filtrează lista când dai click pe ele.
const TEME = [
  { key: "locatie", label: "🪑 Locație & confort", re: /scaun|locati|spati[uo]|mes[ae]|lipicio|inghesu|frig|vizibil|acustic|vedere|ecran|incapere|sala|miros/ },
  { key: "interactiv", label: "🎲 Interactivitate & socializare", re: /interactiv|joc|social|networking|q&a|intrebari|dezbat|cunoastere/ },
  { key: "continut", label: "🧠 Conținut & speakeri", re: /superficial|profund|structur|specializ|slide|prezentar|speaker|aprofund/ },
  { key: "timp", label: "⏱️ Punctualitate & durată", re: /punctual|intarz|durat|mai lung|prea scurt|pauz|sa dureze/ },
  { key: "laude", label: "❤️ Laude", re: /felicit|super|minunat|perfect|excelent|bravo|placut|multumim|fain|\btop\b|genial/ },
];

const CATEGORII = ["Îmbunătățire", "Altele"];

function categoria(r: Row): string {
  return r.tip === "raspuns" ? "Răspuns cu note" : (r.intrebare ?? "Altele");
}

function BaraCount({ label, n, max, total }: { label: string; n: number; max: number; total: number }) {
  return (
    <div className="fb-bara">
      <span className="fb-bara-label">{label}</span>
      <div className="fb-bara-track">
        <div className="fb-bara-fill" style={{ width: max ? `${(n / max) * 100}%` : 0 }} />
      </div>
      <span className="fb-bara-val">{n} ({total ? Math.round((n / total) * 100) : 0}%)</span>
    </div>
  );
}

function Bara({ label, val }: { label: string; val: number }) {
  return (
    <div className="fb-bara">
      <span className="fb-bara-label">{label}</span>
      <div className="fb-bara-track">
        <div className="fb-bara-fill" style={{ width: `${(val / 5) * 100}%` }} />
      </div>
      <span className="fb-bara-val">{val.toFixed(2)}</span>
    </div>
  );
}

function medie(vals: (number | null)[]): number | null {
  const v = vals.filter((x): x is number => x != null);
  return v.length ? v.reduce((a, b) => a + b, 0) / v.length : null;
}

export default function Dashboard({ rows }: { rows: Row[] }) {
  const [curs, setCurs] = useState("");
  const [cat, setCat] = useState("");
  const [tema, setTema] = useState("");
  const [q, setQ] = useState("");

  // Cursurile în ordine cronologică, cu tema și data lor.
  const cursuri = useMemo(() => {
    const map = new Map<string, { tema: string; data: string | null; n: number }>();
    for (const r of rows) {
      const c = map.get(r.curs);
      if (c) c.n++;
      else map.set(r.curs, { tema: r.tema, data: r.data_curs, n: 1 });
    }
    return [...map.entries()];
  }, [rows]);

  const notate = useMemo(() => rows.filter((r) => r.tip === "raspuns"), [rows]);

  const stats = useMemo(() => {
    const exp = medie(notate.map((r) => r.experienta));
    // „revenire" e text („Clar da") la cursurile vechi și notă 1–5 la cele noi.
    const revNote = rows.filter((r) => r.revenire && /^\d$/.test(r.revenire)).map((r) => Number(r.revenire));
    const revDist: Record<number, number> = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
    for (const v of revNote) revDist[v]++;
    const revNum = medie(revNote);
    const revTextDist = new Map<string, number>();
    for (const r of rows) {
      if (r.revenire && !/^\d$/.test(r.revenire)) {
        revTextDist.set(r.revenire, (revTextDist.get(r.revenire) ?? 0) + 1);
      }
    }
    const pretDist = new Map<string, number>();
    for (const p of ["Ieftin", "Potrivit", "Scump"]) pretDist.set(p, 0);
    for (const r of rows) if (r.pret) pretDist.set(r.pret, (pretDist.get(r.pret) ?? 0) + 1);
    const pretTotal = [...pretDist.values()].reduce((a, b) => a + b, 0);
    return { exp, revDist, revNum, revTotal: revNote.length, revTextDist, pretDist, pretTotal };
  }, [rows, notate]);

  const temeCount = useMemo(() => {
    const cnt: Record<string, number> = {};
    for (const t of TEME) cnt[t.key] = rows.filter((r) => r.text && t.re.test(norm(r.text))).length;
    return cnt;
  }, [rows]);

  const noteMedii = useMemo(
    () => [
      { label: "Experiența", val: medie(notate.map((r) => r.experienta)) },
      { label: "Speakerul", val: medie(notate.map((r) => r.speaker)) },
      { label: "Conținutul", val: medie(notate.map((r) => r.continut)) },
      { label: "Locația", val: medie(notate.map((r) => r.locatie)) },
    ],
    [notate],
  );

  const filtrate = useMemo(() => {
    const temaDef = TEME.find((t) => t.key === tema);
    const nq = norm(q.trim());
    return rows.filter((r) => {
      if (curs && r.curs !== curs) return false;
      if (cat && categoria(r) !== cat) return false;
      if (temaDef && !(r.text && temaDef.re.test(norm(r.text)))) return false;
      if (nq && !norm(`${r.tema} ${r.text ?? ""} ${r.revenire ?? ""}`).includes(nq)) return false;
      return true;
    });
  }, [rows, curs, cat, tema, q]);

  return (
    <div className="fb">
      <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
      />
      <style>{CSS}</style>

      <header className="fb-header">
        <h1>Feedback participanți</h1>
        <p>
          Tot ce ne-au scris participanții după cursuri — {rows.length} răspunsuri, {cursuri.length} cursuri,{" "}
          {dataScurta(cursuri[0]?.[1].data)} → {dataScurta(cursuri[cursuri.length - 1]?.[1].data)}.
        </p>
      </header>

      <section className="fb-stats">
        <div className="fb-stat">
          <b>{rows.length}</b>
          <span>răspunsuri</span>
        </div>
        <div className="fb-stat">
          <b>{cursuri.length}</b>
          <span>cursuri</span>
        </div>
        <div className="fb-stat">
          <b>{stats.exp ? stats.exp.toFixed(2) : "—"}<i>/5</i></b>
          <span>experiența (medie, {notate.length} note)</span>
        </div>
        <div className="fb-stat">
          <b>{stats.revNum ? stats.revNum.toFixed(2) : "—"}<i>/5</i></b>
          <span>cât de probabil ar reveni (medie, {stats.revTotal} note)</span>
        </div>
      </section>

      <section className="fb-rezumat">
        <h2>Rezumat</h2>
        <div className="fb-rezumat-grid">
          <div className="fb-card">
            <h3>Note medii (cursurile cu formular cu note)</h3>
            {noteMedii.map((n) => (n.val != null ? <Bara key={n.label} label={n.label} val={n.val} /> : null))}
          </div>
          <div className="fb-card">
            <h3>Cât de probabil ar mai veni? (1–5) — {stats.revTotal} voturi</h3>
            {[5, 4, 3, 2, 1].map((v) => (
              <BaraCount
                key={v}
                label={`Nota ${v}`}
                n={stats.revDist[v]}
                max={Math.max(...Object.values(stats.revDist))}
                total={stats.revTotal}
              />
            ))}
            {stats.revTextDist.size > 0 && (
              <p className="fb-hint">
                La primele cursuri întrebarea avea răspuns text:{" "}
                {[...stats.revTextDist.entries()].map(([k, n]) => `${n}× „${k}”`).join(", ")}.
              </p>
            )}
          </div>
          <div className="fb-card">
            <h3>Cum li s-a părut prețul biletului? ({stats.pretTotal} voturi)</h3>
            {[...stats.pretDist.entries()].map(([k, n]) => (
              <BaraCount
                key={k}
                label={k}
                n={n}
                max={Math.max(...stats.pretDist.values())}
                total={stats.pretTotal}
              />
            ))}
          </div>
          <div className="fb-card">
            <h3>Ce se repetă în comentarii <small>(click pe o temă ca să filtrezi)</small></h3>
            <div className="fb-teme">
              {TEME.map((t) => (
                <button
                  key={t.key}
                  className={tema === t.key ? "on" : ""}
                  onClick={() => setTema(tema === t.key ? "" : t.key)}
                >
                  {t.label} <b>{temeCount[t.key]}</b>
                </button>
              ))}
            </div>
            <p className="fb-hint">
              Cel mai des cerut: locație mai încăpătoare și scaune mai comode, apoi mai multă interactivitate
              (jocuri, Q&amp;A, socializare). Laudele domină: atmosfera, speakerii și inițiativa în sine.
            </p>
          </div>
        </div>
      </section>

      <section className="fb-filtre">
        <select value={curs} onChange={(e) => setCurs(e.target.value)} aria-label="Curs">
          <option value="">Toate cursurile</option>
          {cursuri.map(([cod, c]) => (
            <option key={cod} value={cod}>
              {dataScurta(c.data)} — {c.tema} ({c.n})
            </option>
          ))}
        </select>
        <select value={cat} onChange={(e) => setCat(e.target.value)} aria-label="Categorie">
          <option value="">Toate categoriile</option>
          <option>Răspuns cu note</option>
          {CATEGORII.map((c) => (
            <option key={c}>{c}</option>
          ))}
        </select>
        <input type="search" value={q} onChange={(e) => setQ(e.target.value)} aria-label="Caută" />
        <span className="fb-count">{filtrate.length} rezultate</span>
      </section>

      <section className="fb-lista">
        {filtrate.map((r) => (
          <article key={r.id} className="fb-item">
            <div className="fb-item-meta">
              <span className="fb-item-curs">{r.tema}</span>
              <span className="fb-item-data">{dataScurta(r.data_curs)}</span>
              <span className={`fb-badge ${r.tip === "raspuns" ? "note" : ""}`}>{categoria(r)}</span>
            </div>
            {r.tip === "raspuns" && (
              <div className="fb-note">
                {r.experienta != null && <span>Experiență <b>{r.experienta}</b></span>}
                {r.speaker != null && <span>Speaker <b>{r.speaker}</b></span>}
                {r.continut != null && <span>Conținut <b>{r.continut}</b></span>}
                {r.locatie != null && <span>Locație <b>{r.locatie}</b></span>}
                {r.durata != null && <span>Durată <b>{r.durata}</b></span>}
                {r.pret && <span>Preț: <b>{r.pret}</b></span>}
                {r.revenire && <span>Ar reveni: <b>{r.revenire}{/^\d$/.test(r.revenire) ? "/5" : ""}</b></span>}
              </div>
            )}
            {r.text && <p className="fb-text">{r.text}</p>}
          </article>
        ))}
        {!filtrate.length && <p className="fb-gol">Niciun rezultat cu filtrele astea.</p>}
      </section>
    </div>
  );
}

const CSS = `
  .fb {
    min-height: 100vh; background: #ffffff; color: #171717;
    font-family: 'Poppins', sans-serif; padding: 40px 20px 80px;
  }
  .fb > * { max-width: 960px; margin-left: auto; margin-right: auto; }
  .fb-header h1 {
    font-weight: 800; text-transform: uppercase;
    font-size: clamp(28px, 5vw, 40px); line-height: 1.2; color: #171717;
  }
  .fb-header p { color: #6b6b6b; margin: 8px 0 28px; font-size: 15px; }
  .fb-stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px; margin-bottom: 28px;
  }
  .fb-stat {
    background: #fafafa; border: 1px solid #e5e5e5; border-radius: 12px; padding: 16px;
  }
  .fb-stat b { font-size: 26px; color: #8E1B1B; display: block; font-weight: 700; }
  .fb-stat b i { font-style: normal; font-size: 15px; color: #c99a9a; }
  .fb-stat span { font-size: 12.5px; color: #6b6b6b; }
  .fb-rezumat h2, .fb h2 {
    font-weight: 800; text-transform: uppercase;
    font-size: 21px; line-height: 1.2; margin-bottom: 14px;
  }
  .fb-rezumat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 32px; }
  @media (max-width: 720px) { .fb-rezumat-grid { grid-template-columns: 1fr; } }
  .fb-card { background: #fafafa; border: 1px solid #e5e5e5; border-radius: 12px; padding: 18px; }
  .fb-card h3 { font-size: 14px; font-weight: 600; margin-bottom: 14px; color: #444; }
  .fb-card h3 small { font-weight: 400; color: #8a8a8a; }
  .fb-bara { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
  .fb-bara-label { width: 90px; font-size: 13px; color: #6b6b6b; }
  .fb-bara-track { flex: 1; height: 8px; background: #ececec; border-radius: 4px; overflow: hidden; }
  .fb-bara-fill { height: 100%; background: #8E1B1B; border-radius: 4px; }
  .fb-bara-val { min-width: 64px; font-size: 13px; color: #8E1B1B; font-weight: 600; text-align: right; white-space: nowrap; }
  .fb-teme { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
  .fb-teme button {
    background: #ffffff; border: 1px solid #d4d4d4; color: #444; border-radius: 999px;
    padding: 7px 13px; font-size: 13px; cursor: pointer; font-family: inherit;
  }
  .fb-teme button b { color: #8E1B1B; }
  .fb-teme button.on { border-color: #8E1B1B; background: rgba(142, 27, 27, .08); }
  .fb-hint { font-size: 13px; color: #6b6b6b; line-height: 1.5; }
  .fb-filtre {
    display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 18px;
    position: sticky; top: 0; background: rgba(255, 255, 255, .95); padding: 12px 0; z-index: 5;
  }
  .fb-filtre select, .fb-filtre input {
    background: #ffffff; border: 1px solid #d4d4d4; color: #171717; border-radius: 8px;
    padding: 10px 12px; font-size: 14px; font-family: inherit; max-width: 100%;
  }
  .fb-filtre input { flex: 1; min-width: 140px; }
  .fb-filtre select { max-width: 320px; }
  .fb-filtre :focus { outline: none; border-color: #8E1B1B; }
  .fb-count { font-size: 13px; color: #6b6b6b; white-space: nowrap; }
  .fb-lista { display: flex; flex-direction: column; gap: 10px; }
  .fb-item { background: #fafafa; border: 1px solid #e5e5e5; border-radius: 12px; padding: 14px 16px; }
  .fb-item-meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 8px; }
  .fb-item-curs { font-weight: 600; font-size: 14px; }
  .fb-item-data { color: #8a8a8a; font-size: 13px; }
  .fb-badge {
    font-size: 11.5px; border: 1px solid #d4d4d4; color: #6b6b6b; border-radius: 999px; padding: 2px 9px;
  }
  .fb-badge.note { border-color: #8E1B1B; color: #8E1B1B; }
  .fb-note { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: 13px; color: #6b6b6b; margin-bottom: 8px; }
  .fb-note b { color: #8E1B1B; }
  .fb-text { font-size: 14.5px; line-height: 1.55; color: #333; white-space: pre-line; }
  .fb-gol { color: #6b6b6b; padding: 30px 0; text-align: center; }
`;
