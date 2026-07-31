import { sql } from "@/lib/db";
import { anuleazaComanda, confirmaComanda } from "@/lib/comenzi";
import { PLATIT } from "@/lib/netopia";
import { trimiteEmailComanda, trimiteEmailPlataEsuata } from "@/lib/trimite";

export const dynamic = "force-dynamic";

// Notificarea de la Netopia. Vine server-la-server, poate veni de mai multe ori
// pentru aceeași comandă, deci totul e idempotent. Răspundem mereu 200 cu
// errorCode 0, altfel o retrimit la nesfârșit.
export async function POST(req: Request) {
  try {
    const body = (await req.json()) as {
      order?: { orderID?: string };
      payment?: { status?: number; ntpID?: string; message?: string; code?: string };
    };
    const cod = body.order?.orderID;
    if (!cod) return Response.json({ errorCode: 1 });

    const [o] = (await sql`SELECT id, status FROM orders WHERE cod = ${cod}`) as {
      id: number;
      status: string;
    }[];
    if (!o) return Response.json({ errorCode: 1 });

    const status = Number(body.payment?.status ?? 0);
    if (PLATIT.has(status)) {
      const nou = o.status !== "platita";
      await confirmaComanda(o.id, String(body.payment?.ntpID ?? ""));
      if (nou) await trimiteEmailComanda(o.id);
    } else if (o.status === "noua") {
      await anuleazaComanda(o.id, body.payment?.message || `status ${status}`, "esuata");
      await trimiteEmailPlataEsuata(o.id);
    }
    return Response.json({ errorCode: 0 });
  } catch {
    return Response.json({ errorCode: 1 });
  }
}

export async function GET() {
  return Response.json({ ok: true });
}
