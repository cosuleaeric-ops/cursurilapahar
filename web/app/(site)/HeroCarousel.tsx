"use client";

import { useEffect, useState } from "react";

export type HeroSlide = { src: string; pos: string; zoom: number };

export default function HeroCarousel({ slides }: { slides: HeroSlide[] }) {
  const [cur, setCur] = useState(0);

  useEffect(() => {
    if (slides.length < 2) return;
    const id = setInterval(() => setCur((c) => (c + 1) % slides.length), 4500);
    return () => clearInterval(id);
  }, [slides.length]);

  return (
    <div className="hero-slides">
      {slides.map((sl, i) => (
        <div
          key={i}
          className={`hero-slide${i === cur ? " active" : ""}`}
          style={
            {
              backgroundImage: `url('${sl.src}')`,
              "--hero-pos": sl.pos,
              "--hero-zoom": sl.zoom,
            } as React.CSSProperties
          }
        />
      ))}
    </div>
  );
}
