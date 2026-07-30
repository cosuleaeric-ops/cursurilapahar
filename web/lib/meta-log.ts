// Jurnalul deciziilor din /admin/meta-ads: cine a schimbat ce, când și cu ce cifre pe masă.
// Tabelul se creează leneș la prima scriere (vezi migration/neon_schema.sql pentru forma canonică).

import { sql } from "@/lib/db";

export type LogAction = "pause" | "resume" | "budget";

export type LogEntry = {
  id: number;
  campaign_id: string;
  campaign_name: string | null;
  action: LogAction;
  detail: string | null;
  context: { spend?: number; purchases?: number; cpa?: number | null };
  actor: string | null;
  created_at: string;
};

let ensured = false;

async function ensureTable(): Promise<void> {
  if (ensured) return;
  await sql`
    CREATE TABLE IF NOT EXISTS meta_ads_log (
      id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
      campaign_id   TEXT NOT NULL,
      campaign_name TEXT,
      action        TEXT NOT NULL,
      detail        TEXT,
      context       JSONB NOT NULL DEFAULT '{}',
      actor         TEXT,
      created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
    )
  `;
  await sql`CREATE INDEX IF NOT EXISTS idx_meta_ads_log_created ON meta_ads_log(created_at DESC)`;
  ensured = true;
}

export async function logDecision(e: {
  campaignId: string;
  campaignName?: string | null;
  action: LogAction;
  detail?: string | null;
  context?: Record<string, unknown>;
  actor?: string | null;
}): Promise<void> {
  try {
    await ensureTable();
    await sql`
      INSERT INTO meta_ads_log (campaign_id, campaign_name, action, detail, context, actor)
      VALUES (
        ${e.campaignId}, ${e.campaignName ?? null}, ${e.action}, ${e.detail ?? null},
        ${JSON.stringify(e.context ?? {})}::jsonb, ${e.actor ?? null}
      )
    `;
  } catch {
    // Jurnalul nu trebuie să blocheze niciodată acțiunea reală asupra campaniei.
  }
}

export async function getLog(limit = 40): Promise<LogEntry[]> {
  try {
    await ensureTable();
    return (await sql`
      SELECT id, campaign_id, campaign_name, action, detail, context, actor, created_at
      FROM meta_ads_log ORDER BY created_at DESC LIMIT ${limit}
    `) as LogEntry[];
  } catch {
    return [];
  }
}
