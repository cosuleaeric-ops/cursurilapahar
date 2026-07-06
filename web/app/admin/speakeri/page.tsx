import Link from "next/link";
import { sql } from "@/lib/db";
import { deleteSpeaker } from "./actions";
import { STATUS_COLOR } from "./statuses";
import styles from "./speakeri.module.css";

export const dynamic = "force-dynamic";

type Speaker = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  status: string | null;
  topics: string[] | null;
};

export default async function SpeakeriPage() {
  const speakers = (await sql`
    SELECT id, name, email, phone, status, topics
    FROM speakers
    ORDER BY
      CASE status
        WHEN 'CONTACTAT' THEN 0 WHEN 'URMEAZĂ' THEN 1 WHEN 'RECURENT' THEN 2
        WHEN 'MID' THEN 3 WHEN 'NOPE' THEN 4 ELSE 3 END,
      name
  `) as Speaker[];

  return (
    <>
      <div className={styles.head}>
        <h1 className={styles.h1}>Speakeri ({speakers.length})</h1>
        <Link className={styles.btnPrimary} href="/admin/speakeri/nou">
          + Adaugă speaker
        </Link>
      </div>

      <div className={styles.list}>
        {speakers.map((s) => (
          <div key={s.id} className={styles.item}>
            <div className={styles.itemMain}>
              <div className={styles.itemTop}>
                <span className={styles.name}>{s.name}</span>
                {s.status && (
                  <span className={styles.badge} style={{ background: STATUS_COLOR[s.status] ?? "#888" }}>
                    {s.status}
                  </span>
                )}
              </div>
              <div className={styles.meta}>
                {[s.email, s.phone].filter(Boolean).join(" · ") || "—"}
                {s.topics && s.topics.length > 0 && ` · ${s.topics.length} teme`}
              </div>
            </div>
            <div className={styles.itemActions}>
              <Link className={styles.btnGhost} href={`/admin/speakeri/${s.id}`}>
                Editează
              </Link>
              <form action={deleteSpeaker}>
                <input type="hidden" name="id" value={s.id} />
                <button className={styles.btnDanger} type="submit">
                  Șterge
                </button>
              </form>
            </div>
          </div>
        ))}
      </div>
    </>
  );
}
