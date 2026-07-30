import { notFound } from "next/navigation";
import { sql } from "@/lib/db";
import { casareRows, getOrganizator } from "@/lib/bilete";
import { money, PRINT_CSS } from "@/app/admin/bilete/print";
import PrintBar from "@/app/admin/bilete/PrintBar";

export const dynamic = "force-dynamic";
export const metadata = { title: "Proces-verbal casare bilete" };

// PV-ul de anulare a biletelor rămase nevândute, întocmit la DITL după eveniment.
export default async function CasarePage({ params }: { params: Promise<{ id: string }> }) {
  const { id: idStr } = await params;
  const id = Number(idStr);
  if (!id) notFound();

  const [event] = (await sql`SELECT id, title FROM events WHERE id = ${id}`) as { id: number; title: string }[];
  if (!event) notFound();

  const [org, rows] = await Promise.all([getOrganizator(), casareRows(id)]);

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: PRINT_CSS }} />
      <PrintBar back={`/admin/cursuri/${id}/bilete`} />

      <div className="sheet">
        <div style={{ display: "flex", justifyContent: "space-between", gap: 30 }}>
          <div className="org" style={{ flex: 1 }}>
            ROMÂNIA
            <br />
            <span className="fill-line" style={{ minWidth: 200 }} />
            <br />
            <span className="cap">(judeţ, oraş, sector)</span>
            <br />
            DIRECŢIA IMPOZITE ŞI TAXE LOCALE A
            <br />
            <span className="fill-line" style={{ minWidth: 200 }} />
          </div>
          <div className="org" style={{ flex: 1, textAlign: "right" }}>
            ORGANIZATOR SPECTACOLE
            <br />
            <strong>{org.nume}</strong>
            <br />
            {org.sediu}
            <br />
            CIF: {org.cui}
            <br />
            E-mail: {org.email}
            <br />
            Nume eveniment: {event.title}
          </div>
        </div>

        <h1 style={{ marginTop: 34 }}>PROCES-VERBAL</h1>

        <p style={{ marginTop: 20 }}>
          Încheiat astăzi, <span className="fill-line" style={{ minWidth: 40 }} />/
          <span className="fill-line" style={{ minWidth: 40 }} />/
          <span className="fill-line" style={{ minWidth: 70 }} /> la{" "}
          <span className="fill-line" style={{ minWidth: 160 }} />
        </p>

        <p>
          Subsemnatul(a) <span className="fill-line" style={{ minWidth: 190 }} /> din cadrul{" "}
          <span className="fill-line" style={{ minWidth: 190 }} />, Serviciul Constatare Persoane Juridice, având
          funcţia de <span className="fill-line" style={{ minWidth: 170 }} />, în prezenţa{" "}
          <span className="fill-line" style={{ minWidth: 190 }} /> (reprezentantul organizatorului de spectacole) şi a{" "}
          <span className="fill-line" style={{ minWidth: 190 }} /> (gestionarul abonamentelor/biletelor), am procedat
          la anularea seriilor/numerelor pentru bilete de intrare la spectacole, rămase nevândute, şi care nu mai pot
          fi utilizate, după cum urmează:
        </p>

        <table>
          <thead>
            <tr>
              <th>Seria biletelor şi abonamentelor</th>
              <th>Număr total</th>
              <th>
                Preţ unitar
                <br />- lei -
              </th>
              <th>
                Valoare totală
                <br />- lei -
              </th>
              <th>Observaţii</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 ? (
              <tr>
                <td colSpan={5} className="center">
                  Nu au rămas bilete nevândute.
                </td>
              </tr>
            ) : (
              rows.map((r) => (
                <tr key={r.interval}>
                  <td>{r.interval}</td>
                  <td className="num">{r.nr}</td>
                  <td className="num">{money(r.price)}</td>
                  <td className="num">{money(r.total)}</td>
                  <td>{r.name}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>

        <p>
          Prezentul proces-verbal s-a întocmit în două exemplare, identic egale din punct de vedere juridic, câte unul
          la fiecare parte, spre a servi la descărcarea din evidenţe.
        </p>

        <div className="sign" style={{ marginTop: 40 }}>
          <div>
            Funcţionarul public,
            <div className="line" style={{ marginTop: 22 }} />
            <div className="cap">(numele, prenumele si functia)</div>
            <div className="line" style={{ marginTop: 22 }} />
            <div className="cap">(data întocmirii declaratiei)</div>
          </div>
          <div>
            Reprezentantul organizatorului de spectacole,
            <div className="line" style={{ marginTop: 22 }} />
            <div className="cap">(numele, prenumele si semnatura)</div>
          </div>
        </div>
      </div>
    </>
  );
}
