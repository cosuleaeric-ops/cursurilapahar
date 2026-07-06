"use client";

import Link from "next/link";
import { SPEAKER_STATUSES } from "./statuses";

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
    <form action={action} className="crm-form" style={{ maxWidth: 580 }}>
      {initial?.id != null && <input type="hidden" name="id" value={initial.id} />}

      <div className="form-group">
        <label>Nume</label>
        <input name="name" type="text" required defaultValue={initial?.name ?? ""} autoFocus />
      </div>

      <div style={{ display: "flex", gap: 14 }}>
        <div className="form-group" style={{ flex: 1 }}>
          <label>Email</label>
          <input name="email" type="email" defaultValue={initial?.email ?? ""} />
        </div>
        <div className="form-group" style={{ flex: 1 }}>
          <label>Telefon</label>
          <input name="phone" type="text" defaultValue={initial?.phone ?? ""} />
        </div>
      </div>

      <div className="form-group">
        <label>Status</label>
        <select name="status" defaultValue={initial?.status ?? "MID"}>
          {SPEAKER_STATUSES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group">
        <label>Teme (una pe linie)</label>
        <textarea name="topics" rows={5} defaultValue={(initial?.topics ?? []).join("\n")} />
      </div>

      <div className="form-group">
        <label>Notițe</label>
        <textarea name="notes" rows={3} defaultValue={initial?.notes ?? ""} />
      </div>

      <div style={{ display: "flex", gap: 10 }}>
        <button type="submit" className="btn btn-primary">
          Salvează
        </button>
        <Link className="btn btn-secondary" href="/admin/speakeri">
          Anulează
        </Link>
      </div>
    </form>
  );
}
