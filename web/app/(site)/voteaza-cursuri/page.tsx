import type { Metadata } from "next";
import { pageMetadata } from "@/lib/metadata";
import { sql } from "@/lib/db";
import VoteList, { VoteBackLink, type VoteCourse } from "./VoteList";
import styles from "./vote.module.css";

export const dynamic = "force-dynamic";

export const metadata: Metadata = pageMetadata({
  title: "Votează cursuri – Cursuri la Pahar",
  description:
    "Votează temele de curs care te interesează. Cele mai apreciate teme au șanse mai mari să devină cursuri viitoare.",
  path: "/voteaza-cursuri",
});

export default async function VotePage() {
  // voteaza-cursuri.php:37-38 — doar temele active, în ordine complet aleatorie
  // la fiecare încărcare (shuffle), ca să nu avantajeze temele deja populare.
  const courses = (await sql`
    SELECT id, legacy_id, name, emoji, description, likes
    FROM vote_courses
    WHERE active = true
    ORDER BY random()
  `) as VoteCourse[];

  // voteaza-cursuri.php:29-30 — titlul și subtitlul vin din settings (editabile
  // din admin), cu fallback doar când cheia lipsește.
  const rows = (await sql`
    SELECT key, value FROM settings WHERE key IN ('vote_title', 'vote_subtitle')
  `) as { key: string; value: unknown }[];
  const s = Object.fromEntries(rows.map((r) => [r.key, r.value]));
  const str = (k: string, d: string) => (typeof s[k] === "string" ? (s[k] as string) : d);

  const voteTitle = str("vote_title", "Votează cursurile");
  const voteSubtitle = str(
    "vote_subtitle",
    "Apasă ❤️ pe temele care te interesează. Cele mai apreciate au șanse mai mari să devină cursuri viitoare."
  );

  return (
    <>
      <section className={styles.section}>
        <div className={styles.header}>
          <VoteBackLink />
          <h1>{voteTitle}</h1>
          <p>{voteSubtitle}</p>
        </div>

        {courses.length === 0 ? (
          // voteaza-cursuri.php:262-265
          <div className={styles.empty}>
            <p>Nu există teme de votat momentan. Revino curând!</p>
          </div>
        ) : (
          <VoteList courses={courses} />
        )}
      </section>
    </>
  );
}
