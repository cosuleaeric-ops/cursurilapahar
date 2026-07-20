"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { deleteImage, saveImageSelection } from "./actions";

export type LibImage = { url: string; name: string; deletable: boolean };
type Transform = { x: number; y: number; zoom: number };

const DEF: Transform = { x: 50, y: 50, zoom: 100 };

export default function ImaginiManager({
  library,
  heroInit,
  galleryInit,
  transformsInit,
}: {
  library: LibImage[];
  heroInit: string[];
  galleryInit: string[];
  transformsInit: Record<string, Partial<Transform>>;
}) {
  const [hero, setHero] = useState<string[]>(heroInit);
  const [gallery, setGallery] = useState<string[]>(galleryInit);
  const [transforms, setTransforms] = useState<Record<string, Partial<Transform>>>(transformsInit);
  const [dirty, setDirty] = useState(false);
  const [editorUrl, setEditorUrl] = useState<string | null>(null);
  const [dragUrl, setDragUrl] = useState<string | null>(null);
  const [pendingDelete, startDelete] = useTransition();
  const router = useRouter();

  const nameFor = (url: string) => library.find((l) => l.url === url)?.name ?? url.split("/").pop() ?? url;
  const tFor = (url: string): Transform => ({ ...DEF, ...(transforms[url] ?? {}) });

  const lists = { hero: [hero, setHero] as const, gallery: [gallery, setGallery] as const };

  function toggle(target: "hero" | "gallery", url: string) {
    const [list, setList] = lists[target];
    if (list.includes(url)) {
      setList(list.filter((u) => u !== url));
      if (target === "hero") {
        setTransforms(({ [url]: _drop, ...rest }) => rest);
        if (editorUrl === url) setEditorUrl(null);
      }
    } else {
      setList([...list, url]);
    }
    setDirty(true);
  }

  function reorder(target: "hero" | "gallery", overUrl: string) {
    if (!dragUrl || dragUrl === overUrl) return;
    const [list, setList] = lists[target];
    if (!list.includes(dragUrl) || !list.includes(overUrl)) return;
    const next = list.filter((u) => u !== dragUrl);
    next.splice(next.indexOf(overUrl), 0, dragUrl);
    setList(next);
    setDirty(true);
  }

  function setT(url: string, patch: Partial<Transform>) {
    setTransforms((t) => ({ ...t, [url]: { ...tFor(url), ...patch } }));
    setDirty(true);
  }

  const editorT = editorUrl ? tFor(editorUrl) : DEF;

  function Strip({ target }: { target: "hero" | "gallery" }) {
    const [list] = lists[target];
    if (!list.length) {
      return (
        <div className="img-strip" data-target={target}>
          <div className="img-strip-empty">
            {target === "hero" ? "Nicio imagine în slideshow. Adaug-o din Bibliotecă." : "Nicio imagine în galerie. Adaug-o din Bibliotecă."}
          </div>
        </div>
      );
    }
    return (
      <div className="img-strip" data-target={target}>
        {list.map((url, i) => {
          const t = tFor(url);
          return (
            <div
              key={url}
              className="img-strip-item"
              draggable
              onDragStart={() => setDragUrl(url)}
              onDragEnd={() => setDragUrl(null)}
              onDragOver={(e) => {
                e.preventDefault();
                reorder(target, url);
              }}
            >
              <span className="img-strip-badge">{i + 1}</span>
              <img
                src={url}
                alt={nameFor(url)}
                loading="lazy"
                decoding="async"
                style={
                  target === "hero"
                    ? { objectPosition: `${t.x}% ${t.y}%`, transform: `scale(${t.zoom / 100})`, transformOrigin: `${t.x}% ${t.y}%` }
                    : undefined
                }
              />
              <button type="button" className="img-strip-remove" title="Scoate" onClick={() => toggle(target, url)}>
                ✕
              </button>
              {target === "hero" && (
                <button type="button" className="img-strip-cog" title="Poziție & zoom" onClick={() => setEditorUrl(url)}>
                  ⚙
                </button>
              )}
            </div>
          );
        })}
      </div>
    );
  }

  return (
    <>
      <form action={saveImageSelection}>
        {hero.map((u) => (
          <input key={`h-${u}`} type="hidden" name="hero_images" value={u} />
        ))}
        {gallery.map((u) => (
          <input key={`g-${u}`} type="hidden" name="gallery_featured" value={u} />
        ))}
        <input type="hidden" name="hero_transforms" value={JSON.stringify(transforms)} />

        <div className="card img-select-card">
          <div className="card-title">Hero — slideshow pagina principală</div>
          <p className="img-hint">
            Trage pentru a reordona. <strong>Imaginea ① se încarcă instant</strong>; restul rulează în slideshow la fiecare
            4.5s. Adaugă/scoate din Bibliotecă (butonul <span className="img-hint-chip">Hero</span>). Apasă <strong>⚙</strong>{" "}
            pe o imagine ca să-i reglezi poziția și zoom-ul.
          </p>
          <Strip target="hero" />

          {editorUrl && (
            <div className="hero-editor">
              <div className="hero-editor-preview">
                <div
                  className="hero-editor-bg"
                  style={{
                    backgroundImage: `url('${editorUrl}')`,
                    backgroundPosition: `${editorT.x}% ${editorT.y}%`,
                    transform: `scale(${editorT.zoom / 100})`,
                    transformOrigin: `${editorT.x}% ${editorT.y}%`,
                  }}
                />
                <div className="hero-editor-frame"></div>
              </div>
              <div className="hero-editor-controls">
                <div className="hero-editor-head">
                  <span>{nameFor(editorUrl)}</span>
                  <button type="button" className="hero-editor-close" title="Închide" onClick={() => setEditorUrl(null)}>
                    ✕
                  </button>
                </div>
                <label className="hero-slider">
                  Sus ↕ jos <span>{editorT.y}%</span>
                  <input type="range" min={0} max={100} step={1} value={editorT.y} onChange={(e) => setT(editorUrl, { y: +e.target.value })} />
                </label>
                <label className="hero-slider">
                  Stânga ↔ dreapta <span>{editorT.x}%</span>
                  <input type="range" min={0} max={100} step={1} value={editorT.x} onChange={(e) => setT(editorUrl, { x: +e.target.value })} />
                </label>
                <label className="hero-slider">
                  Zoom <span>{editorT.zoom}%</span>
                  <input type="range" min={100} max={200} step={1} value={editorT.zoom} onChange={(e) => setT(editorUrl, { zoom: +e.target.value })} />
                </label>
                <button type="button" className="btn btn-sm" onClick={() => setT(editorUrl, { ...DEF })}>
                  Resetează
                </button>
              </div>
            </div>
          )}
        </div>

        <div className="card img-select-card">
          <div className="card-title">Galerie — sliderul din secțiunea „Galerie"</div>
          <p className="img-hint">
            Trage pentru a reordona. Adaugă/scoate imagini din Bibliotecă (butonul{" "}
            <span className="img-hint-chip img-hint-chip-gal">Galerie</span>).
          </p>
          <Strip target="gallery" />
        </div>

        <div className="img-save-bar">
          <button type="submit" className="btn btn-primary">
            Salvează selecția
          </button>
          {dirty && <span className="img-dirty">● Modificări nesalvate</span>}
        </div>
      </form>

      <div className="card">
        <div className="card-title">Biblioteca — toate imaginile (cele mai noi primele)</div>
        {library.length === 0 ? (
          <p style={{ color: "var(--text-muted)" }}>Nu există imagini.</p>
        ) : (
          <div className="img-lib">
            {library.map((img) => (
              <div className="img-tile" key={img.url}>
                <div className="img-tile-thumb">
                  <img loading="lazy" decoding="async" src={img.url} alt={img.name} />
                  {img.deletable && (
                    <button
                      type="button"
                      className="img-tile-del"
                      title="Șterge imaginea"
                      disabled={pendingDelete}
                      onClick={() => {
                        if (!confirm(`Ștergi imaginea „${img.name}"?`)) return;
                        const fd = new FormData();
                        fd.append("url", img.url);
                        startDelete(async () => {
                          await deleteImage(fd);
                          router.refresh();
                        });
                      }}
                    >
                      ✕
                    </button>
                  )}
                </div>
                <div className="img-tile-chips">
                  <button
                    type="button"
                    className={`img-chip${hero.includes(img.url) ? " is-active" : ""}`}
                    onClick={() => toggle("hero", img.url)}
                  >
                    Hero
                  </button>
                  <button
                    type="button"
                    className={`img-chip img-chip-gal${gallery.includes(img.url) ? " is-active" : ""}`}
                    onClick={() => toggle("gallery", img.url)}
                  >
                    Galerie
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </>
  );
}
