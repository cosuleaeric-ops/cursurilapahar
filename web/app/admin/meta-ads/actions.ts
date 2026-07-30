"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { getSession, type Session } from "@/lib/auth";
import { getCampaigns, setCampaignStatus, setDailyBudget, DAILY_CAP_BANI, type MetaCampaign } from "@/lib/meta";
import { logDecision } from "@/lib/meta-log";

async function requireOwner(): Promise<Session> {
  const s = await getSession();
  if (!s) redirect("/login");
  if (s.role !== "owner") redirect("/admin");
  return s;
}

function back(msg?: string): never {
  redirect(msg ? `/admin/meta-ads?err=${encodeURIComponent(msg)}` : "/admin/meta-ads");
}

/** Cifrele campaniei în momentul deciziei — ca jurnalul să spună și „de ce", nu doar „ce". */
const snapshot = (c: MetaCampaign | undefined) => ({
  spend: c?.spend ?? null,
  purchases: c?.purchases ?? null,
  cpa: c && c.purchases > 0 ? Number((c.spend / c.purchases).toFixed(2)) : null,
});

/** Bugetul se editează doar din pagina campaniei, deci erorile se întorc tot acolo. */
function backToCampaign(id: string, msg?: string): never {
  redirect(msg ? `/admin/meta-ads/${id}?err=${encodeURIComponent(msg)}` : `/admin/meta-ads/${id}`);
}

export async function saveBudget(formData: FormData): Promise<void> {
  const session = await requireOwner();
  const objectId = String(formData.get("object_id") ?? "");
  const campaignId = String(formData.get("campaign_id") ?? "");
  const lei = Number(String(formData.get("budget_lei") ?? "").replace(",", "."));
  if (!objectId || !Number.isFinite(lei) || lei < 1) backToCampaign(campaignId, "Buget invalid.");
  const budgetBani = Math.round(lei * 100);

  let campaign: MetaCampaign | undefined;
  let oldBani = 0;
  try {
    const campaigns = await getCampaigns();
    campaign = campaigns.find((c) => c.id === campaignId);
    oldBani = campaign?.dailyBudgetBani ?? 0;
    if (oldBani === budgetBani) backToCampaign(campaignId);

    // Plafonul de 100 lei/zi pe totalul campaniilor active.
    const totalActive = campaigns
      .filter((c) => c.status === "ACTIVE")
      .reduce((s, c) => s + (c.id === campaignId ? budgetBani : c.dailyBudgetBani), 0);
    if (totalActive > DAILY_CAP_BANI) {
      backToCampaign(
        campaignId,
        `Peste plafonul de ${DAILY_CAP_BANI / 100} lei/zi: totalul campaniilor active ar fi ${(totalActive / 100).toFixed(0)} lei.`,
      );
    }
    await setDailyBudget(objectId, budgetBani);
  } catch (e) {
    // redirect() aruncă intern — nu-l transforma în eroare de API.
    if (e && typeof e === "object" && "digest" in e) throw e;
    backToCampaign(campaignId, e instanceof Error ? e.message : "Eroare Meta API");
  }

  await logDecision({
    campaignId,
    campaignName: campaign?.name ?? null,
    action: "budget",
    detail: `${(oldBani / 100).toFixed(0)} → ${lei.toFixed(0)} lei/zi`,
    context: snapshot(campaign),
    actor: session.username,
  });

  revalidatePath("/admin/meta-ads");
  revalidatePath(`/admin/meta-ads/${campaignId}`);
  backToCampaign(campaignId);
}

export async function toggleCampaign(formData: FormData): Promise<void> {
  const session = await requireOwner();
  const id = String(formData.get("campaign_id") ?? "");
  const status = String(formData.get("status") ?? "") === "ACTIVE" ? "ACTIVE" : "PAUSED";

  let campaign: MetaCampaign | undefined;
  try {
    campaign = (await getCampaigns()).find((c) => c.id === id);
    await setCampaignStatus(id, status);
  } catch (e) {
    back(e instanceof Error ? e.message : "Eroare Meta API");
  }

  await logDecision({
    campaignId: id,
    campaignName: campaign?.name ?? null,
    action: status === "ACTIVE" ? "resume" : "pause",
    detail: status === "ACTIVE" ? "pornită" : "pusă pe pauză",
    context: snapshot(campaign),
    actor: session.username,
  });

  revalidatePath("/admin/meta-ads");
  redirect("/admin/meta-ads");
}

