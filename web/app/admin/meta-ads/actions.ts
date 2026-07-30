"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { getSession, type Session } from "@/lib/auth";
import { getCampaigns, setCampaignStatus, type MetaCampaign } from "@/lib/meta";
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

