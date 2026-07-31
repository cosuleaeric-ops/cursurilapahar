import type { Metadata } from "next";
import { pageMetadata } from "@/lib/metadata";
import { ColaborareForm } from "../colaborare-form";
import Gallery from "../Gallery";
import { SP_CSS } from "./styles";

export const metadata: Metadata = pageMetadata({
  title: "Parteneri - Cursuri la Pahar",
  description:
    "Colaborează cu Cursuri la Pahar: peste 200.000 de vizualizări pe lună pe Instagram și TikTok, un newsletter cu open rate de peste 50% și cursuri săptămânale cu săli pline în București.",
  ogTitle: "Parteneri - Cursuri la Pahar",
  ogDescription:
    "Peste 200.000 de vizualizări pe lună, newsletter cu open rate de peste 50% și cursuri săptămânale cu săli pline. Vezi cifrele și scrie-ne.",
  // parteneri.php:52 — pe twitter fraza „Vezi cifrele și scrie-ne." lipsește.
  twitterDescription:
    "Peste 200.000 de vizualizări pe lună, newsletter cu open rate de peste 50% și cursuri săptămânale cu săli pline.",
  // parteneri.php:46 și :53 — fără `?v=2`, spre deosebire de celelalte pagini.
  ogImage: "/assets/images/og-image.jpg",
  path: "/parteneri",
});

const GALLERY = ["gallery-05", "gallery-11", "gallery-01", "gallery-25", "gallery-08", "gallery-32", "gallery-17", "gallery-06"].map(
  (g) => `/assets/images/gallery/${g}.webp`
);

const CHANNELS = [
  { icon: "📸", name: "Instagram", big: "150k+", label: "vizualizări / lună", text: "21.2k urmăritori · reels și stories de la fiecare curs." },
  { icon: "🎬", name: "TikTok", big: "50k+", label: "vizualizări / lună", text: "8.4k urmăritori · clipuri filmate la evenimente." },
  { icon: "📬", name: "Newsletter", big: "~53%", label: "open rate", text: "1.943 abonați · un email pe săptămână, citit de o comunitate fidelă." },
  { icon: "🍻", name: "Evenimente", big: "50-70", label: "participanți / curs", text: "În fiecare săptămână, în baruri din București, cu bilete plătite." },
];

export default function ParteneriPage() {
  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: SP_CSS }} />
      <div className="sp-wrap">
        <header className="sp-hero" id="oferta">
          <div className="container">
            <div className="sp-hero-grid">
              <div>
                <h1>
                  Brandul tău în fața a peste <span>200.000 de oameni</span> pasionați de educație
                </h1>
                <p className="sp-hero-sub">
                  Organizăm cursuri în fiecare săptămână, în baruri din București. Avem peste{" "}
                  <strong>200.000 de vizualizări pe lună</strong> pe Instagram și TikTok, un newsletter citit de aproape
                  2.000 de oameni și săli pline la fiecare eveniment.
                </p>
                <p className="sp-hero-sub">
                  Dacă vrei ca brandul tău să ajungă la acești oameni, scrie-ne și găsim împreună cea mai bună formă de
                  colaborare.
                </p>
              </div>
              <div className="sp-form-card">
                <ColaborareForm formType="sponsorizare" buttonClassName="sp-btn" buttonLabel="Hai să vorbim">
                  <label htmlFor="sp_company">
                    Nume companie <em>*</em>
                  </label>
                  <input type="text" id="sp_company" name="company" required />

                  <label htmlFor="sp_contact">
                    Persoana de contact <em>*</em>
                  </label>
                  <input type="text" id="sp_contact" name="contact_person" required />

                  <label htmlFor="sp_email">
                    Email <em>*</em>
                  </label>
                  <input type="email" id="sp_email" name="email" required />

                  <label htmlFor="sp_phone">
                    Număr de telefon <em>*</em>
                  </label>
                  <input type="tel" id="sp_phone" name="phone" required />

                  <label htmlFor="sp_type">
                    Tipul de parteneriat <em>*</em>
                  </label>
                  <select id="sp_type" name="partnership_type" required defaultValue="">
                    <option value="">Alege o variantă</option>
                    <option value="financiar">Partener financiar</option>
                    <option value="strategic">Partener strategic</option>
                    <option value="newsletter">Mențiune în newsletter</option>
                    <option value="altceva">Încă nu știu / altceva</option>
                  </select>

                  <label htmlFor="sp_msg">Cum îți dorești să colaborăm?</label>
                  <textarea id="sp_msg" name="message" rows={3}></textarea>
                </ColaborareForm>
              </div>
            </div>
          </div>
        </header>

        <section className="sp-sec">
          <div className="container">
            <h2 className="section-title">Patru canale, aceeași comunitate</h2>
            <p className="sp-lead">Un public tânăr și educat, care iese în oraș și plătește bilet ca să învețe.</p>
            <div className="sp-demo">
              <span>25-40 ani</span>
              <span>Educație superioară</span>
              <span>75% femei · 25% bărbați</span>
              <span>Din București</span>
            </div>
            <div className="sp-aud">
              {CHANNELS.map((c) => (
                <div className="sp-aud-card" key={c.name}>
                  <span className="ic">{c.icon}</span>
                  <h3>{c.name}</h3>
                  <div className="big">{c.big}</div>
                  <div className="biglbl">{c.label}</div>
                  <p>{c.text}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="sp-sec">
          <div className="container">
            <h2 className="section-title">Curs la Pahar</h2>
            {/* parteneri.php:322-337 randează doar sliderul; #galleryLightbox
                există exclusiv în index.php, deci aici click-ul nu deschide nimic */}
            <Gallery images={GALLERY} lightbox={false} />
          </div>
        </section>

        <section className="sp-cta">
          <div className="container">
            <h2>Susține un proiect educațional</h2>
            <p>Scrie-ne și găsim împreună cea mai bună formă de colaborare.</p>
            <a href="#oferta" className="sp-btn">
              Hai să vorbim
            </a>
          </div>
        </section>
      </div>
    </>
  );
}
