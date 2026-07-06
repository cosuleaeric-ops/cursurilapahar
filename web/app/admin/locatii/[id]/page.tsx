import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { updateLocation } from "../actions";
import LocationForm, { type LocationInitial } from "../LocationForm";

export const dynamic = "force-dynamic";

export default async function EditLocationPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const rows = (await sql`
    SELECT id, name, phone, maps_link, days, notes FROM locations WHERE id = ${Number(id)}
  `) as LocationInitial[];

  if (!rows[0]) notFound();

  return (
    <>
      <h1 className="wp-page-title">Editează locație</h1>
      <div className="card crm-form">
        <LocationForm action={updateLocation} initial={rows[0]} />
      </div>
    </>
  );
}
