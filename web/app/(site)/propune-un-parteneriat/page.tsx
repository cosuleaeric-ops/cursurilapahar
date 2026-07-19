import type { Metadata } from "next";
import { sql } from "@/lib/db";
import { BackLink, ColaborareForm } from "../colaborare-form";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Propune un parteneriat – Cursuri la Pahar",
  description:
    "Propune un parteneriat cu Cursuri la Pahar. Hai să explorăm ce putem construi împreună.",
};

const DEFAULTS = {
  parteneriat_title: "Propune un parteneriat",
  parteneriat_intro_1:
    "Credem în <strong>puterea colaborării</strong> și în ideea că <strong>proiectele faine cresc prin conexiuni valoroase</strong>. Dacă reprezinți un brand, o platformă media sau un proiect care rezonează cu misiunea noastră de a aduce educația într-un format relaxat, ne-ar plăcea să explorăm cum putem construi împreună.",
  parteneriat_intro_2:
    "Căutăm parteneri care <strong>pun preț pe calitate</strong> și care vor să se implice activ în experiența pe care o oferim comunității noastre. Deci, dacă te regăsești în această descriere, completează formularul de mai jos.",
};

export default async function PropuneUnParteneriatPage() {
  const rows = (await sql`
    SELECT key, value FROM settings
    WHERE key IN ('parteneriat_title', 'parteneriat_intro_1', 'parteneriat_intro_2')
  `) as { key: keyof typeof DEFAULTS; value: unknown }[];
  const s = { ...DEFAULTS };
  for (const r of rows) if (typeof r.value === "string" && r.value) s[r.key] = r.value;

  return (
    <section className="page-content-section">
      <div className="container container-narrow">
        <BackLink />
        <h1>{s.parteneriat_title}</h1>
        <div style={{ color: "var(--text-muted)", lineHeight: 1.8, marginBottom: 32 }}>
          <p dangerouslySetInnerHTML={{ __html: s.parteneriat_intro_1 }} />
          <p style={{ marginTop: 16 }} dangerouslySetInnerHTML={{ __html: s.parteneriat_intro_2 }} />
        </div>

        <div className="inner-form">
          <ColaborareForm formType="parteneriat">
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="pp_partner">Nume partener / companie *</label>
                <input type="text" id="pp_partner" name="partner_name" required />
              </div>
              <div className="form-group">
                <label htmlFor="pp_contact">Persoana de contact *</label>
                <input type="text" id="pp_contact" name="contact_person" required />
              </div>
            </div>
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="pp_email">Email *</label>
                <input type="email" id="pp_email" name="email" required />
              </div>
              <div className="form-group">
                <label htmlFor="pp_phone">Număr de telefon</label>
                <input type="tel" id="pp_phone" name="phone" required />
              </div>
            </div>
            <div className="form-group">
              <label>Tipul parteneriatului</label>
              <div className="checkbox-group">
                <label className="checkbox-label">
                  <input type="checkbox" name="partnership_type" value="media" /> Parteneriat Media (vizibilitate,
                  promovare, PR)
                </label>
                <label className="checkbox-label">
                  <input type="checkbox" name="partnership_type" value="product" /> Activare de produs (sampling,
                  experiență directă cu participanții)
                </label>
                <label className="checkbox-label">
                  <input type="checkbox" name="partnership_type" value="strategic" /> Parteneriat Strategic / Financiar
                  (sponsorizare)
                </label>
                <label className="checkbox-label">
                  <input type="checkbox" name="partnership_type" value="other" /> Altul
                </label>
              </div>
            </div>
            <div className="form-group">
              <label htmlFor="pp_vision">Cum vizualizezi colaborarea cu Cursuri la Pahar? *</label>
              <textarea id="pp_vision" name="vision" rows={4} required></textarea>
            </div>
            <div className="form-group">
              <label htmlFor="pp_values">De ce crezi că valorile noastre se aliniază?</label>
              <textarea id="pp_values" name="values_alignment" rows={3} required></textarea>
            </div>
            <div className="form-group">
              <label htmlFor="pp_other">Mai e ceva ce vrei să ne transmiți?</label>
              <textarea id="pp_other" name="other" rows={2}></textarea>
            </div>
          </ColaborareForm>
        </div>
      </div>
    </section>
  );
}
