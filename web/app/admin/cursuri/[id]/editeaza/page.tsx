import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import CourseEditor, { type CourseData, type Opt } from "../../CourseEditor";
import { EDITOR_CSS } from "../../editor-styles";
import { saveCourseFull } from "../../editor-actions";

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const [e] = (await sql`SELECT title FROM events WHERE id = ${Number(id) || 0}`) as { title: string }[];
  return { title: `${e?.title ?? "Curs"} — Admin` };
}

export default async function EditeazaPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ err?: string; ok?: string }>;
}) {
  const { id: idStr } = await params;
  const { err, ok } = await searchParams;
  const id = Number(idStr);
  if (!id) notFound();

  const [row] = (await sql`
    SELECT id, title, slug, speaker_id, speaker_name, location, livetickets_url, description,
           image_url, image_landscape_url, active,
           to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS date_raw,
           to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'HH24:MI')    AS time
    FROM events WHERE id = ${id}
  `) as (Omit<CourseData, "tipuri"> & { date_raw: string | null; time: string | null })[];
  if (!row) notFound();

  const [speakers, locations, tipuri] = await Promise.all([
    sql`SELECT id, name FROM speakers ORDER BY name` as unknown as Promise<Opt[]>,
    sql`SELECT id, name FROM locations ORDER BY position, name` as unknown as Promise<Opt[]>,
    sql`SELECT name, price, stock, COALESCE(description, '') AS description
        FROM ticket_types WHERE event_id = ${id} AND discount_code_id IS NULL
        ORDER BY position, id` as unknown as Promise<{ name: string; price: string; stock: number; description: string }[]>,
  ]);

  const data: CourseData = {
    ...row,
    date_raw: row.date_raw ?? "",
    time: row.time ?? "",
    speaker_name: row.speaker_name ?? "",
    location: row.location ?? "",
    livetickets_url: row.livetickets_url ?? "",
    description: row.description ?? "",
    image_url: row.image_url ?? "",
    image_landscape_url: row.image_landscape_url ?? "",
    tipuri: tipuri.map((t) => ({ ...t, price: Number(t.price) })),
  };

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: EDITOR_CSS }} />
      <CourseEditor
        action={saveCourseFull}
        speakers={speakers}
        locations={locations}
        data={data}
        error={err}
        saved={ok === "1"}
      />
    </>
  );
}
