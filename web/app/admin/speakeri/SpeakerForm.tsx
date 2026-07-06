"use client";

import Link from "next/link";
import { SPEAKER_STATUSES } from "./statuses";
import styles from "./speakeri.module.css";

export type SpeakerInitial = {
  id?: number;
  name?: string;
  email?: string | null;
  phone?: string | null;
  status?: string | null;
  notes?: string | null;
  topics?: string[] | null;
};

export default function SpeakerForm({
  action,
  initial,
}: {
  action: (formData: FormData) => void | Promise<void>;
  initial?: SpeakerInitial;
}) {
  return (
    <form className={styles.form} action={action}>
      {initial?.id != null && <input type="hidden" name="id" value={initial.id} />}

      <label className={styles.label}>
        Nume
        <input className={styles.input} name="name" type="text" required defaultValue={initial?.name ?? ""} autoFocus />
      </label>

      <div className={styles.row}>
        <label className={styles.label}>
          Email
          <input className={styles.input} name="email" type="email" defaultValue={initial?.email ?? ""} />
        </label>
        <label className={styles.label}>
          Telefon
          <input className={styles.input} name="phone" type="text" defaultValue={initial?.phone ?? ""} />
        </label>
      </div>

      <label className={styles.label}>
        Status
        <select className={styles.input} name="status" defaultValue={initial?.status ?? "MID"}>
          {SPEAKER_STATUSES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
      </label>

      <label className={styles.label}>
        Teme (una pe linie)
        <textarea className={styles.textarea} name="topics" rows={5} defaultValue={(initial?.topics ?? []).join("\n")} />
      </label>

      <label className={styles.label}>
        Notițe
        <textarea className={styles.textarea} name="notes" rows={3} defaultValue={initial?.notes ?? ""} />
      </label>

      <div className={styles.formActions}>
        <button className={styles.btnPrimary} type="submit">
          Salvează
        </button>
        <Link className={styles.btnGhost} href="/admin/speakeri">
          Anulează
        </Link>
      </div>
    </form>
  );
}
