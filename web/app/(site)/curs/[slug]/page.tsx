import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { descriereText } from "@/lib/descriere";
import { pageMetadata } from "@/lib/metadata";
import { checkoutPropriuActiv } from "@/lib/checkout";
import TicketPicker, { type PickerType } from "./TicketPicker";

export const dynamic = "force-dynamic";

const TZ = "Europe/Bucharest";
const ziData = new Intl.DateTimeFormat("ro-RO", {
  timeZone: TZ,
  weekday: "long",
  day: "numeric",
  month: "long",
  year: "numeric",
});
const oraFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: TZ, hour: "2-digit", minute: "2-digit" });

type Ev = {
  id: number;
  slug: string;
  title: string;
  starts_at: string | null;
  location: string | null;
  image_url: string | null;
  speaker_name: string | null;
  description: string | null;
  sold_out: boolean;
  livetickets_url: string | null;
  active: boolean;
};

async function getEvent(slug: string): Promise<Ev | null> {
  const rows = (await sql`
    SELECT id, slug, title, starts_at, location, image_url, speaker_name, description,
           sold_out, livetickets_url, active
    FROM events WHERE slug = ${slug}
  `) as Ev[];
  return rows[0] ?? null;
}

// Titlul de card taie sufixul „// 12 mai" folosit pe LiveTickets (cardTitle din homepage).
const curat = (t: string) => t.replace(/\s+\/\/\s+.+$/u, "");

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const e = await getEvent(slug);
  if (!e) return {};
  return pageMetadata({
    title: `${curat(e.title)} — Curs la Pahar`,
    description: e.description ? descriereText(e.description) : "Un curs la un pahar, într-un bar din București.",
    path: `/curs/${e.slug}`,
    ogImage: e.image_url ?? undefined,
  });
}

export default async function CursPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const e = await getEvent(slug);
  if (!e || !e.active) notFound();

  const types = (await sql`
    SELECT t.id, t.name, t.price,
           COUNT(p.id) FILTER (WHERE p.status = 'liber')::int AS libere
    FROM ticket_types t LEFT JOIN ticket_pool p ON p.type_id = t.id
    WHERE t.event_id = ${e.id}
    GROUP BY t.id ORDER BY t.position, t.id
  `) as { id: number; name: string; price: string; libere: number }[];

  const picker: PickerType[] = types.map((t) => ({
    id: t.id,
    name: t.name,
    price: Number(t.price),
    libere: t.libere,
  }));
  const areStoc = picker.some((t) => t.libere > 0);
  const checkoutPropriu = await checkoutPropriuActiv();
  const d = e.starts_at ? new Date(e.starts_at) : null;

  return (
    <section className="section curs-page">
      <div className="container">
        <a href="/#cursuri" className="curs-back">
          ← Toate cursurile
        </a>

        <div className="curs-top">
          <div className="curs-poster">
            {e.image_url ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={e.image_url} alt={curat(e.title)} />
            ) : (
              <div className="curs-poster-empty" />
            )}
            {e.sold_out && <div className="sold-out-badge">SOLD OUT</div>}
          </div>

          <div className="curs-head">
            <h1 className="curs-title">{curat(e.title)}</h1>

            <div className="curs-facts">
              {d && (
                <div className="curs-fact">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 6v6l4 2" />
                  </svg>
                  <span>
                    {ziData.format(d)}
                    <em>ora {oraFmt.format(d)}</em>
                  </span>
                </div>
              )}
              {e.location && (
                <div className="curs-fact">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
                  <span>{e.location}</span>
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
          </div>
        </div>

        <div className="curs-grid">
          <div className="curs-desc">
            {e.description ? (
              <div className="curs-desc-html" dangerouslySetInnerHTML={{ __html: e.description }} />
            ) : (
              <p className="curs-desc-gol">Detaliile cursului vin în curând.</p>
            )}
          </div>

          <aside className="curs-order" id="bilete">
            <h2>Comandă bilete</h2>
            {e.sold_out ? (
              <p className="curs-order-out">S-au epuizat biletele.</p>
            ) : checkoutPropriu && areStoc ? (
              <TicketPicker eventId={e.id} types={picker} />
            ) : e.livetickets_url ? (
              // Fără checkout propriu — sau fără pool definit pe cursul ăsta —
              // vânzarea rămâne pe LiveTickets, prin același /go/course care
              // numără clicul și conversia testului A/B.
              <>
                {picker.length > 0 && (
                  <ul className="curs-preturi">
                    {picker.map((t) => (
                      <li key={t.id}>
                        <span>{t.name}</span>
                        <span>{t.price.toLocaleString("ro-RO", { minimumFractionDigits: 2 })} lei</span>
                      </li>
                    ))}
                  </ul>
                )}
                <a href={`/go/course?id=${e.id}`} className="btn btn-primary tp-cta" target="_blank" rel="noopener">
                  Vreau să vin
                </a>
              </>
            ) : (
              <p className="curs-order-out">Biletele nu sunt încă puse în vânzare.</p>
            )}
          </aside>
        </div>
      </div>
    </section>
  );
}
