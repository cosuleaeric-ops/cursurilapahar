import { sql } from "@/lib/db";
import MarketingSection, { type MktItem } from "./MarketingSection";

export const dynamic = "force-dynamic";

type SectionRow = { id: number; title: string };
type ItemRow = { id: number; section_id: number; payload: { text?: string; link?: string; done?: boolean } };

const COMPETITORS: { name: string; ig: string; tt: string; web: string }[] = [
  { name: "Nota de Subsol", ig: "https://www.instagram.com/notadesubsol.live/", tt: "https://www.tiktok.com/@notadesubsol.live", web: "" },
  { name: "Lectures on Tap", ig: "https://www.instagram.com/lecturesontap/", tt: "https://www.tiktok.com/@lecturesontap", web: "https://lecturesontap.com/" },
  { name: "Boozy Lectures", ig: "https://www.instagram.com/boozylectures/", tt: "https://www.tiktok.com/@boozylecturesyyc", web: "https://www.boozylectures.com/" },
  { name: "Brewing Minds", ig: "https://www.instagram.com/brewingminds_lectures/", tt: "https://www.tiktok.com/@brewingminds", web: "https://www.brewing-minds.com/" },
  { name: "Brains and Barstools", ig: "https://www.instagram.com/brainsandbarstools/", tt: "https://www.tiktok.com/@brainsandbarstools", web: "http://brainsandbarstools.com/" },
  { name: "The Social Study", ig: "https://www.instagram.com/thesocial.study/", tt: "https://www.tiktok.com/@thesocial.study", web: "https://www.thesocial.study/" },
  { name: "Sip and Learn Toronto", ig: "https://www.instagram.com/sip_and_learn_toronto/", tt: "https://www.tiktok.com/@sip_and_learn", web: "https://www.sipandlearn.ca" },
  { name: "The Unlecture", ig: "https://www.instagram.com/theunlecture/", tt: "", web: "" },
  { name: "Sip and Scholar", ig: "https://www.instagram.com/sipandscholar/", tt: "", web: "https://www.sipandscholar.com/" },
  { name: "Pint of View", ig: "https://www.instagram.com/pintofview.club/", tt: "", web: "https://pintofview.club/" },
  { name: "Big Brain SF", ig: "https://www.instagram.com/bigbrainsf/", tt: "https://www.tiktok.com/@bigbrainsf", web: "" },
  { name: "Society of Intellectuals", ig: "https://www.instagram.com/societyofintellectuals/", tt: "", web: "https://societyofintellectuals.org/" },
  { name: "Off-Campus", ig: "https://www.instagram.com/offcampus_fr/", tt: "", web: "https://www.offcampus.fr/" },
  { name: "Cursuri la Bar", ig: "https://www.instagram.com/cursurilabar", tt: "https://www.tiktok.com/@cursurilabar", web: "https://cursurilabar.ro/" },
];

export default async function MarketingPage() {
  const sections = (await sql`
    SELECT id, title FROM marketing_sections ORDER BY position, id
  `) as SectionRow[];
  const items = (await sql`
    SELECT id, section_id, payload FROM marketing_items ORDER BY position, id
  `) as ItemRow[];

  const itemsBySection = new Map<number, MktItem[]>();
  for (const r of items) {
    const list = itemsBySection.get(r.section_id) ?? [];
    list.push({
      id: r.id,
      text: r.payload.text ?? "",
      link: r.payload.link ?? "",
      done: r.payload.done ?? false,
    });
    itemsBySection.set(r.section_id, list);
  }

  return (
    <div className="mkt-page">
      <style>{`
.mkt-page .mkt-add-fields input {
    min-height: 0 !important;
    height: 22px !important;
    padding: 0 4px !important;
    margin: 0 !important;
    line-height: 22px !important;
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    border-radius: 0 !important;
}
.mkt-page .mkt-add-fields { flex-direction: row !important; gap: 12px !important; row-gap: 0 !important; }
.mkt-page .mkt-add-fields input[name="link"] { flex: 0 0 240px !important; }
.mkt-page .mkt-add-form { align-items: flex-start !important; padding: 2px 4px !important; }
.mkt-page .mkt-check-box--ghost { margin-top: 2px !important; }
      `}</style>
      <h1 className="mkt-title">Marketing</h1>
      <p className="mkt-lead">Idei de postări — bifează când e gata, adaugă text și opțional un link.</p>

      {sections.map((s) => (
        <MarketingSection key={s.id} id={s.id} title={s.title} items={itemsBySection.get(s.id) ?? []} />
      ))}

      <section className="mkt-competitori">
        <h2 className="mkt-section-title">Competitori</h2>
        <div className="comp-grid">
          {COMPETITORS.map((c) => (
            <div className="comp-card" key={c.name}>
              <div className="comp-card-name">{c.name}</div>
              <div className="comp-card-links">
                {c.ig && (
                  <a href={c.ig} target="_blank" rel="noopener" className="comp-link comp-link-ig">
                    📸 Instagram
                  </a>
                )}
                {c.tt && (
                  <a href={c.tt} target="_blank" rel="noopener" className="comp-link comp-link-tt">
                    🎵 TikTok
                  </a>
                )}
                {c.web && (
                  <a href={c.web} target="_blank" rel="noopener" className="comp-link comp-link-web">
                    🌐 Website
                  </a>
                )}
              </div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
