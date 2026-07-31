import { sql } from "@/lib/db";
import CourseEditor, { type Opt } from "../CourseEditor";
import { EDITOR_CSS } from "../editor-styles";
import { saveCourseFull } from "../editor-actions";

export const dynamic = "force-dynamic";
export const metadata = { title: "Curs nou — Admin" };

export default async function CursNouPage({ searchParams }: { searchParams: Promise<{ err?: string }> }) {
  const { err } = await searchParams;
  const [speakers, locations] = await Promise.all([
    sql`SELECT id, name FROM speakers ORDER BY name` as unknown as Promise<Opt[]>,
    sql`SELECT id, name FROM locations ORDER BY position, name` as unknown as Promise<Opt[]>,
  ]);

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: EDITOR_CSS }} />
      <CourseEditor action={saveCourseFull} speakers={speakers} locations={locations} data={null} error={err} />
    </>
  );
}
