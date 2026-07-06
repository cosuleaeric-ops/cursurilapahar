"use client";

import { useState } from "react";

type NavLink = { url: string; label: string };

export default function SiteNav({ brand, logo, links }: { brand: string; logo: string; links: NavLink[] }) {
  const [open, setOpen] = useState(false);
  const [first, ...rest] = brand.split(" ");

  return (
    <>
      <nav className="navbar">
        <div className="navbar-inner">
          <a href="/" className="navbar-logo">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={logo} alt={brand} />
            <span className="navbar-brand-text">
              <span>{first}</span>
              <span>{rest.join(" ")}</span>
            </span>
          </a>
          <div className="navbar-links">
            {links.map((l, i) => (
              <a key={i} href={l.url}>
                {l.label}
              </a>
            ))}
          </div>
          <div className="navbar-right">
            <button
              className={`navbar-hamburger${open ? " open" : ""}`}
              onClick={() => setOpen((o) => !o)}
              aria-label="Meniu"
            >
              <span></span>
              <span></span>
              <span></span>
            </button>
          </div>
        </div>
      </nav>

      <div className={`navbar-drawer${open ? " open" : ""}`}>
        {links.map((l, i) => (
          <a key={i} href={l.url} onClick={() => setOpen(false)}>
            {l.label}
          </a>
        ))}
      </div>
    </>
  );
}
