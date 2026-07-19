import { sql } from "@/lib/db";
import CollabForm, { AddToggle, DeleteForm, type Collab } from "./CollabForm";

export const dynamic = "force-dynamic";

export default async function ColaborariPage({
  searchParams,
}: {
  searchParams: Promise<{ edit?: string; saved?: string }>;
}) {
  const { edit: editId, saved } = await searchParams;
  const collabs = (await sql`
    SELECT id, name, contact, contact_info, status, notes
    FROM collaborations ORDER BY position, id
  `) as Collab[];
  const edit = editId ? (collabs.find((c) => String(c.id) === editId) ?? null) : null;

  return (
    <>
      <h1 className="wp-page-title">Colaborări</h1>

      {saved && <div className="notice notice-success">Colaborarea a fost salvată.</div>}

      <div className="card">
        <div className="card-title" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <span>Colaborări ({collabs.length})</span>
          <AddToggle />
        </div>
        {collabs.length === 0 ? (
          <p style={{ color: "var(--text-muted)" }}>Nu există colaborări adăugate încă.</p>
        ) : (
          <table className="wp-table crm-table">
            <thead>
              <tr>
                <th>Brand / Organizație</th>
                <th>Persoana de contact</th>
                <th>Email / Telefon</th>
                <th>Status</th>
                <th style={{ width: 150 }}>Acțiuni</th>
              </tr>
            </thead>
            <tbody>
              {collabs.map((col) => (
                <tr key={col.id}>
                  <td style={{ fontWeight: 600 }}>
                    {col.name}
                    {col.notes ? (
                      <div style={{ fontSize: 11, color: "var(--text-muted)", fontWeight: 400, marginTop: 2 }}>
                        {col.notes.length > 60 ? col.notes.slice(0, 60) + "…" : col.notes}
                      </div>
                    ) : null}
                  </td>
                  <td style={{ fontSize: 13 }}>{col.contact}</td>
                  <td style={{ fontSize: 13 }}>{col.contact_info}</td>
                  <td style={{ fontSize: 13, color: "var(--text-muted)" }}>{col.status}</td>
                  <td>
                    <div className="row-actions">
                      <a href={`/admin/colaborari?edit=${col.id}`} className="btn btn-sm btn-secondary">
                        Editează
                      </a>
                      <DeleteForm id={col.id} />
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      <CollabForm key={edit?.id ?? "new"} edit={edit} />
    </>
  );
}
