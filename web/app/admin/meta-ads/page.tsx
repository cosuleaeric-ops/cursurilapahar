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

function Row({ c }: { c: MetaCampaign }) {
  const active = c.status === "ACTIVE";
  const costPerPurchase = c.purchases > 0 ? c.spend / c.purchases : null;
  const costColor = costPerPurchase == null ? undefined : costPerPurchase <= 30 ? "#1a7f37" : costPerPurchase > 50 ? "#d63638" : "#b45309";
  const budgetObject = c.budgetAdsetId ?? c.id;
  return (
    <tr style={active ? undefined : { opacity: 0.55 }}>
      <td>
        <strong>{c.name}</strong>
        <div style={{ fontSize: 12, color: "var(--muted, #777)" }}>
          {active ? "🟢 Activă" : c.status === "PAUSED" ? "⏸ Pauză" : c.status}
        </div>
      </td>
      <td>
        <form action={saveBudget} style={{ display: "flex", gap: 4, alignItems: "center" }}>
          <input type="hidden" name="object_id" value={budgetObject} />
          <input type="hidden" name="campaign_id" value={c.id} />
          <input
            type="number"
            name="budget_lei"
            defaultValue={c.dailyBudgetBani / 100}
            min={1}
            step={1}
            style={{ width: 70 }}
          />
          <button type="submit" className="btn btn-sm">
            OK
          </button>
        </form>
      </td>
      <td>{lei2(c.spendToday)}</td>
      <td>{lei2(c.spend)}</td>
      <td>
        {c.purchases}
        {c.purchaseValue > 0 && (
          <span style={{ fontSize: 12, color: "var(--muted, #777)" }}> · {lei2(c.purchaseValue)}</span>
        )}
      </td>
      <td style={{ color: costColor, fontWeight: costPerPurchase != null ? 600 : undefined }}>
        {costPerPurchase != null ? lei2(costPerPurchase) : "—"}
      </td>
      <td>{c.checkouts}</td>
      <td>{c.linkClicks}</td>
      <td>
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
          Tokenul Meta (System User) nu e setat. Adaugă-l în <Link href="/admin/setari">Setări → Meta Ads</Link> și
          revino aici.
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

  // Doar campaniile cu activitate sau active — contul are și campanii vechi goale.
  const visible = campaigns.filter((c) => c.status === "ACTIVE" || c.spend > 0);
  const activeBudget = campaigns.filter((c) => c.status === "ACTIVE").reduce((s, c) => s + c.dailyBudgetBani, 0);
  const spendToday = campaigns.reduce((s, c) => s + c.spendToday, 0);

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
        <div className="card-title">📣 Meta Ads</div>
        <p style={{ marginBottom: 12 }}>
          Buget zilnic activ: <strong>{lei(activeBudget)}</strong> din plafonul de {lei(DAILY_CAP_BANI)} · Cheltuit azi:{" "}
          <strong>{lei2(spendToday)}</strong>
        </p>
        <div style={{ overflowX: "auto" }}>
          <table className="admin-table" style={{ width: "100%" }}>
            <thead>
              <tr>
                <th>Campanie</th>
                <th>Buget/zi</th>
                <th>Azi</th>
                <th>Total</th>
                <th>Achiziții</th>
                <th>Cost/achiziție</th>
                <th>Checkout</th>
                <th>Clicuri link</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {visible.map((c) => (
                <Row key={c.id} c={c} />
              ))}
              {visible.length === 0 && !apiError && (
                <tr>
                  <td colSpan={9}>Nicio campanie activă.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <p className="form-desc" style={{ marginTop: 10 }}>
          Cost/achiziție: <span style={{ color: "#1a7f37" }}>sub 30 lei = bine</span> ·{" "}
          <span style={{ color: "#b45309" }}>30-50 lei = atenție</span> ·{" "}
          <span style={{ color: "#d63638" }}>peste 50 lei = problemă</span>. Datele vin live din Meta (perioadă:
          maximum). Bugetul se editează pe obiectul care îl poartă (set de reclame sau campanie).
        </p>
      </div>
    </>
  );
}
