import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import styles from "./admin.module.css";

export const dynamic = "force-dynamic";

export default async function AdminHome() {
  const session = await getSession();
  const [stats] = (await sql`
    SELECT
      (SELECT count(*) FROM events)        AS events,
      (SELECT count(*) FROM tickets)       AS tickets,
      (SELECT count(*) FROM vote_courses)  AS votes,
      (SELECT count(*) FROM speakers)      AS speakers
  `) as { events: string; tickets: string; votes: string; speakers: string }[];

  return (
    <>
      <h1 className={styles.h1}>Salut, {session?.username} 👋</h1>
      <p className={styles.sub}>Panou de administrare — date live din Neon.</p>

      <div className={styles.stats}>
        <div className={styles.stat}>
          <span className={styles.num}>{stats.events}</span>
          <span className={styles.lbl}>Evenimente</span>
        </div>
        <div className={styles.stat}>
          <span className={styles.num}>{stats.tickets}</span>
          <span className={styles.lbl}>Bilete</span>
        </div>
        <div className={styles.stat}>
          <span className={styles.num}>{stats.votes}</span>
          <span className={styles.lbl}>Cursuri la vot</span>
        </div>
        <div className={styles.stat}>
          <span className={styles.num}>{stats.speakers}</span>
          <span className={styles.lbl}>Speakeri</span>
        </div>
      </div>
    </>
  );
}
