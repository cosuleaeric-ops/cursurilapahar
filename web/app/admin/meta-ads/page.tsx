import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import { getCampaigns, metaToken, DAILY_CAP_BANI, type MetaCampaign } from "@/lib/meta";
import { getLog, type LogEntry } from "@/lib/meta-log";
import { toggleCampaign } from "./actions";
import SubmitButton from "./SubmitButton";

export const dynamic = "force-dynamic";
export const metadata: Metadata = { title: "Meta Ads - Admin" };

const lei = (bani: number) => `${(bani / 100).toFixed(bani % 100 ? 2 : 0)} lei`;
const lei2 = (v: number) => `${v.toFixed(2).replace(/\.00$/, "")} lei`;

const NUM: React.CSSProperties = { textAlign: "right", whiteSpace: "nowrap" };

function Row({ c }: { c: MetaCampaign }) {
  const active = c.status === "ACTIVE";
  const cpa = c.purchases > 0 ? c.spend / c.purchases : null;
  const cpaColor = cpa == null ? undefined : cpa <= 30 ? "#1a7f37" : cpa > 50 ? "#d63638" : "#b45309";
  return (
    <tr>
      <td style={{ minWidth: 220 }}>
        <Link href={`/admin/meta-ads/${c.id}`} style={{ fontWeight: 600, lineHeight: 1.3 }}>
          {c.name}
        </Link>
        <div style={{ fontSize: 11, color: "var(--text-muted)", marginTop: 2 }}>
          {active ? "🟢 Activă" : "⏸ Pauză"} ·{" "}
          <Link href={`/admin/meta-ads/${c.id}`}>detalii</Link>
        </div>
      </td>
      <td style={NUM}>{lei(c.dailyBudgetBani)}</td>
      <td style={NUM}>{c.spendToday > 0 ? lei2(c.spendToday) : "-"}</td>
      <td style={NUM}>{lei2(c.spend)}</td>
      <td style={NUM}>
        {c.purchases || "-"}
        {c.purchaseValue > 0 && (
          <div style={{ fontSize: 11, color: "var(--text-muted)" }}>{lei2(c.purchaseValue)}</div>
        )}
      </td>
      <td style={{ ...NUM, color: cpaColor, fontWeight: cpa != null ? 700 : undefined }}>
        {cpa != null ? lei2(cpa) : "-"}
      </td>
      <td style={NUM}>{c.checkouts || "-"}</td>
      <td style={NUM}>{c.linkClicks || "-"}</td>
      <td style={{ whiteSpace: "nowrap" }}>
        <form action={toggleCampaign}>
          <input type="hidden" name="campaign_id" value={c.id} />
          <input type="hidden" name="status" value={active ? "PAUSED" : "ACTIVE"} />
          <SubmitButton
            className={active ? "btn btn-sm" : "btn btn-sm btn-primary"}
            label={active ? "Pauză" : "Pornește"}
            pendingLabel={active ? "Se oprește…" : "Pornește…"}
          />
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

const ACTION_LABEL: Record<string, { icon: string; text: string; color: string }> = {
  pause: { icon: "⏸", text: "Pauză", color: "#b45309" },
  resume: { icon: "▶", text: "Pornire", color: "#1a7f37" },
  budget: { icon: "💰", text: "Buget", color: "#2563eb" },
  create: { icon: "✨", text: "Creată", color: "#7a2733" },
};

const stamp = new Intl.DateTimeFormat("ro-RO", {
  timeZone: "Europe/Bucharest",
  day: "numeric",
  month: "short",
  hour: "2-digit",
  minute: "2-digit",
});

function Journal({ entries }: { entries: LogEntry[] }) {
  return (
    <div className="card">
      <div className="card-title">📋 Jurnal de decizii</div>
      {entries.length === 0 ? (
        <p style={{ fontSize: 13, color: "var(--text-muted)", margin: 0 }}>
          Nicio modificare încă. Aici apare fiecare pauză, pornire sau schimbare de buget făcută din panoul ăsta,
          cu cifrele campaniei din acel moment.
        </p>
      ) : (
        <div style={{ overflowX: "auto" }}>
          <table className="wp-table">
            <thead>
              <tr>
                <th>Când</th>
                <th>Campanie</th>
                <th>Acțiune</th>
                <th>Cifrele la acel moment</th>
                <th>De cine</th>
              </tr>
            </thead>
            <tbody>
              {entries.map((e) => {
                const a = ACTION_LABEL[e.action] ?? { icon: "•", text: e.action, color: undefined as string | undefined };
                const ctx = e.context ?? {};
                return (
                  <tr key={e.id}>
                    <td style={{ whiteSpace: "nowrap", fontSize: 12 }}>{stamp.format(new Date(e.created_at))}</td>
                    <td style={{ minWidth: 180 }}>
                      <Link href={`/admin/meta-ads/${e.campaign_id}`}>{e.campaign_name ?? e.campaign_id}</Link>
                    </td>
                    <td style={{ whiteSpace: "nowrap", color: a.color, fontWeight: 600 }}>
                      {a.icon} {e.detail ?? a.text}
                    </td>
                    <td style={{ fontSize: 12, color: "var(--text-muted)" }}>
                      {ctx.spend != null ? (
                        <>
                          {lei2(ctx.spend)} cheltuiți · {ctx.purchases ?? 0} achiziții
                          {ctx.cpa != null && ` · ${lei2(ctx.cpa)}/achiziție`}
                        </>
                      ) : (
                        "-"
                      )}
                    </td>
                    <td style={{ fontSize: 12 }}>{e.actor === "auto" ? "🤖 automat" : (e.actor ?? "-")}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
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
  const journal = await getLog();

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
        <div className="card-title" style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
          <span>📣 Campanii active</span>
          <Link href="/admin/meta-ads/noua" className="btn btn-primary btn-sm">
            + Campanie nouă
          </Link>
        </div>
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

      <Journal entries={journal} />

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
