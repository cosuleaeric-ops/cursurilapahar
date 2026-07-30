import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import { getCampaigns, metaToken, DAILY_CAP_BANI, type MetaCampaign } from "@/lib/meta";
import { toggleCampaign, saveBudget } from "./actions";

export const dynamic = "force-dynamic";
export const metadata: Metadata = { title: "Meta Ads — Admin" };

const lei = (bani: number) => `${(bani / 100).toFixed(bani % 100 ? 2 : 0)} lei`;
const lei2 = (v: number) => `${v.toFixed(2).replace(/\.00$/, "")} lei`;

const NUM: React.CSSProperties = { textAlign: "right", whiteSpace: "nowrap" };

function Row({ c }: { c: MetaCampaign }) {
  const active = c.status === "ACTIVE";
  const cpa = c.purchases > 0 ? c.spend / c.purchases : null;
  const cpaColor = cpa == null ? undefined : cpa <= 30 ? "#1a7f37" : cpa > 50 ? "#d63638" : "#b45309";
  const budgetObject = c.budgetAdsetId ?? c.id;
  return (
    <tr>
      <td style={{ minWidth: 220 }}>
        <div style={{ fontWeight: 600, lineHeight: 1.3 }}>{c.name}</div>
        <div style={{ fontSize: 11, color: "var(--text-muted)", marginTop: 2 }}>
          {active ? "🟢 Activă" : "⏸ Pauză"}
        </div>
      </td>
      <td style={{ whiteSpace: "nowrap" }}>
        <form action={saveBudget} style={{ display: "flex", gap: 4, alignItems: "center" }}>
          <input type="hidden" name="object_id" value={budgetObject} />
          <input type="hidden" name="campaign_id" value={c.id} />
          <input
            type="number"
            name="budget_lei"
            defaultValue={c.dailyBudgetBani / 100}
            min={1}
            step={1}
            style={{ width: 58, padding: "4px 6px", fontSize: 13 }}
          />
          <button type="submit" className="btn btn-sm">
            OK
          </button>
        </form>
      </td>
      <td style={NUM}>{c.spendToday > 0 ? lei2(c.spendToday) : "—"}</td>
      <td style={NUM}>{lei2(c.spend)}</td>
      <td style={NUM}>
        {c.purchases || "—"}
        {c.purchaseValue > 0 && (
          <div style={{ fontSize: 11, color: "var(--text-muted)" }}>{lei2(c.purchaseValue)}</div>
        )}
      </td>
      <td style={{ ...NUM, color: cpaColor, fontWeight: cpa != null ? 700 : undefined }}>
        {cpa != null ? lei2(cpa) : "—"}
      </td>
      <td style={NUM}>{c.checkouts || "—"}</td>
      <td style={NUM}>{c.linkClicks || "—"}</td>
      <td style={{ whiteSpace: "nowrap" }}>
        <form action={toggleCampaign}>
          <input type="hidden" name="campaign_id" value={c.id} />
          <input type="hidden" name="status" value={active ? "PAUSED" : "ACTIVE"} />
          <button type="submit" className={active ? "btn btn-sm" : "btn btn-sm btn-primary"}>
            {active ? "Pauză" : "Pornește"}
          </button>
        </form>
      </td>
    </tr>
  );
}

function Head() {
  return (
    <thead>
      <tr>
        <th>Campanie</th>
        <th>Buget/zi</th>
        <th style={{ textAlign: "right" }}>Azi</th>
        <th style={{ textAlign: "right" }}>Total</th>
        <th style={{ textAlign: "right" }}>Achiziții</th>
        <th style={{ textAlign: "right" }}>Cost/achiz.</th>
        <th style={{ textAlign: "right" }}>Checkout</th>
        <th style={{ textAlign: "right" }}>Clicuri</th>
        <th></th>
      </tr>
    </thead>
  );
}

export default async function MetaAdsPage({ searchParams }: { searchParams: Promise<{ err?: string }> }) {
  const session = await getSession();
  if (!session) redirect("/login");
  if (session.role !== "owner") redirect("/admin");
  const { err } = await searchParams;

  if (!(await metaToken())) {
    return (
      <div className="card">
        <div className="card-title">📣 Meta Ads</div>
        <p>
          Tokenul Meta nu e setat. Adaugă-l în <Link href="/admin/setari">Setări → Meta Ads</Link>.
        </p>
      </div>
    );
  }

  let campaigns: MetaCampaign[] = [];
  let apiError: string | null = null;
  try {
    campaigns = await getCampaigns();
  } catch (e) {
    apiError = e instanceof Error ? e.message : "Eroare Meta API";
  }

  const live = campaigns.filter((c) => c.status === "ACTIVE");
  const oprite = campaigns.filter((c) => c.status !== "ACTIVE" && c.spend > 0);
  const activeBudget = live.reduce((s, c) => s + c.dailyBudgetBani, 0);
  const spendToday = campaigns.reduce((s, c) => s + c.spendToday, 0);
  const purchases = live.reduce((s, c) => s + c.purchases, 0);
  const spendLive = live.reduce((s, c) => s + c.spend, 0);

  return (
    <>
      {err && (
        <div className="card" style={{ borderLeft: "4px solid #d63638" }}>
          <strong>Eroare:</strong> {err}
        </div>
      )}
      {apiError && (
        <div className="card" style={{ borderLeft: "4px solid #d63638" }}>
          <strong>Meta API:</strong> {apiError}
        </div>
      )}

      <div className="card">
        <div className="card-title">📣 Campanii active</div>
        <p style={{ marginBottom: 14, fontSize: 13 }}>
          Buget zilnic: <strong>{lei(activeBudget)}</strong> din plafonul de {lei(DAILY_CAP_BANI)} · Cheltuit azi:{" "}
          <strong>{lei2(spendToday)}</strong>
          {purchases > 0 && (
            <>
              {" "}
              · Cost/achiziție pe activ: <strong>{lei2(spendLive / purchases)}</strong>
            </>
          )}
        </p>
        <div style={{ overflowX: "auto" }}>
          <table className="wp-table">
            <Head />
            <tbody>
              {live.map((c) => (
                <Row key={c.id} c={c} />
              ))}
              {live.length === 0 && !apiError && (
                <tr>
                  <td colSpan={9}>Nicio campanie activă.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <p style={{ marginTop: 12, fontSize: 12, color: "var(--text-muted)" }}>
          Cost/achiziție: <span style={{ color: "#1a7f37", fontWeight: 600 }}>sub 30 lei = bine</span> ·{" "}
          <span style={{ color: "#b45309", fontWeight: 600 }}>30-50 lei = atenție</span> ·{" "}
          <span style={{ color: "#d63638", fontWeight: 600 }}>peste 50 lei = problemă</span>
        </p>
      </div>

      {oprite.length > 0 && (
        <div className="card">
          <details>
            <summary style={{ cursor: "pointer", fontWeight: 600 }}>
              Campanii oprite ({oprite.length})
            </summary>
            <div style={{ overflowX: "auto", marginTop: 14 }}>
              <table className="wp-table">
                <Head />
                <tbody>
                  {oprite.map((c) => (
                    <Row key={c.id} c={c} />
                  ))}
                </tbody>
              </table>
            </div>
          </details>
        </div>
      )}
    </>
  );
}
