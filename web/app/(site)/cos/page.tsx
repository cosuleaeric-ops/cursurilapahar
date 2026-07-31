import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { pageMetadata } from "@/lib/metadata";
import { checkoutPropriuActiv } from "@/lib/checkout";

export const dynamic = "force-dynamic";

export const metadata = pageMetadata({
  title: "Comanda mea — Curs la Pahar",
  description: "Biletele alese pentru cursul tău.",
  path: "/cos",
});

const TZ = "Europe/Bucharest";
const ziData = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "numeric", month: "long", year: "numeric" });
const oraFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, hour: "2-digit", minute: "2-digit" });
const money = (v: number) => v.toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const curat = (t: string) => t.replace(/\s+\/\/\s+.+$/u, "");

/** „12x2,13x1" → [{ typeId: 12, qty: 2 }, …]; ignoră ce nu e valid. */
function parseSelectie(t: string): { typeId: number; qty: number }[] {
  return t
    .split(",")
    .map((p) => /^(\d+)x(\d+)$/.exec(p.trim()))
    .filter((m): m is RegExpExecArray => !!m)
    .map((m) => ({ typeId: Number(m[1]), qty: Math.min(Number(m[2]), 10) }))
    .filter((x) => x.qty > 0);
}

export default async function CosPage({
  searchParams,
}: {
  searchParams: Promise<{ e?: string; t?: string }>;
}) {
  const { e: eStr, t: tStr } = await searchParams;
  const eventId = Number(eStr);
  const selectie = parseSelectie(tStr ?? "");
  if (!eventId || !selectie.length) return <CosGol />;

  const [event] = (await sql`
    SELECT id, slug, title, starts_at, location, sold_out, active FROM events WHERE id = ${eventId}
  `) as {
    id: number;
    slug: string;
    title: string;
    starts_at: string | null;
    location: string | null;
    sold_out: boolean;
    active: boolean;
  }[];
  if (!event || !event.active) notFound();

  // Prețurile și disponibilul se citesc din bază, nu din URL: ce vine din browser
  // e doar „ce tip și câte bucăți".
  const types = (await sql`
    SELECT t.id, t.name, t.price,
           COUNT(p.id) FILTER (WHERE p.status = 'liber')::int AS libere
    FROM ticket_types t LEFT JOIN ticket_pool p ON p.type_id = t.id
    WHERE t.event_id = ${eventId} AND t.id = ANY(${selectie.map((s) => s.typeId)})
    GROUP BY t.id ORDER BY t.position, t.id
  `) as { id: number; name: string; price: string; libere: number }[];

  const linii = types
    .map((t) => {
      const cerut = selectie.find((s) => s.typeId === t.id)?.qty ?? 0;
      const qty = Math.min(cerut, t.libere);
      return { ...t, price: Number(t.price), cerut, qty, total: Number(t.price) * qty };
    })
    .filter((l) => l.qty > 0);

  if (!linii.length) return <CosGol />;

  const checkoutPropriu = await checkoutPropriuActiv();
  const redus = linii.some((l) => l.qty < l.cerut);
  const total = linii.reduce((s, l) => s + l.total, 0);
  const bucati = linii.reduce((s, l) => s + l.qty, 0);
  const d = event.starts_at ? new Date(event.starts_at) : null;

  return (
    <section className="section cos-page">
      <div className="container container-narrow">
        <div className="cos-card">
          <div className="cos-head">
            <h1>Comanda mea</h1>
            <a href={`/curs/${event.slug}#bilete`} className="cos-modifica">
              Modifică
            </a>
          </div>

          <div className="cos-event">
            <span className="cos-count">{bucati}</span>
            <div>
              <strong>{curat(event.title)}</strong>
              {event.location && <div>{event.location}</div>}
              {d && (
                <div>
                  {ziData.format(d)}, ora {oraFmt.format(d)}
                </div>
              )}
            </div>
          </div>

          {redus && (
            <p className="cos-warn">
              Nu mai sunt atâtea bilete disponibile — comanda a fost ajustată la cât a mai rămas.
            </p>
          )}

          <ul className="cos-linii">
            {linii.map((l) => (
              <li key={l.id}>
                <span>
                  {l.qty} × {l.name}
                </span>
                <span className="cos-suma">{money(l.total)} lei</span>
              </li>
            ))}
          </ul>

          <div className="cos-total">
            <span>Total</span>
            <strong>{money(total)} lei</strong>
          </div>

          {checkoutPropriu ? (
            <form className="cos-form" action="/api/comanda" method="post">
              <input type="hidden" name="e" value={event.id} />
              <input type="hidden" name="t" value={linii.map((l) => `${l.id}x${l.qty}`).join(",")} />
              <div className="cos-f">
                <label htmlFor="nume">Numele tău</label>
                <input id="nume" name="nume" required autoComplete="name" />
              </div>
              <div className="cos-f">
                <label htmlFor="email">Email</label>
                <input id="email" name="email" type="email" required autoComplete="email" />
                <small>Aici primești biletele.</small>
              </div>
              <button type="submit" className="btn btn-primary cos-cta">
                Mergi la plată
              </button>
            </form>
          ) : (
            <div className="cos-form">
              <p className="cos-warn" style={{ marginTop: 0, marginBottom: 16 }}>
                Plata direct pe site se activează în curând. Până atunci, biletele se iau de aici:
              </p>
              <a href={`/go/course?id=${event.id}`} className="btn btn-primary cos-cta" target="_blank" rel="noopener">
                Cumpără biletele
              </a>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}

function CosGol() {
  return (
    <section className="section cos-page">
      <div className="container container-narrow">
        <div className="cos-card cos-card--gol">
          <h1>Coșul e gol</h1>
          <p>Alege un curs și numărul de bilete.</p>
          <a href="/#cursuri" className="btn btn-primary">
            Vezi cursurile
          </a>
        </div>
      </div>
    </section>
  );
}
