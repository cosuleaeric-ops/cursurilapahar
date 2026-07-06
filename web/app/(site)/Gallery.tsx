"use client";

import { useRef, useState } from "react";

export default function Gallery({ images }: { images: string[] }) {
  const track = useRef<HTMLDivElement>(null);
  const [lightbox, setLightbox] = useState<number | null>(null);

  const scroll = (dir: number) => {
    const el = track.current;
    if (el) el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: "smooth" });
  };

  return (
    <>
      <div className="gallery-slider-wrap">
        <button className="gslider-btn gslider-prev" onClick={() => scroll(-1)} aria-label="Anterior">
          &#8249;
        </button>
        <div className="gallery-slider" ref={track} style={{ overflowX: "auto", scrollbarWidth: "none" }}>
          {images.map((img, i) => (
            <div className="gallery-item" key={i} onClick={() => setLightbox(i)}>
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={img} alt="Cursuri la Pahar" loading="lazy" />
            </div>
          ))}
        </div>
        <button className="gslider-btn gslider-next" onClick={() => scroll(1)} aria-label="Următor">
          &#8250;
        </button>
      </div>

      {lightbox !== null && (
        <div className="gallery-lightbox active" onClick={() => setLightbox(null)}>
          <button className="lightbox-close" aria-label="Închide">
            &times;
          </button>
          <div className="lightbox-img-wrap">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={images[lightbox]} alt="" />
          </div>
        </div>
      )}
    </>
  );
}
