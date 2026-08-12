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

/** Defalcare generică: plasare, dispozitiv, regiune, oră etc. */
export async function getCampaignBreakdown(
  campaignId: string,
  breakdowns: string,
  label: (r: Record<string, string | undefined>) => string,
): Promise<(CostBreakdown & { key: string; label: string })[]> {
  const res = await graph<{ data: (RawCostInsight & Record<string, string | undefined>)[] }>(
    `${campaignId}/insights`,
    { date_preset: "maximum", breakdowns, fields: COST_FIELDS, limit: "200" },
  );
  return res.data
    .map((r) => ({ key: label(r), label: label(r), ...toCost(r) }))
    .sort((a, b) => b.spend - a.spend);
}

const PLATFORM_RO: Record<string, string> = {
  facebook: "Facebook",
  instagram: "Instagram",
  audience_network: "Audience Network",
  messenger: "Messenger",
  threads: "Threads",
};

const POSITION_RO: Record<string, string> = {
  feed: "feed",
  instagram_stories: "stories",
  facebook_stories: "stories",
  story: "stories",
  instagram_reels: "reels",
  facebook_reels: "reels",
  reels: "reels",
  instream_video: "video in-stream",
  right_hand_column: "coloana dreapta",
  marketplace: "marketplace",
  search: "căutare",
  explore: "explore",
  video_feeds: "feed video",
  profile_feed: "feed profil",
  biz_disco_feed: "descoperire",
  classic: "clasic",
  rewarded_video: "video cu recompensă",
};

export const placementLabel = (r: Record<string, string | undefined>): string => {
  const plat = PLATFORM_RO[r.publisher_platform ?? ""] ?? r.publisher_platform ?? "?";
  const pos = POSITION_RO[r.platform_position ?? ""] ?? r.platform_position ?? "";
  return pos ? `${plat} · ${pos}` : plat;
};

export const deviceLabel = (r: Record<string, string | undefined>): string => r.impression_device ?? "?";
export const regionLabel = (r: Record<string, string | undefined>): string => r.region ?? "?";

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

// ---------------------------------------------------------------- creare campanie

/** POST cu corpul în form-body (nu în query) — pentru payload-uri mari (imagine, targeting). */
async function graphForm<T>(path: string, params: Record<string, string>): Promise<T> {
  const token = await metaToken();
  if (!token) throw new Error("Tokenul Meta Ads nu e setat (admin → Setări).");
  const body = new URLSearchParams({ ...params, access_token: token });
  const res = await fetch(`${API}/${path}`, { method: "POST", body, cache: "no-store" });
  const json = (await res.json()) as { error?: { message?: string; error_user_msg?: string } } & T;
  if (!res.ok || json.error) throw new Error(json.error?.error_user_msg ?? json.error?.message ?? `Meta API ${res.status}`);
  return json;
}

/**
 * Campania-șablon: „4 august - Checkout" — singura configurație validată cu achiziții.
 * Noile campanii îi copiază targetarea (seed-uri + Advantage+), evenimentul de
 * optimizare și identitatea (pagină + Instagram), ca să nu reconstruim manual
 * setările care au funcționat.
 */
const TEMPLATE_CAMPAIGN_ID = "52572343843703";

type TemplateAdset = {
  targeting?: Record<string, unknown>;
  promoted_object?: Record<string, unknown>;
  optimization_goal?: string;
  billing_event?: string;
  bid_strategy?: string;
};

export type NewCampaignInput = {
  name: string;
  dailyBudgetBani: number;
  link: string;
  bodies: string[];
  titles: string[];
  description: string;
  /** PNG/JPG în base64, fără prefixul data: */
  imageBase64: string;
};

export async function createFullCampaign(input: NewCampaignInput): Promise<{ campaignId: string }> {
  // 1. Șablonul: set de reclame + identitatea din reclamă.
  const [adsets, ads] = await Promise.all([
    graph<{ data: TemplateAdset[] }>(`${TEMPLATE_CAMPAIGN_ID}/adsets`, {
      fields: "targeting,promoted_object,optimization_goal,billing_event,bid_strategy",
      limit: "5",
    }),
    graph<{ data: { creative?: { object_story_spec?: { page_id?: string; instagram_user_id?: string; instagram_actor_id?: string } } }[] }>(
      `${TEMPLATE_CAMPAIGN_ID}/ads`,
      { fields: "creative{object_story_spec}", limit: "5" },
    ),
  ]);
  const tpl = adsets.data[0];
  const story = ads.data[0]?.creative?.object_story_spec;
  if (!tpl?.targeting || !tpl.promoted_object || !story?.page_id) {
    throw new Error("Șablonul (campania 4 august) nu mai are targetarea sau pagina — nu pot copia configurația.");
  }

  // 2. Campania (pornește pe pauză — activarea e un pas explicit, din panou).
  const camp = await graphForm<{ id: string }>(`${ACT}/campaigns`, {
    name: input.name,
    objective: "OUTCOME_SALES",
    status: "PAUSED",
    special_ad_categories: "[]",
    buying_type: "AUCTION",
    // Buget la nivel de set, fără partajare între seturi — comportament previzibil,
    // ca la campania șablon (un singur set per campanie oricum).
    is_adset_budget_sharing_enabled: "false",
  });

  // 3. Setul de reclame — configurația șablonului, bugetul nou.
  const adset = await graphForm<{ id: string }>(`${ACT}/adsets`, {
    name: input.name,
    campaign_id: camp.id,
    daily_budget: String(input.dailyBudgetBani),
    billing_event: tpl.billing_event ?? "IMPRESSIONS",
    optimization_goal: tpl.optimization_goal ?? "OFFSITE_CONVERSIONS",
    bid_strategy: tpl.bid_strategy ?? "LOWEST_COST_WITHOUT_CAP",
    promoted_object: JSON.stringify(tpl.promoted_object),
    targeting: JSON.stringify(tpl.targeting),
    status: "ACTIVE",
  });

  // 4. Imaginea.
  const img = await graphForm<{ images: Record<string, { hash: string }> }>(`${ACT}/adimages`, {
    bytes: input.imageBase64,
  });
  const hash = Object.values(img.images)[0]?.hash;
  if (!hash) throw new Error("Meta nu a acceptat imaginea.");

  // 5. Creativa: toate variantele de text, CTA Cumpără bilete, îmbunătățirile AI oprite.
  const identity: Record<string, string> = { page_id: story.page_id };
  if (story.instagram_user_id) identity.instagram_user_id = story.instagram_user_id;
  else if (story.instagram_actor_id) identity.instagram_actor_id = story.instagram_actor_id;

  const creative = await graphForm<{ id: string }>(`${ACT}/adcreatives`, {
    name: input.name,
    object_story_spec: JSON.stringify(identity),
    asset_feed_spec: JSON.stringify({
      images: [{ hash }],
      bodies: input.bodies.filter((t) => t.trim()).map((text) => ({ text })),
      titles: input.titles.filter((t) => t.trim()).map((text) => ({ text })),
      descriptions: input.description.trim() ? [{ text: input.description.trim() }] : [],
      ad_formats: ["SINGLE_IMAGE"],
      call_to_action_types: ["BUY_TICKETS"],
      link_urls: [{ website_url: input.link }],
    }),
    degrees_of_freedom_spec: JSON.stringify({
      creative_features_spec: { standard_enhancements: { enroll_status: "OPT_OUT" } },
    }),
  });

  // 6. Reclama.
  await graphForm<{ id: string }>(`${ACT}/ads`, {
    name: `Reclama ${input.name}`,
    adset_id: adset.id,
    creative: JSON.stringify({ creative_id: creative.id }),
    status: "ACTIVE",
  });

  return { campaignId: camp.id };
}

export async function setCampaignStatus(campaignId: string, status: "ACTIVE" | "PAUSED"): Promise<void> {
  await graph(campaignId, { status }, { method: "POST" });
}

/** Bugetul zilnic (în bani) pe obiectul care îl poartă: set de reclame sau campanie. */
export async function setDailyBudget(objectId: string, budgetBani: number): Promise<void> {
  await graph(objectId, { daily_budget: String(budgetBani) }, { method: "POST" });
}
