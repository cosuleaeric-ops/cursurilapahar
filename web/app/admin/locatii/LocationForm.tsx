"use client";

import { useRef } from "react";

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
  onSubmitted,
}: {
  action: (formData: FormData) => void | Promise<void>;
  initial?: LocationInitial;
  onSubmitted?: () => void;
}) {
  const editing = initial?.id != null;
  const formRef = useRef<HTMLFormElement>(null);
  return (
    <form
      ref={formRef}
      action={action}
      onSubmit={() => {
        // PHP: salvarea se termină cu redirect (reîncărcare completă), deci
        // formularul se închide și inputurile revin goale. Amânat, ca React
        // să apuce să citească FormData înainte de reset.
        setTimeout(() => {
          formRef.current?.reset();
          onSubmitted?.();
        }, 0);
      }}
    >
      {editing && <input type="hidden" name="id" value={initial.id} />}

      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr 1fr", gap: 8 }}>
        <div className="form-group">
          {/* PHP: asterisc simplu, în culoarea etichetei (locatii-tab.php:48) */}
          <label>Nume *</label>
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
        {/* PHP: „Anulează" e ancoră normală → reîncărcare completă, care închide
            și golește formularul (locatii-tab.php:56) */}
        <a href="/admin/locatii" className="btn btn-secondary btn-sm">
          Anulează
        </a>
      </div>
    </form>
  );
}
