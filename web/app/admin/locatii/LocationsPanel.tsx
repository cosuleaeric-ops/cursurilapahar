"use client";

import { useState } from "react";
import LocationForm from "./LocationForm";

export type Loc = {
  id: number;
  name: string;
  phone: string | null;
  maps_link: string | null;
  days: string | null;
  notes: string | null;
};

// Structura din locatii-tab.php: cardul cu grila de locații și, sub el,
// formularul ascuns pe care îl deschide „+ Adaugă locație" (sau ?edit=).
export default function LocationsPanel({
  locations,
  edit,
  createAction,
  updateAction,
  deleteAction,
}: {
  locations: Loc[];
  edit?: Loc;
  createAction: (fd: FormData) => void | Promise<void>;
  updateAction: (fd: FormData) => void | Promise<void>;
  deleteAction: (fd: FormData) => void | Promise<void>;
}) {
  const [open, setOpen] = useState(false);
  const show = open || !!edit;

  return (
    <>
      <div className="card">
        <div className="card-title" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <span>Locații ({locations.length})</span>
          <button type="button" onClick={() => setOpen(!open)} className="btn btn-sm btn-primary">
            + Adaugă locație
          </button>
        </div>
        {locations.length === 0 ? (
          <p style={{ color: "var(--text-muted)" }}>Nu există locații adăugate încă.</p>
        ) : (
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 10 }}>
            {locations.map((loc) => (
              <div key={loc.id} style={{ border: "1px solid #e5e7eb", borderRadius: 10, padding: "12px 14px", background: "#fafafa" }}>
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", gap: 8 }}>
                  <div>
                    <div style={{ fontWeight: 700, fontSize: 13 }}>{loc.name}</div>
                    {loc.phone && <div style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 2 }}>{loc.phone}</div>}
                    {loc.days && <div style={{ fontSize: 12, color: "var(--text-muted)" }}>{loc.days}</div>}
                    {loc.notes && (
                      <div style={{ fontSize: 11, color: "#9ca3af", marginTop: 3 }}>
                        {loc.notes.slice(0, 80)}
                        {loc.notes.length > 80 ? "…" : ""}
                      </div>
                    )}
                  </div>
                  <div style={{ display: "flex", gap: 5, flexShrink: 0, alignItems: "center" }}>
                    {loc.maps_link && (
                      <a href={loc.maps_link} target="_blank" rel="noopener" className="btn btn-sm btn-secondary">
                        Maps ↗
                      </a>
                    )}
                    <a href={`/admin/locatii?edit=${loc.id}`} className="btn btn-sm btn-secondary">
                      Editează
                    </a>
                    <form
                      action={deleteAction}
                      onSubmit={(e) => {
                        if (!confirm("Ștergi locația?")) e.preventDefault();
                      }}
                      style={{ display: "inline" }}
                    >
                      <input type="hidden" name="id" value={loc.id} />
                      <button type="submit" className="btn btn-sm btn-danger">
                        Șterge
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <div style={show ? undefined : { display: "none" }}>
        <div className="card crm-form">
          <div className="card-title">{edit ? "Editează locație" : "Adaugă locație"}</div>
          <LocationForm action={edit ? updateAction : createAction} initial={edit} />
        </div>
      </div>
    </>
  );
}
