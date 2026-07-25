"use client";

import { useEffect, useState } from "react";
import { vote, trackVoteView, trackVotePageView } from "./actions";
import styles from "./vote.module.css";

export type VoteCourse = {
  id: number;
  legacy_id: string | null;
  name: string;
  emoji: string | null;
  description: string | null;
  likes: number;
};

// voteaza-cursuri.php:302 — aceeași cheie și aceleași ID-uri text ca pe PHP
// (serverul nu deduplică deloc, toată protecția stă aici), ca votul dat pe
// site-ul vechi să rămână marcat după migrare.
const STORAGE_KEY = "clp_voted";
const PAGE_VIEW_KEY = "clp_vote_page_viewed";
const VIEW_KEY = "clp_vote_viewed";

/** ID-ul din data/vote_courses.json (ex. vc_numerologie); doar temele create
 *  după migrare nu au legacy_id și cad pe ID-ul din Neon. */
const voteKey = (c: VoteCourse) => c.legacy_id || String(c.id);

// voteaza-cursuri.php:25-27 și 392 — plural românesc: 1 apreciere, restul aprecieri.
const likesLabel = (n: number) => `${n} ${n === 1 ? "apreciere" : "aprecieri"}`;

/** voteaza-cursuri.php:254-257 — se întoarce în istoric dacă există, altfel acasă. */
export function VoteBackLink() {
  return (
    <a
      href="/"
      onClick={(e) => {
        if (history.length > 1) {
          e.preventDefault();
          history.back();
        }
      }}
      className="page-hero-back"
      style={{ display: "inline-flex", marginBottom: 20 }}
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <path d="M19 12H5M12 5l-7 7 7 7" />
      </svg>
      Înapoi
    </a>
  );
}

export default function VoteList({ courses }: { courses: VoteCourse[] }) {
  const [likes, setLikes] = useState<Record<number, number>>(() =>
    Object.fromEntries(courses.map((c) => [c.id, c.likes]))
  );
  const [voted, setVoted] = useState<Set<string>>(new Set());
  const [open, setOpen] = useState<Set<number>>(new Set());

  // hidratare din localStorage (după mount, ca să nu strice SSR-ul)
  useEffect(() => {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) setVoted(new Set(JSON.parse(raw) as string[]));
    } catch {
      /* ignore */
    }
  }, []);

  // Vizita pe pagină: o dată pe sesiune (ca în JS-ul PHP).
  useEffect(() => {
    try {
      if (sessionStorage.getItem(PAGE_VIEW_KEY)) return;
    } catch {
      return;
    }
    trackVotePageView()
      .then(() => {
        try {
          sessionStorage.setItem(PAGE_VIEW_KEY, "1");
        } catch {
          /* ignore */
        }
      })
      .catch(() => {});
  }, []);

  // Vizualizare per card: la 35% vizibil, o singură dată pe sesiune.
  useEffect(() => {
    const cards = document.querySelectorAll<HTMLElement>("[data-vote-id]");
    if (!cards.length) return;
    let seen: Set<string>;
    try {
      seen = new Set(JSON.parse(sessionStorage.getItem(VIEW_KEY) || "[]") as string[]);
    } catch {
      seen = new Set();
    }
    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting || entry.intersectionRatio < 0.35) continue;
          const id = entry.target.getAttribute("data-vote-id");
          if (!id || seen.has(id)) continue;
          seen.add(id);
          try {
            sessionStorage.setItem(VIEW_KEY, JSON.stringify([...seen]));
          } catch {
            /* ignore */
          }
          trackVoteView(Number(id)).catch(() => {});
          observer.unobserve(entry.target);
        }
      },
      { threshold: [0.35] }
    );
    cards.forEach((c) => observer.observe(c));
    return () => observer.disconnect();
  }, []);

  function persist(next: Set<string>) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify([...next]));
    } catch {
      /* ignore */
    }
  }

  // voteaza-cursuri.php:422-425 — click pe header deschide/închide descrierea.
  function toggleDesc(id: number) {
    setOpen((p) => {
      const next = new Set(p);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  }

  // voteaza-cursuri.php:383-420 — fără lock global: fiecare click pleacă independent.
  async function toggle(c: VoteCourse) {
    const key = voteKey(c);
    const has = voted.has(key);
    const delta = has ? -1 : 1;

    // UI optimist: inima, contorul și localStorage se schimbă înainte de cerere;
    // contorul rămâne pe +1/-1, răspunsul serverului nu-l suprascrie (linia 397).
    setLikes((p) => ({ ...p, [c.id]: (p[c.id] ?? 0) + delta }));
    const next = new Set(voted);
    if (has) next.delete(key);
    else next.add(key);
    setVoted(next);
    persist(next);

    try {
      await vote(c.id, has ? "remove" : "add");
    } catch {
      // voteaza-cursuri.php:410-419 — la eșec se dau înapoi inima, contorul ȘI localStorage.
      setLikes((p) => ({ ...p, [c.id]: (p[c.id] ?? 0) - delta }));
      setVoted(voted);
      persist(voted);
    }
  }

  return (
    <div className={styles.grid}>
      {courses.map((c) => {
        const has = voted.has(voteKey(c));
        return (
          <div
            key={c.id}
            className={`${styles.card} ${open.has(c.id) ? styles.open : ""}`}
            data-vote-id={c.id}
          >
            <div className={styles.cardHeader} onClick={() => toggleDesc(c.id)}>
              <span className={styles.emoji}>{c.emoji ?? "📚"}</span>
              <span className={styles.name}>{c.name}</span>
              <button
                className={`${styles.voteBtn} ${has ? styles.voted : ""}`}
                onClick={(e) => {
                  e.stopPropagation();
                  toggle(c);
                }}
                aria-pressed={has}
              >
                <span className={styles.heart}>{has ? "♥" : "♡"}</span>
              </button>
              <span className={styles.toggleIcon}>▾</span>
            </div>
            <div className={styles.descWrap}>
              <div className={styles.descInner}>
                <div className={styles.desc}>
                  {/* voteaza-cursuri.php:287 — numărul de aprecieri se vede doar în card, după deschidere */}
                  <strong className={styles.likesLabel}>{likesLabel(likes[c.id] ?? 0)}</strong>
                  {c.description ?? ""}
                </div>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
