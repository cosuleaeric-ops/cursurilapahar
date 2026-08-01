import { sql } from "@/lib/db";
import { marcheazaEsec, confirmaComanda } from "@/lib/comenzi";
import { PLATIT, verificaNotificare } from "@/lib/netopia";
import { trimiteEmailComanda, trimiteEmailPlataEsuata } from "@/lib/trimite";

export const dynamic = "force-dynamic";

// Notificarea de la Netopia. Vine server-la-server, poate veni de mai multe ori
// pentru aceeași comandă, deci totul e idempotent. Răspundem mereu 200 cu
// errorCode 0, altfel o retrimit la nesfârșit.
/**
 * Fiecare notificare lasă urmă, altfel o respingere e invizibilă. La eșec
 * păstrăm și tokenul și corpul exact: fără ele nu se poate afla offline care
 * cheie ar fi verificat semnătura, iar fiecare ipoteză ar costa o plată nouă.
 */
async function noteaza(
  ok: boolean,
  motiv: string,
  cod?: string,
  status?: number,
  token?: string | null,
  corp?: string,
): Promise<void> {
  try {
    await sql`
      INSERT INTO webhook_log (ok, motiv, cod, status, token, corp)
      VALUES (${ok}, ${motiv.slice(0, 300)}, ${cod ?? null}, ${status ?? null},
              ${ok ? null : (token ?? null)}, ${ok ? null : (corp ?? null)})
    `;
  } catch {
    // Jurnalul nu are voie să rupă plata.
  }
}

export async function POST(req: Request) {
  try {
    // Corpul brut, nu JSON-ul reparsat: semnătura acoperă exact octeții primiți.
    const raw = await req.text();
    const codBrut = (() => {
      try {
        return (JSON.parse(raw) as { order?: { orderID?: string } }).order?.orderID;
      } catch {
        return undefined;
      }
    })();

    const token = req.headers.get("verification-token");
    const v = await verificaNotificare(raw, token);
    if (!v.ok) {
      await noteaza(false, v.motiv, codBrut, undefined, token, raw.slice(0, 4000));
      // 1, nu 0: dacă e o notificare reală și noi avem cheia greșită, Netopia
      // reîncearcă și se repară singur după ce corectăm. Pe una falsă nu are
      // cine reîncerca.
      return Response.json({ errorCode: 1 }, { status: 400 });
    }

    const body = JSON.parse(raw) as {
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
      const emise = await confirmaComanda(o.id, String(body.payment?.ntpID ?? ""));
      // Fără bilete emise nu plecă niciun email cu bilete: comanda rămâne
      // marcată `platita_fara_bilete`, ca s-o prindem manual.
      if (emise && nou) await trimiteEmailComanda(o.id);
    } else if (o.status === "noua") {
      // Biletele rămân rezervate: omul e încă pe pagina Netopia și poate
      // reîncerca cu alt card, pe același orderID.
      await marcheazaEsec(o.id, body.payment?.message || `status ${status}`);
      await trimiteEmailPlataEsuata(o.id);
    }
    await noteaza(true, body.payment?.message || "ok", cod, status);
    return Response.json({ errorCode: 0 });
  } catch (e) {
    await noteaza(false, `eroare la procesare: ${e instanceof Error ? e.message : "necunoscută"}`);
    return Response.json({ errorCode: 1 });
  }
}

export async function GET() {
  return Response.json({ ok: true });
}
