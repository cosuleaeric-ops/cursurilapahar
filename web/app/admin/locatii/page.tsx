import { sql } from "@/lib/db";
import { createLocation, updateLocation, deleteLocation } from "./actions";
import LocationsPanel, { type Loc } from "./LocationsPanel";

export const dynamic = "force-dynamic";

// Port din admin/partials/locatii-tab.php — fără titlu de pagină, „+ Adaugă
// locație" în antetul cardului deschide formularul, grilă fixă pe 3 coloane.
export default async function LocatiiPage({
  searchParams,
}: {
  searchParams: Promise<{ edit?: string; saved?: string }>;
}) {
  const sp = await searchParams;
  const locations = (await sql`
    SELECT id, name, phone, maps_link, days, notes FROM locations ORDER BY position, id
  `) as Loc[];

  const editId = Number(sp.edit) || 0;
  const edit = editId ? locations.find((l) => Number(l.id) === editId) : undefined;

  return (
    <>
      {sp.saved && <div className="notice notice-success">Locația a fost salvată.</div>}
      <LocationsPanel
        locations={locations}
        edit={edit}
        createAction={createLocation}
        updateAction={updateLocation}
        deleteAction={deleteLocation}
      />
    </>
  );
}
