import type { Metadata } from "next";
import { cookies } from "next/headers";
import { jwtVerify } from "jose";
import { sql } from "@/lib/db";
import { getRealSession } from "@/lib/auth";
import Dashboard, { type Row } from "./Dashboard";
import PasswordGate from "./PasswordGate";

export const metadata: Metadata = {
  title: "Feedback participanți — Cursuri la Pahar",
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

  const rows = (await sql`
    SELECT id, curs, data_curs::text AS data_curs, tema, tip,
           experienta, speaker, continut, locatie, durata, pret, revenire, intrebare, text
    FROM feedback
    ORDER BY data_curs, id
  `) as Row[];

  return <Dashboard rows={rows} />;
}
