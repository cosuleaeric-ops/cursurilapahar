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
  createFullCampaign,
  placementLabel,
  deviceLabel,
  regionLabel,
} from "@/lib/meta";
import { getLog, logDecision } from "@/lib/meta-log";

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

/**
 * Creare de campanie prin token — SINGURA scriere permisă pe ruta asta, și
 * intenționat inofensivă: campania se creează întotdeauna PE PAUZĂ, deci nu
 * cheltuie nimic până n-o pornește cineva din admin. Body JSON:
 * { name, budget_lei, link, bodies: [..], titles: [..], description?, image_base64 }
 */
export async function POST(req: NextRequest): Promise<NextResponse> {
  if (!(await authorized(req))) {
    return NextResponse.json({ error: "Neautorizat" }, { status: 401 });
  }

  let body: Record<string, unknown>;
  try {
    body = (await req.json()) as Record<string, unknown>;
  } catch {
    return NextResponse.json({ error: "Body JSON invalid" }, { status: 400 });
  }

  const s = (k: string) => String(body[k] ?? "").trim();
  const arr = (k: string) =>
    Array.isArray(body[k]) ? (body[k] as unknown[]).map((x) => String(x).trim()).filter(Boolean) : [];

  const name = s("name");
  const lei = Number(body.budget_lei);
  const link = s("link");
  const bodies = arr("bodies");
  const titles = arr("titles");
  const imageBase64 = s("image_base64");

  if (!name || !Number.isFinite(lei) || lei < 1 || !/^https:\/\//.test(link) || !bodies.length || !titles.length || !imageBase64) {
    return NextResponse.json(
      { error: "Câmpuri lipsă: name, budget_lei, link (https), bodies[], titles[], image_base64" },
      { status: 400 },
    );
  }

  try {
    const { campaignId } = await createFullCampaign({
      name,
      dailyBudgetBani: Math.round(lei * 100),
      link,
      bodies,
      titles,
      description: s("description"),
      imageBase64,
    });
    await logDecision({
      campaignId,
      campaignName: name,
      action: "create",
      detail: `creată prin API · ${lei.toFixed(0)} lei/zi · pe pauză`,
      actor: "api",
    });
    return NextResponse.json({ ok: true, campaignId, status: "PAUSED" });
  } catch (e) {
    return NextResponse.json({ error: e instanceof Error ? e.message : "Eroare Meta API" }, { status: 502 });
  }
}
