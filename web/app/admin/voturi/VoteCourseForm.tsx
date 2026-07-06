"use client";

import Link from "next/link";

export type VoteCourseInitial = {
  id?: number;
  name?: string;
  emoji?: string | null;
  description?: string | null;
};

export default function VoteCourseForm({
  action,
  initial,
}: {
  action: (formData: FormData) => void | Promise<void>;
  initial?: VoteCourseInitial;
}) {
  const editing = initial?.id != null;
  return (
    <form action={action}>
      {editing && <input type="hidden" name="id" value={initial.id} />}

      <div style={{ display: "grid", gridTemplateColumns: "64px 1fr", gap: 12, alignItems: "start" }}>
        <div className="form-group" style={{ marginBottom: 0 }}>
          <label>Emoji</label>
          <input
            name="emoji"
            type="text"
            maxLength={4}
            defaultValue={initial?.emoji ?? "📚"}
            style={{ textAlign: "center", fontSize: "1.5rem", padding: "6px 4px" }}
          />
        </div>
        <div className="form-group" style={{ marginBottom: 0 }}>
          <label>
            Nume curs <span style={{ color: "var(--danger)" }}>*</span>
          </label>
          <input name="name" type="text" required defaultValue={initial?.name ?? ""} />
        </div>
      </div>

      <div className="form-group" style={{ marginTop: 12 }}>
        <label>Descriere</label>
        <textarea name="description" rows={4} defaultValue={initial?.description ?? ""} />
      </div>

      <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
        <button type="submit" className="btn btn-primary">
          {editing ? "Salvează modificările" : "Adaugă cursul"}
        </button>
        {editing && (
          <Link href="/admin/voturi" className="btn btn-secondary">
            Anulează
          </Link>
        )}
      </div>
    </form>
  );
}
