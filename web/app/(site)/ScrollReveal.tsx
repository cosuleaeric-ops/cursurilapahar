"use client";

import { useEffect } from "react";
import { usePathname } from "next/navigation";

// Port 1:1 al lui initReveal din assets/js/main.js:78-97: aceleași selectoare,
// titlul din #cursuri e exclus, threshold 0.08, iar .visible se pune o singură
// dată (unobserve). Clasa .reveal se adaugă din JS, deci fără JS nu se ascunde
// nimic — exact ca pe PHP. main.js e încărcat pe toate paginile publice, așa că
// stă în layout; pathname re-rulează observer-ul la navigarea client-side.
const SELECTOR =
  ".step, .collab-card, .faq-item, .section-title, .section-subtitle, .newsletter-form, .contact-form";

export default function ScrollReveal() {
  const pathname = usePathname();

  useEffect(() => {
    const targets = Array.from(document.querySelectorAll<HTMLElement>(SELECTOR)).filter(
      (el) => !(el.matches(".section-title") && el.closest("#cursuri"))
    );
    if (!targets.length) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            e.target.classList.add("visible");
            observer.unobserve(e.target);
          }
        });
      },
      { threshold: 0.08 }
    );

    targets.forEach((el) => {
      el.classList.add("reveal");
      observer.observe(el);
    });

    return () => observer.disconnect();
  }, [pathname]);

  return null;
}
