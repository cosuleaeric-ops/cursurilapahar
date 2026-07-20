// Test A/B + contorizare click-uri pe cursuri — port din lib/ab_button.php +
// lib/course_clicks.php. Contoarele stau în ab_experiments / events.clicks (Neon).

import { cookies, headers } from "next/headers";
import { sql } from "@/lib/db";

export const AB_COOKIE = "clp_ab_btn";
export const AB_VARIANTS = ["off", "on"] as const;
export type AbVariant = (typeof AB_VARIANTS)[number];

const BOT_PATTERNS = [
  "bot", "crawl", "spider", "slurp", "archiver", "preview", "prerender",
  "googlebot", "bingbot", "yandex", "baiduspider", "duckduckbot", "applebot",
  "facebookexternalhit", "facebot", "twitterbot", "linkedinbot", "pinterest",
  "whatsapp", "telegrambot", "discordbot", "snapchat", "skypeuripreview",
  "semrush", "ahrefs", "mj12bot", "dotbot", "petalbot", "bytespider",
  "uptimerobot", "pingdom", "statuscake", "gtmetrix", "lighthouse",
  "headlesschrome", "phantomjs", "selenium", "webdriver",
  "wget", "curl/", "python-requests", "python-urllib", "go-http-client",
  "java/", "libwww-perl", "httpclient", "okhttp", "axios/", "node-fetch",
  "scrapy", "httpunit", "mechanize", "aiohttp", "postman",
  "ia_archiver", "archive.org", "mediapartners-google",
];

/** Varianta atribuită vizitatorului (cookie sau header-ul pus de proxy la prima vizită). */
export async function abVariant(): Promise<AbVariant | null> {
  const store = await cookies();
  const v = store.get(AB_COOKIE)?.value ?? (await headers()).get("x-ab-btn") ?? "";
  return (AB_VARIANTS as readonly string[]).includes(v) ? (v as AbVariant) : null;
}

/** True doar pentru navigări umane: fără boți, prefetch-uri sau adminul logat. */
export async function shouldCountClick(): Promise<boolean> {
  const h = await headers();
  const ua = (h.get("user-agent") ?? "").toLowerCase();
  if (!ua || BOT_PATTERNS.some((p) => ua.includes(p))) return false;

  const purpose = `${h.get("purpose") ?? ""} ${h.get("x-purpose") ?? ""} ${h.get("sec-purpose") ?? ""}`.toLowerCase();
  if (purpose.includes("prefetch")) return false;

  // Vizitele noastre (sesiune de admin) nu poluează statisticile.
  const store = await cookies();
  if (store.get("clp_session")?.value) return false;

  return true;
}

export async function trackAb(variant: AbVariant, metric: "views" | "conversions"): Promise<void> {
  if (metric === "views") {
    await sql`
      INSERT INTO ab_experiments (experiment, variant, views, conversions) VALUES ('button', ${variant}, 1, 0)
      ON CONFLICT (experiment, variant) DO UPDATE SET views = ab_experiments.views + 1
    `;
  } else {
    await sql`
      INSERT INTO ab_experiments (experiment, variant, views, conversions) VALUES ('button', ${variant}, 0, 1)
      ON CONFLICT (experiment, variant) DO UPDATE SET conversions = ab_experiments.conversions + 1
    `;
  }
}
