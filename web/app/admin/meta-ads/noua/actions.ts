"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { getSession, type Session } from "@/lib/auth";
import { createFullCampaign } from "@/lib/meta";
import { logDecision } from "@/lib/meta-log";

async function requireOwner(): Promise<Session> {
  const s = await getSession();
  if (!s) redirect("/login");
  if (s.role !== "owner") redirect("/admin");
  return s;
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

function fail(msg: string): never {
  redirect(`/admin/meta-ads/noua?err=${encodeURIComponent(msg)}`);
}

export async function createCampaignAction(formData: FormData): Promise<void> {
  const session = await requireOwner();

  const name = g(formData, "name");
  const lei = Number(g(formData, "budget_lei").replace(",", "."));
  const link = g(formData, "link");
  const bodies = [g(formData, "body1"), g(formData, "body2"), g(formData, "body3")].filter(Boolean);
  const titles = [g(formData, "title1"), g(formData, "title2"), g(formData, "title3")].filter(Boolean);
  const description = g(formData, "description");
  const image = formData.get("image");

  if (!name) fail("Pune un nume campaniei.");
  if (!Number.isFinite(lei) || lei < 1) fail("Buget invalid.");
  if (!/^https:\/\//.test(link)) fail("Linkul trebuie să fie un URL https:// complet.");
  if (bodies.length === 0) fail("Cel puțin un text principal.");
  if (titles.length === 0) fail("Cel puțin un titlu.");
  if (!(image instanceof File) || image.size === 0) fail("Alege afișul.");
  if (image.size > 20_000_000) fail("Imaginea e prea mare (max 20MB).");

  const imageBase64 = Buffer.from(await image.arrayBuffer()).toString("base64");

  let campaignId = "";
  try {
    ({ campaignId } = await createFullCampaign({
      name,
      dailyBudgetBani: Math.round(lei * 100),
      link,
      bodies,
      titles,
      description,
      imageBase64,
    }));
  } catch (e) {
    // redirect() aruncă intern — nu-l raporta ca eroare Meta.
    if (e && typeof e === "object" && "digest" in e) throw e;
    fail(e instanceof Error ? e.message : "Eroare Meta API");
  }

  await logDecision({
    campaignId,
    campaignName: name,
    action: "create",
    detail: `creată din formular · ${lei.toFixed(0)} lei/zi · pe pauză`,
    actor: session.username,
  });

  revalidatePath("/admin/meta-ads");
  redirect(`/admin/meta-ads/${campaignId}?nou=1`);
}
