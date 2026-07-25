import { NextResponse } from "next/server";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

// Port din admin/statistici/export.php — descarcă toate datele ca JSON.
export const dynamic = "force-dynamic";

export async function GET(): Promise<NextResponse> {
  if (!(await getSession())) return NextResponse.json({ error: "Unauthorized" }, { status: 403 });

  const cursuri = await sql`
    SELECT e.id, e.title AS name, to_char(e.starts_at AT TIME ZONE 'Europe/Bucharest', 'YYYY-MM-DD') AS date,
           (SELECT count(*)::int FROM tickets t WHERE t.event_id = e.id) AS total_tickets
    FROM events e ORDER BY e.starts_at DESC
  `;
  const venituri = await sql`
    SELECT to_char(data, 'YYYY-MM-DD') AS data, descriere, suma::float8 AS suma
    FROM venituri ORDER BY data DESC
  `;
  const cheltuieli = await sql`
    SELECT to_char(c.data, 'YYYY-MM-DD') AS data, cat.nume AS categorie, c.suma::float8 AS suma
    FROM cheltuieli c JOIN cheltuiala_categorii cat ON cat.id = c.categorie_id
    ORDER BY c.data DESC
  `;
  const lunar = await sql`
    SELECT luna,
      SUM(CASE WHEN tip = 'venit' THEN suma ELSE 0 END)::float8 AS venituri,
      SUM(CASE WHEN tip = 'cheltuiala' THEN suma ELSE 0 END)::float8 AS cheltuieli
    FROM (
      SELECT to_char(data, 'YYYY-MM') AS luna, suma, 'venit' AS tip FROM venituri
      UNION ALL
      SELECT to_char(data, 'YYYY-MM') AS luna, suma, 'cheltuiala' AS tip FROM cheltuieli
    ) x GROUP BY luna ORDER BY luna DESC
  `;

  const today = new Intl.DateTimeFormat("en-CA", { timeZone: "Europe/Bucharest" }).format(new Date());
  return new NextResponse(JSON.stringify({ cursuri, venituri, cheltuieli, lunar }, null, 2), {
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "Content-Disposition": `attachment; filename="statistici-${today}.json"`,
    },
  });
}
