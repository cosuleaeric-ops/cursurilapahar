import { decontLuna, getOrganizator } from "@/lib/bilete";
import { dataRo, lunaRo, money, PRINT_CSS } from "../print";
import PrintBar from "../PrintBar";

export const dynamic = "force-dynamic";
export const metadata = { title: "Decont impozit pe spectacole" };

// Decontul lunar de impozit pe spectacole, depus la DITL până pe 10 ale lunii
// următoare. Un rând per curs ținut în luna respectivă.
export default async function DecontPage({ searchParams }: { searchParams: Promise<{ luna?: string }> }) {
  const { luna: lunaParam } = await searchParams;
  const luna = /^\d{4}-\d{2}$/.test(lunaParam ?? "") ? lunaParam! : new Date().toISOString().slice(0, 7);
  const { luna: lunaText, an } = lunaRo(luna);

  const [org, events] = await Promise.all([getOrganizator(), decontLuna(luna)]);

  const totalIncasari = events.reduce((s, e) => s + e.incasari, 0);
  const totalImpozit = events.reduce((s, e) => s + e.impozit, 0);
  const serii = events.flatMap((e) => e.serii);
  const totalBilete = serii.reduce((s, r) => s + r.nr, 0);
  const totalValoare = serii.reduce((s, r) => s + r.total, 0);

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: PRINT_CSS }} />
      <PrintBar back="/admin/cursuri" />

      <div className="sheet">
        <div style={{ display: "flex", justifyContent: "space-between", gap: 30 }}>
          <div className="org" style={{ flex: 1 }}>
            Cod SIRUTA <span className="fill-line" />
            <br />
            Codul de identificare fiscala: {org.cui}
            <br />
            Nr reg. com. {org.regCom}
            <br />
            Adresa {org.sediu}
            <br />
            Tel/fax {org.telefon}
          </div>
          <div className="org center" style={{ flex: 1 }}>
            <strong>ROMÂNIA</strong>
            <br />
            <span className="fill-line" style={{ minWidth: 220 }} />
            <br />
            <span className="cap">(judeţ, oraş, sector)</span>
            <br />
            <strong>DIRECŢIA IMPOZITE ŞI TAXE LOCALE A</strong>
            <br />
            <span className="fill-line" style={{ minWidth: 220 }} />
          </div>
        </div>

        <h1 style={{ marginTop: 30 }}>IMPOZIT PE SPECTACOLE</h1>
        <h2>
          Decont pe luna {lunaText} anul {an}
        </h2>

        <p>
          Subscrisa, {org.nume}, cu sediul în {org.sediu}, C.I.F. {org.cui}, tel/fax {org.telefon}, e-mail{" "}
          {org.email}, reprezentata prin <span className="fill-line" style={{ minWidth: 200 }} /> în calitate de
          actionar unic /asociat/ administrator/împuternicit, declar:
        </p>

        <table>
          <thead>
            <tr>
              <th>Nr. crt.</th>
              <th>
                Tipul spectacolului si adresa la care se desfasoara acesta
              </th>
              <th>
                Încasarile din vânzarea biletelor
                <br />- lei -
              </th>
              <th>
                Încasarile reprezentând contravaloarea timbrelor
                <br />- lei -
              </th>
              <th>
                Încasarile supuse impozitului
                <br />- lei -
              </th>
              <th>Cota de impozit</th>
              <th>Impozitul datorat</th>
            </tr>
          </thead>
          <tbody>
            {events.length === 0 ? (
              <tr>
                <td colSpan={7} className="center">
                  Niciun curs cu bilete vândute în luna {lunaText} {an}.
                </td>
              </tr>
            ) : (
              events.map((e, i) => (
                <tr key={e.id}>
                  <td className="center">{i + 1}</td>
                  <td>
                    {e.title}
                    {e.location ? ` - ${e.location}` : ""} - {dataRo(e.starts_at)}
                  </td>
                  <td className="num">{money(e.incasari)}</td>
                  <td className="num">{money(e.timbre)}</td>
                  <td className="num">{money(e.baza)}</td>
                  <td className="num">{money(e.cota)}</td>
                  <td className="num">{money(e.impozit)}</td>
                </tr>
              ))
            )}
            {events.length > 1 && (
              <tr className="total">
                <td></td>
                <td>TOTAL</td>
                <td className="num">{money(totalIncasari)}</td>
                <td></td>
                <td></td>
                <td></td>
                <td className="num">{money(totalImpozit)}</td>
              </tr>
            )}
          </tbody>
        </table>

        <p>
          Prin semnarea prezentei am luat cunostinta ca declararea necorespunzatoare adevarului se pedepseste conform
          legii penale, cele declarate fiind corecte si complete.
        </p>

        <div className="sign" style={{ marginTop: 34 }}>
          <div>
            Reprezentantul legal,
            <div className="line" style={{ marginTop: 22 }} />
            <div className="cap">(numele, prenumele si semnatura)</div>
            <div className="line" style={{ marginTop: 22 }} />
            <div className="cap">(data întocmirii declaratiei)</div>
          </div>
          <div>
            Seful compartimentului contabil,
            <div className="line" style={{ marginTop: 22 }} />
            <div className="cap">(numele, prenumele si semnatura)</div>
          </div>
        </div>
      </div>

      <div className="sheet">
        <h1>
          II. Situatia biletelor si abonamentelor la spectacole, vândute în luna {lunaText} {an}
        </h1>

        <table style={{ marginTop: 24 }}>
          <thead>
            <tr>
              <th>Seria biletelor şi abonamentelor</th>
              <th>Nr. bilete şi abonamente vândute</th>
              <th>
                Preţ unitar
                <br />- lei -
              </th>
              <th>
                Valoare totala
                <br />- lei -
              </th>
              <th>
                Valoare totala fara TVA
                <br />- lei -
              </th>
              <th>
                TVA
                <br />- % -
              </th>
            </tr>
          </thead>
          <tbody>
            {serii.map((r) => (
              <tr key={r.interval}>
                <td>{r.interval}</td>
                <td className="num">{r.nr}</td>
                <td className="num">{money(r.price)}</td>
                <td className="num">{money(r.total)}</td>
                <td className="num">{money(r.total)}</td>
                <td className="num">0.00</td>
              </tr>
            ))}
            <tr className="total">
              <td>Total</td>
              <td className="num">{totalBilete}</td>
              <td></td>
              <td className="num">{money(totalValoare)}</td>
              <td className="num">{money(totalValoare)}</td>
              <td></td>
            </tr>
          </tbody>
        </table>

        <p style={{ marginTop: 26 }}>Declar ca informaţiile cuprinse în acest decont sunt corecte şi complete.</p>

        <div className="sign" style={{ marginTop: 30, justifyContent: "flex-end" }}>
          <div style={{ maxWidth: 300 }}>
            Reprezentantul legal,
            <div className="line" style={{ marginTop: 22 }} />
            <div className="cap">(numele, prenumele si semnatura)</div>
          </div>
        </div>
      </div>
    </>
  );
}
