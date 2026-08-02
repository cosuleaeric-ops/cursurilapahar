import { pageMetadata } from "@/lib/metadata";
import LegalPage, { DateFirma } from "../legal/LegalPage";

export const metadata = pageMetadata({
  title: "Politica de livrare - Cursuri la Pahar",
  description: "Cum și când primești biletele după ce plătești.",
  path: "/politica-de-livrare",
});

export default function LivrarePage() {
  return (
    <LegalPage titlu="Politica de livrare" actualizat="2 august 2026">
      <p>
        Biletele noastre sunt electronice. Nu trimitem nimic prin curier și nu ai de plătit niciun cost de livrare.
      </p>

      <h2>Cum ajunge biletul la tine</h2>
      <p>
        Imediat după ce plata este aprobată, îți trimitem pe email un mesaj cu biletele. Fiecare bilet are seria,
        numărul și un cod QR propriu, plus un link pe care îl poți deschide oricând de pe telefon.
      </p>
      <p>
        Emailul ajunge de obicei în câteva secunde. Adresa de la care primești este{" "}
        <strong>contact@cursurilapahar.ro</strong>.
      </p>

      <h2>Cât costă livrarea</h2>
      <p>
        Nimic. Prețul biletului este prețul final, iar livrarea electronică e inclusă.
      </p>

      <h2>Dacă ai cumpărat mai multe bilete</h2>
      <p>
        Primești bilete separate, cu coduri diferite, în același email. Le poți trimite mai departe persoanelor care
        vin cu tine - fiecare intră cu biletul lui.
      </p>

      <h2>Dacă nu primești emailul</h2>
      <ol>
        <li>Verifică folderul de spam sau, la Gmail, tabul „Promoții".</li>
        <li>Verifică dacă adresa completată la comandă e scrisă corect.</li>
        <li>
          Dacă tot nu e acolo, scrie-ne și ți-l retrimitem. Spune-ne numele cu care ai comandat sau codul comenzii,
          dacă îl ai.
        </li>
      </ol>
      <p>
        Plata ta rămâne validă chiar dacă emailul se pierde pe drum - biletele există în sistemul nostru și le putem
        retrimite oricând.
      </p>

      <h2>La intrarea în sală</h2>
      <p>
        Deschide biletul pe telefon și îl scanăm la intrare. Nu trebuie printat. Îți recomandăm să ajungi cu 15-30 de
        minute înainte de ora de start, ca să prinzi un loc bun.
      </p>

      <h2>Contact</h2>
      <DateFirma />
    </LegalPage>
  );
}
