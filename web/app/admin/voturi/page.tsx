import Link from "next/link";
import { sql } from "@/lib/db";
import { createVoteCourse, toggleVoteActive, deleteVoteCourse } from "./actions";
import VoteCourseForm from "./VoteCourseForm";

export const dynamic = "force-dynamic";

type VC = {
  id: number;
  name: string;
  emoji: string | null;
  description: string | null;
  likes: number;
  views: number;
  active: boolean;
};

export default async function VoturiPage() {
  const list = (await sql`
    SELECT id, name, emoji, description, likes, views, active
    FROM vote_courses ORDER BY likes DESC, name ASC
  `) as VC[];
  const pageViewsRow = (await sql`SELECT value FROM settings WHERE key = 'vote_page_views'`) as { value: unknown }[];
  const pageViews = Number(pageViewsRow[0]?.value ?? 0);

  // conversie = voturi / vizualizări ale cardului (ca clp_format_vote_conversion)
  const conversion = (likes: number, views: number) =>
    views > 0 ? `${((likes / views) * 100).toFixed(1).replace(".", ",")}%` : "—";

  const th: React.CSSProperties = { textAlign: "left", fontSize: 11, fontWeight: 700, textTransform: "uppercase", letterSpacing: ".04em", color: "var(--text-muted)", padding: "0 0 10px", borderBottom: "1px solid var(--border)" };
  const td: React.CSSProperties = { padding: "12px 0", borderBottom: "1px solid var(--border)", verticalAlign: "middle" };

  return (
    <>
      <h1 className="wp-page-title">Voturi</h1>

      <p style={{ color: "var(--text-muted)", fontSize: 13, marginTop: -6 }}>
        Pagina de votare a fost vizitată de <strong>{pageViews}</strong> ori. Conversia = voturi raportate la cât de des a
        fost văzut cardul.
      </p>

      <div className="card">
        <div className="card-title">Adaugă idee de curs</div>
        <VoteCourseForm action={createVoteCourse} />
      </div>

      <div className="card">
        <div className="card-title" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <span>Idei de cursuri ({list.length})</span>
          <a href="/voteaza-cursuri" target="_blank" rel="noopener" className="btn btn-sm btn-secondary">
            Vezi pagina ↗
          </a>
        </div>

        <table style={{ width: "100%", borderCollapse: "collapse" }}>
          <thead>
            <tr>
              <th style={{ ...th, width: 48, textAlign: "center" }}>Emoji</th>
              <th style={th}>Nume</th>
              <th style={{ ...th, width: 80, textAlign: "center" }}>Voturi</th>
              <th style={{ ...th, width: 80, textAlign: "center" }}>Vizite</th>
              <th style={{ ...th, width: 90, textAlign: "center" }}>Conversie</th>
              <th style={{ ...th, width: 190 }}>Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            {list.map((vc) => (
              <tr key={vc.id} style={{ opacity: vc.active ? 1 : 0.45 }}>
                <td style={{ ...td, fontSize: "1.4rem", textAlign: "center" }}>{vc.emoji || "📚"}</td>
                <td style={td}>
                  <div style={{ fontWeight: 600 }}>
                    {vc.name}
                    {!vc.active && (
                      <span style={{ fontSize: 11, color: "var(--text-muted)", fontWeight: 400, marginLeft: 6 }}>(dezactivat)</span>
                    )}
                  </div>
                  {vc.description && (
                    <div style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 2, maxWidth: 360, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                      {vc.description}
                    </div>
                  )}
                </td>
                <td style={{ ...td, textAlign: "center", fontVariantNumeric: "tabular-nums" }}>❤️ {vc.likes}</td>
                <td style={{ ...td, textAlign: "center", fontVariantNumeric: "tabular-nums", color: "var(--text-muted)" }}>{vc.views}</td>
                <td style={{ ...td, textAlign: "center", fontVariantNumeric: "tabular-nums" }}>{conversion(vc.likes, vc.views)}</td>
                <td style={td}>
                  <div style={{ display: "flex", gap: 6, alignItems: "center" }}>
                    <Link className="btn btn-sm btn-secondary" href={`/admin/voturi/${vc.id}`}>
                      Editează
                    </Link>
                    <form action={toggleVoteActive} style={{ margin: 0 }}>
                      <input type="hidden" name="id" value={vc.id} />
                      <button type="submit" className={`btn btn-sm ${vc.active ? "status-active" : "status-inactive"}`}>
                        {vc.active ? "Activ" : "Inactiv"}
                      </button>
                    </form>
                    <form action={deleteVoteCourse} style={{ margin: 0 }}>
                      <input type="hidden" name="id" value={vc.id} />
                      <button type="submit" className="btn btn-sm btn-danger">
                        ✕
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
