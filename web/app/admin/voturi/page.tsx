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
  active: boolean;
};

export default async function VoturiPage() {
  const list = (await sql`
    SELECT id, name, emoji, description, likes, active
    FROM vote_courses ORDER BY likes DESC, name ASC
  `) as VC[];

  const th: React.CSSProperties = { textAlign: "left", fontSize: 11, fontWeight: 700, textTransform: "uppercase", letterSpacing: ".04em", color: "var(--text-muted)", padding: "0 0 10px", borderBottom: "1px solid var(--border)" };
  const td: React.CSSProperties = { padding: "12px 0", borderBottom: "1px solid var(--border)", verticalAlign: "middle" };

  return (
    <>
      <h1 className="wp-page-title">Voturi</h1>

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
