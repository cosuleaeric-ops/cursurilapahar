import { sql } from "@/lib/db";
import { biletele } from "@/lib/comenzi";

export const dynamic = "force-dynamic";
export const metadata = { title: "Plata - Curs la Pahar" };

const pad = (n: number) => String(n).padStart(4, "0");

// Pagina la care se întoarce omul de la Netopia. Nu decide nimic: confirmarea
// vine pe IPN, server-la-server. Aici doar arătăm ce știm despre comandă.
export default async function PlataPage({ searchParams }: { searchParams: Promise<{ cod?: string }> }) {
  const { cod } = await searchParams;
  const [o] = cod
    ? ((await sql`
        SELECT o.id, o.cod, o.status, o.total, e.title, e.slug
        FROM orders o JOIN events e ON e.id = o.event_id WHERE o.cod = ${cod}
      `) as { id: number; cod: string; status: string; total: string; title: string; slug: string | null }[])
    : [];

  if (!o) {
    return (
      <section className="section cos-page">
        <div className="container container-narrow">
          <div className="cos-card cos-card--gol">
            <h1>Nu găsim comanda</h1>
            <p>Verifică linkul din email sau scrie-ne.</p>
            <a href="/#cursuri" className="btn btn-primary">Vezi cursurile</a>
          </div>
        </div>
      </section>
    );
  }

  const platita = o.status === "platita";
  const bilete = platita ? await biletele(o.id) : [];

  return (
    <section className="section cos-page">
      <div className="container container-narrow">
        <div className="cos-card">
          <h1>{platita ? "Gata, ești pe listă" : o.status === "esuata" ? "Plata nu a trecut" : "Verificăm plata"}</h1>
          <p style={{ color: "var(--text-muted)", margin: "10px 0 20px" }}>
            {platita
              ? `Ți-am trimis biletele pe email. Comanda ${o.cod}.`
              : o.status === "esuata"
                ? "Banca a refuzat plata, iar biletele nu s-au emis. Nu ți s-au luat bani."
                : "Banca ne confirmă plata în câteva secunde. Dă un refresh dacă nu se schimbă nimic."}
          </p>

          {platita && bilete.length > 0 && (
            <ul className="cos-linii">
              {bilete.map((b) => (
                <li key={b.qr_token}>
                  <span>
                    {b.tip} · {b.serie} {pad(b.numar)}
                  </span>
                  <a href={`/bilet/${b.qr_token}`} className="cos-modifica">Deschide biletul</a>
                </li>
              ))}
            </ul>
          )}

          <a href={o.slug ? `/curs/${o.slug}` : "/#cursuri"} className="btn btn-primary cos-cta" style={{ marginTop: 18 }}>
            {platita ? "Înapoi la curs" : "Încearcă din nou"}
          </a>
        </div>
      </div>
    </section>
  );
}
