import { notFound } from "next/navigation";
import QRCode from "qrcode";
import { sql } from "@/lib/db";
import { formatNumar, getOrganizator } from "@/lib/bilete";
import { BILET_CSS } from "./styles";

export const dynamic = "force-dynamic";
export const metadata = { title: "Bilet de intrare" };

const TZ = "Europe/Bucharest";
const dataOra = new Intl.DateTimeFormat("ro-RO", {
  timeZone: TZ,
  weekday: "long",
  day: "numeric",
  month: "long",
  year: "numeric",
  hour: "2-digit",
  minute: "2-digit",
});
const money = (v: number) => Number(v).toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Biletul de intrare la spectacole. Câmpurile obligatorii sunt cele din art. 3 al
// Normelor aprobate prin HG 846/2002: organizatorul, codul fiscal, sediul, data
// spectacolului, categoria locului, tariful — plus seria și numărul din sistemul
// propriu de înseriere.
export default async function BiletPage({ params }: { params: Promise<{ token: string }> }) {
  const { token } = await params;
  if (!/^[a-f0-9]{32}$/.test(token)) notFound();

  const [t] = (await sql`
    SELECT p.serie, p.numar, p.status, p.buyer_name, p.used_at,
           ty.name AS tip, ty.price,
           e.title, e.starts_at, e.location
    FROM ticket_pool p
    JOIN ticket_types ty ON ty.id = p.type_id
    JOIN events e ON e.id = p.event_id
    WHERE p.qr_token = ${token}
  `) as {
    serie: string;
    numar: number;
    status: string;
    buyer_name: string | null;
    used_at: string | null;
    tip: string;
    price: string;
    title: string;
    starts_at: string | null;
    location: string | null;
  }[];
  if (!t) notFound();

  const org = await getOrganizator();
  const url = `https://cursurilapahar.ro/bilet/${token}`;
  const qr = await QRCode.toString(url, { type: "svg", margin: 0, errorCorrectionLevel: "M" });

  const valid = t.status === "vandut";

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: BILET_CSS }} />

      <div className="bilet-wrap">
        <div className={`bilet${valid ? "" : " invalid"}`}>
          <div className="b-head">
            <div className="b-label">Bilet de intrare</div>
            <div className="b-serie">
              {t.serie} {formatNumar(t.numar)}
            </div>
          </div>

          <h1>{t.title}</h1>
          <div className="b-when">{t.starts_at ? dataOra.format(new Date(t.starts_at)) : ""}</div>
          {t.location && <div className="b-where">{t.location}</div>}

          <div className="b-qr" dangerouslySetInnerHTML={{ __html: qr }} />

          {!valid && (
            <div className="b-warn">
              {t.status === "casat" ? "Bilet casat - nu permite accesul." : "Bilet neemis - nu permite accesul."}
            </div>
          )}
          {t.used_at && <div className="b-used">Deja scanat la intrare</div>}

          <dl className="b-rows">
            <div>
              <dt>Categoria locului</dt>
              <dd>{t.tip}</dd>
            </div>
            <div>
              <dt>Tarif</dt>
              <dd>{money(Number(t.price))} lei</dd>
            </div>
            {t.buyer_name && (
              <div>
                <dt>Participant</dt>
                <dd>{t.buyer_name}</dd>
              </div>
            )}
          </dl>

          <div className="b-org">
            {org.nume} · CIF {org.cui}
            <br />
            {org.sediu}
          </div>

          <div className="b-foot">Biletul se va păstra pentru control</div>
        </div>
      </div>
    </>
  );
}
