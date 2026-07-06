import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { updateVoteCourse } from "../actions";
import VoteCourseForm, { type VoteCourseInitial } from "../VoteCourseForm";

export const dynamic = "force-dynamic";

export default async function EditVoteCoursePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const rows = (await sql`
    SELECT id, name, emoji, description FROM vote_courses WHERE id = ${Number(id)}
  `) as VoteCourseInitial[];

  if (!rows[0]) notFound();

  return (
    <>
      <h1 className="wp-page-title">Editează cursul</h1>
      <div className="card">
        <VoteCourseForm action={updateVoteCourse} initial={rows[0]} />
      </div>
    </>
  );
}
