import { pageMetadata } from "@/lib/metadata";
import LegalPage, { DateFirma } from "../legal/LegalPage";

export const metadata = pageMetadata({
  title: "Politica de confidențialitate - Cursuri la Pahar",
  description: "Ce date personale prelucrăm, de ce, cu cine le împărțim și ce drepturi ai.",
  path: "/politica-de-confidentialitate",
});

export default function ConfidentialitatePage() {
  return (
    <LegalPage titlu="Politica de confidențialitate și GDPR" actualizat="2 august 2026">
      <p>
        Documentul explică ce date personale colectăm, de ce, cât le ținem și ce poți cere de la noi. E scris ca să
        se înțeleagă, nu ca să bifeze o casetă.
      </p>

      <h2>1. Cine răspunde de datele tale</h2>
      <p>Operatorul datelor este:</p>
      <DateFirma />
      <p>
        Pentru orice întrebare legată de datele tale, scrie-ne pe adresa de email de mai sus. Îți răspundem în cel
        mult 30 de zile, de obicei mult mai repede.
      </p>

      <h2>2. Ce date colectăm și de ce</h2>
      <table>
        <thead>
          <tr>
            <th>Ce colectăm</th>
            <th>Când</th>
            <th>De ce</th>
            <th>Temei legal</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Nume și adresă de email</td>
            <td>Când cumperi un bilet</td>
            <td>Ca să-ți emitem biletul, să ți-l trimitem și să te putem anunța dacă se schimbă ceva la curs</td>
            <td>Executarea contractului</td>
          </tr>
          <tr>
            <td>Detaliile comenzii și ale biletelor</td>
            <td>Când cumperi un bilet</td>
            <td>Evidența vânzărilor și registrul de bilete cerut de lege</td>
            <td>Obligație legală</td>
          </tr>
          <tr>
            <td>Nume, email, mesaj</td>
            <td>Când completezi un formular de contact sau de colaborare</td>
            <td>Ca să-ți răspundem</td>
            <td>Interes legitim</td>
          </tr>
          <tr>
            <td>Adresă de email</td>
            <td>Când te abonezi la newsletter</td>
            <td>Ca să-ți trimitem anunțuri despre cursuri</td>
            <td>Consimțământ</td>
          </tr>
          <tr>
            <td>Date tehnice (cookie de test, preferințe)</td>
            <td>Când navighezi pe site</td>
            <td>Ca site-ul să funcționeze și ca să vedem ce variantă de pagină merge mai bine</td>
            <td>Interes legitim</td>
          </tr>
        </tbody>
      </table>

      <h3>Datele cardului</h3>
      <p>
        <strong>Nu colectăm și nu stocăm date de card.</strong> Numărul cardului, data expirării și codul CVV se
        introduc direct pe pagina securizată a procesatorului de plăți. Noi primim doar confirmarea că plata a fost
        aprobată și un identificator al tranzacției.
      </p>

      <h2>3. Cu cine împărțim datele</h2>
      <p>
        Nu vindem datele nimănui. Le împărțim doar cu furnizorii de care avem nevoie ca să funcționeze serviciul:
      </p>
      <ul>
        <li>
          <strong>NETOPIA Payments</strong> - procesarea plăților cu cardul.
        </li>
        <li>
          <strong>Brevo</strong> - trimiterea emailurilor legate de comandă (biletele, confirmări, anunțuri despre
          curs).
        </li>
        <li>
          <strong>Kit</strong> - trimiterea newsletterului, dacă te-ai abonat.
        </li>
        <li>
          <strong>Vercel</strong> și <strong>Neon</strong> - găzduirea site-ului și a bazei de date, în Uniunea
          Europeană.
        </li>
        <li>
          <strong>Meta</strong> - măsurarea eficienței reclamelor, dacă ai ajuns pe site dintr-o reclamă.
        </li>
      </ul>
      <p>
        Putem transmite date și autorităților, atunci când legea ne obligă - de exemplu registrul biletelor către
        primărie, pentru impozitul pe spectacole.
      </p>

      <h2>4. Cât ținem datele</h2>
      <ul>
        <li>
          <strong>Comenzi și bilete:</strong> 10 ani, cât ne obligă legislația fiscală și contabilă.
        </li>
        <li>
          <strong>Mesaje din formulare:</strong> până la 2 ani de la ultima discuție.
        </li>
        <li>
          <strong>Abonarea la newsletter:</strong> până când te dezabonezi. Ai link de dezabonare în fiecare email.
        </li>
      </ul>

      <h2>5. Drepturile tale</h2>
      <p>Conform Regulamentului (UE) 2016/679 (GDPR), ai dreptul:</p>
      <ul>
        <li>să afli ce date avem despre tine și să primești o copie;</li>
        <li>să ceri corectarea datelor greșite;</li>
        <li>să ceri ștergerea datelor, atunci când nu suntem obligați legal să le păstrăm;</li>
        <li>să ceri limitarea prelucrării;</li>
        <li>să primești datele într-un format pe care îl poți muta în altă parte;</li>
        <li>să te opui prelucrării făcute pe interes legitim;</li>
        <li>să îți retragi consimțământul oricând, pentru newsletter.</li>
      </ul>
      <p>
        Scrie-ne pe email și rezolvăm. Dacă nu ești mulțumit de răspuns, te poți adresa Autorității Naționale de
        Supraveghere a Prelucrării Datelor cu Caracter Personal -{" "}
        <a href="https://www.dataprotection.ro/" target="_blank" rel="noopener">
          dataprotection.ro
        </a>
        .
      </p>

      <h2>6. Cookie-uri</h2>
      <p>Folosim un număr mic de cookie-uri:</p>
      <ul>
        <li>
          <strong>Funcționale</strong> - rețin varianta de pagină pe care ai văzut-o, ca să nu ți se schimbe de la un
          click la altul, și sesiunea de administrare, dacă ești administrator.
        </li>
        <li>
          <strong>De măsurare a reclamelor</strong> - dacă ai venit dintr-o reclamă, ne ajută să vedem dacă a
          funcționat.
        </li>
      </ul>
      <p>
        Poți șterge sau bloca cookie-urile din setările browserului. Site-ul rămâne funcțional, dar unele lucruri se
        pot comporta ciudat.
      </p>

      <h2>7. Securitate</h2>
      <p>
        Site-ul rulează exclusiv pe HTTPS. Accesul la datele comenzilor este restricționat și protejat prin parolă.
        Datele de card nu trec prin sistemele noastre.
      </p>

      <h2>8. Copii</h2>
      <p>
        Cursurile noastre se adresează adulților. Nu colectăm cu bună știință date de la persoane sub 16 ani. Dacă
        afli că s-a întâmplat, scrie-ne și ștergem datele.
      </p>

      <h2>9. Modificări</h2>
      <p>
        Dacă schimbăm ceva important aici, actualizăm data de sus. Te încurajăm să arunci un ochi din când în când.
      </p>
    </LegalPage>
  );
}
