import type { Metadata } from "next";
import { sql } from "@/lib/db";
import { BackLink } from "../colaborare-form";
import styles from "./ideas.module.css";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Cursuri posibile – Cursuri la Pahar",
  description:
    "Idei de teme pentru un curs la pahar. Caută inspirație și aplică să susții un curs în cadrul evenimentelor noastre.",
};

type IdeaCategory = { emoji?: string; title?: string; topics?: string[] };
type CourseIdeas = { intro?: string; categories?: IdeaCategory[] };

export default async function CursuriPosibilePage() {
  const rows = (await sql`
    SELECT value FROM settings WHERE key = 'course_ideas'
  `) as { value: CourseIdeas }[];
  const ideas = rows[0]?.value ?? {};
  const categories = Array.isArray(ideas.categories) ? ideas.categories : [];

  return (
    <section className="page-content-section">
      <div className="container">
        <BackLink />
        <h1>Cursuri posibile</h1>
        <div style={{ color: "var(--text-muted)", lineHeight: 1.8 }}>
          <p style={{ whiteSpace: "pre-line" }}>{ideas.intro ?? ""}</p>
          <p style={{ marginTop: 16 }}>
            <a href="/prezinta-un-curs" style={{ color: "var(--accent)", textDecoration: "underline" }}>
              Apasă aici
            </a>{" "}
            dacă vrei să aplici pentru a susține un Curs la Pahar.
          </p>
        </div>

        <div className={styles.ideasGrid}>
          {categories.map((cat, i) => (
            <div className={styles.ideaCard} key={i}>
              <h2>
                {cat.emoji ? <span>{cat.emoji}</span> : null}
                {cat.title ?? ""}
              </h2>
              <ul>
                {(cat.topics ?? []).map((topic, j) => (
                  <li key={j}>{topic}</li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
