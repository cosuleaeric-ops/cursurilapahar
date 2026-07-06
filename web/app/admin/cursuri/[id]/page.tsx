import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { updateCourse } from "../actions";
import CourseForm, { type CourseInitial } from "../CourseForm";

export const dynamic = "force-dynamic";

export default async function EditCoursePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const rows = (await sql`
    SELECT id, title, location, livetickets_url, image_url, active, sold_out,
      to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS date,
      to_char(starts_at AT TIME ZONE 'Europe/Bucharest', 'HH24:MI') AS time
    FROM events WHERE id = ${Number(id)}
  `) as CourseInitial[];

  if (!rows[0]) notFound();

  return (
    <>
      <h1 className="wp-page-title">Editează curs</h1>
      <CourseForm action={updateCourse} initial={rows[0]} />
    </>
  );
}
