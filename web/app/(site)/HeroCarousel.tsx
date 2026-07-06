"use client";

import { useEffect, useState } from "react";

export default function HeroCarousel({ images }: { images: string[] }) {
  const [cur, setCur] = useState(0);

  useEffect(() => {
    if (images.length < 2) return;
    const id = setInterval(() => setCur((c) => (c + 1) % images.length), 4500);
    return () => clearInterval(id);
  }, [images.length]);

  return (
    <div className="hero-slides">
      {images.map((img, i) => (
        <div
          key={i}
          className={`hero-slide${i === cur ? " active" : ""}`}
          style={{ backgroundImage: `url('${img}')` }}
        />
      ))}
    </div>
  );
}
