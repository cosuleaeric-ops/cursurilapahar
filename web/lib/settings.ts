import { cache } from "react";
import { sql } from "@/lib/db";

export type SettingsMap = Record<string, unknown>;

/**
 * Toate setările ca map. Memoizat cu React `cache` pe durata unui render:
 * layout-ul public și homepage-ul cereau același `SELECT key, value FROM settings`
 * de două ori pe cerere.
 */
export const getSettings = cache(async (): Promise<SettingsMap> => {
  const rows = (await sql`SELECT key, value FROM settings`) as { key: string; value: unknown }[];
  return Object.fromEntries(rows.map((r) => [r.key, r.value]));
});
