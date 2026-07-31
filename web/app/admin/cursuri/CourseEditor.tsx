"use client";

import { useEffect, useRef, useState } from "react";
import { COURSE_TIMES } from "./times";

// Editorul de curs, pe secțiuni: în stânga cuprinsul, în dreapta conținutul, iar
// jos o bară de salvare care rămâne pe ecran. Structura urmează felul în care
// platformele de evenimente împart formularul — întâi ce e cursul, apoi când și
// unde, apoi cum arată, apoi ce se vinde, iar publicarea la final.

export type Opt = { id: number; name: string };

export type CourseData = {
  id: number;
  title: string;
  date_raw: string;
  time: string;
  speaker_id: number;
  speaker_name: string;
  location: string;
  livetickets_url: string;
  description: string;
  image_url: string;
  image_landscape_url: string;
  active: boolean;
  slug: string;
  tipuri: { name: string; price: number; stock: number; description: string }[];
};

const SECTIUNI = [
  { id: "despre", nr: "01", label: "Despre curs", hint: "Titlu, speaker, descriere" },
  { id: "cand", nr: "02", label: "Când și unde", hint: "Data, ora, locația" },
  { id: "imagini", nr: "03", label: "Imagini", hint: "Afiș și banner" },
  { id: "bilete", nr: "04", label: "Bilete", hint: "Tipuri, prețuri, stoc" },
  { id: "publicare", nr: "05", label: "Publicare", hint: "Vizibilitate și link" },
];

function Combobox({
  label,
  options,
  value,
  onChange,
  onPick,
  placeholderHint,
}: {
  label: string;
  options: Opt[];
  value: string;
  onChange: (v: string) => void;
  onPick?: (o: Opt) => void;
  placeholderHint?: string;
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
    <div className={`ce-field ce-combo${open ? " ce-combo--open" : ""}`} ref={box}>
      <label>{label}</label>
      {/* Lista se ancorează de input, nu de tot câmpul: altfel pornea sub textul
          ajutător și acoperea eticheta câmpului următor. */}
      <div className="ce-combo-anchor">
        <input
          type="text"
          autoComplete="off"
          value={value}
          onChange={(e) => {
            onChange(e.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
        />
        {open && matches.length > 0 && (
          <div className="ce-suggest">
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
              </button>
            ))}
          </div>
        )}
      </div>
      {placeholderHint && <small>{placeholderHint}</small>}
    </div>
  );
}

function ImagePicker({
  name,
  titlu,
  descriere,
  raport,
  existent,
}: {
  name: string;
  titlu: string;
  descriere: string;
  raport: "portret" | "landscape";
  existent: string;
}) {
  const [preview, setPreview] = useState(existent);

  return (
    <div className={`ce-img ce-img--${raport}`}>
      <div className="ce-img-head">
        <strong>{titlu}</strong>
        <span>{descriere}</span>
      </div>
      <label className="ce-drop">
        <input
          type="file"
          name={name}
          accept="image/*"
          onChange={(e) => {
            const f = e.target.files?.[0];
            if (f) setPreview(URL.createObjectURL(f));
          }}
        />
        {preview ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={preview} alt="" />
        ) : (
          <span className="ce-drop-gol">Alege o imagine</span>
        )}
      </label>
    </div>
  );
}

export default function CourseEditor({
  action,
  speakers,
  locations,
  data,
  error,
  saved,
}: {
  action: (fd: FormData) => void | Promise<void>;
  speakers: Opt[];
  locations: Opt[];
  data: CourseData | null;
  error?: string;
  saved?: boolean;
}) {
  const nou = !data?.id;
  const [title, setTitle] = useState(data?.title ?? "");
  const [speaker, setSpeaker] = useState(data?.speaker_name ?? "");
  const [speakerId, setSpeakerId] = useState(data?.speaker_id ?? 0);
  const [location, setLocation] = useState(data?.location ?? "");
  const [activ, setActiv] = useState(data?.active ?? false);
  const [tipuri, setTipuri] = useState<
    { name: string; price: number; stock: number; description: string; bundle: number; max: number }[]
  >(
    data?.tipuri?.length
      ? data.tipuri.map((t) => ({ ...t, bundle: 1, max: 10 }))
      : [
          { name: "Bilet standard", price: 50, stock: 55, description: "", bundle: 1, max: 10 },
          { name: "Bilet student", price: 30, stock: 25, description: "", bundle: 1, max: 10 },
          { name: "Bilet 1+1 GRATIS", price: 50, stock: 8, description: "", bundle: 2, max: 1 },
        ],
  );
  const [activa, setActiva] = useState("despre");

  // Cuprinsul din stânga se aprinde după secțiunea aflată în dreptul ecranului.
  useEffect(() => {
    const obs = new IntersectionObserver(
      (entries) => {
        const vizibil = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
        if (vizibil) setActiva(vizibil.target.id);
      },
      { rootMargin: "-20% 0px -70% 0px" },
    );
    for (const s of SECTIUNI) {
      const el = document.getElementById(s.id);
      if (el) obs.observe(el);
    }
    return () => obs.disconnect();
  }, []);

  const setTip = (i: number, patch: Partial<(typeof tipuri)[number]>) =>
    setTipuri((t) => t.map((x, j) => (i === j ? { ...x, ...patch } : x)));

  function resolveSpeaker(): number {
    const q = speaker.trim().toLowerCase();
    if (!q) return 0;
    const exact = speakers.find((s) => s.name.toLowerCase() === q);
    if (exact) return exact.id;
    const partial = speakers.filter((s) => s.name.toLowerCase().includes(q));
    return partial.length === 1 ? partial[0].id : 0;
  }

  return (
    <form
      action={action}
      className="ce"
      onSubmit={(e) => {
        const sid = speakerId || resolveSpeaker();
        if (!sid) {
          e.preventDefault();
          alert("Alege un speaker din listă.");
          return;
        }
        setSpeakerId(sid);
      }}
    >
      {data?.id ? <input type="hidden" name="id" value={data.id} /> : null}
      <input type="hidden" name="speaker_id" value={speakerId || resolveSpeaker() || ""} />

      <aside className="ce-nav">
        <div className="ce-nav-title">{nou ? "Curs nou" : "Editezi cursul"}</div>
        <ol>
          {SECTIUNI.map((s) => (
            <li key={s.id}>
              <a href={`#${s.id}`} className={activa === s.id ? "on" : ""}>
                <span className="ce-nr">{s.nr}</span>
                <span>
                  <strong>{s.label}</strong>
                  <em>{s.hint}</em>
                </span>
              </a>
            </li>
          ))}
        </ol>
      </aside>

      <div className="ce-body">
        {error && <div className="ce-alert ce-alert--err">{error}</div>}
        {saved && <div className="ce-alert ce-alert--ok">Salvat.</div>}

        <section id="despre" className="ce-sec">
          <h2>
            <span className="ce-nr">01</span> Despre curs
          </h2>
          <div className="ce-field">
            <label>Numele cursului</label>
            <input name="title" value={title} onChange={(e) => setTitle(e.target.value)} required />
            <small>Apare pe card, pe pagina cursului și pe bilet.</small>
          </div>
          <Combobox
            label="Speaker"
            options={speakers}
            value={speaker}
            onChange={(v) => {
              setSpeaker(v);
              setSpeakerId(0);
            }}
            onPick={(o) => setSpeakerId(o.id)}
            placeholderHint="Alege din lista de speakeri."
          />
          <div className="ce-field">
            <label>Descriere</label>
            <textarea name="description" rows={12} defaultValue={data?.description ?? ""} />
            <small>Se acceptă HTML simplu: paragrafe, bold, liste, linkuri.</small>
          </div>
        </section>

        <section id="cand" className="ce-sec">
          <h2>
            <span className="ce-nr">02</span> Când și unde
          </h2>
          <div className="ce-row">
            <div className="ce-field">
              <label>Data</label>
              <input type="date" name="date_raw" defaultValue={data?.date_raw ?? ""} required />
            </div>
            <div className="ce-field">
              <label>Ora de început</label>
              <select name="time" defaultValue={data?.time ?? ""} required>
                <option value=""></option>
                {COURSE_TIMES.map((t) => (
                  <option key={t} value={t}>
                    {t}
                  </option>
                ))}
              </select>
              <small>Accesul se face cu 30 de minute înainte.</small>
            </div>
          </div>
          <Combobox
            label="Locația"
            options={locations}
            value={location}
            onChange={setLocation}
            placeholderHint="Numele localului și orașul, ex. „Mojo Club, București”."
          />
          <input type="hidden" name="location" value={location} />
        </section>

        <section id="imagini" className="ce-sec">
          <h2>
            <span className="ce-nr">03</span> Imagini
          </h2>
          <div className="ce-imgs">
            <ImagePicker
              name="image_portrait"
              titlu="Afiș (portret)"
              descriere="Pe card și pe pagina cursului. Recomandat 3:4."
              raport="portret"
              existent={data?.image_url ?? ""}
            />
            <ImagePicker
              name="image_landscape"
              titlu="Banner (landscape)"
              descriere="Pentru share pe social și antet. Recomandat 16:9."
              raport="landscape"
              existent={data?.image_landscape_url ?? ""}
            />
          </div>
        </section>

        <section id="bilete" className="ce-sec">
          <h2>
            <span className="ce-nr">04</span> Bilete
          </h2>
          {nou ? (
            <>
              <p className="ce-note">
                Se creează odată cu cursul, cu serii proprii și pool numerotat. Le poți schimba oricând după.
              </p>
              <div className="ce-tipuri">
                {tipuri.map((t, i) => (
                  <div className="ce-tip" key={i}>
                    <div className="ce-row">
                      <div className="ce-field">
                        <label>Nume</label>
                        <input name="tip_name" value={t.name} onChange={(e) => setTip(i, { name: e.target.value })} />
                      </div>
                      <div className="ce-field ce-field--mic">
                        <label>Preț</label>
                        <input
                          name="tip_price"
                          type="number"
                          step="0.01"
                          value={t.price}
                          onChange={(e) => setTip(i, { price: Number(e.target.value) })}
                        />
                      </div>
                      <div className="ce-field ce-field--mic">
                        <label>Stoc</label>
                        <input
                          name="tip_stock"
                          type="number"
                          min={1}
                          value={t.stock}
                          onChange={(e) => setTip(i, { stock: Number(e.target.value) })}
                        />
                      </div>
                      <button
                        type="button"
                        className="ce-x"
                        onClick={() => setTipuri((x) => x.filter((_, j) => j !== i))}
                        title="Scoate"
                      >
                        ×
                      </button>
                    </div>
                    <div className="ce-row">
                      <div className="ce-field ce-field--mic">
                        <label>Persoane</label>
                        <input
                          name="tip_bundle"
                          type="number"
                          min={1}
                          value={t.bundle}
                          onChange={(e) => setTip(i, { bundle: Number(e.target.value) })}
                        />
                        <small>{t.bundle > 1 ? `intră ${t.bundle} pe un bilet` : "un bilet, o persoană"}</small>
                      </div>
                      <div className="ce-field ce-field--mic">
                        <label>Max / comandă</label>
                        <input
                          name="tip_max"
                          type="number"
                          min={1}
                          value={t.max}
                          onChange={(e) => setTip(i, { max: Number(e.target.value) })}
                        />
                        <small>cât poate lua cineva odată</small>
                      </div>
                      <div className="ce-field">
                        <label>Scurtă descriere</label>
                        <input
                          name="tip_description"
                          value={t.description}
                          onChange={(e) => setTip(i, { description: e.target.value })}
                        />
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              <button
                type="button"
                className="ce-add"
                onClick={() =>
                  setTipuri((t) => [...t, { name: "", price: 50, stock: 20, description: "", bundle: 1, max: 10 }])
                }
              >
                + Încă un tip de bilet
              </button>
            </>
          ) : (
            <>
              <p className="ce-note">
                Cursul are {data?.tipuri.length ?? 0} tipuri de bilete. Seriile, programarea vânzării, pachetele și
                codurile de reducere se editează în pagina lor.
              </p>
              <a className="ce-link" href={`/admin/cursuri/${data?.id}/bilete`}>
                Deschide biletele cursului →
              </a>
            </>
          )}
        </section>

        <section id="publicare" className="ce-sec">
          <h2>
            <span className="ce-nr">05</span> Publicare
          </h2>
          <label className="ce-check">
            <input type="checkbox" name="active" checked={activ} onChange={(e) => setActiv(e.target.checked)} />
            <span>
              <strong>Curs vizibil pe site</strong>
              <em>Nebifat, rămâne ciornă și nu apare nicăieri.</em>
            </span>
          </label>
          {data?.slug && (
            <div className="ce-field">
              <label>Adresa paginii</label>
              <a className="ce-link" href={`/curs/${data.slug}`} target="_blank" rel="noopener">
                cursurilapahar.ro/curs/{data.slug}
              </a>
            </div>
          )}
        </section>
      </div>

      <div className="ce-bar">
        <span>{title || "Curs fără titlu"}</span>
        <div>
          <a href="/admin/cursuri" className="ce-cancel">
            Renunță
          </a>
          <button type="submit" className="ce-save">
            {nou ? "Creează cursul" : "Salvează"}
          </button>
        </div>
      </div>
    </form>
  );
}
