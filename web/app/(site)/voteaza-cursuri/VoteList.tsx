"use client";

import { useEffect, useState } from "react";
import { vote, trackVoteView, trackVotePageView } from "./actions";
import styles from "./vote.module.css";

export type VoteCourse = {
  id: number;
  name: string;
  emoji: string | null;
  description: string | null;
  likes: number;
};

const STORAGE_KEY = "clp_votes";
const PAGE_VIEW_KEY = "clp_vote_page_viewed";
const VIEW_KEY = "clp_vote_viewed";

export default function VoteList({ courses }: { courses: VoteCourse[] }) {
  const [likes, setLikes] = useState<Record<number, number>>(() =>
    Object.fromEntries(courses.map((c) => [c.id, c.likes]))
  );
  const [voted, setVoted] = useState<Set<number>>(new Set());
  const [pending, setPending] = useState<number | null>(null);

  // hidratare din localStorage (după mount, ca să nu strice SSR-ul)
  useEffect(() => {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) setVoted(new Set(JSON.parse(raw) as number[]));
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

  function persist(next: Set<number>) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify([...next]));
    } catch {
      /* ignore */
    }
  }

  async function toggle(id: number) {
    if (pending !== null) return;
    const has = voted.has(id);
    const action = has ? "remove" : "add";

    // optimistic
    setLikes((p) => ({ ...p, [id]: Math.max(0, (p[id] ?? 0) + (has ? -1 : 1)) }));
    const nextVoted = new Set(voted);
    if (has) nextVoted.delete(id);
    else nextVoted.add(id);
    setVoted(nextVoted);
    persist(nextVoted);

    setPending(id);
    try {
      const serverLikes = await vote(id, action);
      setLikes((p) => ({ ...p, [id]: serverLikes }));
    } finally {
      setPending(null);
    }
  }

  return (
    <div className={styles.list}>
      {courses.map((c) => {
        const has = voted.has(c.id);
        return (
          <article key={c.id} className={styles.card} data-vote-id={c.id}>
            <div className={styles.emoji}>{c.emoji || "📚"}</div>
            <div className={styles.body}>
              <h3 className={styles.name}>{c.name}</h3>
              {c.description && <p className={styles.desc}>{c.description}</p>}
            </div>
            <button
              className={`${styles.voteBtn} ${has ? styles.voted : ""}`}
              onClick={() => toggle(c.id)}
              disabled={pending === c.id}
              aria-pressed={has}
            >
              <span className={styles.heart}>{has ? "♥" : "♡"}</span>
              <span className={styles.count}>{likes[c.id] ?? 0}</span>
            </button>
          </article>
        );
      })}
    </div>
  );
}
