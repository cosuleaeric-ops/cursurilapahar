import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import {
  getCampaignBreakdown,
  getCampaignDemographics,
  getCampaignName,
  metaToken,
  placementLabel,
  deviceLabel,
  regionLabel,
  type CostBreakdown,
} from "@/lib/meta";

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }): Promise<Metadata> {
  const { id } = await params;
  try {
    return { title: `Audiență: ${await getCampaignName(id)} - Meta Ads` };
  } catch {
    return { title: "Audiență - Meta Ads" };
  }
}

const lei = (v: number) => `${v.toFixed(2).replace(/\.00$/, "")} lei`;
const nr = (v: number) => v.toLocaleString("ro-RO");
const NUM: React.CSSProperties = { textAlign: "right", whiteSpace: "nowrap" };

type Seg = CostBreakdown & { key: string; label: string };

function Bar({ v, max, color }: { v: number; max: number; color: string }) {
  if (max <= 0) return null;
  return (
    <div style={{ height: 4, background: "var(--border)", borderRadius: 2, marginTop: 4 }}>
      <div style={{ width: `${Math.max(2, (v / max) * 100)}%`, height: "100%", background: color, borderRadius: 2 }} />
    </div>
  );
}

function Table({ rows, title, note }: { rows: Seg[]; title: string; note?: string }) {
  const maxSpend = Math.max(...rows.map((r) => r.spend), 0);
  const totalSpend = rows.reduce((s, r) => s + r.spend, 0);
  const totalPurch = rows.reduce((s, r) => s + r.purchases, 0);
  return (
    <div className="card">
      <div className="card-title">{title}</div>
      {note && <p style={{ fontSize: 12, color: "var(--text-muted)", marginBottom: 12 }}>{note}</p>}
      <div style={{ overflowX: "auto" }}>
        <table className="wp-table">
          <thead>
            <tr>
              <th>Segment</th>
              <th style={{ textAlign: "right" }}>Cheltuit</th>
              <th style={{ textAlign: "right" }}>Afișări</th>
              <th style={{ textAlign: "right" }}>CPM</th>
              <th style={{ textAlign: "right" }}>Clicuri</th>
              <th style={{ textAlign: "right" }}>CPC link</th>
              <th style={{ textAlign: "right" }}>Checkout</th>
              <th style={{ textAlign: "right" }}>Achiziții</th>
              <th style={{ textAlign: "right" }}>Cost/achiz.</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => {
              const cpa = r.purchases > 0 ? r.spend / r.purchases : null;
              const cpaColor = cpa == null ? undefined : cpa <= 30 ? "#1a7f37" : cpa > 50 ? "#d63638" : "#b45309";
              return (
                <tr key={r.key}>
                  <td style={{ minWidth: 150 }}>
                    <div style={{ fontWeight: r.purchases > 0 ? 700 : 500 }}>{r.label}</div>
                    <Bar v={r.spend} max={maxSpend} color={r.purchases > 0 ? "#1a7f37" : "var(--text-muted)"} />
                  </td>
                  <td style={NUM}>{lei(r.spend)}</td>
                  <td style={NUM}>{nr(r.impressions)}</td>
                  <td style={NUM}>{lei(r.cpm)}</td>
                  <td style={NUM}>{nr(r.linkClicks) || "-"}</td>
                  <td style={NUM}>{r.linkClicks ? lei(r.cpcLink) : "-"}</td>
                  <td style={NUM}>{r.checkouts || "-"}</td>
                  <td style={{ ...NUM, fontWeight: r.purchases ? 700 : undefined }}>{r.purchases || "-"}</td>
                  <td style={{ ...NUM, color: cpaColor, fontWeight: cpa != null ? 700 : undefined }}>
                    {cpa != null ? lei(cpa) : "-"}
                  </td>
                </tr>
              );
            })}
            {rows.length === 0 && (
              <tr>
                <td colSpan={9}>Nu există încă date pe segmente.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
      {rows.length > 0 && (
        <p style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 12 }}>
          Total: {lei(totalSpend)} cheltuiți, {totalPurch} achiziții. Segmentele îngroșate sunt cele care au cumpărat.
        </p>
      )}
    </div>
  );
}

export default async function AudientaPage({ params }: { params: Promise<{ id: string }> }) {
  const session = await getSession();
  if (!session) redirect("/login");
  if (session.role !== "owner") redirect("/admin");
  const { id } = await params;
  if (!(await metaToken())) redirect("/admin/meta-ads");

  let name = id;
  let demo: Awaited<ReturnType<typeof getCampaignDemographics>> = [];
  let placement: Seg[] = [];
  let device: Seg[] = [];
  let region: Seg[] = [];
  let apiError: string | null = null;
  try {
    [name, demo, placement, device, region] = await Promise.all([
      getCampaignName(id),
      getCampaignDemographics(id),
      getCampaignBreakdown(id, "publisher_platform,platform_position", placementLabel),
      getCampaignBreakdown(id, "impression_device", deviceLabel),
      getCampaignBreakdown(id, "region", regionLabel),
    ]);
  } catch (e) {
    apiError = e instanceof Error ? e.message : "Eroare Meta API";
  }

  // Agregare pe gen și pe vârstă separat, ca să se vadă tiparul fără zgomot.
  const agg = (keyOf: (r: (typeof demo)[number]) => string): Seg[] => {
    const map = new Map<string, Seg>();
    for (const r of demo) {
      const k = keyOf(r);
      const cur = map.get(k);
      if (!cur) {
        map.set(k, { ...r, key: k, label: k });
        continue;
      }
      cur.spend += r.spend;
      cur.impressions += r.impressions;
      cur.linkClicks += r.linkClicks;
      cur.checkouts += r.checkouts;
      cur.purchases += r.purchases;
      cur.purchaseValue += r.purchaseValue;
      cur.cpm = cur.impressions ? (cur.spend / cur.impressions) * 1000 : 0;
      cur.cpcLink = cur.linkClicks ? cur.spend / cur.linkClicks : 0;
    }
    return [...map.values()].sort((a, b) => b.spend - a.spend);
  };

  const detailed: Seg[] = demo.map((r) => ({ ...r, label: `${r.gender} ${r.age}` }));

  return (
    <>
      <div style={{ marginBottom: 14, fontSize: 13 }}>
        <Link href="/admin/meta-ads">← Toate campaniile</Link>
        {" · "}
        <Link href={`/admin/meta-ads/${id}`}>← Detalii campanie</Link>
      </div>

      {apiError && (
        <div className="card" style={{ borderLeft: "4px solid #d63638" }}>
          <strong>Meta API:</strong> {apiError}
        </div>
      )}

      <div className="card">
        <div className="card-title">👥 Cine vede reclama - {name}</div>
        <p style={{ fontSize: 13, margin: 0 }}>
          Meta decide singură cui livrează, în limitele setate de tine. Tabelele arată unde s-au dus banii și cine a
          cumpărat efectiv. Segmentele sub ~100 de persoane sunt ascunse de Meta din motive de confidențialitate.
        </p>
      </div>

      <Table
        rows={placement}
        title="După plasare"
        note="Unde a apărut reclama. Aici se vede risipa: dacă Audience Network mănâncă buget fără achiziții, merită exclus."
      />
      <Table rows={agg((r) => r.gender)} title="După gen" />
      <Table rows={agg((r) => r.age)} title="După vârstă" />
      <Table rows={device} title="După dispozitiv" note="iPhone-urile blochează des pixelul, deci conversiile reale de pe iOS pot fi mai multe decât se raportează." />
      <Table rows={region} title="După regiune" />
      <Table
        rows={detailed}
        title="Detaliat (gen × vârstă)"
        note="Ordonat după cheltuială. La numere mici de conversii, un segment câștigător poate fi pură întâmplare - nu restrânge audiența pe baza a 2-3 achiziții."
      />
    </>
  );
}
