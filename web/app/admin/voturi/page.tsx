import { sql } from "@/lib/db";
import { createVoteCourse, updateVoteCourse, toggleVoteActive, deleteVoteCourse } from "./actions";
import VoteCourseForm from "./VoteCourseForm";
import VoteRows, { type VC } from "./VoteRows";

export const dynamic = "force-dynamic";

// Port din admin/partials/vot-tab.php — fără titlu de pagină, formular inline
// (?edit=), tabel wp-table vc-table cu Emoji | Nume | Vizite | Voturi | Conv.
export default async function VoturiPage({
  searchParams,
}: {
  searchParams: Promise<{ edit?: string; saved?: string }>;
}) {
  const sp = await searchParams;
  // clp_sort_vote_courses() (lib/vote.php:20-28) sortează cu usort stabil:
  // active înaintea inactivelor, apoi likes desc, iar la egalitate rămâne
  // ordinea de adăugare din vote_courses.json → aici id crescător.
  const list = (await sql`
    SELECT id, name, emoji, description, likes, views, active
    FROM vote_courses ORDER BY active DESC, likes DESC, id ASC
  `) as VC[];

  const editId = Number(sp.edit) || 0;
  const edit = editId ? list.find((v) => v.id === editId) : undefined;

  return (
    <>
      {sp.saved && <div className="notice notice-success">Cursul a fost salvat.</div>}

      <div className="card">
        <div className="card-title">{edit ? "Editează cursul" : "Adaugă idee de curs"}</div>
        <VoteCourseForm action={edit ? updateVoteCourse : createVoteCourse} initial={edit} />
      </div>

      <div className="card">
        <div className="card-title" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <span>Idei de cursuri ({list.length})</span>
          <a href="/voteaza-cursuri" target="_blank" rel="noopener" className="btn btn-sm btn-secondary">
            Vezi pagina ↗
          </a>
        </div>
        {list.length === 0 ? (
          <p style={{ color: "var(--text-muted)" }}>Nu există idei de cursuri adăugate încă.</p>
        ) : (
          <table className="wp-table vc-table">
            <thead>
              <tr>
                <th style={{ width: 48 }}>Emoji</th>
                <th>Nume</th>
                <th style={{ width: 64, whiteSpace: "nowrap" }}>Vizite</th>
                <th style={{ width: 72, whiteSpace: "nowrap" }}>Voturi</th>
                <th style={{ width: 72, whiteSpace: "nowrap" }}>Conv.</th>
                <th style={{ width: 210 }}>Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              <VoteRows list={list} toggle={toggleVoteActive} remove={deleteVoteCourse} />
            </tbody>
          </table>
        )}
      </div>
    </>
  );
}
