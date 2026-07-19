"use client";

import { useState } from "react";
import { saveCollaboration, deleteCollaboration } from "./actions";

export type Collab = {
  id: number;
  name: string;
  contact: string | null;
  contact_info: string | null;
  status: string | null;
  notes: string | null;
};

export function AddToggle() {
  return (
    <button
      type="button"
      className="btn btn-sm btn-primary"
      onClick={() => {
        const el = document.getElementById("col-form");
        if (el) el.style.display = el.style.display === "none" ? "block" : "none";
      }}
    >
      + Adaugă colaborare
    </button>
  );
}

export function DeleteForm({ id }: { id: number }) {
  return (
    <form
      action={deleteCollaboration}
      style={{ display: "inline" }}
      onSubmit={(e) => {
        if (!confirm("Ștergi colaborarea?")) e.preventDefault();
      }}
    >
      <input type="hidden" name="id" value={id} />
      <button type="submit" className="btn btn-sm btn-danger">
        Șterge
      </button>
    </form>
  );
}

export default function CollabForm({ edit }: { edit: Collab | null }) {
  const [visible] = useState(!!edit);
  return (
    <div id="col-form" style={{ display: visible ? "block" : "none" }}>
      <div className="card crm-form">
        <div className="card-title">{edit ? "Editează colaborare" : "Adaugă colaborare"}</div>
        <form action={saveCollaboration}>
          <input type="hidden" name="id" value={edit?.id ?? ""} />
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr 1fr", gap: 8 }}>
            <div className="form-group">
              <label>Nume brand / org. *</label>
              <input type="text" name="name" defaultValue={edit?.name ?? ""} required />
            </div>
            <div className="form-group">
              <label>Persoana de contact</label>
              <input type="text" name="contact" defaultValue={edit?.contact ?? ""} />
            </div>
            <div className="form-group">
              <label>Email / Telefon</label>
              <input type="text" name="contact_info" defaultValue={edit?.contact_info ?? ""} />
            </div>
            <div className="form-group">
              <label>Status</label>
              <input type="text" name="status" defaultValue={edit?.status ?? ""} />
            </div>
          </div>
          <div className="form-group">
            <label>Note</label>
            <textarea name="notes" rows={2} defaultValue={edit?.notes ?? ""}></textarea>
          </div>
          <div style={{ display: "flex", gap: 8 }}>
            <button type="submit" className="btn btn-primary btn-sm">
              {edit ? "Salvează" : "Adaugă colaborarea"}
            </button>
            <a href="/admin/colaborari" className="btn btn-secondary btn-sm">
              Anulează
            </a>
          </div>
        </form>
      </div>
    </div>
  );
}
