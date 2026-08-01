import { NextRequest, NextResponse } from "next/server";
import { timingSafeEqual } from "node:crypto";
import { getSettings } from "@/lib/settings";
import { getSession } from "@/lib/auth";
import {
  getCampaigns,
  getCampaignBudget,
  getCampaignCosts,
  getCampaignCreatives,
  getCampaignDemographics,
  getCampaignBreakdown,
  placementLabel,
  deviceLabel,
  regionLabel,
} from "@/lib/meta";
import { getLog } from "@/lib/meta-log";

// Citire read-only a stării Meta Ads, ca JSON — același conținut ca paginile din
// /admin/meta-ads, dar consumabil cu curl. Autentificare: sesiune de owner SAU
// ?token=<sync_token> (același token ca sync-export).
//
//   /api/meta                      → toate campaniile + jurnalul
//   /api/meta?campaign=<id>        → costuri, buget, texte/vizual
//   /api/meta?campaign=<id>&audience=1  → + defalcări (plasare, gen, vârstă, device, regiune)

export const dynamic = "force-dynamic";

function safeEqual(a: string, b: string): boolean {
  const ba = Buffer.from(a);
  const bb = Buffer.from(b);
  return ba.length === bb.length && timingSafeEqual(ba, bb);
}

async function authorized(req: NextRequest): Promise<boolean> {
  const session = await getSession();
  if (session?.role === "owner") return true;
  const token = req.nextUrl.searchParams.get("token") ?? "";
  if (!token) return false;
  const expected = String((await getSettings()).sync_token ?? "");
  return expected.length > 0 && safeEqual(token, expected);
}

export async function GET(req: NextRequest): Promise<NextResponse> {
  if (!(await authorized(req))) {
    return NextResponse.json({ error: "Neautorizat" }, { status: 401 });
  }

  const id = req.nextUrl.searchParams.get("campaign");
  const wantAudience = req.nextUrl.searchParams.get("audience") === "1";

  try {
    if (!id) {
      const [campaigns, log] = await Promise.all([getCampaigns(), getLog(20)]);
      const active = campaigns.filter((c) => c.status === "ACTIVE");
      return NextResponse.json({
        rezumat: {
          campanii_active: active.length,
          buget_zilnic_lei: active.reduce((s, c) => s + c.dailyBudgetBani, 0) / 100,
          cheltuit_azi_lei: campaigns.reduce((s, c) => s + c.spendToday, 0),
        },
        campanii: campaigns
          .filter((c) => c.status === "ACTIVE" || c.spend > 0)
          .map((c) => ({
            id: c.id,
            nume: c.name,
            status: c.status,
            buget_zilnic_lei: c.dailyBudgetBani / 100,
            cheltuit_azi: c.spendToday,
            cheltuit_total: c.spend,
            achizitii: c.purchases,
            valoare_achizitii: c.purchaseValue,
            cost_per_achizitie: c.purchases ? +(c.spend / c.purchases).toFixed(2) : null,
            checkout: c.checkouts,
            clicuri_link: c.linkClicks,
          })),
        jurnal: log,
      });
    }

    const [budget, costs, ads] = await Promise.all([
      getCampaignBudget(id),
      getCampaignCosts(id),
      getCampaignCreatives(id),
    ]);

    const payload: Record<string, unknown> = {
      id,
      nume: budget.name,
      status: budget.status,
      buget_zilnic_lei: budget.dailyBudgetBani / 100,
      costuri: costs,
      reclame: ads,
    };

    if (wantAudience) {
      const [demo, placement, device, region] = await Promise.all([
        getCampaignDemographics(id),
        getCampaignBreakdown(id, "publisher_platform,platform_position", placementLabel),
        getCampaignBreakdown(id, "impression_device", deviceLabel),
        getCampaignBreakdown(id, "region", regionLabel),
      ]);
      payload.audienta = { demografic: demo, plasare: placement, dispozitiv: device, regiune: region };
    }

    return NextResponse.json(payload);
  } catch (e) {
    return NextResponse.json({ error: e instanceof Error ? e.message : "Eroare Meta API" }, { status: 502 });
  }
}
