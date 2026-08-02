import { pageMetadata } from "@/lib/metadata";
import LegalPage, { DateFirma } from "../legal/LegalPage";

export const metadata = pageMetadata({
  title: "Termeni și condiții - Cursuri la Pahar",
  description: "Condițiile în care cumperi bilete la cursurile Cursuri la Pahar.",
  path: "/termeni-si-conditii",
});

export default function TermeniPage() {
  return (
    <LegalPage titlu="Termeni și condiții" actualizat="2 august 2026">
      <p>
        Documentul acesta stabilește condițiile în care cumperi bilete de pe cursurilapahar.ro. Prin plasarea unei
        comenzi confirmi că le-ai citit și că ești de acord cu ele.
      </p>

      <h2>1. Cine îți vinde biletul</h2>
      <DateFirma />
      <p>
        Ne găsești cel mai repede pe email. Răspundem în general în aceeași zi lucrătoare.
      </p>

      <h2>2. Ce vindem</h2>
      <p>
        Vindem bilete de intrare la cursuri și evenimente proprii, organizate în locații din București. Fiecare curs
        are propria pagină, unde găsești tema, speakerul, data, ora, locația, durata și prețul biletelor.
      </p>
      <p>
        Un curs durează în jur de două ore și se ține fizic, la locația anunțată. Accesul în sală începe de obicei cu
        30 de minute înainte de ora de start.
      </p>

      <h2>3. Prețuri</h2>
      <p>
        Toate prețurile sunt exprimate în <strong>lei (RON)</strong> și sunt prețuri finale pentru un bilet. Nu
        adăugăm comisioane, taxe de procesare sau alte costuri la final: cât scrie pe pagina cursului, atât plătești.
      </p>
      <p>
        Unele tipuri de bilet se vând la pachet, de exemplu „1+1". În cazul lor, prețul afișat este{" "}
        <strong>prețul pentru fiecare bilet</strong>, iar oferta se aplică atunci când adaugi în coș numărul de bilete
        din pachet. Suma totală o vezi întotdeauna în coș, înainte să plătești.
      </p>
      <p>
        Ne rezervăm dreptul de a modifica prețurile pentru cursurile viitoare. Prețul valabil pentru comanda ta este
        cel afișat în momentul în care plasezi comanda.
      </p>

      <h2>4. Cum comanzi</h2>
      <ol>
        <li>Alegi cursul și tipul de bilet, apoi numărul de bilete.</li>
        <li>În coș verifici ce ai ales și completezi numele și adresa de email.</li>
        <li>Ești trimis pe pagina securizată a procesatorului de plăți, unde introduci datele cardului.</li>
        <li>După ce plata este aprobată, primești biletele pe email.</li>
      </ol>
      <p>
        Din momentul în care ajungi la pagina de plată, biletele alese îți sunt rezervate 30 de minute. Dacă plata nu
        se finalizează în acest interval, rezervarea expiră și biletele se întorc în vânzare.
      </p>
      <p>
        Contractul dintre noi se încheie în momentul în care plata este confirmată și primești biletele. Până atunci,
        comanda este doar o rezervare.
      </p>

      <h2>5. Plata</h2>
      <p>
        Plata se face online, cu cardul, prin <strong>NETOPIA Payments</strong>. Acceptăm carduri Visa și Mastercard,
        de debit sau de credit.
      </p>
      <p>
        Datele cardului tău se introduc direct pe pagina securizată a procesatorului și{" "}
        <strong>nu ajung niciodată pe serverele noastre</strong>. Noi primim doar confirmarea că plata a fost
        aprobată. Tranzacțiile sunt protejate prin 3-D Secure.
      </p>
      <p>
        Dacă plata este refuzată, comanda nu se finalizează și nu ți se ia niciun ban. Poți încerca din nou, cu
        același card sau cu altul.
      </p>

      <h2>6. Cum primești biletele</h2>
      <p>
        Biletele sunt electronice și ajung pe email imediat după confirmarea plății. Detaliile sunt în{" "}
        <a href="/politica-de-livrare">politica de livrare</a>.
      </p>

      <h2>7. Accesul la curs</h2>
      <p>
        La intrare arăți biletul de pe telefon. Fiecare bilet are un cod unic care se scanează o singură dată, așa că
        îți recomandăm să nu îl distribui mai departe. Nu e nevoie să îl printezi.
      </p>
      <p>
        Un bilet dă dreptul la o singură intrare, pentru o singură persoană. Dacă ai cumpărat mai multe bilete,
        primești bilete distincte, cu coduri diferite, pe care le poți trimite fiecărei persoane.
      </p>
      <p>
        La unele cursuri se servesc băuturi alcoolice. Accesul este permis persoanelor de peste 18 ani și putem cere
        un act de identitate.
      </p>

      <h2>8. Anulări și rambursări</h2>
      <p>
        Condițiile complete sunt în <a href="/politica-de-anulare">politica de anulare și rambursare</a>. Pe scurt:
        îți dăm banii înapoi dacă anulezi cu cel puțin 24 de ore înainte de curs, iar dacă anulăm noi cursul primești
        integral banii înapoi.
      </p>

      <h2>9. Dacă un curs se schimbă</h2>
      <p>
        Se poate întâmpla să fim nevoiți să schimbăm data, ora, locația sau speakerul unui curs. Te anunțăm pe email
        de îndată ce știm. Dacă schimbarea nu îți convine, îți returnăm integral banii.
      </p>
      <p>
        Nu răspundem pentru situații care nu depind de noi - calamități, restricții impuse de autorități, indisponibilitatea
        neprevăzută a locației sau alte cazuri de forță majoră. Și în aceste situații îți returnăm banii pe bilete.
      </p>

      <h2>10. Datele tale</h2>
      <p>
        Prelucrăm datele tale personale conform{" "}
        <a href="/politica-de-confidentialitate">politicii de confidențialitate</a>, care explică ce colectăm, de ce,
        cu cine împărțim și ce drepturi ai.
      </p>

      <h2>11. Conținutul site-ului</h2>
      <p>
        Textele, imaginile, logoul și materialele de pe site ne aparțin sau le folosim cu acordul autorilor. Nu le
        poți refolosi comercial fără acordul nostru scris.
      </p>
      <p>
        La cursuri filmăm și fotografiem pentru promovare. Dacă nu vrei să apari în materiale, spune-ne la fața
        locului sau scrie-ne pe email și ne asigurăm că nu te includem.
      </p>

      <h2>12. Reclamații și soluționarea disputelor</h2>
      <p>
        Dacă ceva nu a mers cum trebuie, scrie-ne întâi nouă pe email - de obicei rezolvăm repede și direct.
      </p>
      <p>Dacă nu ajungem la o înțelegere, te poți adresa:</p>
      <ul>
        <li>
          Autorității Naționale pentru Protecția Consumatorilor -{" "}
          <a href="https://anpc.ro/" target="_blank" rel="noopener">
            anpc.ro
          </a>
        </li>
        <li>
          Platformei de soluționare alternativă a litigiilor (SAL) -{" "}
          <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener">
            anpc.ro/ce-este-sal
          </a>
        </li>
        <li>
          Platformei europene de soluționare online a litigiilor (SOL) -{" "}
          <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener">
            ec.europa.eu/consumers/odr
          </a>
        </li>
      </ul>

      <h2>13. Modificarea termenilor</h2>
      <p>
        Putem actualiza documentul acesta. Versiunea care se aplică comenzii tale este cea publicată în momentul în
        care ai plasat comanda. Data ultimei actualizări o vezi sus.
      </p>

      <h2>14. Legea aplicabilă</h2>
      <p>
        Acestor termeni li se aplică legea română. Eventualele litigii se soluționează de instanțele competente din
        România.
      </p>
    </LegalPage>
  );
}
