# Paritate site public PHP → Next: diferențe

Audit din 25 iulie 2026 pe 6 zone. 52 diferențe confirmate de un verificator independent.

## Vizibile (28)

### 1. Card curs — badge-ul SOLD OUT

- **PHP:** index.php:103-125 interoghează LiveTickets pentru fiecare curs afișat (lib/livetickets.php:110-131 lt_is_sold_out: toate items[] au soldout, sau remaining_count===0 cu ticket_count>0), cu cache în data/soldout_cache.json — TTL 900s, sau doar 60s dacă e deja sold out. Rezultatul controlează: badge-ul „SOLD OUT" (index.php:270-272), scoaterea href-ului de pe card (index.php:269), clasa event-card--soldout, ascunderea butonului „Vreau să vin" (index.php:317) și blocarea reducerii (index.php:260).
- **Next:** web/app/(site)/page.tsx:143 citește coloana `sold_out` din events și o folosește la liniile 189, 194, 201, 203, 245. Coloana nu e scrisă NICĂIERI: grep pe tot web/ + migration/ dă doar page.tsx și migration/neon_schema.sql:44 (`sold_out BOOLEAN NOT NULL DEFAULT false`, plus sold_out_checked_at nefolosit). migration/src/migrate.ts:290-315 nu setează sold_out, web/lib/livetickets.ts nu are niciun cod de sold-out (doar fetchCourseMeta pentru titlu/imagine/locație), iar web/app/api/cron/daily/route.ts nu atinge evenimentele.
- **Efect:** Un curs epuizat rămâne în Next un card normal: fără banda „SOLD OUT", cu link activ către LiveTickets, cu butonul „Vreau să vin" și cu reducerea afișată. Pe PHP același curs se blochează automat în max 15 minute. Practic funcția SOLD OUT lipsește complet din port.

### 2. Card curs — linia de dată/oră (meta-item ceas)

- **PHP:** lib/courses.php:207-219 clp_course_datetime_label pune înaintea datei prefixul din lib/dates.php:43-62 clp_ro_day_prefix: „Astăzi" dacă e ziua cursului, „Mâine" dacă e a doua zi (fus Europe/Bucharest), altfel numele zilei. Rezultat: „Astăzi, 25 iulie, 18:30".
- **Next:** web/app/(site)/page.tsx:49 + 54-59 formatează cu Intl.DateTimeFormat("ro-RO", {weekday:"long", day:"numeric", month:"long"}) și capitalizează prima literă — mereu numele zilei, fără cazurile azi/mâine. Rezultat: „Sâmbătă, 25 iulie, 18:30".
- **Efect:** În ziua cursului și în ajun, cardul nu mai spune „Astăzi" / „Mâine", ci numele zilei — dispare semnalul de urgență exact în cele 2 zile care contează pentru vânzări.

### 3. Scripturi <head> / analytics

- **PHP:** includes/head-scripts.php:16-18 scoate settings.head_scripts, apoi liniile 20-33 adaugă HARDCODAT două tracker-e pe fiecare pagină: Plausible (script async https://plausible.io/js/pa-3t0zbcrOJNHSBQ4-KIokx.js + plausible.init()) și analytics-ul self-hosted de pe ericcosulea.ro (data-website-id="dfid_a1e25f0ab3", data-domain="cursurilapahar.ro"). Inclus din index.php:173.
- **Next:** web/app/(site)/layout.tsx:50 randează DOAR <HeadScripts html={str("head_scripts")} />, adică exclusiv ce e în settings.head_scripts (Umami + GA4). Grep pe web/app și web/lib: zero apariții de "plausible" sau "ericcosulea".
- **Efect:** Pe portul Next nu se mai înregistrează niciun vizitator în Plausible și în analytics-ul self-hosted — două surse de trafic dispar complet după migrare (GA4 și Umami rămân, că vin din settings).

### 4. Newsletter – iconița plic

- **PHP:** index.php:343-345 randează <div class="newsletter-icon"> cu SVG-ul de plic deasupra titlului; assets/css/style.css:734-742 îl afișează centrat, 44x44px, în culoarea accent.
- **Next:** web/app/(site)/page.tsx:264-270 — secțiunea #newsletter conține doar <h2 class="section-title">, <p class="newsletter-desc"> și <NewsletterForm/>. Iconița lipsește din markup, nu e ascunsă din CSS.
- **Efect:** Deasupra titlului de newsletter lipsește plicul auriu de 44px; secțiunea începe direct cu textul.

### 5. Galerie – lightbox

- **PHP:** index.php:443-450 randează lightbox-ul cu butoane .lightbox-prev și .lightbox-next; assets/js/main.js:304-319: navigare circulară între poze (lbNav wrap cu modulo), taste ArrowLeft/ArrowRight/Escape, document.body.style.overflow='hidden' cât e deschis, și închidere pe click DOAR pe fundal (if (e.target === galleryLightbox)).
- **Next:** web/app/(site)/Gallery.tsx:33-43: lightbox-ul are doar butonul .lightbox-close; nu există prev/next, nu există handler de tastatură, nu se blochează scroll-ul body, iar onClick e pe containerul întreg, deci orice click — inclusiv pe imaginea mărită — închide lightbox-ul.
- **Efect:** În Next nu poți trece de la o poză la alta din lightbox (nici cu săgeți, nici cu tastatura), pagina continuă să deruleze în spate, iar un click pe imagine îl închide din greșeală.

### 6. Galerie – slider

- **PHP:** assets/js/main.js:236-284: slider transform-based, circular — clonează ultima poză la început și prima la final (liniile 244-251), fiecare click pe .gslider-prev/.gslider-next mută EXACT un item (slideTo(--idx)/slideTo(++idx), lățime item + 10px gap), iar la capete sare silențios pe omologul real (liniile 268-272), deci bucla e infinită. Butoanele sunt blocate cât durează tranziția (flag busy).
- **Next:** web/app/(site)/Gallery.tsx:9-12: scroll(dir) face el.scrollBy({ left: dir * el.clientWidth * 0.8 }) pe un container cu overflowX:auto (linia 20) — fără clone, fără buclă, fără pas fix.
- **Efect:** În Next un click derulează ~80% din lățimea vizibilă (nu o poză), iar pe ultima poză butonul 'Următor' nu mai face nimic — în PHP se întorcea la prima. Sliderul devine și derulabil manual/orizontal, ceea ce în PHP nu era posibil (.gallery-slider-wrap are overflow:hidden).

### 7. Toate formularele publice — validare nativă de browser

- **PHP:** Toate formularele au atributul `novalidate` (prezinta-un-curs.php:117, gazduieste-un-curs.php:126, propune-un-parteneriat.php:117, parteneri.php:244, index.php:458), deci atributele `required` din HTML sunt ignorate de browser. Singura validare client-side este pe email (assets/js/main.js:348-358). Un vizitator poate trimite formularul cu doar emailul completat — api/contact.php:23-27 verifică doar emailul, restul se salvează gol.
- **Next:** Formularele nu au `noValidate` (web/app/(site)/colaborare-form.tsx:19, web/app/(site)/forms.tsx:26) — grep pe tot web/app nu găsește niciun `noValidate`. Browserul aplică nativ toate atributele `required` din markup.
- **Efect:** Câmpuri care pe live sunt opționale (label FĂRĂ `*`) devin obligatorii pe Next: „Număr de telefon", „Link profil social media", „Ce experiențe sau competențe te califică?", radio „Ai mai susținut astfel de prezentări?", „În ce oraș ai vrea să susții cursul?" (prezinta-un-curs), „Număr de telefon" + „Capacitate (seated)" (gazduieste), „Număr de telefon" + „De ce crezi că valorile noastre se aliniază?" (parteneriat), select „Tipul de parteneriat" (parteneri). Vizitatorul primește bula nativă a browserului („Please fill out this field") în loc să poată trimite formularul.

### 8. prezinta-un-curs — câmpul „Link profil social media" (type=url)

- **PHP:** prezinta-un-curs.php:135 `<input type="url" id="suc_social" name="social" required>` — cu `novalidate` pe formular (linia 117), formatul URL nu e verificat deloc; „instagram.com/eu" sau „@eu" trec.
- **Next:** web/app/(site)/prezinta-un-curs/page.tsx:60 același input `type="url" required`, dar formularul nu are `noValidate` → browserul cere URL cu schemă (http://…).
- **Efect:** Pe Next, un vizitator care scrie „instagram.com/numele.meu" (fără http://) e blocat cu eroarea nativă „Please enter a URL" și nu poate trimite formularul. Pe live trece.

### 9. Mesaj de succes — formularele prezinta/gazduieste/parteneriat/sponsorizare

- **PHP:** assets/js/main.js:377 — „Mulțumim! Te vom contacta în cel mai scurt timp."
- **Next:** web/app/(site)/colaborare-action.ts:33 — „✓ Trimis! Îți răspundem cât putem de repede."
- **Efect:** Text complet diferit în caseta verde de după trimitere, plus un bifat „✓" la început care nu există pe live.

### 10. Mesaj de succes — formularul de contact (secțiunea #contact)

- **PHP:** assets/js/main.js:221 — „Mesaj trimis! Îți răspundem în cel mai scurt timp."
- **Next:** web/app/(site)/contact-action.ts:19 — „✓ Mesaj trimis! Îți răspundem cât putem de repede."
- **Efect:** Text diferit în caseta verde după trimiterea mesajului de contact.

### 11. gazduieste-un-curs — lista „De ce să devii locație parteneră?"

- **PHP:** gazduieste-un-curs.php:70-71 injectează inline în `<head>`: `.benefits-bullets { list-style: disc; }` și `.benefits-bullets li::marker { color: var(--accent); }`, care anulează resetul global `ul { list-style: none; }` din assets/css/style.css:56.
- **Next:** web/app/(site)/gazduieste-un-curs/page.tsx:42 folosește `className="benefits-bullets"`, dar regula CSS nu există nicăieri în web/ (grep pe tot repo găsește `benefits-bullets` doar în gazduieste-un-curs.php:70-71 și în page.tsx:42). web/public/assets/css/style.css:56 păstrează `ul { list-style: none; }`.
- **Efect:** Cele 3 beneficii (Vizibilitate / Comunitate / Vibe) apar fără bulinele aurii — text simplu indentat, în loc de listă cu marker colorat cu accentul.

### 12. api/contact.php — confirmarea Brevo pentru formularul de sponsorizare (/parteneri)

- **PHP:** api/contact.php:114-121 definește o confirmare dedicată pentru `sponsorizare`: subiect „Am primit cererea ta de parteneriat 🍷" cu corp propriu; api/contact.php:123-124 alege intrarea după `$ctype`.
- **Next:** web/lib/brevo.ts CONFIRMATIONS are doar cheile `contact`, `sustine`, `gazduieste`, `parteneriat` (liniile 10, 19, 28, 37) — nu există `sponsorizare`; linia cu `CONFIRMATIONS[category] ?? CONFIRMATIONS.contact` cade pe varianta de contact, deși colaborare-action.ts:7 acceptă categoria `sponsorizare` (folosită de web/app/(site)/parteneri/page.tsx:51).
- **Efect:** Compania care completează formularul de pe /parteneri primește emailul generic „Am primit mesajul tău 🍻" („Îți mulțumim că ne-ai scris…") în loc de „Am primit cererea ta de parteneriat 🍷".

### 13. Ordinea cardurilor de vot

- **PHP:** voteaza-cursuri.php:37-38 filtrează activele (`array_filter(fn($c) => $c['active'] ?? true)`) și apoi `shuffle($vote_courses);` — ordine complet aleatorie la FIECARE încărcare de pagină, ca să nu avantajeze temele deja populare.
- **Next:** web/app/(site)/voteaza-cursuri/page.tsx:18-23 — `SELECT ... FROM vote_courses WHERE active = true ORDER BY likes DESC, name ASC` — ordine fixă, deterministă, descrescător după voturi.
- **Efect:** Pe Next lista arată mereu la fel și temele cu cele mai multe voturi sunt mereu primele (efect de bulgăre: primele iau și mai multe voturi). Pe PHP ordinea se schimbă la fiecare refresh. Este cea mai mare diferență de comportament a paginii.

### 14. Titlu H1 + subtitlu

- **PHP:** voteaza-cursuri.php:29-30 citește din settings cu fallback: `'Votează cursurile'` și `'Apasă ❤️ pe temele care te interesează. Cele mai apreciate au șanse mai mari să devină cursuri viitoare.'`, randate la liniile 258-259. Deci textul e editabil din admin (settings.vote_title / vote_subtitle).
- **Next:** page.tsx:30-33 hardcodează alte texte: `<h1>Votează următoarele cursuri</h1>` și `Alege ce teme ți-ar plăcea să vezi la un pahar. Cele mai votate ajung primele pe scenă.` — niciun grep pe `vote_title`/`vote_subtitle` nu găsește nimic în web/.
- **Efect:** Alt titlu și alt subtitlu pe pagină, plus pierderea posibilității de a le schimba din admin.

### 15. Afișarea numărului de aprecieri

- **PHP:** voteaza-cursuri.php:25-27 `likes_label()` → `N . ' ' . ($n === 1 ? 'apreciere' : 'aprecieri')` (0 → „0 aprecieri", 1 → „1 apreciere", 46 → „46 aprecieri"). Randat la linia 287 ca `<strong class="vote-likes-label">`, ÎN interiorul `.vote-desc`, deci vizibil doar după ce deschizi cardul. JS-ul păstrează același plural la actualizare (linia 392).
- **Next:** VoteList.tsx:134 `<span className={styles.count}>{likes[c.id] ?? 0}</span>` — doar cifra goală, fără cuvânt și fără plural, afișată permanent lângă inimă în buton.
- **Efect:** Pe PHP vezi „46 aprecieri" doar când deschizi cardul; pe Next vezi „46" tot timpul, lângă inimă. Regula de plural română (apreciere/aprecieri) lipsește complet.

### 16. Acordeon descriere (toggle)

- **PHP:** Cardul e un acordeon: header-ul întreg e clickabil (voteaza-cursuri.php:276 `onclick="toggleVoteDesc('...')"`), există iconița `▾` (linia 282) care se rotește 180° pe `.open` (CSS 185-187), iar descrierea e într-un wrap grid 0fr→1fr (CSS 190-197). Funcția `toggleVoteDesc` există la liniile 422-425.
- **Next:** VoteList.tsx:121-126 randează `<article>` cu descrierea mereu prezentă, fără niciun handler de toggle, fără iconiță `▾` și fără stare `open` — nu există nimic echivalent în fișier. vote.module.css:62-71 o taie la 3 rânduri cu `-webkit-line-clamp: 3`.
- **Efect:** Pe Next descrierea e mereu vizibilă dar trunchiată la 3 rânduri, fără posibilitatea de a o extinde; pe PHP e ascunsă până dai click pe card, apoi se vede integral. Interacțiunea principală a paginii lipsește.

### 17. Stare goală (niciun curs de votat)

- **PHP:** voteaza-cursuri.php:262-265 — `if (empty($vote_courses))` afișează `<div class="vote-empty"><p>Nu există teme de votat momentan. Revino curând!</p></div>`.
- **Next:** VoteList.tsx:116-140 face doar `courses.map(...)` fără nicio ramură pentru listă goală; page.tsx:34 randează `<VoteList>` necondiționat.
- **Efect:** Dacă toate cursurile sunt inactive, pe Next rămâne titlu + subtitlu + footnote și un spațiu gol, fără mesajul „Nu există teme de votat momentan. Revino curând!".

### 18. Link de întoarcere

- **PHP:** voteaza-cursuri.php:254-257 — `<a href="/" onclick="if(history.length>1){history.back();return false}" class="page-hero-back">` cu SVG săgeată stânga și textul `Înapoi`; se întoarce în istoric dacă există istoric, altfel la homepage.
- **Next:** page.tsx:27-29 — `<Link href="/" className={styles.back}>← Acasă</Link>` — text diferit, săgeată text în loc de SVG, mereu navighează la homepage.
- **Efect:** Alt text („← Acasă" în loc de „Înapoi"), alt aspect și pierderea comportamentului de întoarcere în istoric (utilizatorul venit din altă pagină ajunge pe homepage, nu înapoi de unde a venit).

### 19. Text în plus la finalul paginii

- **PHP:** Nu există nimic după `.vote-grid`; secțiunea se închide la voteaza-cursuri.php:296.
- **Next:** page.tsx:35 — `<p className={styles.footnote}>Voturi live în Neon Postgres · scaffold migrare Next.js</p>`.
- **Efect:** Text de debug intern despre migrare afișat vizitatorilor pe pagina publică.

### 20. Footer

- **PHP:** voteaza-cursuri.php:427 `include __DIR__ . '/includes/footer.php';` — brand, „Aducem educația în baruri.", linkurile Cursuri/FAQ/Colaborare/Contact și iconițele Instagram/TikTok/Facebook/cesaicumpar.ro.
- **Next:** web/app/(site)/layout.tsx nu randează niciun footer (randează doar AdminBar + SiteNav + children), iar page.tsx-ul de vot se termină la `</main>`. Footer-ul există doar inline în web/app/(site)/page.tsx:316-360, adică exclusiv pe homepage.
- **Efect:** Pagina de vot din Next se termină brusc, fără footer, fără linkuri și fără social — spre deosebire de toate paginile PHP.

### 21. Tema vizuală a paginii

- **PHP:** voteaza-cursuri.php:68-217 definește totul pe variabilele site-ului: card `background: var(--surface)` cu bordură `rgba(255,255,255,.07)` (liniile 117-123), inimă `color: var(--text-muted)` → `#e05565` cu animație `heartPop` (150-175), titlu cu `var(--font-heading)`, fundal întunecat `--bg: #0D0D0D`.
- **Next:** vote.module.css:35-43 card `background: #fff; border: 1px solid #e5e5e5`, :73-87 buton pastilă mov `background: #f3f1ff; color: #6b4eff`, `.voted { background: #6b4eff }`, :5 `font-family: var(--font-geist-sans)` — culori hardcodate, niciun `var(--surface)`/`var(--accent)`, iar dark mode se face după `prefers-color-scheme` (:125-131), nu după tema site-ului.
- **Efect:** Pe Next pagina apare albă cu accente mov Geist în mijlocul unui site negru-auriu (navbar-ul din layout rămâne pe tema site-ului), fără animația inimii. Vizual e o pagină din alt site.

### 22. parteneri + cursuri-posibile — footer

- **PHP:** cursuri-posibile.php:164 și parteneri.php:350 fac `include __DIR__ . '/includes/footer.php'` → randează <footer class="footer"> cu brand „Cursuri la Pahar / Aducem educația în baruri.", 4 linkuri (/#cursuri, /#faq, /#colaborare, /#contact), 4 iconițe social (Instagram, TikTok, Facebook, cesaicumpar.ro) și „© <an> Cursuri la Pahar. Toate drepturile rezervate.". Confirmat pe live: curl https://cursurilapahar.ro/parteneri returnează <footer class="footer">.
- **Next:** web/app/(site)/layout.tsx:44-60 randează doar AdminBar + SiteNav + {children} — niciun footer. Footer-ul există exclusiv inline în web/app/(site)/page.tsx:316-360 (homepage). Nici web/app/(site)/cursuri-posibile/page.tsx, nici web/app/(site)/parteneri/page.tsx nu îl includ.
- **Efect:** Pe /cursuri-posibile și /parteneri în Next pagina se termină brusc: dispar complet linkurile de navigare din subsol, cele 4 iconițe de social media și textul de copyright.

### 23. parteneri — email de confirmare către vizitator

- **PHP:** Formularul trimite form_type="sponsorizare" (parteneri.php:244). api/contact.php:120 face $ctype = str_replace('-un-curs','',$form_type) = 'sponsorizare' și găsește intrarea dedicată din api/contact.php:110-118: subiect „Am primit cererea ta de parteneriat 🍷" cu textul „Îți mulțumim pentru interesul de a fi partener Cursuri la Pahar. 🍷 … Am primit cererea ta. Ne gândim la cum putem colabora…".
- **Next:** web/lib/brevo.ts:9-45 definește CONFIRMATIONS doar pentru contact, sustine, gazduieste, parteneriat — cheia `sponsorizare` LIPSEȘTE. La linia 54, `CONFIRMATIONS[category] ?? CONFIRMATIONS.contact` cade pe varianta generică de contact.
- **Efect:** Compania care completează formularul de pe /parteneri primește emailul „Am primit mesajul tău 🍻" cu text de contact generic („Îți mulțumim că ne-ai scris… îl citim cu atenție"), nu confirmarea de parteneriat.

### 24. parteneri — galerie, lightbox la click

- **PHP:** Elementul #galleryLightbox există DOAR în index.php:443-446. parteneri.php:322-337 randează doar slider-ul, fără lightbox, deci garda din assets/js/main.js:287 `if (galleryLightbox)` e falsă și handler-ul de click de la main.js:309 nu se atașează niciodată. Confirmat pe live: curl https://cursurilapahar.ro/parteneri → 0 apariții „galleryLightbox".
- **Next:** web/app/(site)/Gallery.tsx:22 pune `onClick={() => setLightbox(i)}` pe fiecare .gallery-item, iar liniile 33-43 randează overlay-ul .gallery-lightbox.active cu imaginea mare.
- **Efect:** Pe /parteneri în PHP clic pe o poză din galerie nu face nimic; în Next se deschide imaginea pe tot ecranul. Funcționalitate în plus, absentă pe live.

### 25. parteneri — validarea câmpurilor obligatorii

- **PHP:** parteneri.php:244 declară `<form class="inner-page-form" data-form-type="sponsorizare" novalidate>` — `novalidate` dezactivează validarea nativă, iar main.js:348-358 validează DOAR emailul. Se poate trimite cu Nume companie / Persoana de contact / Telefon / Tipul de parteneriat goale.
- **Next:** web/app/(site)/colaborare-form.tsx:19 `<form className="inner-page-form" action={action}>` — fără `novalidate`, deci atributele `required` de pe #sp_company, #sp_contact, #sp_email, #sp_phone, #sp_type (parteneri/page.tsx:55,60,65,70,75) sunt aplicate de browser.
- **Efect:** În Next browserul blochează trimiterea cu tooltip nativ pe primul câmp gol; în PHP formularul pleacă și se salvează cu câmpuri goale.

### 26. parteneri — eticheta butonului după trimitere

- **PHP:** assets/js/main.js:361 pune „Se trimite…" pe buton, dar în blocul finally de la main.js:388-391 îl readuce hardcodat la `btn.textContent = 'Trimite'`. Pe parteneri.php butonul pornește ca „Hai să vorbim" (parteneri.php:269) și rămâne „Trimite" după orice submit.
- **Next:** web/app/(site)/colaborare-form.tsx:22-24 afișează „Se trimite…" cât timp `pending`, apoi revine la buttonLabel = „Hai să vorbim" (parteneri/page.tsx:51).
- **Efect:** După trimitere, pe live butonul scrie „Trimite", în Next scrie tot „Hai să vorbim".

### 27. Footer pe subpagini

- **PHP:** Toate cele 7 pagini publice includ același footer: index.php:479, voteaza-cursuri.php:427, gazduieste-un-curs.php:185, prezinta-un-curs.php:183, propune-un-parteneriat.php:174, parteneri.php:350, cursuri-posibile.php:164 → `include includes/footer.php` (brand „Cursuri la Pahar / Aducem educația în baruri.", 4 linkuri, 4 iconițe social, „© <an> Cursuri la Pahar. Toate drepturile rezervate.")
- **Next:** Footerul e scris inline DOAR în app/(site)/page.tsx:316-360. app/(site)/layout.tsx:43-59 randează doar HeadScripts + AdminBar + SiteNav + {children}, fără footer. Nicio subpagină nu îl are: grep „footer" în app/(site)/voteaza-cursuri/page.tsx, gazduieste-un-curs/page.tsx, prezinta-un-curs/page.tsx, propune-un-parteneriat/page.tsx, parteneri/page.tsx, cursuri-posibile/page.tsx → 0 rezultate
- **Efect:** Pe Next, 6 din 7 pagini publice se termină brusc, fără footer: dispar linkurile Cursuri/FAQ/Colaborare/Contact, iconițele Instagram/TikTok/Facebook/cesaicumpar.ro și textul de copyright. Pe PHP footerul apare pe toate.

### 28. Tracking – scripturi hardcodate în head

- **PHP:** includes/head-scripts.php:16-18 scoate settings.head_scripts, apoi liniile 20-25 adaugă hardcodat Plausible (`https://plausible.io/js/pa-3t0zbcrOJNHSBQ4-KIokx.js` + init) și liniile 27-33 al doilea analytics self-hosted (`https://www.ericcosulea.ro/js/script.js`, data-website-id="dfid_a1e25f0ab3", data-domain="cursurilapahar.ro"). Fișierul e inclus pe toate paginile publice (index.php:173, voteaza-cursuri.php:218, gazduieste-un-curs.php:73, prezinta-un-curs.php:71, propune-un-parteneriat.php:71, parteneri.php:193, cursuri-posibile.php:104).
- **Next:** app/(site)/layout.tsx:50 randează doar `<HeadScripts html={str("head_scripts")} />`; app/(site)/HeadScripts.tsx:23-40 parsează și emite exclusiv tag-urile din settings.head_scripts. Grep „plausible|ericcosulea" în web/app și web/public → 0 rezultate.
- **Efect:** Pe Next nu se mai trimit deloc pageview-uri către Plausible și către analytics-ul self-hosted de pe ericcosulea.ro. Rămâne doar ce e în settings.head_scripts (Umami + GA4). Două surse de trafic dispar complet din raportare.

## Subtile (16)

### 1. Card curs — imaginea preluată automat din LiveTickets

- **PHP:** index.php:55-72 la FIECARE afișare a homepage-ului parcurge toate cursurile (înainte de filtrare) și, dacă un curs are livetickets_url dar image_url gol, cheamă lt_get_event_by_url + lt_image_url_from_event și persistă imaginea în data/courses.json (clp_save_courses, index.php:70-72).
- **Next:** web/app/(site)/page.tsx:142-150 doar face SELECT image_url; nu există niciun backfill pe pagina publică. Imaginea se ia doar din admin: la salvare (web/app/admin/cursuri/actions.ts:65-70) sau când se deschide formularul de editare al unui curs fără imagine (web/app/admin/cursuri/CourseAddForm.tsx:187).
- **Efect:** Un curs al cărui poster apare pe LiveTickets după ce a fost salvat rămâne pe Next cu placeholder-ul gri (event-card-img-placeholder) până când cineva redeschide manual cursul în admin; pe PHP imaginea apare singură la primul refresh al homepage-ului.

### 2. Newsletter – texte de validare și eroare

- **PHP:** assets/js/main.js:128-129 validează cu /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/ (TLD minim 2 caractere) și afișează 'Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro).' (main.js:144); email gol → return fără niciun mesaj (main.js:141). api/subscribe.php:8 'Email invalid.', :16 'API key lipsă în setări Kit.', :17 'Form ID lipsă în setări Kit.', :36 'Eroare conexiune: <curl error>'; fallback în main.js:171-173: 'Ceva n-a mers bine. Încearcă din nou sau scrie-ne la contact@cursurilapahar.ro'.
- **Next:** web/app/(site)/newsletter-action.ts:8 regex /^[^\s@]+@[^\s@]+\.[^\s@]+$/ (TLD de 1 caracter trece) cu mesajul 'Email invalid.'; :14 'Newsletterul nu e configurat încă.' (un singur mesaj pentru api_key sau form_id lipsă); :27 'Eroare (HTTP ${res.status}). Încearcă din nou.'; :29 'Eroare de conexiune. Încearcă din nou.'
- **Efect:** Alte texte pentru exact aceleași situații și un prag de validare diferit (a@b.c e respins de PHP, acceptat de Next). Practic invizibil azi pentru că mesajele nici nu se afișează (vezi finding-ul cu .form-message), dar regula de date diferă.

### 3. Validarea adresei de email

- **PHP:** Client: assets/js/main.js:128-130 regex `/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/` (cere punct + TLD de minim 2 caractere), cu mesajul „Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro)." (main.js:354). Server: api/contact.php:23-27 `filter_var(..., FILTER_VALIDATE_EMAIL)` → „Email invalid." și oprește salvarea.
- **Next:** web/app/(site)/colaborare-action.ts:13-14 verifică doar `if (!email)` → „Completează adresa de email."; contact-action.ts:9-11 la fel. Niciun control de format pe server; pe client rămâne doar validarea nativă `type=email`, care acceptă adrese fără punct în domeniu.
- **Efect:** O adresă gen „eric@localhost" sau „a@b" e respinsă pe live (mesaj roșu), dar pe Next e acceptată, salvată în tabelul `messages` și trimisă la Brevo. Textele de eroare diferă și ele.

### 4. Mesajul de eroare când trimiterea eșuează

- **PHP:** assets/js/main.js:383-387 (formulare interne) și main.js:226-228 (contact) prind orice eroare și afișează în caseta roșie „Ceva n-a mers bine. Scrie-ne direct la contact@cursurilapahar.ro" (respectiv mesajul returnat de server), păstrând datele completate în formular.
- **Next:** Nici colaborare-action.ts, nici contact-action.ts nu au try/catch în jurul `INSERT`-ului; o eroare de DB propagă din server action. În web/app nu există niciun error.tsx / global-error.tsx.
- **Efect:** La o eroare de bază de date vizitatorul nu primește caseta roșie cu adresa de contact, ci ecranul generic de eroare Next (sau nimic), și pierde ce a completat.

### 5. Ce se salvează la trimiterea formularului (messages.log vs tabelul messages)

- **PHP:** api/contact.php:41-59 scrie în data/messages.log un bloc `=== Y-m-d H:i:s | <form_type> ===` cu TOATE cheile trimise de JS, inclusiv cele goale (main.js:333-344 trimite și grupurile de checkbox nebifate ca array gol). Eticheta e `ucfirst(str_replace('_',' ',$key))`, deci cheile de checkbox păstrează sufixul `[]` („Facilities[]: audio, projector", „Partnership type[]: "). Valorile trec prin `strip_tags` și au newline-urile înlocuite cu spațiu (api/contact.php:47).
- **Next:** web/app/(site)/colaborare-action.ts:17-25 face `.trim()` + `.filter(Boolean)` și salvează cheia doar `if (vals.length)` — câmpurile goale dispar complet din payload; numele checkbox-urilor sunt curate (`facilities`, `partnership_type` — vezi gazduieste-un-curs/page.tsx:96 și propune-un-parteneriat/page.tsx:67). Textele multi-linie se păstrează cu newline, fără strip_tags.
- **Efect:** Mesajele salvate din Next au alt set de chei decât cele din log-ul live: lipsesc rândurile pentru câmpurile lăsate goale (ex. „Other", „Phone") și cheile de checkbox se numesc altfel — în /admin/mesaje aceleași formulare arată cu mai puține rânduri.

### 6. Notificarea pe email către echipă

- **PHP:** api/contact.php:62-67 trimite, pe lângă confirmarea către vizitator, un email cu tot conținutul formularului la contact@cursurilapahar.ro (subiect din tabelul de la liniile 30-39, ex. „Cerere nouă: Prezintă un curs — Cursuri la Pahar", Reply-To = emailul vizitatorului).
- **Next:** colaborare-action.ts și contact-action.ts fac doar `INSERT` în `messages` + `sendConfirmationEmail(...)` către vizitator (web/lib/brevo.ts). Nu există niciun apel care să trimită notificare la contact@cursurilapahar.ro.
- **Efect:** După migrare nu mai ajunge niciun email în inboxul echipei la fiecare formular completat; mesajele se văd doar dacă intri în /admin/mesaje.

### 7. Protecția la vot dublu (cheie localStorage)

- **PHP:** voteaza-cursuri.php:302 `const VOTED_KEY = 'clp_voted';` — array de ID-uri string (ex. `vc_numerologie`), citit la DOMContentLoaded (liniile 313-321) ca să marcheze inimile deja votate. Serverul (api/vote.php) NU face nicio deduplicare — toată protecția e în această cheie.
- **Next:** VoteList.tsx:15 `const STORAGE_KEY = "clp_votes";` (altă cheie) și valori numerice (`JSON.parse(raw) as number[]`, linia 30), pentru că ID-urile sunt BIGINT în Neon (migration/neon_schema.sql:151).
- **Efect:** Un vizitator care a votat deja pe site-ul PHP ajunge pe Next cu toate inimile goale și poate vota a doua oară aceleași teme — voturile se dublează. Nici nu există migrare din `clp_voted` în `clp_votes`.

### 8. Revenire (revert) la eroare de rețea

- **PHP:** voteaza-cursuri.php:404-419 — `try { await fetch('/api/vote.php', ...) } catch { applyVoted(btn, isVoted); countEl.textContent = likesLabel(currentCount() - delta); setVoted(...) }` — la eșec inima ȘI contorul ȘI localStorage se dau înapoi.
- **Next:** VoteList.tsx:108-113 — `try { const serverLikes = await vote(...); setLikes(...) } finally { setPending(null); }` — nu există `catch`, deci dacă server action-ul aruncă, inima rămâne roșie, contorul rămâne incrementat și ID-ul rămâne scris în localStorage.
- **Efect:** Cu rețea proastă, pe Next utilizatorul crede că a votat deși nimic nu s-a scris în DB — și, fiind în localStorage, nu mai poate reîncerca (a doua apăsare va trimite `remove`).

### 9. Blocaj global cât timp un vot e în curs

- **PHP:** voteaza-cursuri.php:383-420 — `toggleVote` nu are niciun lock; poți apăsa oricâte inimi în orice ordine, fiecare cerere pleacă independent.
- **Next:** VoteList.tsx:95 `if (pending !== null) return;` — un singur `pending: number | null` pentru toată lista, plus `disabled={pending === c.id}` la linia 130.
- **Efect:** Pe Next, cât timp un vot e în zbor, click-urile pe ORICE alt card sunt ignorate în tăcere (butonul nici măcar nu apare dezactivat, doar cel apăsat). La click-uri rapide pe mai multe teme, unele voturi se pierd fără niciun feedback.

### 10. Sursa contorului după vot

- **PHP:** voteaza-cursuri.php:397 — contorul rămâne optimist: `countEl.textContent = likesLabel(currentCount() + delta)`; răspunsul de la api/vote.php (care întoarce doar `{success: bool}`, api/vote.php:58) nu e folosit.
- **Next:** actions.ts:12-22 întoarce `RETURNING likes` (valoarea reală din Neon), iar VoteList.tsx:109-110 suprascrie: `setLikes((p) => ({ ...p, [id]: serverLikes }))`.
- **Efect:** Pe Next, după apăsarea inimii, numărul poate sări brusc (ex. de la 47 la 52) pentru că se sincronizează cu voturile altora acumulate între timp. Pe PHP crește exact cu 1.

### 11. Validare la contorizarea vizualizărilor per card

- **PHP:** api/vote_view.php:20-26 — sanitizează ID-ul cu regex, apoi `if ($id === '' || !clp_vote_course_exists($id))` returnează `{'success': false, 'message': 'ID invalid'}`; `clp_vote_course_exists` (lib/vote_views.php:132-151) cere explicit `($course['active'] ?? true)`, deci un curs inactiv NU primește vizualizări.
- **Next:** actions.ts:30-33 — `if (!Number.isFinite(id) || !(await shouldCountClick())) return;` apoi `UPDATE vote_courses SET views = views + 1 WHERE id = ${id}` — fără nicio condiție `active = true` și fără verificarea existenței.
- **Efect:** Un ID trimis manual către server action incrementează `views` inclusiv pentru cursuri dezactivate, ceea ce strică rata de conversie afișată în admin (VoteRows.tsx:14-16, likes/views).

### 12. parteneri — comportamentul slider-ului de galerie

- **PHP:** assets/js/main.js:236-283: clonează ultimul item în față și primul în spate, avansează exact UN item per click (`slideTo(++idx)` / `slideTo(--idx)`) cu transform + tranziție 0.4s, și e circular (transitionend sare înapoi la itemul real, deci după ultima poză se ajunge la prima).
- **Next:** web/app/(site)/Gallery.tsx:9-12: `el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' })` pe un container cu overflowX: auto. Fără clone, fără wrap.
- **Efect:** În Next galeria derulează 80% din lățimea vizibilă (mai multe poze deodată) și se oprește la capete; în PHP e infinită și avansează câte o poză.

### 13. parteneri — validarea formatului de email

- **PHP:** Client: assets/js/main.js:128-130 regex `/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/` și main.js:351-357 respinge cu „Adresa de email nu este validă. Verifică formatul (ex: nume@exemplu.ro).". Server: api/contact.php:23-27 FILTER_VALIDATE_EMAIL → {"success":false,"message":"Email invalid."}.
- **Next:** web/app/(site)/colaborare-action.ts:11-13 verifică doar dacă e string gol: `if (!email) return "Completează adresa de email."` — niciun test de format înainte de INSERT.
- **Efect:** Mesaje de eroare complet diferite; în plus adrese pe care PHP le respinge (ex. „a@b", acceptată de validarea nativă HTML5) trec în Next și se salvează în tabela messages.

### 14. parteneri — ce se scrie / se trimite la submit

- **PHP:** api/contact.php face trei lucruri: (1) append în data/messages.log cu blocul „=== <data> | sponsorizare ===" (liniile 52-59), (2) @mail('contact@cursurilapahar.ro', 'Cerere nouă: Parteneriat — Cursuri la Pahar', …) cu Reply-To pe emailul vizitatorului (liniile 36, 61-67), (3) confirmarea Brevo.
- **Next:** web/app/(site)/colaborare-action.ts:27-30 face doar `INSERT INTO messages (category, name, email, payload)` + sendConfirmationEmail + revalidatePath('/admin/mesaje'). Nu există niciun echivalent al notificării interne pe contact@cursurilapahar.ro.
- **Efect:** Nu mai ajunge niciun email de anunț pe inbox-ul echipei la o cerere de parteneriat — trebuie intrat în admin ca să fie văzută.

### 15. parteneri — câmpurile goale din mesajul salvat

- **PHP:** api/contact.php:41-49 iterează TOATE cheile din body (mai puțin form_type) și scrie o linie pentru fiecare, inclusiv cele goale: eticheta e `ucfirst(str_replace('_',' ',$key))`, deci apar rânduri de tipul „Message: " sau „Phone: " chiar când sunt goale.
- **Next:** web/app/(site)/colaborare-action.ts:18-25 filtrează valorile goale (`.filter(Boolean)`) și sare peste cheie dacă nu a rămas nimic (`if (vals.length)`), deci câmpurile necompletate lipsesc cu totul din payload-ul JSONB.
- **Efect:** În admin, un formular trimis fără „Cum îți dorești să colaborăm?" arată în PHP rândul gol, în Next rândul nu mai există deloc.

### 16. Variabile CSS din :root – culoarea „Fundal carduri/secțiuni"

- **PHP:** index.php:167 (și identic voteaza-cursuri.php:74, gazduieste-un-curs.php:65, prezinta-un-curs.php:65, propune-un-parteneriat.php:65, parteneri.php:67, cursuri-posibile.php:60) scrie `--surface: <settings.color_surface>`. Dar assets/css/style.css folosește exclusiv `--bg-surface` (declarat fix `#161616` la linia 10 și consumat la liniile 71, 491, 1088, 1237, 1265) — deci setarea color_surface din admin NU schimbă cardurile/secțiunile; `--surface` e folosit doar de CSS-ul local al paginii voteaza-cursuri.php:118 și suprascris în parteneri.php:82.
- **Next:** web/app/(site)/layout.tsx:30 mapează `"--bg-surface": str("color_surface", "#161616")` pe div-ul .clp-site-shell, adică exact variabila pe care o consumă style.css. Variabila `--surface` nu e definită nicăieri în Next (grep în web/app, web/lib → 0 rezultate).
- **Efect:** Dacă cineva schimbă „Fundal carduri/secțiuni" din admin, pe Next se schimbă fundalul tuturor cardurilor și secțiunilor (style.css linia 71 etc.), pe PHP nu se schimbă nimic. Cu valoarea actuală (#161616, identică cu default-ul din style.css) nu se vede diferență.

## Cosmetice (8)

### 1. Banner de anunț sub grila de cursuri

- **PHP:** index.php:332-334 randează necondiționat <div class="announcement-banner"> cu textul din settings; când setarea e goală rămâne o bandă galbenă goală (assets/css/style.css:306-315: background var(--banner-bg), padding 11px 20px).
- **Next:** web/app/(site)/page.tsx:262 `{announcement && <div className="announcement-banner">…}` — cu textul gol elementul nu se randează deloc.
- **Efect:** Dacă adminul golește anunțul, pe PHP rămâne o bandă galbenă de ~35px sub cursuri, pe Next nu mai e nimic. (Cu setarea actuală, care are text, ambele arată identic.)

### 2. Hero — lista de imagini goală

- **PHP:** index.php:212-224 iterează $settings['hero_images']; dacă lista e goală nu se randează niciun .hero-slide, deci hero-ul rămâne doar cu .hero-overlay (fundal negru).
- **Next:** web/app/(site)/page.tsx:72-73 face fallback la ["/assets/images/hero1.jpg"] (convertit apoi la .webp la linia 81), deci hero-ul afișează hero1.webp.
- **Efect:** Dacă adminul șterge toate imaginile de hero (admin/actions.php:283 permite salvarea unei liste goale), PHP arată un hero negru, Next arată poza veche hero1.

### 3. Animații scroll-reveal (FAQ, titluri, formulare, carduri colaborare)

- **PHP:** assets/js/main.js:78-97 (initReveal) pune clasa .reveal pe '.step, .collab-card, .faq-item, .section-title, .section-subtitle, .newsletter-form, .contact-form' (mai puțin titlul din #cursuri) și adaugă .visible la IntersectionObserver cu threshold 0.08; style.css:1302-1308 → .reveal { opacity:0; transform:translateY(24px) } și .reveal.visible le aduce la normal. index.php:481 încarcă main.js.
- **Next:** Homepage-ul Next nu încarcă main.js (web/app/(site)/layout.tsx nu are niciun <script src="/assets/js/main.js">, iar web/public/assets/js conține doar coloris.min.js) și nu are echivalent — grep 'reveal|IntersectionObserver' pe web/app + web/lib găsește doar VoteList.tsx:64.
- **Efect:** Pe Next elementele apar direct, fără fade-in de 24px la scroll. Nu ascunde conținut (clasa .reveal nu se mai adaugă deloc), deci e strict diferență de animație.

### 4. cursuri-posibile — meta twitter:description

- **PHP:** cursuri-posibile.php:45 → `Idei de teme pentru un curs la pahar: știință, istorie, psihologie, film, muzică și multe altele.` (fără fraza finală), în timp ce og:description de la linia 37 conține în plus „Caută inspirație și prezintă un curs.".
- **Next:** cursuri-posibile/page.tsx:9-14 nu trimite `ogDescription`, deci în web/lib/metadata.ts:22 ogDescription = description, iar linia 38 pune aceeași descriere completă și pe twitter:description.
- **Efect:** Cardul de Twitter/X are în plus fraza „Caută inspirație și prezintă un curs." față de live.

### 5. parteneri — meta twitter:description

- **PHP:** parteneri.php:52 → `Peste 200.000 de vizualizări pe lună, newsletter cu open rate de peste 50% și cursuri săptămânale cu săli pline.` — fără „Vezi cifrele și scrie-ne.", care apare doar în og:description (parteneri.php:44).
- **Next:** parteneri/page.tsx:12-13 setează `ogDescription` cu fraza inclusă, iar web/lib/metadata.ts:38 folosește același ogDescription și pentru twitter.description.
- **Efect:** Cardul de Twitter/X afișează în plus „Vezi cifrele și scrie-ne.".

### 6. parteneri — og:image / twitter:image

- **PHP:** parteneri.php:46 și 53 folosesc `https://cursurilapahar.ro/assets/images/og-image.jpg`, fără query string (spre deosebire de cursuri-posibile.php:39 care are `?v=2`).
- **Next:** web/lib/metadata.ts:7-12 folosește o singură constantă OG_IMAGE cu `/assets/images/og-image.jpg?v=2` pentru toate paginile, inclusiv /parteneri.
- **Efect:** URL-ul imaginii de share pentru /parteneri diferă prin `?v=2` — altă cheie de cache la scraperele de social.

### 7. Default-uri de culoare când cheia lipsește din settings

- **PHP:** index.php:165-166: `--text: <color_text ?? '#E8E4DC'>` și `--text-muted: <color_text_muted ?? '#9CA3AF'>`. Aceleași fallback-uri sunt și pe celelalte pagini publice; lib/design.php:7-8 declară aceleași default-uri ('#E8E4DC', '#9CA3AF').
- **Next:** web/app/(site)/layout.tsx:32-33: `"--text": str("color_text", "#F0EBE1")` și `"--text-muted": str("color_text_muted", "#8A8A8A")` — alte valori de rezervă (cele din style.css:14-15, nu cele din lib/design.php).
- **Efect:** Doar dacă cheile color_text / color_text_muted lipsesc din settings: textul și textul secundar ies pe alte nuanțe în Next față de PHP. Cu settings-ul actual (color_text=#ffffff, color_text_muted=#9CA3AF) nu se vede nimic.

### 8. Tracking vizualizări per card de vot

- **PHP:** api/vote_view.php:20-24 curăță id-ul cu `preg_replace('/[^a-zA-Z0-9._-]/','',...)` și incrementează DOAR dacă `clp_vote_course_exists($id)` — lib/vote_views.php:145-149 cere ca înregistrarea din vote_courses.json să existe ȘI să aibă `active` truthy; altfel răspunde `{'success':false,'message':'ID invalid'}` fără să scrie nimic.
- **Next:** web/app/(site)/voteaza-cursuri/actions.ts:30-33 `trackVoteView` verifică doar `Number.isFinite(id)` și `shouldCountClick()`, apoi face direct `UPDATE vote_courses SET views = views + 1 WHERE id = ${id}` — fără nicio condiție pe `active`.
- **Efect:** Nimic vizibil pentru vizitator; în DB, pe Next se pot acumula views și pe cursuri de vot dezactivate (dacă id-ul e apelat direct), pe PHP nu. Rata de conversie likes/views a acelor rânduri iese diferită în admin.
