import Link from "next/link";
import { sql } from "@/lib/db";
import { createLocation, deleteLocation } from "./actions";
import LocationForm from "./LocationForm";

export const dynamic = "force-dynamic";

type Loc = {
  id: number;
  name: string;
  phone: string | null;
  maps_link: string | null;
  days: string | null;
  notes: string | null;
};

export default async function LocatiiPage() {
  const locations = (await sql`
    SELECT id, name, phone, maps_link, days, notes FROM locations ORDER BY name
  `) as Loc[];

  return (
    <>
      <h1 className="wp-page-title">Locații</h1>

      <div className="card">
        <div className="card-title">Locații ({locations.length})</div>
        {locations.length === 0 ? (
          <p style={{ color: "var(--text-muted)" }}>Nu există locații adăugate încă.</p>
        ) : (
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(240px, 1fr))", gap: 10 }}>
            {locations.map((loc) => (
              <div key={loc.id} style={{ border: "1px solid var(--border)", borderRadius: 10, padding: "12px 14px", background: "var(--bg-warm)" }}>
                <div style={{ fontWeight: 700, fontSize: 13 }}>{loc.name}</div>
                {loc.phone && <div style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 2 }}>{loc.phone}</div>}
                {loc.days && <div style={{ fontSize: 12, color: "var(--text-muted)" }}>{loc.days}</div>}
                {loc.notes && <div style={{ fontSize: 11, color: "#9ca3af", marginTop: 3 }}>{loc.notes}</div>}
                <div style={{ display: "flex", gap: 5, marginTop: 10, flexWrap: "wrap" }}>
                  {loc.maps_link && (
                    <a href={loc.maps_link} target="_blank" rel="noopener" className="btn btn-sm btn-secondary">
                      Maps ↗
                    </a>
                  )}
                  <Link href={`/admin/locatii/${loc.id}`} className="btn btn-sm btn-secondary">
                    Editează
                  </Link>
                  <form action={deleteLocation} style={{ margin: 0 }}>
                    <input type="hidden" name="id" value={loc.id} />
                    <button type="submit" className="btn btn-sm btn-danger">
                      Șterge
                    </button>
                  </form>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="card crm-form">
        <div className="card-title">Adaugă locație</div>
        <LocationForm action={createLocation} />
      </div>
    </>
  );
}
