"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { COURSE_TIMES } from "./times";
import { lookupTicketMeta } from "./actions";

// Port din admin/partials/cursuri-tab.php + admin/assets/js/admin-course-form.js:
// formularul inline de adăugare/editare, cu combobox pentru speaker și locație,
// preluarea imaginii din linkul de bilete și previzualizarea cardului.

export type CourseEdit = {
  id: number;
  title: string;
  date_raw: string;
  time: string;
  speaker_name: string;
  location: string;
  livetickets_url: string;
  image_url: string;
};

type Opt = { id: number; name: string; status?: string | null };

function Combobox({
  id,
  name,
  label,
  options,
  value,
  onChange,
  required,
}: {
  id: string;
  name: string;
  label: string;
  options: Opt[];
  value: string;
  onChange: (v: string) => void;
  required?: boolean;
}) {
  const [open, setOpen] = useState(false);
  const box = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const onDoc = (e: MouseEvent) => {
      if (box.current && !box.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, []);

  const q = value.trim().toLowerCase();
  const matches = options.filter((o) => !q || o.name.toLowerCase().includes(q));

  return (
    <div className="form-group speaker-combobox" ref={box} style={{ position: "relative" }}>
      <label htmlFor={id}>{label}</label>
      <input
        type="text"
        id={id}
        name={name}
        autoComplete="off"
        required={required}
        value={value}
        onChange={(e) => {
          onChange(e.target.value);
          setOpen(true);
        }}
        onFocus={() => setOpen(true)}
      />
      {open && matches.length > 0 && (
        <div className="speaker-suggestions">
          {matches.map((o) => (
            <button
              type="button"
              key={o.id}
              onMouseDown={(e) => {
                e.preventDefault();
                onChange(o.name);
                setOpen(false);
              }}
            >
              {o.name}
              {o.status ? ` (${o.status})` : ""}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

export default function CourseAddForm({
  action,
  speakers,
  locations,
  edit,
  error,
}: {
  action: (fd: FormData) => void | Promise<void>;
  speakers: Opt[];
  locations: Opt[];
  edit?: CourseEdit | null;
  error?: string;
}) {
  const [title, setTitle] = useState(edit?.title ?? "");
  const [dateRaw, setDateRaw] = useState(edit?.date_raw ?? "");
  const [time, setTime] = useState(edit?.time ?? "");
  const [speaker, setSpeaker] = useState(edit?.speaker_name ?? "");
  const [location, setLocation] = useState(edit?.location ?? "");
  const [ltUrl, setLtUrl] = useState(edit?.livetickets_url ?? "");
  const [imageUrl, setImageUrl] = useState(edit?.image_url ?? "");
  const [msg, setMsg] = useState<{ text: string; ok: boolean } | null>(null);

  async function pullImage(force = false) {
    const url = ltUrl.trim();
    if (!url) {
      setImageUrl("");
      setMsg(null);
      return;
    }
    if (imageUrl && !force) return;
    setMsg({ text: "Se preia imaginea…", ok: true });
    const res = await lookupTicketMeta(url);
    if (res.success) {
      setImageUrl(res.data.image_url);
      if (!location.trim() && res.data.location) setLocation(res.data.location);
      setMsg({ text: res.data.image_url ? "✓ Imagine preluată." : "Link valid, dar nu s-a găsit imagine.", ok: true });
    } else {
      setMsg({ text: res.message, ok: false });
    }
  }

  const dateDisplay = dateRaw
    ? new Intl.DateTimeFormat("ro-RO", { day: "numeric", month: "long", year: "numeric" }).format(
        new Date(`${dateRaw}T12:00:00`),
      )
    : "";
  const showPreview = title.trim() !== "" || imageUrl !== "";

  return (
    <div className="card" id="course-form-card">
      <div className="card-title">{edit ? "Editează curs" : "Adaugă curs"}</div>
      {error && <p style={{ color: "var(--danger)", fontSize: 13, margin: "0 0 12px" }}>{error}</p>}
      {speakers.length === 0 ? (
        <p style={{ color: "var(--text-muted)", margin: 0 }}>
          Adaugă mai întâi speakeri în tab-ul <Link href="/admin/speakeri">Speakeri</Link>.
        </p>
      ) : (
        <form action={action} className="course-add-form">
          {edit && <input type="hidden" name="id" value={edit.id} />}
          <input type="hidden" name="image_url" value={imageUrl} />
          <div className="course-add-fields">
            <div className="form-group">
              <label htmlFor="f_title">Nume curs</label>
              <input
                type="text"
                name="title"
                id="f_title"
                required
                value={title}
                onChange={(e) => setTitle(e.target.value)}
              />
            </div>
            <div className="form-group">
              <label htmlFor="f_date_raw">Dată</label>
              <input
                type="date"
                name="date_raw"
                id="f_date_raw"
                required
                value={dateRaw}
                onChange={(e) => setDateRaw(e.target.value)}
              />
            </div>
            <div className="form-group">
              <label htmlFor="f_time">Oră</label>
              <select name="time" id="f_time" required value={time} onChange={(e) => setTime(e.target.value)}>
                <option value=""></option>
                {COURSE_TIMES.map((t) => (
                  <option key={t} value={t}>
                    {t}
                  </option>
                ))}
              </select>
            </div>
            <Combobox
              id="f_speaker_input"
              name="speaker_name"
              label="Speaker"
              options={speakers}
              value={speaker}
              onChange={setSpeaker}
              required
            />
            <Combobox
              id="f_location_input"
              name="location"
              label="Locație"
              options={locations}
              value={location}
              onChange={setLocation}
            />
            <div className="form-group" style={{ position: "relative" }}>
              <label htmlFor="f_lt_url">Link bilete (LiveTickets / iaBilet)</label>
              <input
                type="url"
                name="livetickets_url"
                id="f_lt_url"
                style={{ paddingRight: 26 }}
                value={ltUrl}
                onChange={(e) => setLtUrl(e.target.value)}
                onBlur={() => pullImage()}
              />
              <button
                type="button"
                onClick={() => pullImage(true)}
                title="Preia imaginea din nou"
                style={{
                  position: "absolute",
                  right: 4,
                  bottom: 4,
                  width: 20,
                  height: 20,
                  padding: 0,
                  border: 0,
                  background: "none",
                  cursor: "pointer",
                  color: "var(--text-muted)",
                  fontSize: 14,
                  lineHeight: 1,
                }}
              >
                ↻
              </button>
            </div>
          </div>

          {msg && (
            <div style={{ marginTop: 8, fontSize: 13, color: msg.ok ? "var(--text-muted)" : "var(--danger)" }}>
              {msg.text}
            </div>
          )}

          <div className="course-preview" id="coursePreview" style={{ display: showPreview ? "flex" : "none" }}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            {imageUrl ? <img src={imageUrl} alt="" /> : null}
            <div className="course-preview-body">
              <div className="course-preview-title">{title}</div>
              <div className="course-preview-meta">
                {[dateDisplay, time, speaker, location].filter(Boolean).join(" · ")}
              </div>
            </div>
          </div>

          <div style={{ display: "flex", gap: 8, marginTop: 10, flexWrap: "wrap" }}>
            <button type="submit" className="btn btn-primary btn-sm">
              {edit ? "Salvează" : "Adaugă cursul"}
            </button>
            {edit && (
              <Link href="/admin/cursuri" className="btn btn-secondary btn-sm">
                Anulează
              </Link>
            )}
          </div>
        </form>
      )}
    </div>
  );
}
