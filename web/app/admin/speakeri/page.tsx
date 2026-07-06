import Link from "next/link";
import { sql } from "@/lib/db";
import { deleteSpeaker } from "./actions";
import { STATUS_COLOR } from "./statuses";

export const dynamic = "force-dynamic";

type Speaker = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  status: string | null;
  topics: string[] | null;
};

const rowStyle: React.CSSProperties = {
  display: "flex",
  alignItems: "center",
  gap: 14,
  padding: "12px 0",
  borderBottom: "1px solid var(--border)",
};

export default async function SpeakeriPage() {
  const speakers = (await sql`
    SELECT id, name, email, phone, status, topics
    FROM speakers
    ORDER BY
      CASE status
        WHEN 'CONTACTAT' THEN 0 WHEN 'URMEAZĂ' THEN 1 WHEN 'RECURENT' THEN 2
        WHEN 'MID' THEN 3 WHEN 'NOPE' THEN 4 ELSE 3 END,
      name
  `) as Speaker[];

  return (
    <>
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 8 }}>
        <h1 className="wp-page-title" style={{ marginBottom: 0 }}>
          Speakeri ({speakers.length})
        </h1>
        <Link className="btn btn-primary" href="/admin/speakeri/nou">
          + Adaugă speaker
        </Link>
      </div>

      {speakers.map((s) => (
        <div key={s.id} style={rowStyle}>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
              <span style={{ fontWeight: 600 }}>{s.name}</span>
              {s.status && (
                <span className="crm-status-badge" style={{ background: STATUS_COLOR[s.status] ?? "#6b7280" }}>
                  {s.status}
                </span>
              )}
            </div>
            <div style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 2 }}>
              {[s.email, s.phone].filter(Boolean).join(" · ") || "—"}
              {s.topics && s.topics.length > 0 ? ` · ${s.topics.length} teme` : ""}
            </div>
          </div>
          <div style={{ display: "flex", alignItems: "center", gap: 8, flexShrink: 0 }}>
            <Link className="btn btn-sm btn-secondary" href={`/admin/speakeri/${s.id}`}>
              Editează
            </Link>
            <form action={deleteSpeaker} style={{ margin: 0 }}>
              <input type="hidden" name="id" value={s.id} />
              <button type="submit" className="btn btn-sm btn-danger">
                Șterge
              </button>
            </form>
          </div>
        </div>
      ))}
    </>
  );
}
