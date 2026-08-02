import { pageMetadata } from "@/lib/metadata";
import LegalPage, { DateFirma } from "../legal/LegalPage";

export const metadata = pageMetadata({
  title: "Politica de anulare și rambursare - Cursuri la Pahar",
  description: "Când poți renunța la un bilet, cum ceri banii înapoi și în cât timp îi primești.",
  path: "/politica-de-anulare",
});

export default function AnularePage() {
  return (
    <LegalPage titlu="Politica de anulare și rambursare" actualizat="2 august 2026">
      <p>
        Pe scurt: dacă nu mai poți veni, anunță-ne cu cel puțin 24 de ore înainte și îți dăm banii înapoi. Dacă
        anulăm noi cursul, primești integral banii înapoi, fără să ceri.
      </p>

      <h2>1. Dreptul legal de retragere</h2>
      <p>
        Biletele la evenimente cu dată fixă fac parte dintre serviciile exceptate de la dreptul de retragere în 14
        zile, conform art. 16 lit. l) din OUG nr. 34/2014. Motivul e simplu: locul rezervat pentru tine nu mai poate
        fi vândut altcuiva în ultimul moment.
      </p>
      <p>
        Cu toate astea, am ales să îți oferim o politică mai generoasă decât ne obligă legea. Iată care e:
      </p>

      <h2>2. Când renunți tu</h2>
      <table>
        <thead>
          <tr>
            <th>Când ne anunți</th>
            <th>Ce primești</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Cu cel puțin 24 de ore înainte de începerea cursului</td>
            <td>Banii înapoi, integral</td>
          </tr>
          <tr>
            <td>Cu mai puțin de 24 de ore înainte</td>
            <td>
              Nu putem returna banii, dar poți trimite pe altcineva în locul tău - dă-i pur și simplu biletul
            </td>
          </tr>
          <tr>
            <td>Nu vii și nu ne anunți</td>
            <td>Biletul se pierde</td>
          </tr>
        </tbody>
      </table>
      <p>
        Biletul e transferabil oricând. Dacă nu mai poți ajunge, cea mai simplă soluție e să-l dai unui prieten -
        contează codul de pe bilet, nu numele.
      </p>

      <h2>3. Când anulăm noi</h2>
      <p>
        Dacă anulăm un curs, îți returnăm integral banii, fără să fie nevoie să ceri. Te anunțăm pe email și primești
        suma înapoi pe cardul cu care ai plătit.
      </p>
      <p>
        Dacă mutăm cursul pe altă dată, îți scriem. Poți veni la data nouă cu același bilet sau, dacă nu îți convine,
        ne spui și îți returnăm banii.
      </p>

      <h2>4. Cum ceri banii înapoi</h2>
      <p>
        Scrie-ne pe email, de la adresa cu care ai comandat. Spune-ne codul comenzii, dacă îl ai la îndemână, sau
        numele cu care ai cumpărat și cursul la care te-ai înscris.
      </p>
      <p>
        Nu ai de completat niciun formular. Confirmăm cererea pe email în aceeași zi lucrătoare.
      </p>

      <h2>5. În cât timp primești banii</h2>
      <p>
        Returnăm suma pe <strong>același card</strong> cu care ai plătit, în cel mult 14 zile de la acceptarea
        cererii. De obicei mult mai repede - în practică, în 1-3 zile lucrătoare.
      </p>
      <p>
        Timpul până când banii apar efectiv în cont depinde și de banca ta. Dacă au trecut mai mult de 14 zile și tot
        nu vezi suma, scrie-ne și verificăm împreună.
      </p>
      <p>Nu percepem niciun comision pentru returnare.</p>

      <h2>6. Dacă ceva n-a fost în regulă la curs</h2>
      <p>
        Dacă ai ajuns la curs și ceva a mers prost - nu ai putut intra, evenimentul a fost substanțial diferit de ce
        am anunțat - scrie-ne. Ne uităm la fiecare caz în parte și, dacă am greșit, returnăm banii.
      </p>

      <h2>7. Contact</h2>
      <DateFirma />
    </LegalPage>
  );
}
