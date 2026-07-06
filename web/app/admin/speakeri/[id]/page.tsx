import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { updateSpeaker } from "../actions";
import SpeakerForm, { type SpeakerInitial } from "../SpeakerForm";

export const dynamic = "force-dynamic";

export default async function EditSpeakerPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const rows = (await sql`
    SELECT id, name, email, phone, status, notes, topics
    FROM speakers WHERE id = ${Number(id)}
  `) as SpeakerInitial[];

  if (!rows[0]) notFound();

  return (
    <>
      <h1 className="wp-page-title">Editează speaker</h1>
      <SpeakerForm action={updateSpeaker} initial={rows[0]} />
    </>
  );
}
