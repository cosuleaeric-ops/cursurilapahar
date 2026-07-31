// Toate emailurile din jurul unui bilet: confirmare, probleme la plată,
// aduceri-aminte, anulări, retururi. Aceeași ramă ca la confirmările de
// formular (lib/brevo.ts) - banner sus, corp serif, rețelele jos.

const IG = "https://instagram.com/cursurilapahar";
const LNK = "color:#7a2733;font-weight:bold;text-decoration:none";
const BANNER = "https://cursurilapahar.ro/assets/images/email.jpeg";
const ROSU = "#e5484d";

export type Bilet = { serie: string; numar: string; tip: string; link: string };

export type CtxEmail = {
  nume: string;
  curs: string;
  data: string;
  ora: string;
  acces: string;
  locatie: string;
  adresa: string;
  bilete: Bilet[];
  total: string;
  linkCurs: string;
  linkComanda: string;
  motiv?: string;
  dataNoua?: string;
};

const buton = (href: string, text: string) =>
  `<p style="margin:0 0 20px"><a href="${href}" style="display:inline-block;background:${ROSU};color:#fff;font-weight:bold;text-decoration:none;padding:14px 28px;border-radius:4px;font-family:Arial,Helvetica,sans-serif">${text}</a></p>`;

const detalii = (c: CtxEmail) =>
  '<table cellpadding="0" cellspacing="0" style="width:100%;background:#faf9f7;border:1px solid #eae7e1;border-radius:8px;margin:0 0 20px">' +
  '<tr><td style="padding:16px 18px;font-size:15px;line-height:1.6;color:#2b2b2b">' +
  `<div style="font-weight:bold;font-size:17px;margin-bottom:8px">${c.curs}</div>` +
  `<div>${c.data}, ora ${c.ora} (acces de la ora ${c.acces})</div>` +
  `<div>${c.locatie}${c.adresa ? `, ${c.adresa}` : ""}</div>` +
  "</td></tr></table>";

const listaBilete = (c: CtxEmail) =>
  '<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 20px">' +
  c.bilete
    .map(
      (b) =>
        '<tr><td style="padding:12px 0;border-bottom:1px solid #eae7e1;font-size:15px">' +
        `<b>${b.tip}</b><br><span style="font-family:monospace;color:#6f6a66">${b.serie} ${b.numar}</span>` +
        `</td><td align="right" style="padding:12px 0;border-bottom:1px solid #eae7e1">` +
        `<a href="${b.link}" style="${LNK}">Deschide biletul</a></td></tr>`,
    )
    .join("") +
  "</table>";

export const EMAILURI: Record<string, { cand: string; subject: (c: CtxEmail) => string; body: (c: CtxEmail) => string }> = {
  comanda: {
    cand: "Imediat după ce plata a trecut. Conține biletele.",
    subject: (c) => `Biletele tale pentru ${c.curs} 🎟️`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      "<p style=\"margin:0 0 18px\">Gata, ești pe listă. Mai jos ai biletele - le poți deschide de pe telefon direct la intrare, nu trebuie să le printezi.</p>" +
      detalii(c) +
      listaBilete(c) +
      `<p style="margin:0 0 18px">Ai plătit <b>${c.total}</b>. Dacă vrei factura, scrie-ne și ți-o trimitem.</p>` +
      buton(c.bilete[0]?.link ?? c.linkCurs, "Vezi biletele") +
      "<p style=\"margin:0 0 14px\">Ne vedem acolo. Vino cu 30 de minute mai devreme dacă vrei loc bun și un pahar înainte să înceapă.</p>",
  },

  plata_esuata: {
    cand: "Când banca refuză plata sau tranzacția pică.",
    subject: (c) => `Plata nu a trecut pentru ${c.curs}`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      "<p style=\"margin:0 0 18px\">Banca ta a refuzat plata, așa că biletele nu s-au emis. Nu ți s-au luat bani - dacă vezi o sumă blocată pe card, se eliberează singură în câteva zile.</p>" +
      detalii(c) +
      "<p style=\"margin:0 0 18px\">De obicei se rezolvă dintr-o încercare cu alt card sau după ce aprobi plata din aplicația băncii. Locurile nu sunt rezervate până nu intră plata, așa că nu aștepta prea mult.</p>" +
      buton(c.linkComanda, "Încearcă din nou") +
      "<p style=\"margin:0 0 14px\">Dacă tot nu merge, scrie-ne și rezolvăm noi.</p>",
  },

  reminder_24h: {
    cand: "Cu o zi înainte de curs, dimineața.",
    subject: (c) => `Mâine ne vedem la ${c.curs}`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      "<p style=\"margin:0 0 18px\">Mâine e ziua. Îți lăsăm detaliile aici, ca să le ai la îndemână.</p>" +
      detalii(c) +
      listaBilete(c) +
      `<p style="margin:0 0 18px">Accesul se face de la ora ${c.acces}. Vino mai devreme dacă vrei să prinzi loc bun.</p>` +
      buton(c.linkCurs, "Vezi pagina cursului") +
      "<p style=\"margin:0 0 14px\">Dacă nu mai poți ajunge, spune-ne azi - încă putem da locul altcuiva.</p>",
  },

  reminder_azi: {
    cand: "În ziua cursului, cu 3 ore înainte.",
    subject: (c) => `Azi, ora ${c.ora} - ${c.curs}`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      `<p style="margin:0 0 18px">Ne vedem în câteva ore. Accesul e de la ora ${c.acces}, cursul începe la ${c.ora}.</p>` +
      detalii(c) +
      listaBilete(c) +
      "<p style=\"margin:0 0 14px\">Ține biletul la îndemână pe telefon, îl scanăm la intrare.</p>",
  },

  anulare: {
    cand: "Când cursul se anulează.",
    subject: (c) => `Am anulat ${c.curs}`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      `<p style="margin:0 0 18px">Ne pare rău - a trebuit să anulăm cursul${c.motiv ? `, ${c.motiv}` : ""}. Știm că ți-ai făcut planuri și chiar ne pare rău că îți dăm peste ele.</p>` +
      detalii(c) +
      `<p style="margin:0 0 18px">Îți returnăm integral <b>${c.total}</b>, pe același card. Durează între 3 și 10 zile lucrătoare, în funcție de bancă. Nu trebuie să faci nimic.</p>` +
      buton(c.linkCurs, "Vezi celelalte cursuri") +
      "<p style=\"margin:0 0 14px\">Dacă preferi să păstrezi banii ca bilet la alt curs, scrie-ne și îl mutăm.</p>",
  },

  reprogramare: {
    cand: "Când cursul se mută pe altă dată.",
    subject: (c) => `${c.curs} se mută pe ${c.dataNoua ?? "altă dată"}`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      `<p style="margin:0 0 18px">A trebuit să mutăm cursul${c.motiv ? `, ${c.motiv}` : ""}. <b>Biletul tău rămâne valabil</b>, nu trebuie să faci nimic.</p>` +
      `<table cellpadding="0" cellspacing="0" style="width:100%;background:#faf9f7;border:1px solid #eae7e1;border-radius:8px;margin:0 0 20px"><tr><td style="padding:16px 18px;font-size:15px;line-height:1.6">` +
      `<div style="font-weight:bold;font-size:17px;margin-bottom:8px">${c.curs}</div>` +
      `<div style="color:#9a958f;text-decoration:line-through">${c.data}, ora ${c.ora}</div>` +
      `<div style="font-weight:bold">${c.dataNoua ?? ""}, ora ${c.ora}</div>` +
      `<div>${c.locatie}${c.adresa ? `, ${c.adresa}` : ""}</div>` +
      "</td></tr></table>" +
      buton(c.linkCurs, "Vezi noua dată") +
      "<p style=\"margin:0 0 14px\">Dacă nu poți în noua zi, scrie-ne și îți dăm banii înapoi integral.</p>",
  },

  rambursare: {
    cand: "Când s-au trimis banii înapoi.",
    subject: (c) => `Ți-am trimis banii înapoi pentru ${c.curs}`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      `<p style="margin:0 0 18px">Am trimis înapoi <b>${c.total}</b>, pe cardul cu care ai plătit. Ajung în 3-10 zile lucrătoare, cât durează la bancă.</p>` +
      detalii(c) +
      "<p style=\"margin:0 0 18px\">Biletele nu mai sunt valabile de acum. Dacă nu vezi banii peste zece zile lucrătoare, scrie-ne și ne uităm împreună.</p>" +
      buton(c.linkCurs, "Vezi ce urmează") +
      "<p style=\"margin:0 0 14px\">Sperăm să ne vedem la altul.</p>",
  },

  retrimitere: {
    cand: "Când cineva cere biletele din nou.",
    subject: (c) => `Biletele tale pentru ${c.curs}`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      "<p style=\"margin:0 0 18px\">Uite biletele din nou, așa cum ai cerut.</p>" +
      detalii(c) +
      listaBilete(c) +
      buton(c.bilete[0]?.link ?? c.linkCurs, "Deschide biletul") +
      "<p style=\"margin:0 0 14px\">Salvează emailul ăsta, ca să nu-l mai cauți în ziua cursului.</p>",
  },

  multumim: {
    cand: "A doua zi după curs.",
    // titlul poate deja să se termine cu „?" - îl punem înainte, ca să nu iasă „??"
    subject: (c) => `${c.curs} - cum ți s-a părut?`,
    body: (c) =>
      `<p style="margin:0 0 14px">Salut, ${c.nume}!</p>` +
      "<p style=\"margin:0 0 18px\">Mersi că ai venit aseară. Ne-a plăcut că ai fost acolo.</p>" +
      "<p style=\"margin:0 0 18px\">Dacă ai două minute, spune-ne ce ți-a plăcut și ce am putea face mai bine. Citim tot, iar din feedbackul ăsta ies următoarele cursuri.</p>" +
      buton("https://cursurilapahar.ro/feedback", "Spune-ne părerea ta") +
      `<p style="margin:0 0 14px">Următoarele cursuri sunt deja pe <a href="https://cursurilapahar.ro/#cursuri" style="${LNK}">site</a>. Ne vedem la unul dintre ele.</p>`,
  },

  bine_ai_venit: {
    cand: "Când cineva se abonează la newsletter. Ăsta se pune în Kit, nu în Brevo.",
    subject: () => "Bine ai venit la Cursuri la Pahar 🍷",
    body: (c) =>
      `<p style="margin:0 0 14px">Salut${c.nume ? `, ${c.nume}` : ""}!</p>` +
      "<p style=\"margin:0 0 18px\">Te-ai abonat, deci de acum afli primul când punem un curs nou. Scriem rar și doar când avem ceva de zis - nu-ți umplem inboxul.</p>" +
      "<p style=\"margin:0 0 18px\">Dacă e prima dată când auzi de noi: aducem profesori și experți în baruri din București și îi punem să vorbească două ore despre lucruri care chiar contează. Fără slide-uri plictisitoare, cu un pahar în mână.</p>" +
      buton("https://cursurilapahar.ro/#cursuri", "Vezi ce urmează") +
      `<p style="margin:0 0 14px">Ne găsești și pe <a href="${IG}" style="${LNK}">Instagram</a>, acolo postăm cel mai des.</p>`,
  },
};

/** Rama comună: banner, titlu, corp, semnătură, rețele. */
export function ramaEmail(titlu: string, corp: string): string {
  return (
    '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 0"><tr><td align="center">' +
    "<table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;font-family:Georgia,'Times New Roman',serif\">" +
    `<tr><td><img src="${BANNER}" alt="Cursuri la Pahar" width="600" style="display:block;width:100%;height:auto;border:0"></td></tr>` +
    '<tr><td style="padding:28px 32px;color:#2b2b2b;font-size:16px;line-height:1.65">' +
    `<div style="font-size:25px;font-weight:bold;color:#1a1a1a;margin-bottom:18px">${titlu}</div>` +
    corp +
    '<p style="margin:18px 0 4px">Cu drag,</p>' +
    '<p style="margin:0;font-weight:bold;color:#1a1a1a">Echipa Cursuri la Pahar</p>' +
    "</td></tr>" +
    '<tr><td style="padding:18px 32px 26px;border-top:1px solid #eaeaea;text-align:center;font-family:Arial,Helvetica,sans-serif;font-size:13px">' +
    `<a href="${IG}" style="${LNK};margin:0 10px">Instagram</a>` +
    `<a href="https://facebook.com/cursurilapahar" style="${LNK};margin:0 10px">Facebook</a>` +
    `<a href="https://tiktok.com/@cursurilapahar" style="${LNK};margin:0 10px">TikTok</a>` +
    "</td></tr></table></td></tr></table>"
  );
}

/** Date de probă, ca să se poată vedea fiecare email fără o comandă reală. */
export const CTX_EXEMPLU: CtxEmail = {
  nume: "Eric",
  curs: "De ce înșală oamenii?",
  data: "duminică, 2 august",
  ora: "19:00",
  acces: "18:30",
  locatie: "Mojo Club",
  adresa: "Strada Lipscani 69, București",
  bilete: [
    { serie: "SZS", numar: "0012", tip: "Bilet standard", link: "https://cursurilapahar.ro/bilet/exemplu1" },
    { serie: "BMV", numar: "0003", tip: "Bilet student", link: "https://cursurilapahar.ro/bilet/exemplu2" },
  ],
  total: "80,00 lei",
  linkCurs: "https://cursurilapahar.ro/curs/curs-la-pahar-de-ce-insala-oamenii",
  linkComanda: "https://cursurilapahar.ro/cos?e=38&t=4x1,5x1",
  motiv: "speakerul s-a îmbolnăvit",
  dataNoua: "duminică, 16 august",
};
