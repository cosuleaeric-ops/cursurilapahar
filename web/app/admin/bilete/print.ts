// Stil comun pentru cele trei formulare depuse la primărie. Se listează din
// browser (Print → Salvează ca PDF), pe A4.
export const PRINT_CSS = `
* { box-sizing:border-box; }
    body { background:#f4f4f4; margin:0; }
    .sheet { background:#fff; width:210mm; min-height:297mm; margin:16px auto; padding:18mm 16mm; font-family:"Helvetica Neue",Arial,sans-serif; font-size:11px; color:#000; line-height:1.45; }
    .sheet + .sheet { margin-top:24px; }
    .org { font-size:11px; line-height:1.5; margin-bottom:18px; }
    .to { font-weight:700; margin:18px 0; }
    .to .fill { font-weight:400; border-bottom:1px solid #000; display:inline-block; min-width:260px; }
    h1 { font-size:12px; font-weight:700; text-align:center; margin:22px 0 4px; letter-spacing:.2px; }
    h2 { font-size:11px; font-weight:700; text-align:center; margin:0 0 18px; }
    p { margin:0 0 12px; text-align:justify; }
    table { width:100%; border-collapse:collapse; margin:14px 0 16px; font-size:10.5px; }
    th, td { border:1px solid #000; padding:6px 7px; vertical-align:middle; }
    th { font-weight:400; text-align:center; }
    td.num { text-align:right; font-variant-numeric:tabular-nums; }
    tr.total td { font-weight:700; }
    .sign { display:flex; justify-content:space-between; gap:40px; margin-top:34px; }
    .sign > div { flex:1; }
    .line { border-bottom:1px solid #000; height:26px; margin-bottom:3px; }
    .cap { font-size:10px; color:#000; }
    .center { text-align:center; }
    .fill-line { display:inline-block; border-bottom:1px solid #000; min-width:90px; }
    .toolbar { width:210mm; margin:16px auto 0; display:flex; gap:8px; }
    .toolbar button, .toolbar a { font-size:13px; padding:7px 15px; border:1px solid #ccc; background:#fff; border-radius:6px; cursor:pointer; text-decoration:none; color:#333; font-family:system-ui,sans-serif; }
    @media print {
      body { background:#fff; }
      .toolbar { display:none; }
      .sheet { width:auto; min-height:0; margin:0; padding:0; box-shadow:none; }
      .sheet + .sheet { page-break-before:always; margin-top:0; }
    }
`;

export const money = (v: number) =>
  Number(v).toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const TZ = "Europe/Bucharest";
const dmy = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "2-digit", month: "2-digit", year: "numeric" });
/** „02.08.2026" — formatul din formularele primăriei. */
export const dataRo = (s: string | null): string => (s ? dmy.format(new Date(s)).replaceAll("/", ".") : "");

const luniRo = [
  "ianuarie", "februarie", "martie", "aprilie", "mai", "iunie",
  "iulie", "august", "septembrie", "octombrie", "noiembrie", "decembrie",
];
/** „2026-08" → { luna: „august", an: „2026" } */
export const lunaRo = (luna: string) => {
  const [an, m] = luna.split("-");
  return { luna: luniRo[Number(m) - 1] ?? "", an };
};
