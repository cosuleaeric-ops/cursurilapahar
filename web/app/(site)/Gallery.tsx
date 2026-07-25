"use client";

import { useEffect, useRef, useState } from "react";

// Slider + lightbox portate din assets/js/main.js:236-319.
// `lightbox` e opțional pentru că #galleryLightbox există DOAR în index.php:443-450;
// pe parteneri.php nu e randat, deci garda `if (galleryLightbox)` din main.js:287
// e falsă și click-ul pe o poză nu face nimic.
export default function Gallery({ images, lightbox = true }: { images: string[]; lightbox?: boolean }) {
  const track = useRef<HTMLDivElement>(null);
  const idx = useRef(1); // 1 = prima poză reală (main.js:253)
  const busy = useRef(false);
  const [current, setCurrent] = useState<number | null>(null);

  const n = images.length;
  // main.js:244-251: ultima poză clonată la început, prima la final → buclă infinită.
  const slides = n ? [images[n - 1], ...images, images[0]] : [];

  // main.js:256: lățimea unui item + cei 10px de gap din style.css:1317.
  const itemW = () => (track.current?.querySelector<HTMLElement>(".gallery-item")?.offsetWidth ?? 0) + 10;

  const jumpTo = (i: number) => {
    const el = track.current;
    if (!el) return;
    el.style.transition = "none";
    el.style.transform = `translateX(${-i * itemW()}px)`;
  };
  const slideTo = (i: number) => {
    const el = track.current;
    if (!el) return;
    el.style.transition = "transform 0.4s cubic-bezier(0.4,0,0.2,1)";
    el.style.transform = `translateX(${-i * itemW()}px)`;
  };

  // main.js:274 — poziția inițială pe prima poză reală.
  useEffect(() => {
    const raf = requestAnimationFrame(() => jumpTo(1));
    return () => cancelAnimationFrame(raf);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // main.js:268-273: la finalul tranziției, dacă suntem pe o clonă, sărim
  // silențios pe omologul real; abia atunci se deblochează butoanele.
  const onTransitionEnd = (e: React.TransitionEvent<HTMLDivElement>) => {
    if (e.target !== e.currentTarget) return; // ignoră tranziția de hover a imaginilor
    if (idx.current === n + 1) {
      idx.current = 1;
      jumpTo(1);
    } else if (idx.current === 0) {
      idx.current = n;
      jumpTo(n);
    }
    busy.current = false;
  };

  // main.js:276-283: exact un item per click, blocat cât durează tranziția.
  const step = (dir: number) => {
    if (busy.current) return;
    busy.current = true;
    idx.current += dir;
    slideTo(idx.current);
  };

  const open = current !== null;

  // main.js:296-318: cât e deschis lightbox-ul, body-ul nu mai derulează, iar
  // săgețile / Escape navighează circular.
  useEffect(() => {
    if (!open) return;
    document.body.style.overflow = "hidden";
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setCurrent(null);
      if (e.key === "ArrowLeft") setCurrent((c) => (c === null ? c : (c - 1 + n) % n));
      if (e.key === "ArrowRight") setCurrent((c) => (c === null ? c : (c + 1) % n));
    };
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
    };
  }, [open, n]);

  const nav = (dir: number) => setCurrent((c) => (c === null ? c : (c + dir + n) % n));

  return (
    <>
      <div className="gallery-slider-wrap">
        <button className="gslider-btn gslider-prev" onClick={() => step(-1)} aria-label="Anterior">
          &#8249;
        </button>
        <div className="gallery-slider" ref={track} onTransitionEnd={onTransitionEnd}>
          {slides.map((img, i) => {
            const clone = i === 0 || i === slides.length - 1;
            const real = i - 1;
            return (
              <div
                className={clone ? "gallery-item gallery-clone" : "gallery-item"}
                key={i}
                aria-hidden={clone || undefined}
                // main.js:290 leagă click-ul doar pe `.gallery-item:not(.gallery-clone)`
                onClick={lightbox && !clone ? () => setCurrent(real) : undefined}
              >
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={img} alt="Cursuri la Pahar" loading="lazy" />
              </div>
            );
          })}
        </div>
        <button className="gslider-btn gslider-next" onClick={() => step(1)} aria-label="Următor">
          &#8250;
        </button>
      </div>

      {lightbox && current !== null && (
        // main.js:313 — se închide DOAR la click pe fundal, nu și pe imagine.
        <div
          className="gallery-lightbox active"
          onClick={(e) => {
            if (e.target === e.currentTarget) setCurrent(null);
          }}
        >
          <button className="lightbox-close" aria-label="Închide" onClick={() => setCurrent(null)}>
            &times;
          </button>
          <button className="lightbox-prev" aria-label="Anteriorul" onClick={() => nav(-1)}>
            &#8249;
          </button>
          <button className="lightbox-next" aria-label="Următorul" onClick={() => nav(1)}>
            &#8250;
          </button>
          <div className="lightbox-img-wrap">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={images[current]} alt="" />
          </div>
        </div>
      )}
    </>
  );
}
