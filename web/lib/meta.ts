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

export async function setCampaignStatus(campaignId: string, status: "ACTIVE" | "PAUSED"): Promise<void> {
  await graph(campaignId, { status }, { method: "POST" });
}

/** Bugetul zilnic (în bani) pe obiectul care îl poartă: set de reclame sau campanie. */
export async function setDailyBudget(objectId: string, budgetBani: number): Promise<void> {
  await graph(objectId, { daily_budget: String(budgetBani) }, { method: "POST" });
}
