import type { Metadata } from "next";
import { EMAIL_CSS } from "./styles";

// Landing pentru abonații reci (linkul din emailurile Kit). Nu face parte din
// grupul (site) — e o pagină de sine stătătoare, fără navbar/footer, noindex.
export const metadata: Metadata = {
  title: "Rămâi cu noi — Cursuri la Pahar",
  robots: { index: false, follow: false },
  icons: { icon: "/favicon.png" },
};

export default function EmailLandingPage() {
  return (
    <>
      <link
        href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap"
        rel="stylesheet"
      />
      <style dangerouslySetInnerHTML={{ __html: EMAIL_CSS }} />
      <div className="card">
        <div className="brand">
          <span className="glass">🍷</span> Cursuri la Pahar
        </div>
        <div className="badge">Ești pe listă ✦</div>
        <h1>Mă bucur că ești aici 👋</h1>
        <p>Te-am trecut înapoi pe lista activă.</p>
        <p>
          Vei primi <strong>în continuare</strong> invitații la următoarele <strong>Cursuri la Pahar</strong> — fără
          spam, doar ce contează.
        </p>
        <a href="/" className="cta">
          Vezi următoarele cursuri
        </a>
        <div className="foot">Ne vedem la un pahar. 🍷</div>
      </div>
    </>
  );
}
