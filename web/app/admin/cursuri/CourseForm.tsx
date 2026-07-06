"use client";

import Link from "next/link";
import { COURSE_TIMES } from "./times";
import styles from "./cursuri.module.css";

export type CourseInitial = {
  id?: number;
  title?: string;
  date?: string | null;
  time?: string | null;
  location?: string | null;
  livetickets_url?: string | null;
  image_url?: string | null;
  active?: boolean;
  sold_out?: boolean;
};

export default function CourseForm({
  action,
  initial,
}: {
  action: (formData: FormData) => void | Promise<void>;
  initial?: CourseInitial;
}) {
  return (
    <form className={styles.form} action={action}>
      {initial?.id != null && <input type="hidden" name="id" value={initial.id} />}

      <label className={styles.label}>
        Titlu
        <input className={styles.input} name="title" type="text" required defaultValue={initial?.title ?? ""} autoFocus />
      </label>

      <div className={styles.row}>
        <label className={styles.label}>
          Data
          <input className={styles.input} name="date" type="date" required defaultValue={initial?.date ?? ""} />
        </label>
        <label className={styles.label}>
          Ora
          <select className={styles.input} name="time" defaultValue={initial?.time ?? "19:00"}>
            {COURSE_TIMES.map((t) => (
              <option key={t} value={t}>
                {t}
              </option>
            ))}
          </select>
        </label>
      </div>

      <label className={styles.label}>
        Locație
        <input className={styles.input} name="location" type="text" defaultValue={initial?.location ?? ""} />
      </label>

      <label className={styles.label}>
        Link Livetickets
        <input className={styles.input} name="livetickets_url" type="url" defaultValue={initial?.livetickets_url ?? ""} />
      </label>

      <label className={styles.label}>
        Link imagine
        <input className={styles.input} name="image_url" type="url" defaultValue={initial?.image_url ?? ""} />
      </label>

      <div className={styles.checks}>
        <label className={styles.check}>
          <input type="checkbox" name="active" defaultChecked={initial?.active ?? false} />
          Activ (afișat pe site)
        </label>
        <label className={styles.check}>
          <input type="checkbox" name="sold_out" defaultChecked={initial?.sold_out ?? false} />
          Sold out
        </label>
      </div>

      <div className={styles.formActions}>
        <button className={styles.btnPrimary} type="submit">
          Salvează
        </button>
        <Link className={styles.btnGhost} href="/admin/cursuri">
          Anulează
        </Link>
      </div>
    </form>
  );
}
