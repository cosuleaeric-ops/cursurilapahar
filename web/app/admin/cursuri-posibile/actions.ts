"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

export type IdeaCategory = { emoji?: string; title?: string; topics?: string[] };

export async function saveCourseIdeas(formData: FormData): Promise<void> {
  if (!(await getSession())) redirect("/login");
  const emojis = formData.getAll("cat_emoji").map(String);
  const titles = formData.getAll("cat_title").map(String);
  const topics = formData.getAll("cat_topics").map(String);

  const categories: IdeaCategory[] = [];
  titles.forEach((rawTitle, i) => {
    const title = rawTitle.trim();
    if (!title) return;
    categories.push({
      emoji: (emojis[i] ?? "").trim(),
      title,
      topics: (topics[i] ?? "")
        .split("\n")
        .map((l) => l.trim())
        .filter(Boolean),
    });
  });

  // păstrăm restul cheilor (flag-urile de migrație din PHP), ca array_merge
  const rows = (await sql`SELECT value FROM settings WHERE key = 'course_ideas'`) as { value: unknown }[];
  const existing = (rows[0]?.value && typeof rows[0].value === "object" ? rows[0].value : {}) as Record<string, unknown>;
  const next = { ...existing, intro: String(formData.get("ideas_intro") ?? "").trim(), categories };

  await sql`
    INSERT INTO settings (key, value) VALUES ('course_ideas', ${JSON.stringify(next)})
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()
  `;
  revalidatePath("/cursuri-posibile");
  redirect("/admin/cursuri-posibile?saved=1");
}
