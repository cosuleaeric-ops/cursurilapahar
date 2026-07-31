import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { descriereText } from "@/lib/descriere";
import { pageMetadata } from "@/lib/metadata";
import { findDiscountCode } from "@/lib/bilete";
import CodReducere from "./CodReducere";
import DescriereToggle from "./DescriereToggle";
import TicketPicker, { type PickerType } from "./TicketPicker";

export const dynamic = "force-dynamic";

const TZ = "Europe/Bucharest";
const ziData = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, weekday: "long", day: "numeric", month: "long" });
const oraFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, hour: "2-digit", minute: "2-digit" });

/** Accesul se face cu 30 de minute înainte de începere — se calculează, nu se scrie. */
const ACCES_MIN = 30;
const randDeData = (d: Date) => {
  const acces = new Date(d.getTime() - ACCES_MIN * 60_000);
  return `${ziData.format(d)}, ora ${oraFmt.format(d)} (acces de la ora ${oraFmt.format(acces)})`;
};
const money = (v: number) => v.toLocaleString("ro-RO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const vanzareFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, day: "numeric", month: "long", hour: "2-digit", minute: "2-digit" });
const randDeVanzare = (d: Date) => `în vânzare din ${vanzareFmt.format(d)}`;

type Ev = {
  id: number;
  slug: string;
  title: string;
  starts_at: string | null;
  location: string | null;
  image_url: string | null;
  image_landscape_url: string | null;
  speaker_name: string | null;
  description: string | null;
  sold_out: boolean;
  livetickets_url: string | null;
  active: boolean;
};

async function getEvent(slug: string): Promise<Ev | null> {
  const rows = (await sql`
    SELECT id, slug, title, starts_at, location, image_url, image_landscape_url, speaker_name, description,
           sold_out, livetickets_url, active
    FROM events WHERE slug = ${slug}
  `) as Ev[];
  return rows[0] ?? null;
}

// Titlul de card taie sufixul „// 12 mai" folosit pe LiveTickets (cardTitle din homepage).
const curat = (t: string) => t.replace(/\s+\/\/\s+.+$/u, "");
/** „Mojo Club, București" → { loc: „Mojo Club", oras: „București" } */
const locOras = (s: string | null) => {
  const p = (s ?? "").split(",").map((x) => x.trim()).filter(Boolean);
  return { loc: p[0] ?? "", oras: p.slice(1).join(", ") };
};

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const e = await getEvent(slug);
  if (!e) return {};
  return pageMetadata({
    title: `${curat(e.title)} — Curs la Pahar`,
    description: e.description ? descriereText(e.description) : "Un curs la un pahar, într-un bar din București.",
    path: `/curs/${e.slug}`,
    // landscape-ul e făcut pentru share; posterul portret rămâne pe pagină
    ogImage: e.image_landscape_url ?? e.image_url ?? undefined,
  });
}

export default async function CursPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<{ cod?: string }>;
}) {
  const { slug } = await params;
  const { cod: codRaw } = await searchParams;
  const e = await getEvent(slug);
  if (!e || !e.active) notFound();

  // Codul nu reduce prețul biletelor normale: arată alt set de bilete, cu
  // seriile și tarifele lui, declarate separat la primărie.
  const cod = codRaw ? await findDiscountCode(e.id, codRaw) : null;
  const codGresit = codRaw && !cod ? codRaw.trim().toUpperCase() : undefined;

  // Biletele „doar cu cod" nu apar în lista normală; cele programate apar, dar
  // cu ora de la care se pot lua, ca omul să știe când să revină.
  const types = (await sql`
    SELECT t.id, t.name, t.description, t.price, t.max_per_order, t.bundle_size,
           t.sale_starts_at, t.sale_ends_at,
           COUNT(p.id) FILTER (WHERE p.status = 'liber')::int AS libere
    FROM ticket_types t LEFT JOIN ticket_pool p ON p.type_id = t.id
    WHERE t.event_id = ${e.id}
      AND t.discount_code_id IS NOT DISTINCT FROM ${cod?.id ?? null}
      AND (t.only_with_code = false OR ${cod?.id ?? null}::int IS NOT NULL)
      AND (t.sale_ends_at IS NULL OR t.sale_ends_at > now())
    GROUP BY t.id ORDER BY t.position, t.id
  `) as {
    id: number;
    name: string;
    description: string | null;
    price: string;
    max_per_order: number;
    bundle_size: number;
    sale_starts_at: string | null;
    sale_ends_at: string | null;
    libere: number;
  }[];

  const acum = Date.now();
  const picker: PickerType[] = types.map((t) => ({
    id: t.id,
    name: t.name,
    description: t.description,
    price: Number(t.price),
    libere: t.libere,
    maxPerOrder: t.max_per_order,
    bundle: t.bundle_size,
    dinData: t.sale_starts_at && new Date(t.sale_starts_at).getTime() > acum ? randDeVanzare(new Date(t.sale_starts_at)) : null,
  }));
  const areStoc = picker.some((t) => t.libere > 0 && !t.dinData);
  const d = e.starts_at ? new Date(e.starts_at) : null;
  const { loc, oras } = locOras(e.location);

  return (
    <section className="section curs-page">
      <div className="container">
        <a href="/#cursuri" className="curs-back">
          ← Toate cursurile
        </a>

        <div className="curs-top">
          <div className="curs-col-poster">
          <div className="curs-poster">
            {e.image_url ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={e.image_url} alt={curat(e.title)} />
            ) : (
              <div className="curs-poster-empty" />
            )}
            {e.sold_out && <div className="sold-out-badge">SOLD OUT</div>}
          </div>

          <a
            className="curs-follow"
            href="https://www.instagram.com/cursurilapahar"
            target="_blank"
            rel="noopener"
          >
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
            </svg>
            Follow Cursuri la Pahar
          </a>
          </div>

          <div className="curs-head">
            <h1 className="curs-title">{curat(e.title)}</h1>

            <div className="curs-facts">
              {e.location && (
                <div className="curs-fact">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
                  <span>
                    <b>{loc}</b>
                    {oras && `, ${oras}`}
                  </span>
                </div>
              )}
              {d && (
                <div className="curs-fact">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 6v6l4 2" />
                  </svg>
                  <span>{randDeData(d)}</span>
                </div>
              )}
              {e.speaker_name && (
                <div className="curs-fact">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                  </svg>
                  <span>{e.speaker_name}</span>
                </div>
              )}
            </div>

            {e.description && <DescriereToggle html={e.description} />}
          </div>
        </div>

        <div className="curs-bilete" id="bilete">
          <h2>Comandă bilete</h2>

          {cod && (
            <div className="cod-activ">
              <span>
                Cod <strong>{cod.code}</strong> aplicat — reducere {Number(cod.percent)}%
              </span>
              <a href={`/curs/${e.slug}`}>Renunță</a>
            </div>
          )}

          {e.sold_out ? (
            <p className="curs-order-out">S-au epuizat biletele.</p>
          ) : areStoc ? (
            <TicketPicker eventId={e.id} types={picker} slug={e.slug} codAplicat={!!cod} codGresit={codGresit} />
          ) : e.livetickets_url ? (
            // Fără checkout propriu — sau fără pool definit pe cursul ăsta —
            // vânzarea rămâne pe LiveTickets, prin același /go/course care
            // numără clicul și conversia testului A/B.
            <>
              <div className="bt-list">
                {picker.map((t) => (
                  <div key={t.id} className="bt-row">
                    <div className="bt-main">
                      <h3>
                        {t.name}
                        {t.bundle > 1 && <span className="bt-pachet">pentru {t.bundle} persoane</span>}
                      </h3>
                      {t.description && <p>{t.description}</p>}
                    </div>
                    <div className="bt-price">
                      {money(t.price)} lei
                      {t.dinData && <span className="bt-left">{t.dinData}</span>}
                    </div>
                  </div>
                ))}
              </div>
              <div className="bt-foot">
                <div className="bt-total">{!cod && <CodReducere slug={e.slug} gresit={codGresit} />}</div>
                <a href={`/go/course?id=${e.id}`} className="btn btn-primary bt-cta" target="_blank" rel="noopener">
                  Comandă bilete
                </a>
              </div>
            </>
          ) : (
            <p className="curs-order-out">Biletele nu sunt încă puse în vânzare.</p>
          )}
        </div>
      </div>
    </section>
  );
}
