"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { getSession, type Session } from "@/lib/auth";
import { getCampaigns, setCampaignStatus, setDailyBudget, DAILY_CAP_BANI } from "@/lib/meta";

async function requireOwner(): Promise<Session> {
  const s = await getSession();
  if (!s) redirect("/login");
  if (s.role !== "owner") redirect("/admin");
  return s;
}

function back(msg?: string): never {
  redirect(msg ? `/admin/meta-ads?err=${encodeURIComponent(msg)}` : "/admin/meta-ads");
}

export async function toggleCampaign(formData: FormData): Promise<void> {
  await requireOwner();
  const id = String(formData.get("campaign_id") ?? "");
  const status = String(formData.get("status") ?? "") === "ACTIVE" ? "ACTIVE" : "PAUSED";
  try {
    await setCampaignStatus(id, status);
  } catch (e) {
    back(e instanceof Error ? e.message : "Eroare Meta API");
  }
  revalidatePath("/admin/meta-ads");
  redirect("/admin/meta-ads");
}

export async function saveBudget(formData: FormData): Promise<void> {
  await requireOwner();
  const objectId = String(formData.get("object_id") ?? "");
  const campaignId = String(formData.get("campaign_id") ?? "");
  const lei = Number(String(formData.get("budget_lei") ?? "").replace(",", "."));
  if (!objectId || !Number.isFinite(lei) || lei < 1) back("Buget invalid.");
  const budgetBani = Math.round(lei * 100);

  // Plafonul de 100 lei/zi pe totalul campaniilor active.
  try {
    const campaigns = await getCampaigns();
    const totalActive = campaigns
      .filter((c) => c.status === "ACTIVE")
      .reduce((s, c) => s + (c.id === campaignId ? budgetBani : c.dailyBudgetBani), 0);
    if (totalActive > DAILY_CAP_BANI) {
      back(`Peste plafonul de ${DAILY_CAP_BANI / 100} lei/zi: totalul campaniilor active ar fi ${(totalActive / 100).toFixed(0)} lei.`);
    }
    await setDailyBudget(objectId, budgetBani);
  } catch (e) {
    back(e instanceof Error ? e.message : "Eroare Meta API");
  }
  revalidatePath("/admin/meta-ads");
  redirect("/admin/meta-ads");
}
