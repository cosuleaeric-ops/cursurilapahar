"use server";

import { sql } from "@/lib/db";
import { shouldCountClick } from "@/lib/ab";

/**
 * Toggle vot: +1 (add) sau -1 (remove). Serverul face doar delta;
 * clientul reține ce a votat (ca în PHP-ul vechi). Scriere reală în Neon.
 * api/vote.php:58 întoarce doar {success}, iar clientul nici nu-l citește —
 * contorul rămâne optimist, deci nu întoarcem numărul de likes.
 */
export async function vote(id: number, action: "add" | "remove"): Promise<void> {
  const delta = action === "remove" ? -1 : 1;
  // api/vote.php:37 — un curs inactiv nu primește voturi
  await sql`
    UPDATE vote_courses
    SET likes = GREATEST(0, likes + ${delta})
    WHERE id = ${id} AND active = true
  `;
}

/**
 * Tracking vizite — port din api/vote_view.php + api/vote_page_view.php.
 * Boții, prefetch-urile și adminul logat nu se numără (shouldCountClick);
 * clientul deduplică pe sesiune, ca în JS-ul vechi.
 */
export async function trackVoteView(id: number): Promise<void> {
  if (!Number.isFinite(id) || !(await shouldCountClick())) return;
  // lib/vote_views.php:132-151 — vizualizarea se numără doar pentru un curs
  // care există ȘI e activ (api/vote_view.php:20-26 refuză restul cu „ID invalid")
  await sql`UPDATE vote_courses SET views = views + 1 WHERE id = ${id} AND active = true`;
}

export async function trackVotePageView(): Promise<void> {
  if (!(await shouldCountClick())) return;
  await sql`
    INSERT INTO settings (key, value) VALUES ('vote_page_views', '1'::jsonb)
    ON CONFLICT (key) DO UPDATE
    SET value = to_jsonb(COALESCE((settings.value)::text::int, 0) + 1), updated_at = now()
  `;
}
