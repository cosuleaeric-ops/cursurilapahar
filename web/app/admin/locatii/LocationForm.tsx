"use client";

import Link from "next/link";

export type LocationInitial = {
  id?: number;
  name?: string;
  phone?: string | null;
  maps_link?: string | null;
  days?: string | null;
  notes?: string | null;
};

export default function LocationForm({
  action,
  initial,
}: {
  action: (formData: FormData) => void | Promise<void>;
  initial?: LocationInitial;
}) {
  const editing = initial?.id != null;
  return (
    <form action={action}>
      {editing && <input type="hidden" name="id" value={initial.id} />}

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(140px, 1fr))", gap: 8 }}>
        <div className="form-group">
          <label>
            Nume <span style={{ color: "var(--danger)" }}>*</span>
          </label>
          <input name="name" type="text" required defaultValue={initial?.name ?? ""} />
        </div>
        <div className="form-group">
          <label>Telefon</label>
          <input name="phone" type="text" defaultValue={initial?.phone ?? ""} />
        </div>
        <div className="form-group">
          <label>Link Google Maps</label>
          <input name="maps_link" type="url" defaultValue={initial?.maps_link ?? ""} />
        </div>
        <div className="form-group">
          <label>Zile disponibile</label>
          <input name="days" type="text" defaultValue={initial?.days ?? ""} />
        </div>
      </div>

      <div className="form-group">
        <label>Note</label>
        <textarea name="notes" rows={2} defaultValue={initial?.notes ?? ""} />
      </div>

      <div style={{ display: "flex", gap: 8 }}>
        <button type="submit" className="btn btn-primary btn-sm">
          {editing ? "Salvează" : "Adaugă locația"}
        </button>
        {editing && (
          <Link href="/admin/locatii" className="btn btn-secondary btn-sm">
            Anulează
          </Link>
        )}
      </div>
    </form>
  );
}
