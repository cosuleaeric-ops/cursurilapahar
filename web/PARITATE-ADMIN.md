# Paritate admin PHP → Next: diferențe rămase

Rezultatul auditului automat din 25 iulie 2026: 14 zone comparate linie cu linie,
144 diferențe raportate, **113 confirmate** de un verificator independent (31 respinse ca fals-pozitive).

Ordinea: întâi ce se vede pe ecran.

## Stare la 1 august 2026: 106 din 113 închise

Reverificare a fiecărei intrări față de codul curent din `web/`. Ce a mai rămas:

- **Vizibile 13** — la egalitate de participări, `web/lib/statistici.ts` sortează cu
  `localeCompare(..., "ro")`, PHP-ul compara binar. Ordine ușor diferită în listă.
- **Vizibile 33** — în Bibliotecă, butonul ✕ lipsește la cele 3 foldere statice
  (`imagini/page.tsx:24`, `deletable: false`). Probabil corect: pe Vercel alea vin din
  build, nu din Blob, deci nici n-ar avea ce șterge.
- **Subtile 22** — la upload de favicon lipsesc două mesaje de eroare („prea mare" și
  codul de upload); restul ramurilor există.

**Abateri intenționate, a nu se „repara":** *Vizibile 31*, *Cosmetice 4, 7, 18* — lipsa
atributelor `placeholder`, care e politică explicită în proiect. *Cosmetice 16* — textul
„contul tău … în baza Neon", adaptat la stack-ul actual.

*Vizibile 29* (coloana `views` lipsă din `migration/neon_schema.sql`) a fost reparată pe
1 august 2026. Restul intrărilor descriu starea de la 25 iulie și sunt istorie.

## Vizibile (55)

### 1. Card „To-dos" — care 5 to-do-uri apar și în ce ordine

- **PHP:** admin/partials/dashboard-tab.php:9-10 — filtrează todos-urile în ordinea din data/todos.json (ordine de inserare, cel mai vechi primul, pentru că lib/todos.php:42 face `$todos[] = $todo`) și ia `array_slice($_dash_td_pending, 0, 5)` → PRIMELE 5 = cele mai VECHI 5 to-do-uri necompletate.
- **Next:** web/app/admin/page.tsx:27-31 — `SELECT id, title FROM todos WHERE completed = false AND assigned_to = ... ORDER BY created_at DESC LIMIT 5` → cele mai NOI 5.
- **Efect:** Cardul de pe dashboard listează alte 5 to-do-uri decât pe live, în ordine inversă. Pe PHP primele 5 din card sunt exact primele 5 din pagina /admin/todos (admin/todos/index.php:53 + :216 listează pending în aceeași ordine de fișier); pe Next cardul arată coada listei. Dacă userul are peste 5 to-do-uri, seturile nu se suprapun deloc.

### 2. Card „Cursuri" — titlul cursului

- **PHP:** admin/partials/dashboard-tab.php:53 — `<?= h($_uc['title'] ?? '') ?>`, adică titlul BRUT din courses.json, inclusiv sufixul „ // <zi lună>" (7 din cele 20 de carduri curente au „//" în titlu, ex. „Curs la Pahar - STRES VS BURNOUT. Cum eviți epuizarea cronică // 28 aprilie").
- **Next:** web/app/admin/page.tsx:16 `const cardTitle = (t) => t.replace(/\s+\/\/\s+.+$/u, "")` aplicat la web/app/admin/page.tsx:95 — taie tot ce urmează după „ // ". Titlul din DB e brut (migration/src/migrate.ts:286-305 scrie `card.title` neatins), deci tăierea e adăugată de port.
- **Efect:** Pe dashboard-ul Next titlurile apar scurtate („…epuizarea cronică" în loc de „…epuizarea cronică // 28 aprilie"). În plus e inconsecvent chiar în port: mini-calendarul de mai jos (web/app/admin/MiniCal.tsx:45-46) și tabelul din /admin/cursuri (web/app/admin/cursuri/CoursesTable.tsx:90) afișează titlul brut.

### 3. Mini-calendar „Urmatoarele cursuri" (și lista de cursuri viitoare) — ce cursuri intră în calendar

- **PHP:** admin/partials/dashboard-tab.php:144-148 construiește `$_dash_cal_json` DOAR din `$_dash_courses`, care e lib/dashboard.php:5 → `clp_load_courses_for_admin()` → lib/courses.php:163-184, adică exclusiv cardurile din data/courses.json (azi 20 de intrări, toate în fereastra aprilie–iulie 2026). Cursurile istorice din baza de statistici NU apar. Același set alimentează și filtrul de la :15.
- **Next:** web/app/admin/page.tsx:44-47 — `SELECT title, ... FROM events WHERE starts_at IS NOT NULL`, fără niciun filtru. Tabelul `events` conține și câte un rând pentru FIECARE curs din statistici, inserat la migration/src/migrate.ts:265-277 (`INSERT INTO events(title, starts_at, external_id, ...)` pentru tot `bundle.statistici.courses`), nu doar cardurile din courses.json (care au `legacy_card_id` setat, migration/neon_schema.sql:27). La fel, cardul „Cursuri" (web/app/admin/page.tsx:20-25) selectează din tot `events`.
- **Efect:** Navigând în lunile trecute, calendarul din Next afișează cursuri vechi (toate edițiile din arhiva de statistici) pe care dashboard-ul PHP nu le arată niciodată — pe PHP lunile anterioare sunt goale. Filtrul echivalent ar fi `WHERE legacy_card_id IS NOT NULL`.

### 4. formular adăugare/editare curs — popularea câmpurilor

- **PHP:** cursuri-tab.php:17-50 randează valorile server-side la fiecare request (`value="<?= h($edit_course['title'] ?? '') ?>"` etc.), deci la ?edit=ID formularul vine deja completat, iar după adăugare (redirect fără edit) vine gol.
- **Next:** CourseAddForm.tsx:107-113 ține câmpurile în `useState(edit?.title ?? "")` — valoare inițială doar la mount — iar page.tsx:105-111 randează <CourseAddForm> fără `key`. Navigarea /admin/cursuri → /admin/cursuri?edit=5 schimbă doar searchParams, deci componenta client nu se remontează și state-ul rămâne cel vechi. Doar hidden-ul `id` (CourseAddForm.tsx:152) se actualizează din props.
- **Efect:** Click pe „Editează” în tabel: formularul rămâne gol (sau cu ce era tastat înainte), deși titlul cardului devine „Editează curs”; apăsând Salvează se trimite id-ul cursului cu câmpuri goale → eroare „Completează numele cursului” sau suprascriere cu date greșite. Simetric, după „Adaugă cursul” formularul rămâne plin cu cursul tocmai adăugat, în loc să se golească.

### 5. validare speaker la salvare

- **PHP:** Inputul de speaker nu are `name` (cursuri-tab.php:39), se trimite doar `speaker_id` (cursuri-tab.php:40). admin-course-form.js:143-156 blochează submit-ul cu alert dacă nu s-a rezolvat un id, iar admin/actions.php:75-79 respinge cu „Alege un speaker din listă.” dacă `clp_find_speaker_by_id()` nu găsește nimic.
- **Next:** CourseAddForm.tsx:188-196 trimite text liber prin `name="speaker_name"`, iar actions.ts:44 verifică doar `if (!speaker)`; actions.ts:65/78 scriu string-ul ca atare în `speaker_name`.
- **Efect:** În Next poți scrie orice nume (typo, speaker inexistent) și cursul se salvează cu acel nume; pe PHP primeai alerta „Alege un speaker din lista de pe tab-ul Speakeri (nume exact).” și salvarea era blocată.

### 6. coloana „Dată” din tabelul de cursuri

- **PHP:** Afișează `date_display` (lib/courses_admin.php:107), generat la salvare de clp_date_display_from_raw() → clp_format_date_ro($raw, true, true) cu titleCase = true (lib/dates.php:82-85, 64-80). Valorile reale din data/courses.json: „28 Iulie 2026”, „5 Iulie 2026”.
- **Next:** page.tsx:30-31 formatează cu `new Intl.DateTimeFormat("ro-RO", {day:"numeric", month:"long", year:"numeric"})`, care dă luna cu literă mică: „28 iulie 2026”.
- **Efect:** Fiecare rând din tabel arată luna cu literă mică („28 iulie 2026”) în loc de majusculă („28 Iulie 2026”).

### 7. ordinea speakerilor în combobox

- **PHP:** load_speakers_for_picker() (lib/speakers.php:138-141) folosește clp_sort_speakers() (lib/speakers.php:119-126): întâi după rangul statusului — CONTACTAT 0, URMEAZĂ 1, RECURENT 2, MID 3, NOPE 4, status necunoscut 2 (lib/speakers.php:111-113) — apoi alfabetic case-insensitive; scoate din listă intrările fără id sau fără nume.
- **Next:** page.tsx:92 `SELECT id, name, status FROM speakers ORDER BY name` — doar alfabetic, fără gruparea pe status (deși în /admin/speakeri, page.tsx:48-58, ordonarea pe status a fost portată corect).
- **Efect:** Lista de sugestii la „Speaker” apare în altă ordine: pe PHP primii erau cei CONTACTAT/URMEAZĂ, în Next e o listă pur alfabetică în care „NOPE” apare amestecat printre ceilalți.

### 8. ștergere curs

- **PHP:** admin/actions.php:24-32 șterge necondiționat cursul din courses.json (plus rândul din statistici), după confirm-ul „Ștergi cursul?”.
- **Next:** actions.ts:117-126 numără biletele (`SELECT count(*) FROM tickets WHERE event_id = ...`) și face `return` mut dacă există măcar unul — fără mesaj, fără eroare.
- **Efect:** Confirmi ștergerea unui curs care are bilete și nu se întâmplă nimic: rândul rămâne în tabel, fără nicio explicație pe ecran.

### 9. Tabelul lunii (tab Cursuri) — filtrul de vizibilitate

- **PHP:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/lib/courses.php:436-450 + :458-460 construiește un WHERE de vizibilitate: se arată DOAR cursurile cu `c.external_id IN (id-urile din courses.json)` SAU cursurile fără external_id care au statistici (`has_stats` = există raport SAU bilete SAU fișier viza, definit la :426-430) ȘI nu există alt curs în aceeași zi venit din courses.json (`hide_legacy`, :442-448). Apelat din /Users/ericcosulea/Documents/Proiecte/cursurilapahar/api/cursuri_month.php:20.
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/cursuri/stats-data.ts:30-38 selectează TOATE rândurile din `events` din luna respectivă (`WHERE to_char(starts_at AT TIME ZONE 'Europe/Bucharest','YYYY-MM') = prefix`), fără niciun filtru de vizibilitate.
- **Efect:** În Next apar în tabel rânduri pe care PHP-ul le ascunde: evenimente rămase din statistici cu external_id care nu mai e în courses.json (ex. rândul de test 'Public Speaking' 2024-05-28, external_id 'test1', fără bilete/raport/viză — verificat în admin/statistici/data/clp.sqlite) și evenimente vechi fără niciun fel de statistici. Pe lunile vechi PHP arată „Niciun curs pentru perioada selectată.", Next arată rânduri cu 0 bilete și liniuțe. Afectează și cele două casete de sus (Total încasări / Taxă DITL) dacă rândurile în plus au raport.

### 10. Calendar — sursa cursurilor afișate

- **PHP:** calCourses vine din $courses = courses.json (/Users/ericcosulea/Documents/Proiecte/cursurilapahar/admin/bootstrap.php:16 → clp_load_courses_for_admin, mapat la /Users/ericcosulea/Documents/Proiecte/cursurilapahar/lib/courses_admin.php:175), deci DOAR cardurile de site (20 de intrări în data/courses.json), indiferent de lună; randate în /Users/ericcosulea/Documents/Proiecte/cursurilapahar/admin/assets/js/admin-cursuri-stats.js:224-229.
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/cursuri/stats-data.ts:104-109 interoghează tabelul `events` pentru luna afișată, deci include și cursurile venite doar din statistici (toate rândurile din clp.courses au fost importate ca events — migration/src/migrate.ts:264-276).
- **Efect:** Pe lunile vechi (cursuri care nu mai sunt în courses.json) calendarul PHP e gol, iar cel din Next are chipuri cu cursuri. Invers, dacă un card de site e într-o altă lună decât cea afișată, comportamentul rămâne același (PHP oricum desenează doar zilele lunii curente).

### 11. Calendar — culoarea chipurilor (viitor / azi / trecut)

- **PHP:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/admin/assets/js/admin-cursuri-stats.js:243 pune trei clase: `today-ev` dacă e ziua curentă, `past` dacă data e în trecut, altfel `future`. CSS: /Users/ericcosulea/Documents/Proiecte/cursurilapahar/admin/assets/css/admin.css:529-531 (.cal-event.future = fundal accent-soft/albastru deschis, .cal-event.today-ev = fundal accent/albastru închis, text alb).
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/cursuri/Calendar.tsx:37 pune doar `past`: `className={`cal-event${ds < today ? " past" : ""}`}`. Nu există `future` și nici `today-ev`, deși CSS-ul portat le are identic (/Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/public/assets/css/admin.css:529-531).
- **Efect:** Cursurile viitoare și cursul de azi apar ca text simplu, fără fundal colorat (.cal-event nu are background), în loc de chip albastru deschis, respectiv chip albastru închis cu text alb. Legenda de sub calendar („Curs viitor" / „Curs azi" / „Curs trecut") rămâne, dar nu mai corespunde: doar cursurile trecute sunt colorate.

### 12. Calendar — chipurile de postare Instagram și meniul de zi

- **PHP:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/admin/assets/js/admin-cursuri-stats.js:204-209 (igChipsHtml) adaugă în fiecare celulă chipuri galbene `.cal-event.ig-post` cu eticheta tipului (ex. „POSTARE CURSURI"), :246 le inserează, :239 pune clasa `cal-cell--pick` (cursor pointer + hover), iar :253-259 + :281-344 deschid un dropdown pe zi din care se bifează/debifează postările (POST către /api/instagram_posts.php). Datele vin din lib/courses_admin.php:176-177.
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/cursuri/Calendar.tsx:33-42 randează doar numărul zilei și chipurile de curs; nu există igPosts, nici clasa `cal-cell--pick`, nici meniul de zi. Funcționalitatea există în portul dashboard-ului (/Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/MiniCal.tsx:49-52), dar nu și în calendarul din Cursuri.
- **Efect:** Zilele marcate cu postare Instagram nu mai arată chipul galben în calendarul mare, celulele nu mai au cursor/hover de clic și nu se mai poate bifa/debifa o postare din calendarul de la Cursuri.

### 13. Participanți — ordinea listei la egalitate

- **PHP:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/lib/statistici.php:106-109: usort după [num_courses desc, total_tickets desc, participant_name asc], iar comparația de nume e `<=>` pe string, adică ordonare pe octeți (majuscule înaintea minusculelor, diacriticele UTF-8 la coadă).
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/lib/statistici.ts:120-125: aceleași două criterii numerice, dar tie-break cu `a.participant_name.localeCompare(b.participant_name, "ro")` — colațiune românească, insensibilă la majuscule, cu diacriticele lângă litera de bază.
- **Efect:** Coada listei (majoritatea participanților au 1 curs / 1 bilet, deci se departajează după nume) e în altă ordine: în PHP „Zoe" apare înaintea lui „Ștefan" și „Ana" înaintea lui „ana"; în Next „Ștefan" vine între S și T, iar majuscula/minuscula nu mai contează.

### 14. Bara de filtre (Toți / URMEAZĂ / RECURENT / MID / NOPE / CONTACTAT)

- **PHP:** admin/partials/speakeri-tab.php:14-19 pune `data-status="all|URMEAZĂ|RECURENT|MID|NOPE|CONTACTAT"` pe fiecare buton. Tot CSS-ul de colorare se agață de acel atribut: admin/assets/css/admin.css:655-666 (`.sp-filter-btn[data-status="RECURENT"]{background:#dcfce7…}`, `.sp-filter-btn[data-status="MID"].active{background:#d97706}` etc.). Nu există NICIO regulă generică `.sp-filter-btn.active`.
- **Next:** web/app/admin/speakeri/SpeakeriTable.tsx:71-80 randează butoanele doar cu `className={`sp-filter-btn${filter === f ? " active" : ""}`}` — fără `data-status`. Fișierul CSS e identic byte-cu-byte cu cel din PHP (web/public/assets/css/admin.css:655-666), deci niciun selector nu se potrivește.
- **Efect:** Toate cele 6 butoane de filtru apar albe/neutre în loc de pastilele colorate (verde RECURENT, galben MID, roșu NOPE, albastru CONTACTAT, mov URMEAZĂ, gri „Toți"), iar butonul selectat NU se mai colorează deloc — nu se vede care filtru e activ.

### 15. Modal Editează deschis dintr-un rând CONTACTAT (lead)

- **PHP:** admin/assets/js/admin-speakeri.js:128-138 `spContactatEdit()` apelează întâi `spResetForm()` (care setează `sp_status = 'MID'`, linia 31) și NU mai atinge statusul; apoi forțează explicit titlul „Editează speaker" (linia 135) și textul butonului „Salvează" (linia 136), indiferent că `data.id` e gol.
- **Next:** SpeakeriTable.tsx:112-123 construiește obiectul cu `id: 0` și `status: "CONTACTAT"`. Titlul și butonul se calculează din `sp?.id` (SpeakeriTable.tsx:273 și 324), deci cu id=0 (falsy) ies „Adaugă speaker" / „Adaugă speakerul".
- **Efect:** La click pe „Editează" într-un rând CONTACTAT: în PHP scrie „Editează speaker" + buton „Salvează" + status MID preselectat; în Next scrie „Adaugă speaker" + buton „Adaugă speakerul" + status CONTACTAT preselectat. Speakerul se salvează cu alt status decât pe live.

### 16. Popover-ul de schimbare rapidă a statusului (click pe badge)

- **PHP:** admin/partials/speakeri-tab.php:198 randează `<div id="sp-status-pop" class="sp-status-popover">` fără display inline, deci se aplică CSS-ul din admin/assets/css/admin.css:667 — `display:flex; flex-direction:column; gap:3px; min-width:110px`. JS-ul (admin-speakeri.js:164) îl deschide cu `pop.style.display = 'flex'`.
- **Next:** SpeakeriTable.tsx:179 forțează inline `style={{ display: "block", position: "absolute", top: "100%", left: 0, zIndex: 60 }}`. Stilul inline bate clasa, deci `flex-direction:column` nu mai are efect, iar cele 5 `<button>` rămân inline-block.
- **Efect:** Popover-ul cu cele 5 statusuri apare ca un bloc orizontal înghesuit de butoane care se rup pe rânduri, în loc de lista verticală de pe live.

### 17. Modal Detalii — titlul

- **PHP:** admin/assets/js/admin-speakeri.js:60: `document.getElementById('sp-detalii-title').textContent = 'Detalii: ' + (data.name || '')` (fallback-ul static din speakeri-tab.php:170 e „Detalii speaker").
- **Next:** SpeakeriTable.tsx:354: `<div className="card-title">{sp.name}</div>` — doar numele, fără prefix.
- **Efect:** Titlul modalului scrie „Alice Donea" în loc de „Detalii: Alice Donea".

### 18. Modal Detalii → tab Formular, data submisiei

- **PHP:** admin-speakeri.js:63: `data.form_date ? 'Trimis pe ' + data.form_date : ''`. Valoarea vine din lib/messages.php:175 `'date' => trim($m[1] ?? '')`, adică timestamp-ul brut din header-ul blocului de log, format „2026-01-15 14:30:22".
- **Next:** page.tsx:83-90 formatează cu `Intl.DateTimeFormat("ro-RO", {timeZone:"Europe/Bucharest", day:"2-digit", month:"2-digit", year:"numeric", hour:"2-digit", minute:"2-digit"})` → „15.01.2026, 14:30"; SpeakeriTable.tsx:366 îl afișează gol-goluț: `{sp.form_date ?? ""}`.
- **Efect:** Sub taburi scrie „15.01.2026, 14:30" în loc de „Trimis pe 2026-01-15 14:30:22" — lipsește prefixul, secundele, și separatorii sunt punct în loc de liniuță.

### 19. Modal Detalii → tab Formular, starea goală

- **PHP:** admin-speakeri.js:67-69: când `rows.length === 0` scrie `'<div style="font-size:13px;color:#9ca3af">Fără formular trimis.</div>'`.
- **Next:** SpeakeriTable.tsx:368: `<p style={{color:"var(--text-muted)", fontSize:13}}>Nu există o submisie de formular pentru speakerul ăsta.</p>`.
- **Efect:** Text de stare goală complet diferit („Nu există o submisie de formular pentru speakerul ăsta." vs „Fără formular trimis.").

### 20. Modal Detalii → tab Cursuri, ștergerea unui curs

- **PHP:** admin-speakeri.js:105-109 `spDtAddCourse()` creează un wrapper `display:flex;gap:4px;align-items:center` cu input (`flex:1;padding:5px 9px;font-size:12px;border:1px solid #e5e7eb;border-radius:8px`) ȘI un buton „×" (`onclick="this.closest('div').remove()"`), apoi dă `focus()` pe input-ul nou (linia 109).
- **Next:** SpeakeriTable.tsx:385-393 randează doar `<input type="text" name="topics" style={{ width: "100%" }} />`, fără wrapper flex, fără buton „×" și fără focus. Singurul mod de a scoate un curs e să golești manual textul (actions.ts:63-66 filtrează stringurile goale).
- **Efect:** Lipsește butonul „×" de lângă fiecare curs — nu mai poți șterge un rând cu un click, iar input-urile arată altfel (full-width fără bordura fină/padding-ul mic).

### 21. Butoanele de copiere email/telefon

- **PHP:** admin/assets/js/admin-speakeri.js:1-9 `spCopy()` — după copiere înlocuiește iconița cu o bifă, colorează butonul verde (`#27ae60`, și text și bordură) și revine la iconița inițială după 2000 ms.
- **Next:** SpeakeriTable.tsx:39: `onClick={() => navigator.clipboard.writeText(value)}` — copiază și atât, iconița rămâne neschimbată.
- **Efect:** La click pe butonul de copiere nu se mai întâmplă nimic vizual — utilizatorul nu are confirmare că s-a copiat.

### 22. Confirmarea după salvarea unui speaker

- **PHP:** admin/actions.php:781 face redirect cu `&saved=1`, iar admin/partials/speakeri-tab.php:1-3 randează `<div class="notice notice-success">Speakerul a fost salvat.</div>` când `isset($_GET['saved'])`.
- **Next:** web/app/admin/speakeri/actions.ts:45 apelează doar `revalidatePath("/admin/speakeri")`; nu există niciun `notice notice-success` nici în actions.ts, nici în page.tsx, nici în SpeakeriTable.tsx.
- **Efect:** După „Salvează" nu mai apare banda verde „Speakerul a fost salvat." — nicio confirmare pe ecran.

### 23. Mesaje → data afișată pe fiecare card

- **PHP:** Data e string-ul brut din antetul blocului de log: api/contact.php:57 scrie „=== " . date('Y-m-d H:i:s'), lib/messages.php:111 face 'date' => trim($m[1]), iar lib/messages.php:223 îl afișează ca atare → „2026-07-25 20:05:11".
- **Next:** web/lib/messages.ts:45-52 definește Intl.DateTimeFormat('ro-RO', Europe/Bucharest, zi/lună/an + oră/minut) și linia 93 face date: dtFmt.format(new Date(r.created_at)) → „25.07.2026, 20:05"; MessagesBoard.tsx:69 îl randează.
- **Efect:** Colțul dreapta-sus al fiecărui card arată alt format de dată: pe PHP „2026-07-25 20:05:11" (ISO, cu secunde), pe port „25.07.2026, 20:05" (românesc, cu virgulă, fără secunde).

### 24. Mesaje → data afișată la comentariile de pe candidații speakeri

- **PHP:** admin/actions.php:1063 salvează 'at' => date('Y-m-d H:i:s'), iar lib/messages.php:258 îl afișează brut în .msg-comment-when → „2026-07-25 20:05:11".
- **Next:** web/app/admin/mesaje/actions.ts:58-65 formatează cu Intl.DateTimeFormat('ro-RO', …, day/month/year/hour/minute) și salvează deja formatat → „25.07.2026, 20:05"; MessagesBoard.tsx:159-162 îl afișează.
- **Efect:** Sub fiecare comentariu nou, timestamp-ul apare în alt format decât pe live (fără secunde, cu puncte și virgulă).

### 25. Mesaje → butonul „Șterge" (Contact / Locații / Parteneriate)

- **PHP:** admin/assets/js/admin-mesaje.js:51: if (!confirm('Sigur vrei să ștergi acest mesaj?')) return;
- **Next:** web/app/admin/mesaje/MessagesBoard.tsx:144: if (confirm("Ștergi mesajul?")) call(deleteMessage);
- **Efect:** Textul din pop-up-ul de confirmare e altul.

### 26. Mesaje → ștergerea unui comentariu (butonul ×)

- **PHP:** admin/assets/js/admin-mesaje.js:22: deleteComment cere confirmare — if (!confirm('Ștergi comentariul?')) return;
- **Next:** web/app/admin/mesaje/MessagesBoard.tsx:169-172: onClick apelează direct call(deleteComment, {index}) — nicio confirmare.
- **Efect:** Pe port, un click pe × șterge comentariul instant, fără dialog; pe live apare întâi confirmarea.

### 27. Mesaje → formularul de comentariu (tab Speakeri)

- **PHP:** admin/assets/css/admin.css:616 stilizează .msg-comment-form { display:flex; gap:6px } iar admin-mesaje.js:158 îl deschide cu form.style.display = 'flex' → textarea și butonul „Adaugă" stau pe același rând, textarea cu flex:1.
- **Next:** web/app/admin/mesaje/MessagesBoard.tsx:180: style={{ display: commentOpen ? "block" : "none" }} — inline display:block bate regula .msg-comment-form{display:flex}.
- **Efect:** Pe port, când deschizi „💬 Comentariu", textarea și butonul „Adaugă" se așază unul sub altul (textarea la lățime plină, buton dedesubt), nu unul lângă altul ca pe live.

### 28. Locații — formularul rămâne deschis după „Adaugă locația"/„Salvează" și după „Anulează" (idem Colaborări la salvare)

- **PHP:** admin/actions.php:870 face `header('Location: /admin/?tab=locatii&saved=1')` → reîncărcare completă de pagină; admin/partials/locatii-tab.php:41 randează wrapper-ul cu `style="display:none"` când `$edit_loc` e gol, iar inputurile revin goale. „Anulează" e ancoră normală (locatii-tab.php:56, colaborari-tab.php:67) → tot reîncărcare completă. Același tipar la colaborări: actions.php:935 + colaborari-tab.php:52.
- **Next:** web/app/admin/locatii/actions.ts:24 `redirect("/admin/locatii?saved=1")` e navigare soft — LocationsPanel.tsx:30 `const [open, setOpen] = useState(false)` și :31 `show = open || !!edit` rămân montate, deci `open` stă true; inputurile din LocationForm.tsx:31-49 sunt necontrolate (`defaultValue`) și nu se remontează, deci păstrează textul tastat. „Anulează" e `<Link>` (LocationForm.tsx:56), deci nici el nu remontează. La colaborări, CollabForm.tsx:50 setează display prin `useState(!!edit)` dar AddToggle (CollabForm.tsx:21-22) modifică `el.style.display` direct în DOM, iar re-render-ul după redirect nu îl mai resetează.
- **Efect:** În PHP, după ce adaugi o locație/colaborare formularul dispare și e golit; în port rămâne deschis sub notița verde, cu valorile încă completate. La Locații, și butonul „Anulează" lasă formularul deschis în loc să-l închidă.

### 29. Tabel voturi — coloanele Vizite/Conv. (sursa de date)

- **PHP:** admin/partials/vot-tab.php:6 + :67 citește vizitele din data/vote_views.json prin clp_load_vote_views() (lib/vote_views.php:11-29), care sare peste cheia '__page__' și peste valorile ne-numerice.
- **Next:** web/app/admin/voturi/page.tsx:16-19 face `SELECT id, name, emoji, description, likes, views, active FROM vote_courses`, dar coloana `views` NU există în schema declarată: migration/neon_schema.sql:144-154 are doar id, legacy_id, name, emoji, description, likes, active, position, created_at. Nu există niciun ALTER TABLE nicăieri în repo (grep „ADD COLUMN" în migration/ și web/ = 0 rezultate), iar migration/src/migrate.ts:369 inserează totuși în vote_courses(..., views).
- **Efect:** Pe o bază creată din neon_schema.sql, `npm run schema && npm run migrate` crapă la pasul vote_courses, iar pagina /admin/voturi întoarce eroare (SELECT pe coloană inexistentă) — deci tabelul cu Vizite și Conv. nu se afișează deloc. Dacă baza live a fost patch-uită manual, schema din repo e desincronizată și orice re-creare a bazei rupe zona de voturi.

### 30. Notificarea „Cursul a fost salvat.” după adăugare

- **PHP:** admin/actions.php:696 — handler-ul `save_vote_course` face `header('Location: /admin/?tab=vot&saved=1')` pentru ORICE salvare, și la curs nou și la editare; vot-tab.php:1-3 afișează notice-ul pe baza lui $_GET['saved'].
- **Next:** web/app/admin/voturi/actions.ts:24 — `createVoteCourse` face `redirect("/admin/voturi")`, fără `?saved=1`. Doar `updateVoteCourse` (actions.ts:41) redirecționează cu `?saved=1`.
- **Efect:** După ce adaugi o idee de curs nouă, bara verde „Cursul a fost salvat.” nu mai apare în port; pe PHP apare. La editare apare în ambele.

### 31. Formularul de adăugare/editare — placeholder-e

- **PHP:** admin/partials/vot-tab.php:23 are `placeholder="ex: Educație montană"` pe câmpul Nume curs, iar :29 are `placeholder="Descrierea cursului, vizibilă la toggle pe pagina publică."` pe textarea Descriere.
- **Next:** web/app/admin/voturi/VoteCourseForm.tsx:39 (input name) și :45 (textarea description) nu au niciun atribut placeholder.
- **Efect:** Pe formularul gol, PHP arată text-ghid gri în cele două câmpuri, portul le arată complet goale. Atenție: regula ta permanentă „fără placeholder-e în inputuri” spune să nu le adaugi — deci probabil e o omisiune intenționată, o raportez doar ca diferență vizuală constatată.

### 32. Biblioteca — ordinea listei

- **PHP:** lib/images.php:27-31 — colectează toate cele 3 surse (/assets/images/, /assets/images/gallery/, UPLOADS_DIR) într-un singur array, fiecare item primind 'mtime' => @filemtime(...) (lib/images.php:24), apoi `usort($imgs, fn($a,$b) => $b['mtime'] <=> $a['mtime'])` — o singură sortare globală descrescătoare după data fișierului, exact cum promite titlul cardului.
- **Next:** web/app/admin/imagini/page.tsx:63 — `const library = [...blobs, ...statics]`. Blob-urile sunt sortate după uploadedAt desc (page.tsx:36), dar fișierele statice nu au deloc mtime (tipul LibImage nu conține câmpul — ImaginiManager.tsx:7) și sunt adăugate în ordinea readdir, folder după folder: assets/images, apoi assets/images/gallery, apoi assets/images/uploads (page.tsx:26-28). Nu există niciun sort peste ele.
- **Efect:** Cardul „Biblioteca — toate imaginile (cele mai noi primele)” minte în port: pozele statice apar grupate pe foldere în ordine alfabetică (hero1…hero5, logo, og-image, apoi gallery-01…gallery-40, apoi cele 3 din uploads), nu de la cea mai nouă la cea mai veche, și mereu după toate uploadurile din Blob. Pe PHP aceleași fișiere apar amestecate între ele după data reală a fișierului.

### 33. Biblioteca — butonul ✕ de ștergere

- **PHP:** lib/images.php:27-29 — `$collect(..., '/assets/images/', false); $collect(..., '/assets/images/gallery/', true); $collect($uploads_dir, $uploads_url.'/', true);` deci galeria și uploads primesc deletable=true; imagini-tab.php:100-103 randează `<button class="img-tile-del">✕</button>` doar când `$img['deletable']`.
- **Next:** web/app/admin/imagini/page.tsx:19 — `out.push({ url: ..., name: f.name, deletable: false })` pentru TOATE cele trei foldere colectate (page.tsx:26-28), inclusiv assets/images/gallery și assets/images/uploads. Doar blob-urile primesc deletable:true (page.tsx:37). ImaginiManager.tsx:210 afișează ✕ doar dacă img.deletable.
- **Efect:** Pe PHP, cele 40+ imagini din galerie și cele din uploads au ✕ în colțul thumbnail-ului; în port niciuna nu are — doar uploadurile noi din Blob. Userul nu mai poate șterge nimic din galerie.

### 34. Ștergere imagine — curățarea referințelor

- **PHP:** admin/actions.php:269-274 — după unlink, încarcă settings și scoate URL-ul șters din ambele liste: `$settings['hero_images'] = array_values(array_filter($settings['hero_images'] ?? [], fn($u) => $u !== $del_url));` idem pentru `gallery_featured`, apoi save_settings(). Comentariul din cod: „Curăță referințele orfane din hero/galerie ca să nu rămână slide-uri albe”.
- **Next:** web/app/admin/imagini/actions.ts:49-55 — `deleteImage` face doar `await del(url)` + revalidatePath. Nu atinge deloc rândurile hero_images / gallery_featured din tabela settings.
- **Efect:** După ce ștergi din bibliotecă o imagine care era selectată în Hero sau Galerie, în port ea rămâne în bandă ca thumbnail spart (și rămâne salvată în DB), iar pe site apare un slide gol. Pe PHP dispare automat din ambele benzi.

### 35. Notice-uri după upload

- **PHP:** admin/actions.php:257-258 construiește două mesaje separate cu plural corect: `$count_ok . ' imagine' . ($count_ok > 1 ? 'i' : '') . ' încărcată' . ($count_ok > 1 ? 'e' : '') . ' cu succes.'` și `$count_err . ' fișier' . ($count_err>1?'e':'') . ' nu ' . ($count_err>1?'au':'a') . ' putut fi încărcate.'`; actions.php:217 pune `'Niciun fișier selectat.'` dacă nu s-a ales nimic. imagini-tab.php:9-14 le randează ca DOUĂ notice-uri distincte, unul verde (notice-success) și unul roșu (notice-error), care pot apărea simultan.
- **Next:** web/app/admin/imagini/page.tsx:70-74 — un SINGUR notice: `<div className={`notice ${Number(uperr) ? "notice-error" : "notice-success"}`}>{Number(up)} imagini încărcate{Number(uperr) ? `, ${uperr} eșuate` : ""}.</div>`. Fără plural variabil, fără „cu succes.”, fără mesajul „Niciun fișier selectat.” (actions.ts:18 filtrează tăcut fișierele goale și redirecționează cu up=0).
- **Efect:** La un singur fișier: PHP scrie „1 imagine încărcată cu succes.” (verde), portul scrie „1 imagini încărcate.”. La 3 reușite + 1 eșec: PHP arată verde „3 imagini încărcate cu succes.” PLUS roșu „1 fișier nu a putut fi încărcate.”, portul arată un singur banner ROȘU „3 imagini încărcate, 1 eșuate.” — reușitele par eșec. Fără fișier selectat: PHP „Niciun fișier selectat.” (roșu), portul „0 imagini încărcate.” (verde).

### 36. Benzile Hero/Galerie — reordonare prin drag

- **PHP:** admin/assets/js/admin-imagini.js:131-140 `getDragAfter` calculează `offset = x - box.left - box.width/2` și întoarce primul element al cărui mijloc e la dreapta cursorului; js:117-119 — dacă nu există niciunul (`after == null`) face `strip.appendChild(dragEl)`, deci elementul poate ajunge ULTIMUL. Ordinea finală se citește din DOM la `drop` (js:121-128).
- **Next:** web/app/admin/imagini/ImaginiManager.tsx:51-59 `reorder()` — `const next = list.filter(u => u !== dragUrl); next.splice(next.indexOf(overUrl), 0, dragUrl);` adică elementul tras se inserează ÎNTOTDEAUNA înaintea celui survolat, iar handler-ul e legat pe onDragOver al fiecărui item (ImaginiManager.tsx:90-93). Nu există niciun handler pe container și niciun caz „după ultimul”.
- **Efect:** În port nu poți muta o imagine pe ultima poziție trăgând-o spre dreapta: [A,B,C] cu A tras peste C dă [B,A,C], nu [B,C,A]. Ordinea slideshow-ului hero (unde poziția ① e cea care se încarcă instant) nu poate fi obținută prin gestul normal din PHP.

### 37. Templates — buton „+ Adaugă template”

- **PHP:** admin/assets/js/admin-common.js:54-81 — addTemplateRow() creează cardul cu `card.className = 'tpl-card open'` (linia 56), deci panoul de editare e VIZIBIL imediat, iar linia 80 face `card.querySelector('input[name="tpl_label[]"]').focus()` — cursorul intră direct în câmpul de titlu.
- **Next:** web/app/admin/templates/TemplatesEditor.tsx:125 adaugă doar `{icon:'📋', label:'', text:''}` în state, iar Card-ul are `const [open, setOpen] = useState(false)` (linia 47) → cardul nou se randează ÎNCHIS. Nu există niciun focus programatic.
- **Efect:** În PHP, un click pe „+ Adaugă template” deschide formularul cu cursorul în titlu și poți scrie imediat. În Next apare doar un rând colapsat „▸ 📋 Template fără titlu / gol”, pe care trebuie să dai click ca să-l deschizi, apoi să dai click în câmp.

### 38. Aspect — descrierea de sub uploadul de favicon

- **PHP:** admin/partials/aspect-tab.php:38 — `<p class="form-desc">Formate: ICO, PNG, JPG, WEBP. Fișierul va fi salvat în rădăcina site-ului.</p>`
- **Next:** web/app/admin/aspect/page.tsx:89 — `<p className="form-desc">Formate: ICO, PNG, JPG, WEBP. Imaginea e decupată circular automat.</p>`
- **Efect:** A doua propoziție de sub butonul „Încarcă favicon” e alt text: „Fișierul va fi salvat în rădăcina site-ului.” vs „Imaginea e decupată circular automat.”

### 39. Aspect — upload logo respins (fără fișier sau extensie greșită)

- **PHP:** admin/actions.php:409-426 — validarea extensiei e într-un `if` interior, iar `header('Location: /admin/?tab=aspect&saved=1')` de la linia 425 rulează NECONDIȚIONAT. Deci și când nu s-a încărcat nimic, aspect-tab.php:2-4 afișează notice-ul „Setările de aspect au fost salvate.”
- **Next:** web/app/admin/aspect/actions.ts:24 `if (!(f instanceof File) || !f.size) redirect("/admin/aspect")` și linia 26 `if (!['jpg','jpeg','png','webp','svg'].includes(ext)) redirect("/admin/aspect")` — redirect FĂRĂ `?saved=1`.
- **Efect:** Trimiți formularul de logo gol sau cu un .gif: pe PHP apare bara verde „Setările de aspect au fost salvate.”, pe Next pagina se reîncarcă fără niciun mesaj.

### 40. Aspect — poziția și forma mesajului de eroare la favicon

- **PHP:** admin/partials/aspect-tab.php:29-31 — eroarea se randează ÎN INTERIORUL cardului „Favicon”, între titlul cardului și formular, ca div cu stil inline `background:#fcf0f1;border:1px solid #f5c6cb;color:#c0392b;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:12px`.
- **Next:** web/app/admin/aspect/page.tsx:49 — `{fverr && <div className="notice notice-error">…}` e randat SUS DE TOT, imediat sub `<h1>Aspect</h1>` și înaintea cardului „Logo”, cu clasa `.notice.notice-error` (public/assets/css/admin.css:375: `background:#fef2f2; border-left-color:var(--danger); color:#991b1b`, plus bara din stânga a clasei `.notice`).
- **Efect:** Eroarea de favicon apare în alt loc pe pagină (sus, deasupra cardului Logo, în loc de în cardul Favicon) și arată altfel — bandă cu bordură stânga în loc de casetă roz cu bordură completă.

### 41. Aspect — preview imagine favicon

- **PHP:** admin/partials/aspect-tab.php:26-28 — când există `favicon_path` se afișează DOAR paragraful „Favicon curent: <code>…</code>”. Nu există niciun `<img>` în card (spre deosebire de logo, care are `<img>` la linia 11).
- **Next:** web/app/admin/aspect/page.tsx:79 — după `<code>` se adaugă `<img src={str("favicon_path")} alt="Favicon" style={{height:20, verticalAlign:"middle", marginLeft:6}} />`.
- **Efect:** În Next apare o miniatură de 20px a faviconului lângă calea afișată; pe PHP nu apare nicio imagine în cardul Favicon.

### 42. Taskuri recurente — notice după salvare

- **PHP:** admin/partials/config-tab.php:76 — la ?rec=ok afișează „Salvat ✓ (<count(clp_recurring_monthly())> taskuri lunare)", adică numără taskurile de tip 'monthly' din lib/recurring.php:119-122 și pune numărul în paranteză.
- **Next:** web/app/admin/setari/RecurringEditor.tsx:208 — la notice === "ok" afișează doar „Salvat ✓", fără niciun contor; nicăieri în page.tsx/RecurringEditor.tsx nu se numără taskurile lunare.
- **Efect:** După orice salvare/adăugare de task recurent, pe PHP scrie „Salvat ✓ (3 taskuri lunare)", pe Next scrie doar „Salvat ✓". Contorul lipsește complet.

### 43. To-dos — ordinea listei

- **PHP:** clp_load_todos() întoarce array-ul exact în ordinea din todos.json (lib/todos.php:18-22), iar todo-urile noi se adaugă la coadă cu $todos[] = $todo (lib/todos.php:42); admin/todos/index.php:53-54 doar filtrează cu array_filter, deci ordinea rămâne cea mai veche → cea mai nouă. Nicio sortare nicăieri (toate sursele folosesc clp_add_todo: cron/andy_course_tasks.php:84, cron/recurring_tasks.php:47, lib/recurring.php:80).
- **Next:** web/app/admin/todos/page.tsx:18-22 — `FROM todos ORDER BY created_at DESC`, deci cel mai nou primul, exact invers. În plus nu există criteriu secundar de departajare: cele 4 to-do-uri pe care cron-ul Andy le adaugă în aceeași secundă au created_at identic și ies în ordine arbitrară (în PHP rămân în ordinea inserării).
- **Efect:** Toată lista de sarcini nefinalizate e afișată în ordine inversă față de site-ul live (sarcina cea mai nouă apare sus în loc de jos). Același lucru și în interiorul fiecărei grupe de zi din blocul „N completate”. Fix: ORDER BY id ASC (sau created_at ASC, id ASC).

### 44. Marketing — formularul de adăugare idee (Enter)

- **PHP:** admin/marketing/index.php:153-162 randează `<form class="mkt-add-form">` fără buton submit, iar admin/assets/js/admin-marketing.js:21-33 leagă explicit `keydown` pe input[name=text] (Enter fără Shift) și pe input[name=link] (Enter) → `trySubmit()` → `form.submit()`; admin-marketing.js:10-14 validează: dacă și textul și linkul sunt goale, face `preventDefault()` și `textInput.focus()`.
- **Next:** web/app/admin/marketing/MarketingSection.tsx:67-74 randează același formular (`<form action={addItem}>` + 2 input-uri text + hidden section_id) DAR fără niciun buton `type="submit"` și fără niciun handler de keydown/Enter (singurul submit din fișier e la MarketingSection.tsx:36, în formularul de ștergere). Nici BcDoc.tsx:14-27 nu tratează Enter (doar tastele c/m/d, și returnează devreme când targetul e INPUT). Conform specului HTML, un formular fără buton submit și cu mai mult de un câmp „blocking" (2 input-uri text) NU face implicit submission la Enter.
- **Efect:** În port apeși „+", scrii ideea de postare, apeși Enter — nu se întâmplă absolut nimic, ideea nu se salvează și nu apare în listă. Pe PHP aceeași acțiune salvează ideea. Practic adăugarea de idei de marketing e nefuncțională în port.

### 45. Viză bilete — butonul „Extrage date”

- **PHP:** admin/statistici/cursuri/view.php:539-546 — un al doilea form (`#reprocessVizaForm`, action=reprocess_viza) cu butonul `.reprocess-btn` „↻ Extrage date", lângă butonul de ștergere. JS-ul de la view.php:750-769 descarcă PDF-ul de pe server, extrage textul cu PDF.js și retrimite formularul (handler-ul POST e la view.php:169-192). Clasa `.reprocess-btn` e definită în stylesheet (view.php:341).
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx:305-313 conține doar formularul de ștergere (`deleteViza`). Nu există niciun form/buton de reprocesare, iar actions.ts nu exportă nicio acțiune echivalentă cu `reprocess_viza` (actions.ts:1-182). Clasa `.reprocess-btn` rămâne definită degeaba în styles.ts:52.
- **Efect:** În rândul cu PDF-ul de viză lipsește complet linkul subliniat „↻ Extrage date"; rămâne doar „×". Dacă extragerea automată la upload a eșuat, userul nu mai are cum să reîncerce parsarea — trebuie să introducă seriile manual.

### 46. Viză bilete — text de stare goală (fără subtipuri)

- **PHP:** admin/statistici/cursuri/view.php:615 — „Nu s-au putut extrage datele automat. Apasa „Extrage date” sau introdu manual:”
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx:378-380 — „Nu s-au putut extrage datele automat. Introdu manual:”
- **Efect:** Mesajul de sub PDF-ul de viză e mai scurt cu 5 cuvinte — dispare trimiterea la butonul „Extrage date”.

### 47. Viză subtipuri — deduplicare

- **PHP:** Cheia de duplicat e (seria, de_la, pana_la): view.php:201-203 (acțiunea `dedup_viza_subtips`) și view.php:254-256 (`DELETE ... id NOT IN (SELECT MIN(id) ... GROUP BY seria, de_la, pana_la)`), care rulează AUTOMAT la fiecare încărcare a paginii, înainte de SELECT-ul de la view.php:257. Detecția `$has_dupes` (view.php:556-563) folosește aceeași cheie.
- **Next:** web/app/admin/cursuri/[id]/detalii/actions.ts:66-71 șterge după o cheie DIFERITĂ — (seria, tarif, nr_unitati). Nu există nicio deduplicare automată la load: page.tsx:59-62 face doar SELECT. Detecția `hasDupes` (page.tsx:118-124) folosește cheia PHP `seria_de_la_pana_la`, deci nu corespunde cu ce șterge acțiunea.
- **Efect:** Pe Next apar rânduri duplicate în tabelul de serii și butonul roșu „Sterge duplicate" (pe PHP tabelul e curățat automat la fiecare load, deci butonul practic nu apare niciodată). În plus, apăsarea butonului poate să nu șteargă rândurile semnalate (serie+interval identic dar tarif/nr. bilete diferite) sau să șteargă altele (serie+tarif+nr identic, dar interval diferit).

### 48. Card „Raport eveniment” din actions-grid

- **PHP:** admin/statistici/raport_upload_form.inc.php:10-13 — zonă `.raport-drop` cu text FIX „📊 Trage sau apasa pentru a incarca raportul XLSX" (nu se schimbă când există deja raport), `.raport-preview` ascuns care se umple după parsare client-side cu „Total încasări: … RON · Total bilete: … RON · DITL (2%): … RON" (view.php:921-924), și buton `btn btn-green` „Salveaza raportul", width 100%, padding 9px, ascuns până când fișierul e parsat (raport_upload_form.inc.php:15-17).
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx:271-277 — zonă `.upload-zone` (altă clasă) cu text CONDIȚIONAT „Inlocuieste raportul" / „Trage sau apasa pentru XLSX", fără preview, și buton `btn btn-ghost` „Incarca" mereu vizibil (page.tsx:275-277).
- **Efect:** Cardul arată diferit: alt text în zona de drop, lipsește caseta verde de preview cu totalurile și DITL înainte de salvare, iar butonul e gri („Incarca") și mereu afișat în loc de verde („Salveaza raportul") apărut doar după alegerea fișierului.

### 49. Confirmări la ștergere

- **PHP:** Trei `onsubmit="return confirm(...)"`: view.php:397 „Stergi raportul financiar?”, view.php:547 „Stergi viză?”, view.php:648 „Stergi cursul «{nume curs}»? Aceasta actiune este ireversibila.”
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx:169-173 are explicit `onSubmit={undefined}` pe formularul de ștergere raport, page.tsx:306 (delete viză) și page.tsx:415 (delete curs) nu au niciun handler de confirmare.
- **Efect:** Niciun dialog de confirmare: un click pe „Sterge raportul”, pe „×” de la viză sau pe „Sterge cursul” execută direct acțiunea. La „Sterge cursul” asta înseamnă ștergere ireversibilă a cursului cu tot cu bilete și rapoarte, fără avertisment.

### 50. Viză bilete — buton de upload

- **PHP:** admin/statistici/cursuri/view.php:516-523 — formularul are `onsubmit="return false"` și doar zona `.upload-zone` cu `onchange="handleVizaUpload(this)"`; nu există buton de submit. Uploadul pornește singur la alegerea fișierului, iar eticheta se schimbă live: „⏳ Extrag date din PDF…”, „⏳ Incarc PDF…”, „⏳ Salvez date extrase…” (view.php:781, 795, 805).
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx:283-292 — form clasic cu buton `btn btn-ghost` „Incarca" mereu vizibil sub zona de drop; nu există feedback de progres.
- **Efect:** Sub zona „Trage sau apasa pentru a incarca Viză PDF” apare un buton „Incarca” care nu există pe live, iar alegerea fișierului nu mai declanșează nimic până nu apeși butonul; lipsesc și mesajele de progres.

### 51. Nav — dropdown „Site”

- **PHP:** admin/partials/layout-nav.php:72-77 — dropdown-ul „Site” conține exact 4 linkuri, în ordinea: Voturi, Imagini, Aspect, Test A/B. „Cursuri posibile” apare DOAR în harta de breadcrumb-uri ($__bc_labels, layout-nav.php:98), nu în nav.
- **Next:** web/app/admin/AdminNav.tsx:27-35 — dropdown-ul „Site” are 5 linkuri, cu „Cursuri posibile” (/admin/cursuri-posibile) inserat pe poziția 4, între „Aspect” și „Test A/B”.
- **Efect:** Meniul „Site” are un rând în plus față de live și „Test A/B” coboară pe poziția 5. În plus, pe /admin/cursuri-posibile triggerul „Site” se aprinde (AdminNav.tsx:52 items.some(isActive)), pe când în PHP tab-ul cursuri-posibile nu activează nimic în nav ($__site_active, layout-nav.php:55, listează doar vot/imagini/aspect + ab).

### 52. Bara de admin de pe paginile publice — cine o vede

- **PHP:** admin/bar.php:16-21 — clp_is_admin() întoarce true doar dacă userul din cookie are ($u['role'] ?? '') === 'owner'; altfel `if (!clp_is_admin()) return;` și bara nu se randează deloc.
- **Next:** web/app/(site)/AdminBar.tsx:30 — `if (!(await getSession())) return null;` — singura condiție e să existe sesiune, fără verificare de rol.
- **Efect:** Un user non-owner (andy, role city_manager — data/users.json) vede bara neagră de admin peste tot pe site-ul public, cu linkurile Cursuri/Aspect/Imagini/Mesaje/Vot. Pe live nu o vede.

### 53. Bara de admin — offset-ul conținutului

- **PHP:** admin/bar.php:43-44 — când bara e prezentă: `body { padding-top: 120px !important; }` (32px bară + 88px navbar) ȘI `.navbar { top: 32px !important; }`. Fără bară, paginile publice au `body { padding-top: 88px; }` (index.php:171, voteaza-cursuri.php:78 etc.).
- **Next:** web/app/(site)/AdminBar.tsx:26 — CSS-ul barei conține DOAR `body:has(#clp-adminbar) .navbar { top: 32px !important; }`; nu există niciun echivalent al lui `body { padding-top: 120px }`. Padding-ul rămâne fix 88px, setat pe wrapper în web/app/(site)/layout.tsx:36.
- **Efect:** Cu bara de admin activă, navbar-ul fix coboară la 32px și se termină la 120px, dar conținutul începe tot de la 88px — navbar-ul acoperă primii 32px din pagină (hero-ul/primul titlu intră sub navbar).

### 54. Header admin — poziționarea blocului de user

- **PHP:** admin/partials/layout-nav.php — <header class="wp-header"> are TREI copii direcți: div-ul brand+„Vezi site” (:2-5), blocul de user (div la :13-45 pentru owner, sau <span> la :47 pentru non-owner) și <a class="btn-logout"> (:49). CSS-ul .wp-header e `justify-content: space-between` (admin/assets/css/admin.css:45-52), deci cu 3 copii blocul de user cade la MIJLOCUL barei.
- **Next:** web/app/admin/layout.tsx:29-52 — header-ul are DOI copii: div-ul brand (:30-37) și un div care înglobează atât UserSwitcher/span-ul de user cât și formularul de logout (:38-51). Cu 2 copii, space-between lipește userul de butonul de logout, în dreapta.
- **Efect:** Numele userului + săgeata ▾ apar lipite de butonul „Deconectează-te” în colțul din dreapta, în loc de mijlocul barei de sus, ca pe live.

### 55. Header admin — eticheta userului non-owner

- **PHP:** admin/partials/layout-nav.php:47 — `<?= h(ucfirst(clp_current_user()['username'] ?? '')) ?>` — se afișează doar numele, cu prima literă mare.
- **Next:** web/app/admin/layout.tsx:42-44 — `{cap(session.username)} · {session.role}` — se adaugă separatorul „ · ” și rolul brut din DB.
- **Efect:** Un city_manager vede în header „Andy · city_manager” în loc de „Andy”.

## Subtile (31)

### 1. Card „Cursuri" — formatul datei

- **PHP:** admin/partials/dashboard-tab.php:53 afișează câmpul `date_display` salvat în courses.json, generat de lib/dates.php:82-84 `clp_date_display_from_raw()` → `clp_format_date_ro($raw, true, true)` cu titleCase=true, deci luna cu majusculă: „28 Aprilie 2026", „14 Mai 2026".
- **Next:** web/app/admin/page.tsx:15 `new Intl.DateTimeFormat("ro-RO", {timeZone:"Europe/Bucharest", day:"numeric", month:"long", year:"numeric"})` folosit la :96 — ro-RO scrie luna cu literă mică: „28 aprilie 2026", „14 mai 2026" (verificat rulând Intl în node).
- **Efect:** În card scrie „· 28 aprilie 2026" în loc de „· 28 Aprilie 2026". Diferă și sursa: PHP arată textul stocat de admin la salvarea cursului, Next îl recalculează din starts_at, deci orice `date_display` editat manual ar fi ignorat.

### 2. acțiuni din tabel (status / reducere) — navigare

- **PHP:** toggle_course și save_discount fac redirect la `/admin/?tab=cursuri` (admin/actions.php:50 și :204), fără year/month/ctab — pagina se reîncarcă de sus, luna din navigatorul de statistici revine la luna curentă și rândul de reducere se închide.
- **Next:** actions.ts:112-113 (saveDiscount) și :134-135 (toggleActive) fac doar `revalidatePath` — rămâi pe același URL, cu aceeași lună selectată, aceeași poziție de scroll și rândul de reducere deschis.
- **Efect:** După ce salvezi o reducere sau schimbi Activ/Inactiv, pe PHP pagina sărea sus și luna din tab-uri se resetează; în Next nu se schimbă nimic în afară de valoarea actualizată.

### 3. preview curs în formular — data

- **PHP:** admin-course-form.js:6-13 (clpFormatDateRo) scrie luna cu majusculă: `CLP_RO_MONTHS[m].charAt(0).toUpperCase() + ...` → „28 Iulie 2026”.
- **Next:** CourseAddForm.tsx:135-139 folosește Intl ro-RO → „28 iulie 2026”; restul meta-ului (separator „ · ”, ordinea dată·oră·speaker·locație) e identic.
- **Efect:** În cardul de previzualizare de sub formular luna apare cu literă mică, față de majusculă pe PHP.

### 4. preview curs — condiția de afișare

- **PHP:** admin-course-form.js:132-135: dacă titlul e gol, previewul se ascunde („if (!preview || !title) { preview.style.display='none'; return; }”), indiferent dacă s-a preluat o imagine.
- **Next:** CourseAddForm.tsx:140 `const showPreview = title.trim() !== "" || imageUrl !== "";` — previewul apare și fără titlu, doar cu imaginea preluată din link.
- **Efect:** Lipești linkul de bilete înainte să scrii titlul: în Next apare imediat cardul de preview cu imagine și titlu gol; pe PHP nu apărea nimic până nu scriai titlul.

### 5. preluarea automată a imaginii la editare

- **PHP:** admin-course-form.js:232-234: la încărcarea paginii, dacă cursul are link de bilete dar nu are imagine, se apelează automat fetchLTImage().
- **Next:** CourseAddForm.tsx:116-133 apelează `pullImage()` doar la onBlur pe câmpul de link (linia 214) sau la click pe butonul ↻ (linia 218).
- **Efect:** Deschizi la editare un curs cu link dar fără imagine: pe PHP imaginea se prelua singură și apărea în preview; în Next câmpul rămâne gol până atingi manual linkul sau butonul ↻.

### 6. link „Anulează” din formularul de editare

- **PHP:** cursuri-tab.php:79: `/admin/?tab=cursuri&year=<?= (int)$clp_year ?>&month=<?= (int)$clp_month ?>&ctab=cursuri` — păstrează luna și tabul din navigatorul de statistici.
- **Next:** CourseAddForm.tsx:262: `<Link href="/admin/cursuri">` — fără year/month/ctab, deci page.tsx:41-42 revin la luna curentă.
- **Efect:** Dacă erai pe o altă lună în panoul de statistici și dai „Anulează”, luna sare înapoi la luna curentă (pe PHP rămânea unde erai).

### 7. Tabelul lunii — coloana Viză

- **PHP:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/lib/courses.php:454 ia ultimul fișier `course_files` cu file_type='viza', iar :473 setează `has_viza = (bool)$row['viza_filename']` — bifa depinde EXCLUSIV de existența fișierului viza încărcat.
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/cursuri/stats-data.ts:91: `has_viza: Number(r.viza_files) > 0 || (subtipsByEvent.get(...)?.length ?? 0) > 0` — bifează și când există doar rânduri în viza_subtips, fără fișier.
- **Efect:** Un curs căruia i s-au adăugat manual subtipuri de viză fără PDF încărcat (acțiunea add_viza_subtip din admin/statistici/cursuri/view.php:208-224) apare cu ✓ verde la Viză în Next și cu „—" în PHP.

### 8. Calendar — celulele de umplere de la finalul lunii

- **PHP:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/admin/assets/js/admin-cursuri-stats.js:249-250 adaugă după ultima zi `trailing = (7 - ((firstDow + daysInMonth) % 7)) % 7` celule `.cal-cell.other-month`.
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/cursuri/Calendar.tsx:28-43 adaugă doar celulele de dinaintea zilei 1 (linia 28); după bucla zilelor nu mai umple restul ultimului rând.
- **Efect:** Pe ultimul rând al calendarului, în locul zilelor din luna următoare, rămâne fundalul containerului (#calGrid are background:var(--border)) în loc de celule gri deschis — o bandă gri închisă în colțul din dreapta jos.

### 9. Calendar — textul chipului de curs

- **PHP:** Titlul e luat brut din courses.json (/Users/ericcosulea/Documents/Proiecte/cursurilapahar/lib/courses_admin.php:175 `'title' => $c['title']`) și afișat ca atare la /Users/ericcosulea/Documents/Proiecte/cursurilapahar/admin/assets/js/admin-cursuri-stats.js:244 — ex. „Curs la Pahar - PUBLIC SPEAKING // 26 mai".
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/cursuri/stats-data.ts:110 curăță titlul: `.replace(/\s*\/\/.*$/u, "").replace(/^\s*Curs la Pahar\s*[-–]\s*/u, "")`.
- **Efect:** Chipul din calendar arată „PUBLIC SPEAKING" în Next și „Curs la Pahar - PUBLIC SPEAKING // 26 mai" (tăiat cu ellipsis, fiindcă .cal-event e nowrap + overflow hidden) în PHP. 7 din cele 20 de cursuri din data/courses.json au titluri cu „ // ".

### 10. Modal Detalii → tab Formular, valorile goale

- **PHP:** admin-speakeri.js:78: `val.textContent = r.value || '—'` — un câmp trimis gol afișează liniuța em.
- **Next:** SpeakeriTable.tsx:373: `<div style={{fontSize:13, whiteSpace:"pre-wrap"}}>{r.value}</div>` — fără fallback.
- **Efect:** Câmpurile completate gol în formular apar ca rânduri cu etichetă și nimic dedesubt, în loc de „—".

### 11. Modal Detalii — schimbarea între taburile Formular / Cursuri

- **PHP:** admin/partials/speakeri-tab.php:178-190 ține AMBELE taburi în DOM și doar comută `style.display` (admin-speakeri.js:91-92), deci ce ai tastat în input-urile de cursuri rămâne acolo când treci pe „Formular" și înapoi.
- **Next:** SpeakeriTable.tsx:364-408 e un ternar `tab === "formular" ? (…) : (<form …>)` — formularul de cursuri se demontează complet. Input-urile folosesc `defaultValue={t}` (linia 389) alimentat din state-ul `topics` inițializat o singură dată (linia 338), iar tastarea nu actualizează state-ul.
- **Efect:** Scrii un curs nou, treci pe tabul „Formular" și te întorci pe „Cursuri" — textul tastat a dispărut (rămân doar cursurile salvate). Pe live textul rămâne.

### 12. Încărcarea listei de speakeri — deduplicare și nume gol

- **PHP:** lib/speakers.php:89-98 `load_speakers()` trece TOT prin `clp_deduplicate_speakers()` (lib/speakers.php:65-77), care (a) sare peste orice intrare cu `trim(name) === ''` (linia 68) și (b) contopește intrările cu aceeași cheie email → telefon → nume (linia 74, `clp_merge_speaker_entries`, reunind `courses`, alegând statusul cu rangul cel mai mic și completând câmpurile goale).
- **Next:** web/app/admin/speakeri/page.tsx:45-59 face un simplu `SELECT … FROM speakers ORDER BY …`; nu există nicio deduplicare și niciun filtru pe nume gol nicăieri în page.tsx / SpeakeriTable.tsx / actions.ts.
- **Efect:** Dacă în baza Neon există două fișe pentru aceeași persoană (același email sau telefon) sau o fișă cu nume gol, ele apar ca rânduri separate în tabel și se numără în contorul „Speakeri (N)" din titlul cardului; pe live ar fi fost contopite/ascunse.

### 13. Mesaje → cardul unui mesaj Contact/Locații/Parteneriat, după apăsarea „Citit"

- **PHP:** admin/assets/js/admin-mesaje.js:113: după marcarea ca citit, if (now) card.querySelector('.msg-detail').classList.remove('open') — cardul se închide singur.
- **Next:** web/app/admin/mesaje/MessagesBoard.tsx:129-138 apelează doar call(toggleRead) (actions.ts:14-21 face revalidatePath); starea locală `open` (MessagesBoard.tsx:45) rămâne true, iar cheia cardului (key={m.id}) nu se schimbă, deci componenta nu se remontează.
- **Efect:** Pe live, cardul se pliază automat când îl marchezi „✓ Citit"; pe port rămâne deschis.

### 14. Mesaje → tab Speakeri, secțiunile „De evaluat" / „Evaluați"

- **PHP:** admin/assets/js/admin-mesaje.js:133-154 (evalMsg) și 116-132 (markContacted) doar schimbă clasele pe card și contorul din badge; cardul rămâne fizic în secțiunea în care a fost randat de PHP (messages-tab.php:31-33) până la un reload al paginii.
- **Next:** web/app/admin/mesaje/actions.ts:38-39 și 47-49 fac revalidatePath("/admin/mesaje"), iar MessagesBoard.tsx:217-225 recalculează pending/evaluated/shown la fiecare randare.
- **Efect:** Pe port, imediat ce dai „Nope/Meh/Top", cardul dispare din „🤔 De evaluat" și sare în „✅ Evaluați" (și se pliază, pentru că se remontează în alt părinte); la „Contactat", sub filtrele de rating cardul dispare din listă. Pe live cardul rămâne pe loc până reîncarci pagina.

### 15. Mesaje → salvarea unui comentariu

- **PHP:** admin/actions.php:1062 taie textul la 2000 de caractere: 'text' => mb_substr($text, 0, 2000).
- **Next:** web/app/admin/mesaje/actions.ts:57-67: text-ul e doar trim-uit (g()) și salvat integral în jsonb, fără limită de lungime.
- **Efect:** Un comentariu mai lung de 2000 de caractere apare trunchiat pe live și integral pe port.

### 16. Mesaje → ștergerea unui comentariu, verificarea de rol pe server

- **PHP:** admin/actions.php:1036: if ($action === 'delete_message_comment' && is_owner()) — doar owner-ul poate șterge comentarii; pentru un editor cererea nu e tratată deloc.
- **Next:** web/app/admin/mesaje/actions.ts:71-78 (deleteComment) cheamă doar requireAuth() (actions.ts:8-10), care verifică existența sesiunii, nu rolul. Butonul e ascuns pentru non-owner în UI (MessagesBoard.tsx:164), dar regula de server lipsește.
- **Efect:** Nimic pe ecran în fluxul normal; diferă doar regula: pe port orice utilizator autentificat (nu doar owner) poate șterge un comentariu.

### 17. Locații + Colaborări — butonul „Șterge" nu resetează URL-ul

- **PHP:** admin/actions.php:908 `header('Location: /admin/?tab=locatii')` și admin/actions.php:945 `header('Location: /admin/?tab=colaborari')` — după ștergere se pierd `saved=1` și `edit=…` din URL, deci notița verde dispare și formularul de editare se închide (locatii-tab.php:41, colaborari-tab.php:52 depind de `$edit_loc`/`$edit_col`).
- **Next:** web/app/admin/locatii/actions.ts:46-52 `deleteLocation` face doar `revalidatePath("/admin/locatii")`, fără redirect; identic web/app/admin/colaborari/actions.ts:41-47 pentru `deleteCollaboration`.
- **Efect:** Dacă ștergi imediat după o salvare, notița „Locația a fost salvată." / „Colaborarea a fost salvată." rămâne pe ecran; dacă ștergi o intrare cât ești pe `?edit=X`, formularul de editare rămâne deschis, pe când în PHP se închide.

### 18. Locații — butonul „+ Adaugă locație" în modul editare

- **PHP:** admin/partials/locatii-tab.php:8 comută direct DOM-ul: `document.getElementById('loc-form').style.display === 'none' ? 'block' : 'none'`. Cum la `?edit=` wrapper-ul e vizibil (locatii-tab.php:41 `style=""`), primul click ASCUNDE formularul de editare.
- **Next:** web/app/admin/locatii/LocationsPanel.tsx:30-31 `const [open, setOpen] = useState(false)` + `const show = open || !!edit`, iar butonul (:38) doar face `setOpen(!open)` — cât timp `edit` e definit, `show` rămâne true indiferent de starea butonului.
- **Efect:** Când editezi o locație, click pe „+ Adaugă locație" nu face nimic vizibil în port, în timp ce în PHP ascunde/reafișează cardul de formular.

### 19. Ordinea rândurilor la egalitate de voturi

- **PHP:** lib/vote.php:20-28 — clp_sort_vote_courses() sortează cu usort: întâi active înaintea inactivelor, apoi likes descrescător. usort e stabil în PHP 8, deci la același (active, likes) se păstrează ordinea din data/vote_courses.json, adică ordinea de adăugare.
- **Next:** web/app/admin/voturi/page.tsx:18 — `ORDER BY active DESC, likes DESC`, fără niciun criteriu de departajare (nici id, nici position, nici created_at). Postgres nu garantează ordinea rândurilor egale.
- **Efect:** În datele reale există egalități: printre cele active „Cum influențează școala…” și „Statisticile folosite ca armă politică” au ambele 28 de voturi, iar printre inactive sunt două perechi la 46 și la 40. Rândurile astea pot să-și schimbe locul între ele de la o încărcare la alta în port, pe PHP ordinea e fixă.

### 20. Dezactivează / Activează / Șterge — starea URL-ului după acțiune

- **PHP:** admin/actions.php:722 (toggle_vote_course) și :706 (delete_vote_course) fac `header('Location: /admin/?tab=vot')` — URL curat, fără `edit` și fără `saved`.
- **Next:** web/app/admin/voturi/actions.ts:44-51 (toggleVoteActive) și :53-60 (deleteVoteCourse) fac doar `revalidatePath`, fără `redirect`, deci pagina rămâne pe exact aceleași searchParams.
- **Efect:** Dacă ești pe /admin/voturi?edit=12 și apeși Dezactivează la alt curs, în PHP formularul de editare se închide și revii la „Adaugă idee de curs”; în port formularul de editare rămâne deschis. La fel, dacă ești pe ?saved=1, în PHP bara verde „Cursul a fost salvat.” dispare după toggle/ștergere, în port rămâne pe ecran.

### 21. Templates — feedback-ul butonului „Copiază”

- **PHP:** admin/assets/js/admin-common.js:83-91 — după copiere `btn.innerHTML = '✅ Copiat!'` (linia 87), `btn.disabled = true` (linia 88), iar revenirea la textul original se face după `1400` ms (linia 89).
- **Next:** web/app/admin/templates/TemplatesEditor.tsx:41-42 — `{done ? "✓ Copiat" : "📋 Copiază"}`, fără `disabled`, iar `setTimeout(() => setDone(false), 1200)` la linia 25.
- **Efect:** După click pe „📋 Copiază”: PHP arată „✅ Copiat!” (emoji bifă verde + semnul exclamării) cu butonul dezactivat timp de 1,4 s; Next arată „✓ Copiat” (bifă simplă, fără „!”), butonul rămâne apăsabil, iar textul revine după 1,2 s.

### 22. Aspect — setul de mesaje de eroare la favicon

- **PHP:** admin/actions.php:434-485 produce 7 mesaje distincte: 434 „Nu ai selectat niciun fișier.”, 436 „Fișierul este prea mare (limită server).”, 438 „Eroare upload (cod N).”, 442 „Format neacceptat: <extensia efectivă>. Folosește PNG, JPG sau WEBP.”, 444 „Extensia GD nu este disponibilă pe server.”, 454 „Nu am putut citi imaginea. Încearcă alt fișier.”, 485 „Eroare la salvare favicon. Verifică permisiunile directorului.”
- **Next:** web/app/admin/aspect/page.tsx:17-21 mapează doar 3 coduri — `nofile`, `format` → „Format neacceptat. Folosește PNG, JPG sau WEBP.” (FĂRĂ extensia efectivă, spre deosebire de PHP), `read` — plus fallback-ul „Eroare la upload.” de la linia 49. Nu există echivalent pentru fișier prea mare / cod de eroare upload / eroare la scriere.
- **Efect:** La un fișier .gif, PHP scrie „Format neacceptat: gif. Folosește PNG, JPG sau WEBP.”, iar Next scrie „Format neacceptat. Folosește PNG, JPG sau WEBP.” — lipsește extensia. La fișier prea mare sau eroare de scriere, PHP dă un mesaj explicit, Next nu afișează nimic sau „Eroare la upload.”

### 23. Linkuri rapide — iconița când câmpul e golit

- **PHP:** admin/actions.php:317 — `'icon' => trim($icons[$i] ?? '🔗')`. Fallback-ul 🔗 se aplică DOAR dacă indexul lipsește din POST; dacă userul șterge textul din câmp, se salvează string gol.
- **Next:** web/app/admin/setari/actions.ts:35 — `icon: (icons[i] ?? "🔗").trim() || "🔗"`. Al doilea `|| "🔗"` reintroduce iconița și când câmpul e golit de user.
- **Efect:** Golești iconița unui link rapid și salvezi: pe PHP butonul din dashboard rămâne fără emoji, pe Next reapare 🔗. Nu poți face un link rapid fără iconiță în port.

### 24. To-dos — gruparea sarcinilor finalizate pe zi

- **PHP:** admin/todos/index.php:59-63 grupează STRICT după completed_at ($ts = $t['completed_at'] ?? ''; $day = substr($ts,0,10)); dacă lipsește completed_at, cheia e '' și day_label() întoarce „Mai demult” (index.php:70).
- **Next:** web/app/admin/todos/page.tsx:20 — `to_char(coalesce(completed_at, created_at) ...) AS done_day`, deci cade pe ziua creării, nu pe grupul gol; created_at e NOT NULL (migration/neon_schema.sql:220), deci done_day nu e niciodată null și ramura „Mai demult” din page.tsx:39 e cod mort.
- **Efect:** Sarcinile bifate care n-au completed_at (cele finalizate înainte să existe câmpul) apar în PHP sub un grup „Mai demult” la finalul listei, iar în Next apar sub data la care au fost create (ex. „14 mai 2026”), amestecate cronologic printre celelalte grupe. Eticheta „Mai demult” nu mai apare deloc.

### 25. To-dos — focus la deschiderea formularului de adăugare

- **PHP:** toggleTodoForm() adaugă clasa .open și apelează titleInput.focus() (admin/todos/index.php:275-277), deci cursorul intră direct în câmpul de titlu la click pe „+”.
- **Next:** web/app/admin/todos/TodosList.tsx:108 — `autoFocus={open}` pe input. Formularul e montat permanent (TodosList.tsx:107, ascuns doar prin CSS display:none), iar autoFocus în React acționează doar la montare, când open e false; schimbarea ulterioară a prop-ului nu mai focalizează nimic.
- **Efect:** La click pe „+”, în PHP poți scrie imediat; în Next formularul se deschide dar câmpul nu e focalizat, trebuie click în plus.

### 26. Cursuri posibile — contorul „(N categorii)”

- **PHP:** admin/partials/cursuri-posibile-tab.php:10 — count($course_ideas['categories']) randat server-side, valoare fixă; ciAdd()/butonul de ștergere modifică doar DOM-ul (cursuri-posibile-tab.php:59-65, :30), nu și numărul.
- **Next:** web/app/admin/cursuri-posibile/IdeasEditor.tsx:40 — `({rows.length} categorii)` legat de state-ul React, deci se actualizează instant la adăugare/ștergere.
- **Efect:** După „+ Adaugă categorie” sau după ștergerea unei categorii, fără salvare, PHP arată în continuare numărul vechi, iar Next arată numărul actualizat.

### 27. Viză subtipuri — ordinea rândurilor

- **PHP:** admin/statistici/cursuri/view.php:257 — `SELECT * FROM viza_subtips WHERE course_id={$id} ORDER BY tarif DESC` (fără criteriu secundar; la tarife egale SQLite returnează în ordinea rowid = ordinea în care au fost extrase din PDF).
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx:61 — `ORDER BY tarif DESC, seria`, adică un tiebreak alfabetic pe serie.
- **Efect:** Când există mai multe serii la același tarif (ex. două serii de 50 RON), ordinea rândurilor din tabelul de viză diferă: pe live e ordinea din PDF, pe Next e alfabetică după serie.

### 28. Ștergerea vizei — seriile extrase

- **PHP:** admin/statistici/cursuri/view.php:165 — la `delete_viza` se execută și `DELETE FROM viza_subtips WHERE course_id={$id}`, deci seriile dispar odată cu PDF-ul.
- **Next:** web/app/admin/cursuri/[id]/detalii/actions.ts:165-172 — `deleteViza` șterge doar rândul din `event_files`; seriile rămân în `viza_subtips`. Iar `uploadViza` (actions.ts:150-151) șterge seriile vechi doar `if (subtips.length)`.
- **Efect:** După ștergerea PDF-ului și încărcarea altuia din care nu se extrage nimic, tabelul afișează seriile vechi din PDF-ul șters (pe PHP ar arăta mesajul „Nu s-au putut extrage datele automat…”).

### 29. Participanți fideli — sortarea la același număr de cursuri

- **PHP:** admin/statistici/cursuri/view.php:279 — `ORDER BY num_other DESC, t.participant_name ASC`, adică colația BINARY din SQLite: majusculele înaintea minusculelor, iar numele care încep cu diacritice (Ș, Ț, Ă) după toate literele ASCII.
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx:113 — `a.name.localeCompare(b.name, "ro")`, colație românească: „Ștefan” se așază lângă „S”, nu la coadă.
- **Efect:** Ordinea numelor din lista „Participanti fideli” diferă când sunt nume cu diacritice inițiale sau case-uri diferite — de ex. „Ștefan Ionescu” apare la finalul grupului pe live, dar imediat după numele cu S pe Next.

### 30. Notificare după procesarea vizei

- **PHP:** Nu există: după `upload_viza` PHP face doar redirect simplu (view.php:155) — nicio bandă de succes.
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx:135 — `{serii && <div className="notice notice-success">Viza procesată: {serii} serii extrase din PDF.</div>}`, alimentată de redirectul din actions.ts:161.
- **Efect:** După încărcarea vizei apare o bandă verde „Viza procesată: 3 serii extrase din PDF.” care nu există pe live.

### 31. Pagina Test A/B — starea nav-ului și breadcrumb-ul

- **PHP:** admin/statistici/layout_nav.php:8-13 setează $tab = 'dashboard' pentru orice pagină de sub /admin/statistici care nu e pnl (deci și pentru ab_headline.php). Efect în admin/partials/layout-nav.php: linkul „Dashboard” (:58) primește class="active" în același timp cu „Test A/B” (:76, prin $__ab_active de la :53), iar $__bc_is_home (:90) devine true → div-ul primește clasa `bc-doc--home` (:103) și breadcrumb-ul „Dashboard” (:105) NU se randează.
- **Next:** web/app/admin/AdminNav.tsx:16 marchează Dashboard cu `exact: true` și :42 `path === t.href`, deci pe /admin/ab Dashboard nu e activ (doar „Test A/B” + triggerul „Site”). web/app/admin/BcDoc.tsx:11 `isHome = usePathname() === "/admin"` → pe /admin/ab lipsește clasa `bc-doc--home` (:30) și SE afișează breadcrumb-ul „Dashboard” (:31-37).
- **Efect:** Pe pagina Test A/B: pe live sunt aprinse două butoane în nav (Dashboard + Test A/B) și nu există rândul de breadcrumb; în port e aprins doar Test A/B și apare în plus linkul „Dashboard” deasupra titlului, plus spațierea diferită dată de bc-doc--home.

## Cosmetice (27)

### 1. Secțiunea „Templates" — feedback după copiere

- **PHP:** admin/partials/dashboard-tab.php:133 apelează `clpCopyTemplate(this)`, definit în admin/assets/js/admin-common.js:83-91: înlocuiește TOT conținutul butonului cu „✅ Copiat!" (dispare și emoji-ul template-ului), dezactivează butonul (`btn.disabled = true`) și revine după 1400 ms.
- **Next:** web/app/admin/templates/TemplatesEditor.tsx:19-37 (`CopyButton`, folosit la web/app/admin/page.tsx:175): păstrează `<span>` cu emoji și schimbă doar eticheta în „✓ Copiat", nu dezactivează butonul și revine după 1200 ms.
- **Efect:** După click apare „<emoji> ✓ Copiat" în loc de „✅ Copiat!", butonul rămâne activ (se poate da click repetat) și revine cu 200 ms mai devreme.

### 2. mesajul de la preluarea imaginii

- **PHP:** admin-course-form.js:198-199 setează `msg.style.color = 'var(--success)'` pentru „✓ Imagine preluată.” / „Link valid, dar nu s-a găsit imagine.” (verde), și var(--danger) la eroare (linia 201).
- **Next:** CourseAddForm.tsx:241: `color: msg.ok ? "var(--text-muted)" : "var(--danger)"` — mesajul de succes rămâne gri, ca cel de încărcare.
- **Efect:** „✓ Imagine preluată.” apare gri în loc de verde.

### 3. rândurile de reducere din tabel

- **PHP:** toggleDiscountRow() (admin/assets/js/admin-common.js:1-5) comută independent fiecare rând `#discount-row-<id>`, deci pot fi deschise mai multe simultan.
- **Next:** CoursesTable.tsx:48 și :64 folosesc un singur `openDiscount: number | null`, deci deschiderea unui rând îl închide pe cel anterior.
- **Efect:** Nu poți avea deschise două formulare de reducere în același timp; deschiderea celui de-al doilea îl închide pe primul.

### 4. Participanți — câmpul de căutare

- **PHP:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/admin/assets/js/admin-cursuri-stats.js:174: input-ul are `placeholder="Caută participant…"`.
- **Next:** /Users/ericcosulea/Documents/Proiecte/cursurilapahar/web/app/admin/cursuri/StatsPanels.tsx:232-238: input fără placeholder, doar cu `aria-label="Caută participant"`.
- **Efect:** Caseta de căutare apare goală în Next, fără textul gri „Caută participant…". Atenție: regula globală a userului („No placeholders in form inputs") interzice placeholder-ele, deci diferența e probabil intenționată — de confirmat înainte de „reparare".

### 5. Modal Detalii → stilul etichetelor din formular

- **PHP:** admin-speakeri.js:74: `font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.02em;margin-bottom:2px`.
- **Next:** SpeakeriTable.tsx:372: `style={{ fontSize: 12, fontWeight: 600, color: "#374151" }}` — fără uppercase, fără letter-spacing, altă culoare și altă mărime.
- **Efect:** Etichetele întrebărilor („NUME ȘI PRENUME") apar scrise normal, mai mari și mai închise la culoare, în loc de majuscule mici gri.

### 6. Mesaje → butoanele de copiere din dreptul fiecărui câmp

- **PHP:** admin/assets/js/admin-mesaje.js:43-49 (copyField): după copiere schimbă iconița în bifă (_CHECK_SVG), adaugă clasa .copied și revine la iconița de copiere după 2000 ms.
- **Next:** web/app/admin/mesaje/MessagesBoard.tsx:25-42 (CopyBtn): doar navigator.clipboard.writeText(value), fără schimbare de iconiță, fără clasa .copied, fără timeout.
- **Efect:** Pe port nu primești niciun feedback vizual la copiere — butonul rămâne identic, nu apare bifa verde de 2 secunde.

### 7. Mesaje → textarea din formularul de comentariu

- **PHP:** lib/messages.php:267: <textarea placeholder="Scrie un comentariu..." rows="2" …>
- **Next:** web/app/admin/mesaje/MessagesBoard.tsx:181: <textarea rows={2} value={text} … /> — fără atribut placeholder.
- **Efect:** Caseta de comentariu apare goală pe port, fără textul-ghid „Scrie un comentariu..." din live.

### 8. Locații — eticheta „Nume *" din formular

- **PHP:** admin/partials/locatii-tab.php:48 `<label>Nume *</label>` — asterisc simplu, aceeași culoare cu textul etichetei (la fel ca la colaborări, colaborari-tab.php:59 „Nume brand / org. *").
- **Next:** web/app/admin/locatii/LocationForm.tsx:28-30 randează `Nume <span style={{ color: "var(--danger)" }}>*</span>`, iar `--danger: #dc2626` e definit în web/public/assets/css/admin.css:7 (foaia încărcată de app/admin/layout.tsx:28).
- **Efect:** Asteriscul de câmp obligatoriu apare roșu în port și negru/gri (culoarea labelului) în PHP; formularul de colaborări din port a păstrat varianta cu asterisc simplu, deci cele două formulare arată diferit între ele.

### 9. Emoji gol — celula din tabel și valoarea salvată

- **PHP:** admin/partials/vot-tab.php:71 randează `h($vc['emoji'] ?? '📚')` — fallback-ul 📚 se aplică DOAR când cheia lipsește, nu și când emoji e string gol. admin/actions.php:674 salvează `trim($_POST['vc_emoji'] ?? '📚')`, deci dacă golești câmpul se scrie string gol.
- **Next:** web/app/admin/voturi/VoteRows.tsx:34 randează `vc.emoji || "📚"` (stringul gol cade pe 📚), iar web/app/admin/voturi/actions.ts:20 și :36 salvează `g(formData,"emoji") || "📚"`, deci nu se poate salva emoji gol.
- **Efect:** Un curs cu emoji golit arată celulă goală în PHP și 📚 în port; în plus, în port nu poți lăsa deloc coloana Emoji goală. În datele curente (30 de idei) toate au emoji, deci acum nu se vede.

### 10. Etichetele formularului (label ↔ input)

- **PHP:** admin/partials/vot-tab.php:18-19, :22-23, :28-29 — fiecare label are `for` (vc_emoji, vc_name, vc_description) și fiecare input/textarea are `id`-ul corespunzător.
- **Next:** web/app/admin/voturi/VoteCourseForm.tsx:26, :36-38, :44 — label-urile nu au `htmlFor` și inputurile (:27-33, :39, :45) nu au `id`.
- **Efect:** Click pe „Emoji” / „Nume curs” / „Descriere” nu focusează câmpul în port, cum face pe PHP (și se pierde legătura pentru cititoarele de ecran).

### 11. Salvare selecție — curățarea transformărilor hero

- **PHP:** admin/actions.php:286-300 — decodează hero_transforms și reconstruiește `$clean_tr` parcurgând DOAR `$settings['hero_images']` (deci aruncă orice transformare orfană), face clamp `x`/`y` la [0,100] și `zoom` la [100,220] cu `max/min`, și stochează intrarea doar dacă `$x != 50 || $y != 50 || $z != 100` („stochează doar ce diferă de default”).
- **Next:** web/app/admin/imagini/actions.ts:61-66 + 74 — `transforms = JSON.parse(String(formData.get("hero_transforms") ?? "{}"))` și `await set("hero_transforms", transforms)`: obiectul e scris brut în DB, fără filtrare pe lista hero, fără clamp și fără eliminarea valorilor implicite.
- **Efect:** După ce apeși „Resetează” pe o imagine hero și salvezi, PHP nu păstrează nimic pentru acea imagine, iar portul salvează {x:50,y:50,zoom:100}. Randarea e identică, dar conținutul salvat diferă (relevant la comparări/migrări de settings).

### 12. Templates — spațierea rândului de butoane de jos

- **PHP:** admin/partials/templates-tab.php:62 — `<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">` pentru „+ Adaugă template” / „Salvează”.
- **Next:** web/app/admin/templates/TemplatesEditor.tsx:121 — `style={{ display:"flex", gap:8, flexWrap:"wrap", marginTop: 14 }}`.
- **Efect:** Butoanele de sub lista de template-uri stau cu 2px mai sus în Next.

### 13. Taskuri recurente — salvare cu titlu gol

- **PHP:** admin/actions.php:366-373 — `if ($title !== '') $t['title'] = $title;` apoi setează NECONDIȚIONAT `$t['assigned_to']` și `$t['days']`. Titlul gol păstrează titlul vechi, dar responsabilul și zilele se salvează oricum, iar redirectul e ?rec=ok.
- **Next:** web/app/admin/setari/actions.ts:76 — `if (!id || !title) redirect("/admin/setari#rec")`: cu titlu gol nu se face niciun UPDATE, deci nici zilele, nici responsabilul nu se salvează, și nu apare notice-ul de succes.
- **Efect:** Doar la POST cu titlu gol (inputul are `required` în ambele — config-tab.php:113 / RecurringEditor.tsx:97, deci UI-ul blochează cazul normal): pe PHP zilele/responsabilul se salvează, pe Next se pierd în tăcere.

### 14. Taskuri recurente — notice-uri de eroare

- **PHP:** admin/partials/config-tab.php:77-81 — două stări de eroare: ?rec=perm → „Folderul data/ nu e scriibil pe server (permisiuni). Trebuie 755/775 pe data/."; orice altă valoare → „Nu am putut scrie data/recurring_tasks.json." (setate în admin/actions.php:393-395).
- **Next:** web/app/admin/setari/RecurringEditor.tsx:208 — se randează doar ramura `notice === "ok"`; nu există nicio ramură de eroare, iar actions.ts:81-102 nu redirectează niciodată cu rec=perm/fail.
- **Efect:** Practic invizibil în port (nu există scriere pe disc care să eșueze, erorile de DB aruncă), dar cele două mesaje de stare nu au echivalent.

### 15. Sync Token — textul butonului de copiere

- **PHP:** admin/partials/config-tab.php:281 — butonul are mereu textul „Copiază"; admin/assets/js/admin-common.js:12-17 doar selectează inputul și scrie în clipboard, fără feedback vizual.
- **Next:** web/app/admin/setari/SyncToken.tsx:28-34 — după click, textul devine „Copiat" timp de 1500 ms (state `copied`), apoi revine la „Copiază".
- **Efect:** După click pe copiere, portul arată o etichetă „Copiat" care nu există în original.

### 16. Schimbă parola — textul explicativ de sub formular

- **PHP:** admin/partials/config-tab.php:269 — „Parola este salvată în <code>data/settings.json</code> și nu apare nicăieri în cod sau Git."
- **Next:** web/app/admin/setari/page.tsx:140-142 — „Parola se schimbă pentru contul tău ({session.username}) în baza Neon."
- **Efect:** Text complet diferit sub cardul de parolă, plus username-ul curent afișat în port. Diferență asumată de migrare (Neon în loc de fișier), dar textele nu coincid.

### 17. Stilizarea inputurilor din „Linkuri rapide" și din formularul taskului lunar

- **PHP:** admin/partials/config-tab.php:23-25 — inputurile ql_icon/ql_label/ql_url nu au niciun stil inline (doar text-align+font-size la iconiță) și nu sunt în .form-group, deci nu prind regula din admin/assets/css/admin.css:282-288; la fel inputul de titlu din config-tab.php:113 (doar class rec-title) și cel de sistem din 163 (doar width+margin).
- **Next:** web/app/admin/setari/QuickLinksEditor.tsx:10-19 și 33-35 adaugă un obiect INPUT cu padding 8px 10px, border 1px solid var(--border), border-radius var(--radius), background var(--surface), font-size 14, width 100%; RecurringEditor.tsx:46 introduce clasa `.rec-title-input` (aceleași proprietăți), aplicată la 97 și 187. Ambele proiecte încarcă exact același admin.css (diff gol între admin/assets/css/admin.css și web/public/assets/css/admin.css).
- **Efect:** Aceleași câmpuri au chenar/padding/fundal explicit în port, iar în original rămân pe stilul implicit al browserului/DaisyUI. Diferență pur vizuală, de verificat pe pixeli.

### 18. Placeholdere în formulare (diferență intenționată — a NU se „repara")

- **PHP:** admin/partials/config-tab.php:210 (placeholder="kit_..."), :215 ("ex: 1234567"), :261 ("Minim 6 caractere"), :265 ("Repetă parola").
- **Next:** web/app/admin/setari/page.tsx:56, 67, 130, 134 — inputurile corespunzătoare nu au atribut placeholder.
- **Efect:** Câmpurile goale nu mai afișează textul-fantomă. Corespunde regulii explicite a userului („niciodată placeholder pe input/textarea"), deci e o abatere voită de la original.

### 19. To-dos — spațierea primei etichete de zi din blocul „completate”

- **PHP:** admin/todos/index.php:241-243: .todo-done-day e copil direct al <details class="todo-completed">, deci regula .todo-completed > .todo-done-day:first-of-type { margin-top: 6px } (index.php:166) se aplică primei etichete.
- **Next:** web/app/admin/todos/TodosList.tsx:141-149 înfășoară fiecare grupă într-un <div key={g.label}>, deci .todo-done-day nu mai e copil direct al .todo-completed și selectorul din styles.ts:50 (copiat identic) nu se mai potrivește niciodată — rămâne margin: 12px din styles.ts:49.
- **Efect:** Prima etichetă de zi („Azi”) din lista de completate are 6px în plus de spațiu deasupra față de live.

### 20. To-dos — „Anulează” nu golește câmpul

- **PHP:** toggleTodoForm(true) șterge conținutul inputului la închidere: `if (titleInput) titleInput.value = ''` (admin/todos/index.php:271-273), atât la „Anulează”, cât și la re-click pe „+”.
- **Next:** web/app/admin/todos/TodosList.tsx:122 — onClick doar setOpen(false); inputul e necontrolat și nu se resetează.
- **Efect:** După „Anulează” și redeschiderea formularului, în Next textul tastat anterior e încă acolo; în PHP câmpul e gol.

### 21. Cursuri posibile — focus pe categoria nou adăugată

- **PHP:** ciAdd() clonează template-ul, îl adaugă în #ci-blocks și focalizează inputul de titlu al ultimului bloc (admin/partials/cursuri-posibile-tab.php:59-65).
- **Next:** web/app/admin/cursuri-posibile/IdeasEditor.tsx:104-110 — doar setRows([...rows, {...}]), fără focus pe noul câmp.
- **Efect:** După „+ Adaugă categorie”, în PHP poți scrie direct titlul; în Next trebuie click în câmp.

### 22. To-dos — titlul din tab-ul browserului

- **PHP:** admin/todos/index.php:111 — <title>To-dos – Admin</title> (iar pentru tab-ul Cursuri posibile, admin/index.php:44 — „Admin – Cursuri la Pahar”).
- **Next:** Nicio metadata per pagină în web/app/admin/todos/page.tsx sau web/app/admin/cursuri-posibile/page.tsx; se moștenește titlul global „Curs la Pahar” din web/app/layout.tsx:15-16.
- **Efect:** Tab-ul browserului scrie „Curs la Pahar” în loc de „To-dos – Admin”.

### 23. Test A/B — coloana „Descriere", varianta „on"

- **PHP:** admin/statistici/ab_headline.php:12 — `'on' => 'cardurile cu butonul „Vreau să vin"'`, adică ghilimea de închidere este apostroful dublu ASCII `"` (byte 0x22), în pereche cu „ (U+201E).
- **Next:** web/app/admin/ab/page.tsx:8 — `["on", "cardurile cu butonul „Vreau să vin”"]`, ghilimea de închidere este U+201D (`”`). (Restul textelor A/B — h1:30, paragraful:32, mesajele de la 66-77 — folosesc `&quot;` și coincid cu PHP-ul.)
- **Efect:** În rândul „Cu buton", coloana Descriere scrie „cardurile cu butonul „Vreau să vin”" în loc de „...„Vreau să vin"" — ghilimea de închidere e curbă în port și dreaptă pe live.

### 24. Marketing + Test A/B — titlul paginii din tab-ul browserului

- **PHP:** admin/marketing/index.php:110 — `<title>Marketing — Admin</title>`; admin/statistici/ab_headline.php:24 setează `$__page_title = 'Test A/B Buton'`, iar admin/statistici/layout_header.php:15 îl randează ca `<title>Test A/B Buton — Admin</title>`.
- **Next:** Nici web/app/admin/marketing/page.tsx, nici web/app/admin/ab/page.tsx nu exportă `metadata` (niciun `export const metadata` în tot web/app/admin), deci ambele moștenesc web/app/layout.tsx:15-18 → `title: "Curs la Pahar"`.
- **Efect:** Tab-ul browserului scrie „Curs la Pahar" pe ambele pagini, în loc de „Marketing — Admin", respectiv „Test A/B Buton — Admin" (contează și la bookmark-uri și în istoric).

### 25. Marketing — ancora #competitori și atributul data-section

- **PHP:** admin/marketing/index.php:233 — `<section id="competitori" class="mkt-competitori">`, deci URL-ul /admin/marketing/#competitori sare direct la grid-ul de competitori (tab-ul vechi „competitori" e redirecționat aici din admin/index.php:99-100). admin/marketing/index.php:147 pune și `data-section="<id>"` pe fiecare `.mkt-section`.
- **Next:** web/app/admin/marketing/page.tsx:72 — `<section className="mkt-competitori">` fără `id`; web/app/admin/marketing/MarketingSection.tsx:59 — `<section className="mkt-section">` fără `data-section`.
- **Efect:** Un link/bookmark către /admin/marketing/#competitori nu mai derulează la secțiunea Competitori în port (rămâne în capul paginii). Vizual, în rest, grid-ul e identic (aceleași 14 competitori, aceeași ordine, aceleași etichete 📸 Instagram / 🎵 TikTok / 🌐 Website).

### 26. Buton „Copiaza” — feedback

- **PHP:** admin/statistici/cursuri/view.php:946 — după copiere textul devine „Copiat ✓” timp de 1500 ms.
- **Next:** web/app/admin/cursuri/[id]/detalii/CopyDist.tsx:19 — textul devine „Copiat”, fără bifă (același timeout de 1500 ms, CopyDist.tsx:16).
- **Efect:** Lipsește bifa „✓” din confirmarea de copiere.

### 27. Titlul paginii în tab-ul browserului

- **PHP:** admin/statistici/cursuri/view.php:285 setează `$__page_title = h($course['name'])`, folosit în admin/statistici/layout_header.php:15 → `<title>{nume curs} — Admin</title>`.
- **Next:** web/app/admin/cursuri/[id]/detalii/page.tsx nu exportă `metadata`/`generateMetadata`, iar web/app/admin/layout.tsx nu definește niciun titlu, deci rămâne cel din web/app/layout.tsx:16 — „Curs la Pahar”.
- **Efect:** Tab-ul browserului arată „Curs la Pahar” în loc de numele cursului urmat de „— Admin”.
