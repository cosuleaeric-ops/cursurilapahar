import { sql } from "@/lib/db";
import IdeasEditor from "./IdeasEditor";
import { type IdeaCategory } from "./actions";

export const dynamic = "force-dynamic";

export default async function CursuriPosibileAdminPage({
  searchParams,
}: {
  searchParams: Promise<{ saved?: string }>;
}) {
  const { saved } = await searchParams;
  const rows = (await sql`SELECT value FROM settings WHERE key = 'course_ideas'`) as { value: unknown }[];
  const data = (rows[0]?.value && typeof rows[0].value === "object" ? rows[0].value : {}) as {
    intro?: string;
    categories?: IdeaCategory[];
  };

  return (
    <>
      <h1 className="wp-page-title">Cursuri posibile</h1>

      {saved && <div className="notice notice-success">Lista a fost salvată.</div>}

      <IdeasEditor intro={data.intro ?? ""} categories={Array.isArray(data.categories) ? data.categories : []} />

      <p style={{ color: "var(--text-muted)", fontSize: 12, marginTop: 8 }}>
        În fiecare categorie: o temă pe linie. Ordinea de aici e ordinea de pe pagină.
      </p>
    </>
  );
}
