import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import { getCampaignCreatives, getCampaignCosts, getCampaignName, metaToken } from "@/lib/meta";

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }): Promise<Metadata> {
  const { id } = await params;
  try {
    return { title: `${await getCampaignName(id)} — Meta Ads` };
  } catch {
    return { title: "Campanie — Meta Ads" };
  }
}

const lei = (v: number) => `${v.toFixed(2).replace(/\.00$/, "")} lei`;
const nr = (v: number) => v.toLocaleString("ro-RO");
const pct = (v: number) => `${v.toFixed(2)}%`;

function Stat({ label, value, hint, color }: { label: string; value: string; hint?: string; color?: string }) {
  return (
    <div
      style={{
        border: "1px solid var(--border)",
        borderRadius: 8,
        padding: "12px 14px",
        background: "var(--bg-warm)",
      }}
    >
      <div style={{ fontSize: 10, textTransform: "uppercase", letterSpacing: ".05em", color: "var(--text-muted)", fontWeight: 700 }}>
        {label}
      </div>
      <div style={{ fontSize: 20, fontWeight: 700, marginTop: 4, color }}>{value}</div>
      {hint && <div style={{ fontSize: 11, color: "var(--text-muted)", marginTop: 2 }}>{hint}</div>}
    </div>
  );
}

const GRID: React.CSSProperties = {
  display: "grid",
  gridTemplateColumns: "repeat(auto-fill, minmax(160px, 1fr))",
  gap: 10,
};

export default async function CampaignDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const session = await getSession();
  if (!session) redirect("/login");
  if (session.role !== "owner") redirect("/admin");
  const { id } = await params;

  if (!(await metaToken())) redirect("/admin/meta-ads");

  let name = id;
  let costs = null as Awaited<ReturnType<typeof getCampaignCosts>> | null;
  let ads: Awaited<ReturnType<typeof getCampaignCreatives>> = [];
  let apiError: string | null = null;
  try {
    [name, costs, ads] = await Promise.all([
      getCampaignName(id),
      getCampaignCosts(id),
      getCampaignCreatives(id),
    ]);
  } catch (e) {
    apiError = e instanceof Error ? e.message : "Eroare Meta API";
  }

  const cpaColor = !costs?.costPerPurchase
    ? undefined
    : costs.costPerPurchase <= 30
      ? "#1a7f37"
      : costs.costPerPurchase > 50
        ? "#d63638"
        : "#b45309";

  return (
    <>
      <div style={{ marginBottom: 14, fontSize: 13 }}>
        <Link href="/admin/meta-ads">← Toate campaniile</Link>
        {" · "}
        <Link href={`/admin/meta-ads/${id}/audienta`}>Cine vede reclama →</Link>
      </div>

      {apiError && (
        <div className="card" style={{ borderLeft: "4px solid #d63638" }}>
          <strong>Meta API:</strong> {apiError}
        </div>
      )}

      <div className="card">
        <div className="card-title">📣 {name}</div>

        {costs && (
          <>
            <h3 style={{ fontSize: 13, marginTop: 4, marginBottom: 10 }}>Rezultate</h3>
            <div style={GRID}>
              <Stat label="Cheltuit" value={lei(costs.spend)} />
              <Stat
                label="Achiziții"
                value={nr(costs.purchases)}
                hint={costs.purchaseValue > 0 ? `${lei(costs.purchaseValue)} încasați` : undefined}
              />
              <Stat
                label="Cost / achiziție"
                value={costs.purchases ? lei(costs.costPerPurchase) : "—"}
                color={cpaColor}
                hint="sub 30 lei = bine"
              />
              <Stat
                label="Checkout începute"
                value={nr(costs.checkouts)}
                hint={costs.checkouts ? `${lei(costs.costPerCheckout)} bucata` : undefined}
              />
              <Stat
                label="ROAS"
                value={costs.spend > 0 && costs.purchaseValue > 0 ? (costs.purchaseValue / costs.spend).toFixed(2) : "—"}
                hint="lei încasați / leu cheltuit"
              />
            </div>

            <h3 style={{ fontSize: 13, marginTop: 22, marginBottom: 10 }}>Costuri de livrare</h3>
            <div style={GRID}>
              <Stat label="CPM" value={lei(costs.cpm)} hint="cost / 1000 afișări" />
              <Stat label="CPC pe link" value={lei(costs.cpcLink)} hint={`${nr(costs.linkClicks)} clicuri pe link`} />
              <Stat
                label="CPC toate clicurile"
                value={lei(costs.cpcAll)}
                hint={`${nr(costs.clicksAll)} clicuri (și like-uri, expandări)`}
              />
              <Stat
                label="Cost / vizualizare pagină"
                value={costs.landingViews ? lei(costs.costPerLandingView) : "—"}
                hint={`${nr(costs.landingViews)} au ajuns pe site`}
              />
            </div>

            <h3 style={{ fontSize: 13, marginTop: 22, marginBottom: 10 }}>Livrare</h3>
            <div style={GRID}>
              <Stat label="Afișări" value={nr(costs.impressions)} />
              <Stat label="Persoane atinse" value={nr(costs.reach)} />
              <Stat
                label="Frecvență"
                value={costs.frequency.toFixed(2)}
                hint="de câte ori a văzut-o fiecare"
                color={costs.frequency > 3 ? "#d63638" : undefined}
              />
              <Stat label="CTR pe link" value={pct(costs.ctrLink)} />
              <Stat label="CTR total" value={pct(costs.ctrAll)} />
            </div>

            {costs.linkClicks > 0 && costs.landingViews > 0 && (
              <p style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 14 }}>
                Din {nr(costs.linkClicks)} clicuri pe link, {nr(costs.landingViews)} au ajuns efectiv pe pagină (
                {pct((costs.landingViews / costs.linkClicks) * 100)}). Restul se pierd la încărcare, ad-blockere sau
                iPhone-uri care blochează pixelul — sub 40% pierdere e normal.
              </p>
            )}
          </>
        )}
      </div>

      {ads.map((ad) => (
        <div className="card" key={ad.adId}>
          <div className="card-title">
            🖼 {ad.adName} {ad.status !== "ACTIVE" && <span style={{ fontWeight: 400 }}>({ad.status})</span>}
          </div>
          <div style={{ display: "flex", gap: 20, flexWrap: "wrap", alignItems: "flex-start" }}>
            {ad.imageUrl && (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={ad.imageUrl}
                alt=""
                style={{ width: 220, borderRadius: 8, border: "1px solid var(--border)" }}
              />
            )}
            <div style={{ flex: "1 1 340px", minWidth: 280 }}>
              {ad.bodies.length > 0 && (
                <>
                  <h3 style={{ fontSize: 13, marginBottom: 8 }}>
                    Text principal {ad.bodies.length > 1 && `(${ad.bodies.length} variante)`}
                  </h3>
                  {ad.bodies.map((b, i) => (
                    <div
                      key={i}
                      style={{
                        border: "1px solid var(--border)",
                        borderRadius: 8,
                        padding: "10px 12px",
                        marginBottom: 8,
                        fontSize: 13,
                        lineHeight: 1.55,
                        whiteSpace: "pre-wrap",
                        background: "var(--bg-warm)",
                      }}
                    >
                      <div style={{ fontSize: 10, color: "var(--text-muted)", fontWeight: 700, marginBottom: 4 }}>
                        VARIANTA {i + 1}
                      </div>
                      {b}
                    </div>
                  ))}
                </>
              )}

              {ad.titles.length > 0 && (
                <>
                  <h3 style={{ fontSize: 13, margin: "16px 0 6px" }}>Titluri</h3>
                  <ul style={{ margin: 0, paddingLeft: 18, fontSize: 13, lineHeight: 1.8 }}>
                    {ad.titles.map((t, i) => (
                      <li key={i}>{t}</li>
                    ))}
                  </ul>
                </>
              )}

              {ad.descriptions.length > 0 && (
                <>
                  <h3 style={{ fontSize: 13, margin: "16px 0 6px" }}>Descriere</h3>
                  <ul style={{ margin: 0, paddingLeft: 18, fontSize: 13, lineHeight: 1.8 }}>
                    {ad.descriptions.map((d, i) => (
                      <li key={i}>{d}</li>
                    ))}
                  </ul>
                </>
              )}

              <div style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 16 }}>
                {ad.cta && (
                  <div>
                    <strong>Buton:</strong> {ad.cta}
                  </div>
                )}
                {ad.link && (
                  <div style={{ wordBreak: "break-all" }}>
                    <strong>Link:</strong>{" "}
                    <a href={ad.link} target="_blank" rel="noreferrer">
                      {ad.link}
                    </a>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      ))}

      {!apiError && ads.length === 0 && (
        <div className="card">Campania nu are reclame.</div>
      )}
    </>
  );
}
