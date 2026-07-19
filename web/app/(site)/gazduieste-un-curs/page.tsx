import type { Metadata } from "next";
import { sql } from "@/lib/db";
import { BackLink, ColaborareForm } from "../colaborare-form";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Găzduiește un curs – Cursuri la Pahar",
  description:
    "Găzduiește un curs la Cursuri la Pahar. Transformă-ți locația într-un loc de întâlnire pentru comunitatea noastră.",
};

const DEFAULTS = {
  gazduieste_title: "Găzduiește un curs",
  gazduieste_intro_1:
    "Ai o locație cu vibe fain și vrei să o transformi într-un loc de întâlnire al participanților Cursuri la Pahar? Well, noi căutăm parteneri care să devină „acasă\" pentru evenimentele noastre!",
  gazduieste_intro_2:
    "Ai un <strong>bar, un pub, o cafenea</strong> sau un spațiu neconvențional care debordează de personalitate? Ne-ar plăcea să aducem conceptul <strong>Cursuri la Pahar</strong> la tine.",
};

export default async function GazduiesteUnCursPage() {
  const rows = (await sql`
    SELECT key, value FROM settings
    WHERE key IN ('gazduieste_title', 'gazduieste_intro_1', 'gazduieste_intro_2')
  `) as { key: keyof typeof DEFAULTS; value: unknown }[];
  const s = { ...DEFAULTS };
  for (const r of rows) if (typeof r.value === "string" && r.value) s[r.key] = r.value;

  return (
    <section className="page-content-section">
      <div className="container container-narrow">
        <BackLink />
        <h1>{s.gazduieste_title}</h1>
        <div style={{ color: "var(--text-muted)", lineHeight: 1.8, marginBottom: 28 }}>
          <p dangerouslySetInnerHTML={{ __html: s.gazduieste_intro_1 }} />
          <p style={{ marginTop: 16 }} dangerouslySetInnerHTML={{ __html: s.gazduieste_intro_2 }} />
          <p style={{ marginTop: 16 }}>
            <strong>De ce să devii locație parteneră?</strong>
          </p>
          <ul className="benefits-bullets" style={{ marginTop: 8, paddingLeft: 20 }}>
            <li>
              <strong>Vizibilitate:</strong> Atragi un public nou, dornic de experiențe de calitate.
            </li>
            <li>
              <strong>Comunitate:</strong> Spațiul tău devine un punct de reper pentru educație și socializare.
            </li>
            <li>
              <strong>Vibe:</strong> Îți umpli locația cu energie pozitivă și oameni pasionați.
            </li>
          </ul>
          <p style={{ marginTop: 16 }}>
            Pentru a putea susține un Curs la Pahar, localul trebuie să aibă minimum <strong>40 de locuri</strong>{" "}
            seated, un <strong>sistem audio cu microfon</strong> și un{" "}
            <strong>ecran de proiecție/televizor mare</strong>, pentru prezentarea speakerilor.
          </p>
        </div>

        <div className="inner-form">
          <ColaborareForm formType="gazduieste">
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="guc_name">Nume și prenume *</label>
                <input type="text" id="guc_name" name="name" required />
              </div>
              <div className="form-group">
                <label htmlFor="guc_email">Email *</label>
                <input type="email" id="guc_email" name="email" required />
              </div>
            </div>
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="guc_phone">Număr de telefon</label>
                <input type="tel" id="guc_phone" name="phone" required />
              </div>
              <div className="form-group">
                <label htmlFor="guc_venue">Cum se numește localul? *</label>
                <input type="text" id="guc_venue" name="venue_name" required />
              </div>
            </div>
            <div className="form-row">
              <div className="form-group">
                <label htmlFor="guc_city">În ce oraș? *</label>
                <input type="text" id="guc_city" name="city" required />
              </div>
              <div className="form-group">
                <label htmlFor="guc_capacity">Capacitate (seated)</label>
                <input type="text" id="guc_capacity" name="capacity" required />
              </div>
            </div>
            <div className="form-group">
              <label>Ce facilități deține locația?</label>
              <div className="checkbox-group">
                <label className="checkbox-label">
                  <input type="checkbox" name="facilities" value="audio" /> Sistem audio cu microfon
                </label>
                <label className="checkbox-label">
                  <input type="checkbox" name="facilities" value="projector" /> Videoproiector
                </label>
                <label className="checkbox-label">
                  <input type="checkbox" name="facilities" value="screen" /> Ecran de proiecție
                </label>
                <label className="checkbox-label">
                  <input type="checkbox" name="facilities" value="tv" /> Televizor pentru proiecție
                </label>
              </div>
            </div>
            <div className="form-group">
              <label htmlFor="guc_other">Mai e ceva ce vrei să ne transmiți?</label>
              <textarea id="guc_other" name="other" rows={3}></textarea>
            </div>
          </ColaborareForm>
        </div>
      </div>
    </section>
  );
}
