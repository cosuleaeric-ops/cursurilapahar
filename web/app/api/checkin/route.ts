import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { formatNumar } from "@/lib/bilete";

// Validarea unui bilet la intrare. Marcarea folosește `WHERE used_at IS NULL`
// și RETURNING: dacă două scanări ajung simultan, doar una scrie, cealaltă
// vede biletul ca fiind deja folosit.

type Ticket = {
  id: number;
  event_id: number;
  serie: string;
  numar: number;
  status: string;
  buyer_name: string | null;
  used_at: string | null;
  tip: string;
  bundle_size: number;
};

const ora = new Intl.DateTimeFormat("ro-RO", {
  timeZone: "Europe/Bucharest",
  hour: "2-digit",
  minute: "2-digit",
});

export async function POST(request: Request) {
  if (!(await getSession())) return Response.json({ ok: false, msg: "Neautorizat" }, { status: 401 });

  const body = (await request.json().catch(() => null)) as {
    eventId?: number;
    token?: string;
    serie?: string;
    numar?: number;
  } | null;
  if (!body?.eventId) return Response.json({ ok: false, msg: "Cerere invalidă" }, { status: 400 });

  // QR-ul conține adresa biletului; acceptăm și tokenul gol, tastat manual.
  const token = (body.token ?? "").trim().split("/").pop()?.toLowerCase() ?? "";
  const serie = (body.serie ?? "").trim().toUpperCase();
  const numar = Number(body.numar);

  let found: Ticket[];
  if (/^[a-f0-9]{32}$/.test(token)) {
    found = (await sql`
      SELECT p.id, p.event_id, p.serie, p.numar, p.status, p.buyer_name, p.used_at, t.name AS tip, t.bundle_size
      FROM ticket_pool p JOIN ticket_types t ON t.id = p.type_id
      WHERE p.qr_token = ${token}
    `) as Ticket[];
  } else if (/^[A-Z]{3}$/.test(serie) && numar > 0) {
    found = (await sql`
      SELECT p.id, p.event_id, p.serie, p.numar, p.status, p.buyer_name, p.used_at, t.name AS tip, t.bundle_size
      FROM ticket_pool p JOIN ticket_types t ON t.id = p.type_id
      WHERE p.event_id = ${body.eventId} AND p.serie = ${serie} AND p.numar = ${numar}
    `) as Ticket[];
  } else {
    return Response.json({ ok: false, msg: "Cod nerecunoscut" });
  }

  const t = found[0];
  if (!t) return Response.json({ ok: false, msg: "Bilet inexistent" });

  const eticheta = `${t.serie} ${formatNumar(t.numar)}`;
  if (t.event_id !== Number(body.eventId))
    return Response.json({ ok: false, msg: "Bilet pentru alt curs", eticheta });
  if (t.status !== "vandut")
    return Response.json({ ok: false, msg: t.status === "casat" ? "Bilet casat" : "Bilet nevândut", eticheta });
  if (t.used_at)
    return Response.json({ ok: false, msg: `Deja scanat la ${ora.format(new Date(t.used_at))}`, eticheta, nume: t.buyer_name });

  const marked = (await sql`
    UPDATE ticket_pool SET used_at = now() WHERE id = ${t.id} AND used_at IS NULL RETURNING used_at
  `) as { used_at: string }[];
  if (!marked.length) return Response.json({ ok: false, msg: "Deja scanat", eticheta, nume: t.buyer_name });

  const msg = t.bundle_size > 1 ? `${t.tip} — ${t.bundle_size} persoane` : t.tip;
  return Response.json({ ok: true, msg, eticheta, nume: t.buyer_name });
}
