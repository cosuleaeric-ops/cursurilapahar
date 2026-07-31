import type { Metadata } from "next";
import { cookies } from "next/headers";
import { jwtVerify } from "jose";
import { sql } from "@/lib/db";
import { getRealSession } from "@/lib/auth";
import Dashboard, { type Row } from "./Dashboard";
import PasswordGate from "./PasswordGate";

export const metadata: Metadata = {
  title: "Feedback participanți - Cursuri la Pahar",
  robots: { index: false, follow: false },
};
export const dynamic = "force-dynamic";

/** Acces cu cookie-ul de feedback (parolă) sau cu orice sesiune de admin. */
async function areAcces(): Promise<boolean> {
  const token = (await cookies()).get("clp_feedback")?.value;
  if (token && process.env.AUTH_SECRET) {
    try {
      await jwtVerify(token, new TextEncoder().encode(`${process.env.AUTH_SECRET}:feedback`), {
        audience: "clp-feedback",
      });
      return true;
    } catch {}
  }
  return !!(await getRealSession());
}

export default async function FeedbackPage() {
  if (!(await areAcces())) return <PasswordGate />;

  // Fără recomandările de speakeri și temele dorite — pagina arată doar feedback-ul propriu-zis.
  const raw = (await sql`
    SELECT id, curs, data_curs::text AS data_curs, tema, tip,
           experienta, speaker, continut, locatie, durata, pret, revenire, intrebare, text
    FROM feedback
    WHERE intrebare IS NULL OR intrebare NOT IN ('Teme dorite', 'Speakeri')
    ORDER BY data_curs, id
  `) as Row[];

  // În textele cu note, sugestiile vin lipite ca „… | Teme: … | Speakeri: …" — le tăiem.
  const rows = raw.map((r) => ({
    ...r,
    text: r.text
      ? r.text
          .split(" | ")
          .filter((p) => !/^(Teme|Speakeri):/i.test(p.trim()))
          .join(" | ") || null
      : null,
  }));

  return <Dashboard rows={rows} />;
}
