import { sql } from "@/lib/db";
import Link from "next/link";
import VoteList, { type VoteCourse } from "./VoteList";
import styles from "./vote.module.css";

export const dynamic = "force-dynamic";

export default async function VotePage() {
  const courses = (await sql`
    SELECT id, name, emoji, description, likes
    FROM vote_courses
    WHERE active = true
    ORDER BY likes DESC, name ASC
  `) as VoteCourse[];

  return (
    <main className={styles.main}>
      <Link href="/" className={styles.back}>
        ← Acasă
      </Link>
      <h1 className={styles.title}>Votează următoarele cursuri</h1>
      <p className={styles.subtitle}>
        Alege ce teme ți-ar plăcea să vezi la un pahar. Cele mai votate ajung primele pe scenă.
      </p>
      <VoteList courses={courses} />
      <p className={styles.footnote}>Voturi live în Neon Postgres · scaffold migrare Next.js</p>
    </main>
  );
}
