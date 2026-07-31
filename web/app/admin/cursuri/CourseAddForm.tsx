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
  speaker_id: number;
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
  onPick,
  onBlur,
  required,
}: {
  id: string;
  name?: string;
  label: string;
  options: Opt[];
  value: string;
  onChange: (v: string) => void;
  onPick?: (o: Opt) => void;
  onBlur?: () => void;
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
        onBlur={onBlur}
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
                onPick?.(o);
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

// clpFormatDateRo() (admin-course-form.js:6-13) — luna cu majusculă: „28 Iulie 2026".
const RO_MONTHS = ["", "ianuarie", "februarie", "martie", "aprilie", "mai", "iunie", "iulie", "august", "septembrie", "octombrie", "noiembrie", "decembrie"];
function formatDateRo(ymd: string): string {
  if (!ymd) return "";
  const p = ymd.split("-");
  if (p.length !== 3) return ymd;
  const m = RO_MONTHS[parseInt(p[1], 10)] ?? "";
  return `${parseInt(p[2], 10)} ${m ? m.charAt(0).toUpperCase() + m.slice(1) : ""} ${p[0]}`;
}

export default function CourseAddForm({
  action,
  speakers,
  locations,
  edit,
  error,
  year,
  month,
}: {
  action: (fd: FormData) => void | Promise<void>;
  speakers: Opt[];
  locations: Opt[];
  edit?: CourseEdit | null;
  error?: string;
  year: number;
  month: number;
}) {
  const [title, setTitle] = useState(edit?.title ?? "");
  const [dateRaw, setDateRaw] = useState(edit?.date_raw ?? "");
  const [time, setTime] = useState(edit?.time ?? "");
  const [speaker, setSpeaker] = useState(edit?.speaker_name ?? "");
  const [location, setLocation] = useState(edit?.location ?? "");
  const [ltUrl, setLtUrl] = useState(edit?.livetickets_url ?? "");
  const [imageUrl, setImageUrl] = useState(edit?.image_url ?? "");
  const [msg, setMsg] = useState<{ text: string; tone: "muted" | "ok" | "err" } | null>(null);

  // Se trimite doar id-ul speakerului (cursuri-tab.php:39-40 — inputul de text nu are `name`).
  // La editare pornește din `speaker_id`-ul salvat, ca în PHP; dacă lipsește, îl rezolvă
  // clpResolveSpeakerFromInput() la blur/submit, din numele afișat.
  const speakerIdRef = useRef<HTMLInputElement>(null);
  const initialSpeakerId = edit?.speaker_id ?? 0;

  const setSpeakerId = (v: number) => {
    if (speakerIdRef.current) speakerIdRef.current.value = v ? String(v) : "";
    return v;
  };

  // clpResolveSpeakerFromInput(): potrivire exactă pe nume, altfel o singură potrivire parțială.
  function resolveSpeaker(): number {
    const q = speaker.trim().toLowerCase();
    if (!q) return setSpeakerId(0);
    const exact = speakers.find((s) => s.name.toLowerCase() === q);
    if (exact) return setSpeakerId(exact.id);
    const partial = speakers.filter((s) => s.name.toLowerCase().includes(q));
    if (partial.length === 1) {
      setSpeaker(partial[0].name);
      return setSpeakerId(partial[0].id);
    }
    return setSpeakerId(0);
  }

  async function pullImage(force = false) {
    const url = ltUrl.trim();
    if (!url) {
      setImageUrl("");
      setMsg(null);
      return;
    }
    if (imageUrl && !force) return;
    setMsg({ text: "Se preia imaginea…", tone: "muted" });
    const res = await lookupTicketMeta(url);
    if (res.success) {
      setImageUrl(res.data.image_url);
      if (!location.trim() && res.data.location) setLocation(res.data.location);
      setMsg({
        text: res.data.image_url ? "✓ Imagine preluată." : "Link valid, dar nu s-a găsit imagine.",
        tone: "ok",
      });
    } else {
      setMsg({ text: res.message, tone: "err" });
    }
  }

  // admin-course-form.js:232-234 — la încărcarea paginii, un curs cu link dar fără
  // imagine își preia singur imaginea.
  useEffect(() => {
    if (edit?.livetickets_url && !edit.image_url) void pullImage();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const dateDisplay = formatDateRo(dateRaw);
  // updateCoursePreview(): fără titlu previewul rămâne ascuns, indiferent de imagine.
  const showPreview = title.trim() !== "";

  return (
    <div className="card" id="course-form-card">
      <div className="card-title">{edit ? "Editează curs" : "Adaugă curs"}</div>
      {error && <p style={{ color: "var(--danger)", fontSize: 13, margin: "0 0 12px" }}>{error}</p>}
      {speakers.length === 0 ? (
        <p style={{ color: "var(--text-muted)", margin: 0 }}>
          Adaugă mai întâi speakeri în tab-ul <Link href="/admin/speakeri">Speakeri</Link>.
        </p>
      ) : (
        <form
          action={action}
          className="course-add-form"
          // validateCourseForm() (admin-course-form.js:143-156)
          onSubmit={(e) => {
            if (!resolveSpeaker()) {
              e.preventDefault();
              alert("Alege un speaker din lista de pe tab-ul Speakeri (nume exact).");
              return;
            }
            if (!(COURSE_TIMES as readonly string[]).includes(time)) {
              e.preventDefault();
              alert("Alege ora din listă (17:00, 17:30, 18:00, 18:30 sau 19:00).");
            }
          }}
        >
          {edit && <input type="hidden" name="id" value={edit.id} />}
          <input type="hidden" name="image_url" value={imageUrl} />
          <input type="hidden" name="speaker_id" ref={speakerIdRef} defaultValue={initialSpeakerId || ""} />
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
              label="Speaker"
              options={speakers}
              value={speaker}
              onChange={(v) => {
                setSpeaker(v);
                setSpeakerId(0); // tastarea invalidează id-ul rezolvat (admin-course-form.js:113)
              }}
              onPick={(o) => setSpeakerId(o.id)}
              onBlur={resolveSpeaker}
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
            <div
              style={{
                marginTop: 8,
                fontSize: 13,
                // admin-course-form.js: gri la încărcare, verde la reușită, roșu la eroare.
                color: msg.tone === "ok" ? "var(--success)" : msg.tone === "err" ? "var(--danger)" : "var(--text-muted)",
              }}
            >
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
            {/* cursuri-tab.php:79 - „Anulează" păstrează luna și tabul din navigator. */}
            {edit && (
              <Link href={`/admin/cursuri?year=${year}&month=${month}&ctab=cursuri`} className="btn btn-secondary btn-sm">
                Anulează
              </Link>
            )}
          </div>
        </form>
      )}
    </div>
  );
}
