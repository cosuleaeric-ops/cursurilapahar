import type { Metadata } from "next";
import { sql } from "@/lib/db";
import { marketingCompetitorsFromSetting } from "@/lib/marketing-competitors";
import MarketingSection, { type MktItem } from "./MarketingSection";

export const dynamic = "force-dynamic";

// marketing/index.php:110 — <title>Marketing — Admin</title>
export const metadata: Metadata = { title: "Marketing - Admin" };

type SectionRow = { id: number; title: string };
type ItemRow = { id: number; section_id: number; payload: { text?: string; link?: string; done?: boolean } };
type CompetitorSettingRow = { value: unknown };

export default async function MarketingPage() {
  const [sectionRows, itemRows, competitorRowsRaw] = await Promise.all([
    sql`SELECT id, title FROM marketing_sections ORDER BY position, id`,
    sql`SELECT id, section_id, payload FROM marketing_items ORDER BY position, id`,
    sql`SELECT value FROM settings WHERE key = 'marketing_competitors'`,
  ]);
  const sections = sectionRows as SectionRow[];
  const items = itemRows as ItemRow[];
  const competitorRows = competitorRowsRaw as CompetitorSettingRow[];
  const competitors = marketingCompetitorsFromSetting(competitorRows[0]?.value);

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
      <p className="mkt-lead">Idei de postări - bifează când e gata, adaugă text și opțional un link.</p>

      {sections.map((s) => (
        <MarketingSection key={s.id} id={s.id} title={s.title} items={itemsBySection.get(s.id) ?? []} />
      ))}

      {/* marketing/index.php:233 - ancoră pentru /admin/marketing/#competitori */}
      <section id="competitori" className="mkt-competitori">
        <h2 className="mkt-section-title">Competitori</h2>
        <div className="comp-grid">
          {competitors.map((c) => (
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
