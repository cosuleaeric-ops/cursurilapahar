"use server";

import { sql } from "@/lib/db";

/**
 * Toggle vot: +1 (add) sau -1 (remove). Serverul face doar delta;
 * clientul reține ce a votat (ca în PHP-ul vechi). Scriere reală în Neon.
 */
export async function vote(id: number, action: "add" | "remove"): Promise<number> {
  const delta = action === "remove" ? -1 : 1;
  const rows = (await sql`
    UPDATE vote_courses
    SET likes = GREATEST(0, likes + ${delta})
    WHERE id = ${id} AND active = true
    RETURNING likes
  `) as { likes: number }[];
  if (rows.length) return rows[0].likes;

  // curs inexistent/inactiv — întoarce valoarea curentă fără schimbare
  const cur = (await sql`SELECT likes FROM vote_courses WHERE id = ${id}`) as { likes: number }[];
  return cur[0]?.likes ?? 0;
}
