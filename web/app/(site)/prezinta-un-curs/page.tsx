import type { Metadata } from "next";
import { sql } from "@/lib/db";
import { BackLink, ColaborareForm } from "../colaborare-form";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Prezintă un curs – Cursuri la Pahar",
  description:
    "Prezintă un curs la Cursuri la Pahar. Vino să împărtășești expertiza ta cu comunitatea noastră.",
};

const DEFAULTS = {
  sustine_title: "Prezintă un curs",
  sustine_intro_1:
    "Căutăm voci noi pentru <strong>Cursuri la Pahar</strong>! Dacă ai experiență într-un domeniu care te pasionează și vrei să dai mai departe din învățăturile tale, te așteptăm să susții un curs în cadrul evenimentelor noastre.",
  sustine_intro_2:
    'Dacă ai nevoie de inspirație, <a href="/cursuri-posibile" style="color:var(--accent);text-decoration:underline;">uită-te pe lista noastră cu cursuri posibile</a> care credem că s-ar potrivi la un pahar.',
};

export default async function PrezintaUnCursPage() {
  const rows = (await sql`
    SELECT key, value FROM settings
    WHERE key IN ('sustine_title', 'sustine_intro_1', 'sustine_intro_2')
  `) as { key: keyof typeof DEFAULTS; value: unknown }[];
  const s = { ...DEFAULTS };
  for (const r of rows) if (typeof r.value === "string" && r.value) s[r.key] = r.value;

  return (
    <section className="page-content-section">
      <div className="container container-narrow">
        <BackLink />
        <h1>{s.sustine_title}</h1>
        <div style={{ color: "var(--text-muted)", lineHeight: 1.8, marginBottom: 32 }}>
          <p dangerouslySetInnerHTML={{ __html: s.sustine_intro_1 }} />
          <p style={{ marginTop: 16 }} dangerouslySetInnerHTML={{ __html: s.sustine_intro_2 }} />
        </div>

        <div className="inner-form">
          <ColaborareForm formType="sustine">
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="suc_name">Nume și prenume *</label>
                <input type="text" id="suc_name" name="name" required />
              </div>
              <div className="form-group">
                <label htmlFor="suc_email">Email *</label>
                <input type="email" id="suc_email" name="email" required />
              </div>
            </div>
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="suc_phone">Număr de telefon</label>
                <input type="tel" id="suc_phone" name="phone" required />
              </div>
              <div className="form-group">
                <label htmlFor="suc_social">Link profil social media</label>
                <input type="url" id="suc_social" name="social" required />
              </div>
            </div>
            <div className="form-group">
              <label htmlFor="suc_course_name">Nume curs susținut *</label>
              <input type="text" id="suc_course_name" name="course_name" required />
            </div>
            <div className="form-group">
              <label htmlFor="suc_desc">Descrie cursul susținut *</label>
              <textarea id="suc_desc" name="course_desc" rows={4} required></textarea>
            </div>
            <div className="form-group">
              <label htmlFor="suc_why">De ce îți dorești să susții acest curs? *</label>
              <textarea id="suc_why" name="motivation" rows={3} required></textarea>
            </div>
            <div className="form-group">
              <label htmlFor="suc_experience">Ce experiențe sau competențe te califică?</label>
              <textarea id="suc_experience" name="experience" rows={3} required></textarea>
            </div>
            <div className="form-group">
              <label>Ai mai susținut astfel de prezentări?</label>
              <div className="radio-group">
                <label className="radio-label">
                  <input type="radio" name="previous_presentations" value="yes_often" required /> Da, o fac deseori.
                </label>
                <label className="radio-label">
                  <input type="radio" name="previous_presentations" value="yes_few" required /> Da, de puține ori.
                </label>
                <label className="radio-label">
                  <input type="radio" name="previous_presentations" value="no" required /> Nu, dar vreau să încerc.
                </label>
              </div>
            </div>
            <div className="form-group">
              <label htmlFor="suc_city">În ce oraș ai vrea să susții cursul?</label>
              <input type="text" id="suc_city" name="city" required />
            </div>
            <div className="form-group">
              <label htmlFor="suc_other">Mai e ceva ce vrei să ne transmiți?</label>
              <textarea id="suc_other" name="other" rows={2}></textarea>
            </div>
          </ColaborareForm>
        </div>
      </div>
    </section>
  );
}
