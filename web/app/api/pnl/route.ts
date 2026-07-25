import { NextRequest, NextResponse } from "next/server";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";

// Port din admin/statistici/pnl/api.php — aceleași acțiuni și același JSON,
// ca scriptul din pagina P&L (public/admin/statistici/pnl/app.js) să meargă
// neschimbat. Rândurile sunt modelate ca în SQLite (categorie ca string).

export const dynamic = "force-dynamic";

const MONTHS = ["", "Ianuarie", "Februarie", "Martie", "Aprilie", "Mai", "Iunie", "Iulie", "August", "Septembrie", "Octombrie", "Noiembrie", "Decembrie"];
const MONTHS_SHORT = ["", "ian", "feb", "mar", "apr", "mai", "iun", "iul", "aug", "sep", "oct", "nov", "dec"];
const pad = (n: number) => String(n).padStart(2, "0");
const num = (v: unknown) => Number(v ?? 0);

const bad = (msg: string) => NextResponse.json({ error: msg }, { status: 400 });

async function categorii(table: "venit_categorii" | "cheltuiala_categorii") {
  const rows =
    table === "venit_categorii"
      ? ((await sql`SELECT nume FROM venit_categorii ORDER BY id ASC`) as { nume: string }[])
      : ((await sql`SELECT nume FROM cheltuiala_categorii ORDER BY id ASC`) as { nume: string }[]);
  return rows.map((r) => r.nume);
}

async function addCategorie(table: "venit_categorii" | "cheltuiala_categorii", nume: string) {
  if (!nume) return bad("Nume categorie invalid");
  if (table === "venit_categorii") {
    await sql`INSERT INTO venit_categorii(nume) VALUES(${nume}) ON CONFLICT (nume) DO NOTHING`;
  } else {
    await sql`INSERT INTO cheltuiala_categorii(nume) VALUES(${nume}) ON CONFLICT (nume) DO NOTHING`;
  }
  return NextResponse.json({ success: true });
}

/** id-ul categoriei de cheltuială, creată dacă lipsește (ca la upsert-ul din migrare). */
async function cheltCatId(nume: string): Promise<number> {
  await sql`INSERT INTO cheltuiala_categorii(nume) VALUES(${nume}) ON CONFLICT (nume) DO NOTHING`;
  const [r] = (await sql`SELECT id FROM cheltuiala_categorii WHERE nume = ${nume}`) as { id: number }[];
  return r.id;
}

async function listRows(table: "venituri" | "cheltuieli", year: string | null, month: string | null) {
  const y = year ? String(Number(year)) : null;
  const m = month ? pad(Number(month)) : null;
  if (table === "venituri") {
    return (await sql`
      SELECT id, to_char(data, 'YYYY-MM-DD') AS data, descriere, descriere AS categorie, suma::float8 AS suma
      FROM venituri
      WHERE (${y}::text IS NULL OR to_char(data, 'YYYY') = ${y})
        AND (${m}::text IS NULL OR to_char(data, 'MM') = ${m})
      ORDER BY data DESC, id DESC
    `) as unknown[];
  }
  return (await sql`
    SELECT c.id, to_char(c.data, 'YYYY-MM-DD') AS data, c.descriere,
           cat.nume AS categorie, c.suma::float8 AS suma, c.detalii
    FROM cheltuieli c JOIN cheltuiala_categorii cat ON cat.id = c.categorie_id
    WHERE (${y}::text IS NULL OR to_char(c.data, 'YYYY') = ${y})
      AND (${m}::text IS NULL OR to_char(c.data, 'MM') = ${m})
    ORDER BY c.data DESC, c.id DESC
  `) as unknown[];
}

async function stats(yearIn: string | null, monthIn: string | null) {
  const year = String(Number(yearIn ?? new Date().getFullYear()));
  const month = monthIn ? pad(Number(monthIn)) : null;

  // Dividendele rămân în listă, dar sunt excluse din statistici (profit distribuit, nu cost).
  const [tv] = (await sql`
    SELECT coalesce(SUM(suma), 0)::float8 AS total FROM venituri
    WHERE to_char(data, 'YYYY') = ${year} AND (${month}::text IS NULL OR to_char(data, 'MM') = ${month})
  `) as { total: number }[];
  const [tc] = (await sql`
    SELECT coalesce(SUM(c.suma), 0)::float8 AS total FROM cheltuieli c
    JOIN cheltuiala_categorii cat ON cat.id = c.categorie_id
    WHERE to_char(c.data, 'YYYY') = ${year} AND (${month}::text IS NULL OR to_char(c.data, 'MM') = ${month})
      AND lower(btrim(cat.nume)) <> 'dividende'
  `) as { total: number }[];

  const fmt = month ? "YYYY-MM-DD" : "YYYY-MM";
  const mv = (await sql`
    SELECT to_char(data, ${fmt}) AS luna, coalesce(SUM(suma), 0)::float8 AS suma FROM venituri
    WHERE to_char(data, 'YYYY') = ${year} AND (${month}::text IS NULL OR to_char(data, 'MM') = ${month})
    GROUP BY luna ORDER BY luna
  `) as { luna: string; suma: number }[];
  const mc = (await sql`
    SELECT to_char(c.data, ${fmt}) AS luna, coalesce(SUM(c.suma), 0)::float8 AS suma FROM cheltuieli c
    JOIN cheltuiala_categorii cat ON cat.id = c.categorie_id
    WHERE to_char(c.data, 'YYYY') = ${year} AND (${month}::text IS NULL OR to_char(c.data, 'MM') = ${month})
      AND lower(btrim(cat.nume)) <> 'dividende'
    GROUP BY luna ORDER BY luna
  `) as { luna: string; suma: number }[];

  const catRows = (await sql`
    SELECT cat.nume AS categorie, coalesce(SUM(c.suma), 0)::float8 AS suma FROM cheltuieli c
    JOIN cheltuiala_categorii cat ON cat.id = c.categorie_id
    WHERE to_char(c.data, 'YYYY') = ${year} AND (${month}::text IS NULL OR to_char(c.data, 'MM') = ${month})
      AND lower(btrim(cat.nume)) <> 'dividende'
    GROUP BY cat.nume ORDER BY suma DESC
  `) as { categorie: string; suma: number }[];

  const vMap = new Map(mv.map((r) => [r.luna, num(r.suma)]));
  const cMap = new Map(mc.map((r) => [r.luna, num(r.suma)]));
  const keys = [...new Set([...vMap.keys(), ...cMap.keys()])].sort();
  let cumulative = 0;
  const monthly = keys.map((luna) => {
    const v = vMap.get(luna) ?? 0;
    const c = cMap.get(luna) ?? 0;
    cumulative += v - c;
    return { luna, venituri: v, cheltuieli: c, profit: v - c, cumulative };
  });

  // Perioada anterioară, pentru comparație
  let prevYear = Number(year);
  let prevMonth: number | null = null;
  let prevLabel: string;
  if (month) {
    prevMonth = Number(month) - 1;
    if (prevMonth === 0) {
      prevMonth = 12;
      prevYear -= 1;
    }
    prevLabel = `${MONTHS_SHORT[prevMonth]} ${prevYear}`;
  } else {
    prevYear = Number(year) - 1;
    prevLabel = String(prevYear);
  }
  const pY = String(prevYear);
  const pM = prevMonth ? pad(prevMonth) : null;
  const [pv] = (await sql`
    SELECT coalesce(SUM(suma), 0)::float8 AS total FROM venituri
    WHERE to_char(data, 'YYYY') = ${pY} AND (${pM}::text IS NULL OR to_char(data, 'MM') = ${pM})
  `) as { total: number }[];
  const [pc] = (await sql`
    SELECT coalesce(SUM(c.suma), 0)::float8 AS total FROM cheltuieli c
    JOIN cheltuiala_categorii cat ON cat.id = c.categorie_id
    WHERE to_char(c.data, 'YYYY') = ${pY} AND (${pM}::text IS NULL OR to_char(c.data, 'MM') = ${pM})
      AND lower(btrim(cat.nume)) <> 'dividende'
  `) as { total: number }[];

  // Toate cele 12 luni ale anului — graficul macro, independent de luna selectată
  const yv = (await sql`
    SELECT to_char(data, 'MM') AS m, coalesce(SUM(suma), 0)::float8 AS suma FROM venituri
    WHERE to_char(data, 'YYYY') = ${year} GROUP BY m
  `) as { m: string; suma: number }[];
  const yc = (await sql`
    SELECT to_char(c.data, 'MM') AS m, coalesce(SUM(c.suma), 0)::float8 AS suma FROM cheltuieli c
    JOIN cheltuiala_categorii cat ON cat.id = c.categorie_id
    WHERE to_char(c.data, 'YYYY') = ${year} AND lower(btrim(cat.nume)) <> 'dividende' GROUP BY m
  `) as { m: string; suma: number }[];
  const yvMap = new Map(yv.map((r) => [r.m, num(r.suma)]));
  const ycMap = new Map(yc.map((r) => [r.m, num(r.suma)]));
  const yearly = Array.from({ length: 12 }, (_, i) => {
    const mp = pad(i + 1);
    return { luna: `${year}-${mp}`, venituri: yvMap.get(mp) ?? 0, cheltuieli: ycMap.get(mp) ?? 0 };
  });

  const totalV = num(tv.total);
  const totalC = num(tc.total);
  return {
    total_venituri: totalV,
    total_cheltuieli: totalC,
    profit_net: totalV - totalC,
    marja: totalV > 0 ? Math.round(((totalV - totalC) / totalV) * 1000) / 10 : 0,
    monthly,
    yearly,
    categorii_cheltuieli: catRows.map((r) => ({ categorie: r.categorie, suma: num(r.suma) })),
    year: Number(year),
    profit_prev: num(pv.total) - num(pc.total),
    prev_label: prevLabel,
  };
}

async function periods() {
  const rows = (await sql`
    SELECT DISTINCT to_char(data, 'YYYY') AS an, to_char(data, 'MM') AS luna FROM venituri
    UNION
    SELECT DISTINCT to_char(data, 'YYYY') AS an, to_char(data, 'MM') AS luna FROM cheltuieli
  `) as { an: string; luna: string }[];

  const byYear = new Map<number, Set<number>>();
  for (const r of rows) {
    const y = Number(r.an);
    if (!byYear.has(y)) byYear.set(y, new Set());
    byYear.get(y)!.add(Number(r.luna));
  }
  const now = new Date();
  const nowY = now.getFullYear();
  if (!byYear.has(nowY)) byYear.set(nowY, new Set());
  byYear.get(nowY)!.add(now.getMonth() + 1);

  const out: { value: string; label: string; year: number; month: number | null }[] = [];
  for (const year of [...byYear.keys()].sort((a, b) => b - a)) {
    for (const m of [...byYear.get(year)!].sort((a, b) => b - a)) {
      out.push({ value: `${year}-${pad(m)}`, label: `${MONTHS[m]} ${year}`, year, month: m });
    }
    out.push({ value: String(year), label: String(year), year, month: null });
  }
  return out;
}

export async function GET(req: NextRequest): Promise<NextResponse> {
  if (!(await getSession())) return NextResponse.json({ error: "Unauthorized" }, { status: 403 });
  const p = req.nextUrl.searchParams;
  const action = p.get("action") ?? "";
  const year = p.get("year");
  const month = p.get("month");

  switch (action) {
    case "stats":
      return NextResponse.json(await stats(year, month));
    case "venituri":
      return NextResponse.json(await listRows("venituri", year, month));
    case "cheltuieli":
      return NextResponse.json(await listRows("cheltuieli", year, month));
    case "categorii_venituri":
      return NextResponse.json(await categorii("venit_categorii"));
    case "categorii_cheltuieli":
      return NextResponse.json(await categorii("cheltuiala_categorii"));
    case "years": {
      const rows = (await sql`
        SELECT DISTINCT to_char(data, 'YYYY') AS an FROM venituri
        UNION SELECT DISTINCT to_char(data, 'YYYY') AS an FROM cheltuieli
        ORDER BY an DESC
      `) as { an: string }[];
      return NextResponse.json(rows.map((r) => Number(r.an)));
    }
    case "periods":
      return NextResponse.json(await periods());
    case "last_entry": {
      const rows = (await sql`
        SELECT to_char(data, 'YYYY-MM-DD') AS data FROM cheltuieli ORDER BY data DESC, id DESC LIMIT 1
      `) as { data: string }[];
      return NextResponse.json(rows[0] ?? null);
    }
    default:
      return bad("Acțiune invalidă");
  }
}

export async function POST(req: NextRequest): Promise<NextResponse> {
  if (!(await getSession())) return NextResponse.json({ error: "Unauthorized" }, { status: 403 });
  const action = req.nextUrl.searchParams.get("action") ?? "";
  const fd = await req.formData();
  const s = (k: string) => String(fd.get(k) ?? "").trim();
  const id = Number(s("id")) || 0;
  const data = s("data");
  const categorie = s("categorie");
  const suma = Number(s("suma")) || 0;
  const detalii = s("detalii");
  const incomplete = () => bad("Date incomplete sau invalide");

  switch (action) {
    case "add_categorie_venit":
      return addCategorie("venit_categorii", s("nume"));
    case "add_categorie_cheltuiala":
      return addCategorie("cheltuiala_categorii", s("nume"));

    case "add_venit": {
      if (!data || !categorie || suma <= 0) return incomplete();
      const [r] = (await sql`
        INSERT INTO venituri (data, descriere, suma) VALUES (${data}::date, ${categorie}, ${suma}) RETURNING id
      `) as { id: number }[];
      return NextResponse.json({ id: r.id, success: true });
    }
    case "add_cheltuiala": {
      if (!data || !categorie || suma <= 0) return incomplete();
      const catId = await cheltCatId(categorie);
      const [r] = (await sql`
        INSERT INTO cheltuieli (data, descriere, categorie_id, suma, detalii)
        VALUES (${data}::date, ${categorie}, ${catId}, ${suma}, ${detalii || null}) RETURNING id
      `) as { id: number }[];
      return NextResponse.json({ id: r.id, success: true });
    }
    case "edit_venit": {
      if (!id || !data || !categorie || suma <= 0) return incomplete();
      await sql`UPDATE venituri SET data = ${data}::date, descriere = ${categorie}, suma = ${suma} WHERE id = ${id}`;
      return NextResponse.json({ success: true });
    }
    case "edit_cheltuiala": {
      if (!id || !data || !categorie || suma <= 0) return incomplete();
      const catId = await cheltCatId(categorie);
      await sql`
        UPDATE cheltuieli SET data = ${data}::date, descriere = ${categorie},
          categorie_id = ${catId}, suma = ${suma}, detalii = ${detalii || null}
        WHERE id = ${id}
      `;
      return NextResponse.json({ success: true });
    }
    case "delete_venit": {
      if (!id) return bad("ID invalid");
      await sql`DELETE FROM venituri WHERE id = ${id}`;
      return NextResponse.json({ success: true });
    }
    case "delete_cheltuiala": {
      if (!id) return bad("ID invalid");
      await sql`DELETE FROM cheltuieli WHERE id = ${id}`;
      return NextResponse.json({ success: true });
    }
    default:
      return bad("Acțiune invalidă");
  }
}
