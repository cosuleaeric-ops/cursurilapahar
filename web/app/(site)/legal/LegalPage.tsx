// Cadrul comun al paginilor legale cerute de procesatorul de plăți. Ținute
// separat de restul site-ului: aici contează claritatea, nu stilul.

import { getOrganizator } from "@/lib/bilete";

export const LEGAL_CSS = `
.legal { background:#fff; color:#1a1a1a; padding:64px 0 80px; }
.legal .container { max-width:760px; }
.legal h1 { font-size:clamp(28px,4vw,40px); line-height:1.15; margin:0 0 8px; color:#111; }
.legal .legal-data { font-size:14px; color:#666; margin:0 0 40px; }
.legal h2 { font-size:20px; margin:36px 0 12px; color:#111; }
.legal h3 { font-size:16px; margin:24px 0 8px; color:#111; }
.legal p, .legal li { font-size:16px; line-height:1.65; color:#333; }
.legal p { margin:0 0 14px; }
.legal ul, .legal ol { margin:0 0 16px; padding-left:22px; }
.legal li { margin-bottom:8px; }
.legal a { color:#8a6a12; text-decoration:underline; }
.legal strong { color:#111; }
.legal .legal-firma { background:#f7f5f0; border-radius:12px; padding:20px 24px; margin:0 0 32px; }
.legal .legal-firma p { margin:0 0 6px; font-size:15px; }
.legal .legal-firma p:last-child { margin-bottom:0; }
.legal table { width:100%; border-collapse:collapse; margin:0 0 20px; font-size:15px; }
.legal th, .legal td { text-align:left; padding:10px 12px; border-bottom:1px solid #e5e0d6; vertical-align:top; }
.legal th { font-weight:700; color:#111; background:#faf8f4; }
`;

export async function DateFirma() {
  const o = await getOrganizator();
  return (
    <div className="legal-firma">
      <p>
        <strong>{o.nume}</strong>
      </p>
      <p>CUI: {o.cui} · Reg. Com.: {o.regCom}</p>
      <p>Sediu: {o.sediu}</p>
      <p>
        Telefon: <a href={`tel:${o.telefon}`}>{o.telefon}</a> · Email:{" "}
        <a href={`mailto:${o.email}`}>{o.email}</a>
      </p>
    </div>
  );
}

export default async function LegalPage({
  titlu,
  actualizat,
  children,
}: {
  titlu: string;
  actualizat: string;
  children: React.ReactNode;
}) {
  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: LEGAL_CSS }} />
      <section className="legal">
        <div className="container">
          <h1>{titlu}</h1>
          <p className="legal-data">Ultima actualizare: {actualizat}</p>
          {children}
        </div>
      </section>
    </>
  );
}
