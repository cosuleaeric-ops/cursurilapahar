import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { getOrganizator, vizareRows } from "@/lib/bilete";
import { getSettings } from "@/lib/settings";
import { dataRo, money, PRINT_CSS } from "@/app/admin/bilete/print";
import PrintBar from "@/app/admin/bilete/PrintBar";

export const dynamic = "force-dynamic";
export const metadata = { title: "Cerere vizare bilete" };

// Formularul depus la DITL înainte de eveniment, conform art. 481 alin. (4) lit. a
// din Legea 227/2015. Structura urmează formularul pe care îl genera LiveTickets.
export default async function VizarePage({ params }: { params: Promise<{ id: string }> }) {
  const { id: idStr } = await params;
  const id = Number(idStr);
  if (!id) notFound();

  const [event] = (await sql`
    SELECT id, title, starts_at, location FROM events WHERE id = ${id}
  `) as { id: number; title: string; starts_at: string | null; location: string | null }[];
  if (!event) notFound();

  const [org, rows, settings] = await Promise.all([getOrganizator(), vizareRows(id), getSettings()]);
  if (!rows.length) notFound();

  const oras = typeof settings.oras_evenimente === "string" && settings.oras_evenimente ? String(settings.oras_evenimente) : "București";
  const totalNr = rows.reduce((s, r) => s + r.nr, 0);
  const totalVal = rows.reduce((s, r) => s + r.total, 0);
  const zi = dataRo(event.starts_at);

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: PRINT_CSS }} />
      <PrintBar back={`/admin/cursuri/${id}/bilete`} />

      <div className="sheet">
        <div className="org">
          Organizator: {org.nume}
          <br />
          Sediul: {org.sediu}
          <br />
          Cod fiscal: {org.cui}
          <br />
          Nr. înreg. {org.regCom}
          <br />
          Telefon/fax: {org.telefon}
          <br />
          E-mail: {org.email}
        </div>

        <div className="to">
          Către
          <br />
          <br />
          COMPARTIMENTUL DE SPECIALITATE AL AUTORITĂŢILOR
          <br />
          ADMINISTRAŢIEI PUBLICE LOCALE A <span className="fill" />
          <br />
          DIRECŢIA BUGET, FINANŢE, TAXE ŞI IMPOZITE
        </div>

        <h1>CERERE</h1>
        <h2>pentru înregistrare/vizare a abonamentelor şi a biletelor de intrare la spectacole</h2>

        <p>
          În conformitate cu prevederile art. 481, alin. (4), lit. a din Legea nr. 227/2015 privind Codul Fiscal, cu
          modificările şi completările ulterioare vă rugăm să înregistraţi/vizaţi abonamentele/ biletele de intrare la
          spectacole, prevăzute în tabelul de mai jos:
        </p>

        <table>
          <thead>
            <tr>
              <th>
                Felul, nr. şi data doc. de la
                <br />
                unitatea tipografică
              </th>
              <th>
                Numărul de abonamente/
                <br />
                bilete de intrare
              </th>
              <th>
                Tariful
                <br />
                (lei/buc)
              </th>
              <th>
                Valoarea totală
                <br />
                (lei)
              </th>
              <th>
                Seria
                <br />
                abonamentelor/biletelor
              </th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.interval}>
                <td>{r.name} - ONLINE</td>
                <td className="num">{r.nr}</td>
                <td className="num">{money(r.price)}</td>
                <td className="num">{money(r.total)}</td>
                <td>{r.interval}</td>
              </tr>
            ))}
            <tr className="total">
              <td>TOTAL</td>
              <td className="num">{totalNr}</td>
              <td></td>
              <td className="num">{money(totalVal)}</td>
              <td></td>
            </tr>
          </tbody>
        </table>

        <p>
          Aceste abonamente/bilete ne sunt necesare pentru spectacolul {event.title}, pe care le organizăm în
          localitatea {oras}, sediul (locul desfăşurării spectacolului) {event.location ?? ""}, în perioada {zi} -{" "}
          {zi}.
        </p>

        <p className="center" style={{ marginTop: 26, fontStyle: "italic" }}>
          Persoana împuternicită din partea organizatorului de spectacole:
        </p>

        <div className="sign">
          <div>
            <div className="line" />
            <div className="cap">(numele si prenumele)</div>
          </div>
          <div>
            <div className="line" />
            <div className="cap">(funcția)</div>
          </div>
          <div>
            <div className="line" />
            <div className="cap">(semnătura / ștampila)</div>
          </div>
        </div>

        <p style={{ marginTop: 30 }}>
          S-au înregistrat/ vizat <span className="fill-line" /> abonamente de intrare, în valoare totală de{" "}
          <span className="fill-line" /> lei.
        </p>
        <p>
          S-au înregistrat/ vizat <span className="fill-line" /> bilete de intrare, în valoare totală de{" "}
          <span className="fill-line" /> lei.
        </p>

        <div className="center" style={{ marginTop: 26 }}>
          FUNCŢIONARUL PUBLIC
          <div style={{ display: "flex", alignItems: "flex-end", gap: 8, marginTop: 14 }}>
            <span>L.S.</span>
            <div style={{ flex: 1 }}>
              <div className="line" />
              <div className="cap">(numele şi prenumele / semnătura)</div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
