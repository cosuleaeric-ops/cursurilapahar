"use client";

import Link from "next/link";
import { COURSE_TIMES } from "./times";

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
    <form action={action} className="crm-form" style={{ maxWidth: 580 }}>
      {initial?.id != null && <input type="hidden" name="id" value={initial.id} />}

      <div className="form-group">
        <label>Titlu</label>
        <input name="title" type="text" required defaultValue={initial?.title ?? ""} autoFocus />
      </div>

      <div style={{ display: "flex", gap: 14 }}>
        <div className="form-group" style={{ flex: 1 }}>
          <label>Data</label>
          <input name="date" type="date" required defaultValue={initial?.date ?? ""} />
        </div>
        <div className="form-group" style={{ flex: 1 }}>
          <label>Ora</label>
          <select name="time" defaultValue={initial?.time ?? "19:00"}>
            {COURSE_TIMES.map((t) => (
              <option key={t} value={t}>
                {t}
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className="form-group">
        <label>Locație</label>
        <input name="location" type="text" defaultValue={initial?.location ?? ""} />
      </div>

      <div className="form-group">
        <label>Link Livetickets</label>
        <input name="livetickets_url" type="url" defaultValue={initial?.livetickets_url ?? ""} />
      </div>

      <div className="form-group">
        <label>Link imagine</label>
        <input name="image_url" type="url" defaultValue={initial?.image_url ?? ""} />
      </div>

      <div style={{ display: "flex", gap: 22, margin: "6px 0 18px" }}>
        <label style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 13, fontWeight: 500 }}>
          <input type="checkbox" name="active" defaultChecked={initial?.active ?? false} />
          Activ (afișat pe site)
        </label>
        <label style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 13, fontWeight: 500 }}>
          <input type="checkbox" name="sold_out" defaultChecked={initial?.sold_out ?? false} />
          Sold out
        </label>
      </div>

      <div style={{ display: "flex", gap: 10 }}>
        <button type="submit" className="btn btn-primary">
          Salvează
        </button>
        <Link className="btn btn-secondary" href="/admin/cursuri">
          Anulează
        </Link>
      </div>
    </form>
  );
}
