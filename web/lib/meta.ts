// Client Meta Marketing API pentru contul de reclame CLP.
// Tokenul (System User) se setează din /admin/setari → „Meta Ads".

import { getSettings } from "@/lib/settings";

const ACT = "act_934263382655696";
const API = "https://graph.facebook.com/v22.0";

// Plafon dur: suma bugetelor zilnice ale campaniilor active nu poate depăși 100 lei.
export const DAILY_CAP_BANI = 100_00;

export async function metaToken(): Promise<string> {
  const s = await getSettings();
  return String(s.meta_ads_token ?? "").trim();
}

async function graph<T>(path: string, params: Record<string, string>, init?: RequestInit): Promise<T> {
  const token = await metaToken();
  if (!token) throw new Error("Tokenul Meta Ads nu e setat (admin → Setări).");
  const qs = new URLSearchParams({ ...params, access_token: token });
  const res = await fetch(`${API}/${path}?${qs}`, { cache: "no-store", ...init });
  const body = (await res.json()) as { error?: { message?: string } } & T;
  if (!res.ok || body.error) throw new Error(body.error?.message ?? `Meta API ${res.status}`);
  return body;
}

type RawAction = { action_type: string; value: string };
type RawInsight = {
  campaign_id: string;
  spend?: string;
  impressions?: string;
  inline_link_clicks?: string;
  actions?: RawAction[];
  action_values?: RawAction[];
};
type RawAdset = { id: string; name: string; daily_budget?: string; effective_status: string };
type RawCampaign = {
  id: string;
  name: string;
  effective_status: string;
  daily_budget?: string;
  adsets?: { data: RawAdset[] };
};

export type MetaCampaign = {
  id: string;
  name: string;
  status: string; // ACTIVE / PAUSED / ...
  // bugetul zilnic efectiv în bani (nivel campanie sau, dacă lipsește, suma seturilor)
  dailyBudgetBani: number;
  // setul de reclame care poartă bugetul (pentru editare) — null dacă bugetul e pe campanie
  budgetAdsetId: string | null;
  spend: number;
  spendToday: number;
  impressions: number;
  linkClicks: number;
  purchases: number;
  purchaseValue: number;
  checkouts: number;
};

const num = (v: string | undefined): number => (v ? Number(v) : 0);

function pickAction(list: RawAction[] | undefined, ...types: string[]): number {
  if (!list) return 0;
  for (const t of types) {
    const hit = list.find((a) => a.action_type === t);
    if (hit) return Number(hit.value);
  }
  return 0;
}

export async function getCampaigns(): Promise<MetaCampaign[]> {
  const [campaigns, insights, today] = await Promise.all([
    graph<{ data: RawCampaign[] }>(`${ACT}/campaigns`, {
      fields: "id,name,effective_status,daily_budget,adsets{id,name,daily_budget,effective_status}",
      limit: "50",
    }),
    graph<{ data: RawInsight[] }>(`${ACT}/insights`, {
      level: "campaign",
      date_preset: "maximum",
      fields: "campaign_id,spend,impressions,inline_link_clicks,actions,action_values",
      limit: "100",
    }),
    graph<{ data: RawInsight[] }>(`${ACT}/insights`, {
      level: "campaign",
      date_preset: "today",
      fields: "campaign_id,spend",
      limit: "100",
    }),
  ]);

  const insByCampaign = new Map(insights.data.map((i) => [i.campaign_id, i]));
  const todayByCampaign = new Map(today.data.map((i) => [i.campaign_id, i]));

  return campaigns.data.map((c) => {
    const ins = insByCampaign.get(c.id);
    const adsets = c.adsets?.data ?? [];
    const budgetAdset = adsets.find((a) => num(a.daily_budget) > 0) ?? null;
    const dailyBudgetBani = num(c.daily_budget) || adsets.reduce((s, a) => s + num(a.daily_budget), 0);
    return {
      id: c.id,
      name: c.name,
      status: c.effective_status,
      dailyBudgetBani,
      budgetAdsetId: num(c.daily_budget) > 0 ? null : (budgetAdset?.id ?? null),
      spend: num(ins?.spend),
      spendToday: num(todayByCampaign.get(c.id)?.spend),
      impressions: num(ins?.impressions),
      linkClicks: num(ins?.inline_link_clicks),
      purchases: pickAction(ins?.actions, "omni_purchase", "purchase", "offsite_conversion.fb_pixel_purchase"),
      purchaseValue: pickAction(ins?.action_values, "omni_purchase", "purchase", "offsite_conversion.fb_pixel_purchase"),
      checkouts: pickAction(
        ins?.actions,
        "omni_initiated_checkout",
        "initiate_checkout",
        "offsite_conversion.fb_pixel_initiate_checkout",
      ),
    };
  });
}

// ---------------------------------------------------------------- detalii campanie

export type AdCreative = {
  adId: string;
  adName: string;
  status: string;
  /** toate variantele de text principal */
  bodies: string[];
  titles: string[];
  descriptions: string[];
  cta: string | null;
  link: string | null;
  imageUrl: string | null;
};

export type CostBreakdown = {
  spend: number;
  impressions: number;
  reach: number;
  frequency: number;
  cpm: number;
  /** cost per clic pe link (metrica utilă) */
  cpcLink: number;
  /** cost per orice clic (include like-uri, expandări — arată artificial de bine) */
  cpcAll: number;
  ctrLink: number;
  ctrAll: number;
  clicksAll: number;
  linkClicks: number;
  landingViews: number;
  costPerLandingView: number;
  checkouts: number;
  costPerCheckout: number;
  purchases: number;
  costPerPurchase: number;
  purchaseValue: number;
};

type RawCreative = {
  body?: string;
  title?: string;
  image_url?: string;
  thumbnail_url?: string;
  call_to_action_type?: string;
  object_story_spec?: {
    link_data?: {
      message?: string;
      name?: string;
      description?: string;
      link?: string;
      picture?: string;
      call_to_action?: { type?: string };
    };
  };
  asset_feed_spec?: {
    bodies?: { text?: string }[];
    titles?: { text?: string }[];
    descriptions?: { text?: string }[];
    link_urls?: { website_url?: string }[];
    call_to_action_types?: string[];
    images?: { url?: string; permalink_url?: string }[];
  };
};

type RawAd = { id: string; name: string; effective_status: string; creative?: RawCreative };

const uniq = (xs: (string | undefined)[]): string[] =>
  [...new Set(xs.filter((x): x is string => Boolean(x && x.trim())))];

export async function getCampaignCreatives(campaignId: string): Promise<AdCreative[]> {
  const res = await graph<{ data: RawAd[] }>(`${campaignId}/ads`, {
    fields:
      "id,name,effective_status,creative{body,title,image_url,thumbnail_url,call_to_action_type,object_story_spec,asset_feed_spec}",
    limit: "25",
  });
  return res.data.map((ad) => {
    const cr = ad.creative ?? {};
    const ld = cr.object_story_spec?.link_data;
    const afs = cr.asset_feed_spec;
    return {
      adId: ad.id,
      adName: ad.name,
      status: ad.effective_status,
      bodies: uniq([...(afs?.bodies?.map((b) => b.text) ?? []), ld?.message, cr.body]),
      titles: uniq([...(afs?.titles?.map((t) => t.text) ?? []), ld?.name, cr.title]),
      descriptions: uniq([...(afs?.descriptions?.map((d) => d.text) ?? []), ld?.description]),
      cta: afs?.call_to_action_types?.[0] ?? ld?.call_to_action?.type ?? cr.call_to_action_type ?? null,
      link: afs?.link_urls?.[0]?.website_url ?? ld?.link ?? null,
      imageUrl:
        cr.image_url ?? afs?.images?.[0]?.url ?? afs?.images?.[0]?.permalink_url ?? cr.thumbnail_url ?? ld?.picture ?? null,
    };
  });
}

type RawCostInsight = RawInsight & {
  reach?: string;
  frequency?: string;
  cpm?: string;
  cpc?: string;
  ctr?: string;
  clicks?: string;
  inline_link_click_ctr?: string;
  cost_per_inline_link_click?: string;
};

const COST_FIELDS =
  "spend,impressions,reach,frequency,cpm,cpc,ctr,clicks,inline_link_clicks,inline_link_click_ctr,cost_per_inline_link_click,actions,action_values";

function toCost(i: RawCostInsight | undefined): CostBreakdown {
  const spend = num(i?.spend);
  const landingViews = pickAction(i?.actions, "landing_page_view");
  const checkouts = pickAction(
    i?.actions,
    "omni_initiated_checkout",
    "initiate_checkout",
    "offsite_conversion.fb_pixel_initiate_checkout",
  );
  const purchases = pickAction(i?.actions, "omni_purchase", "purchase", "offsite_conversion.fb_pixel_purchase");
  return {
    spend,
    impressions: num(i?.impressions),
    reach: num(i?.reach),
    frequency: num(i?.frequency),
    cpm: num(i?.cpm),
    cpcLink: num(i?.cost_per_inline_link_click),
    cpcAll: num(i?.cpc),
    ctrLink: num(i?.inline_link_click_ctr),
    ctrAll: num(i?.ctr),
    clicksAll: num(i?.clicks),
    linkClicks: num(i?.inline_link_clicks),
    landingViews,
    costPerLandingView: landingViews ? spend / landingViews : 0,
    checkouts,
    costPerCheckout: checkouts ? spend / checkouts : 0,
    purchases,
    costPerPurchase: purchases ? spend / purchases : 0,
    purchaseValue: pickAction(i?.action_values, "omni_purchase", "purchase", "offsite_conversion.fb_pixel_purchase"),
  };
}

export async function getCampaignCosts(campaignId: string): Promise<CostBreakdown> {
  const res = await graph<{ data: RawCostInsight[] }>(`${campaignId}/insights`, {
    date_preset: "maximum",
    fields: COST_FIELDS,
  });
  return toCost(res.data[0]);
}

export async function getCampaignName(campaignId: string): Promise<string> {
  const res = await graph<{ name?: string }>(campaignId, { fields: "name" });
  return res.name ?? campaignId;
}

export type CampaignBudget = {
  name: string;
  status: string;
  dailyBudgetBani: number;
  /** obiectul care poartă bugetul: setul de reclame, sau campania dacă bugetul e la nivel de campanie */
  budgetObjectId: string;
};

export async function getCampaignBudget(campaignId: string): Promise<CampaignBudget> {
  const c = await graph<RawCampaign>(campaignId, {
    fields: "id,name,effective_status,daily_budget,adsets{id,name,daily_budget,effective_status}",
  });
  const adsets = c.adsets?.data ?? [];
  const budgetAdset = adsets.find((a) => num(a.daily_budget) > 0) ?? null;
  return {
    name: c.name,
    status: c.effective_status,
    dailyBudgetBani: num(c.daily_budget) || adsets.reduce((s, a) => s + num(a.daily_budget), 0),
    budgetObjectId: num(c.daily_budget) > 0 ? c.id : (budgetAdset?.id ?? c.id),
  };
}

// ---------------------------------------------------------------- audiență

export type DemoRow = { key: string; age: string; gender: string } & CostBreakdown;

const GENDER_RO: Record<string, string> = { female: "Femei", male: "Bărbați", unknown: "Necunoscut" };

export async function getCampaignDemographics(campaignId: string): Promise<DemoRow[]> {
  const res = await graph<{ data: (RawCostInsight & { age?: string; gender?: string })[] }>(
    `${campaignId}/insights`,
    { date_preset: "maximum", breakdowns: "age,gender", fields: COST_FIELDS, limit: "100" },
  );
  return res.data
    .map((r) => ({
      key: `${r.age}|${r.gender}`,
      age: r.age ?? "?",
      gender: GENDER_RO[r.gender ?? "unknown"] ?? (r.gender ?? "?"),
      ...toCost(r),
    }))
    .sort((a, b) => b.spend - a.spend);
}

export async function setCampaignStatus(campaignId: string, status: "ACTIVE" | "PAUSED"): Promise<void> {
  await graph(campaignId, { status }, { method: "POST" });
}

/** Bugetul zilnic (în bani) pe obiectul care îl poartă: set de reclame sau campanie. */
export async function setDailyBudget(objectId: string, budgetBani: number): Promise<void> {
  await graph(objectId, { daily_budget: String(budgetBani) }, { method: "POST" });
}
