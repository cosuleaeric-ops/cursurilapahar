<?php
/**
 * Cursuri la Pahar – Parteneri (media kit)
 */

// ── Load settings ─────────────────────────────────────────────────────────────
$settings_file = __DIR__ . '/data/settings.json';
$_defaults = [
    'logo_path'      => '/assets/images/logo.webp',
    'favicon_path'   => '',
    'nav_brand_text' => 'Cursuri la Pahar',
    'nav_links'      => [
        ['label' => 'Cursuri',          'url' => '/#cursuri'],
        ['label' => 'FAQ',              'url' => '/#faq'],
        ['label' => 'Colaborare',       'url' => '/#colaborare'],
        ['label' => 'Contact',          'url' => '/#contact'],
    ],
    'pages' => [],
];
$_loaded = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
$settings = array_merge($_defaults, $_loaded);

function clp_e(string $key, array $settings): string {
    return '';
}

// Galerie – imagini din folderul commit-uit (fallback dacă nu sunt setate în admin)
$spons_gallery = [
    'gallery-05','gallery-11','gallery-01','gallery-25',
    'gallery-08','gallery-32','gallery-17','gallery-06',
];

// Chips pentru banda cu cifre (duplicate în HTML pentru loop-ul de marquee)
$spons_chips = [
    '150.000+ vizualizări pe lună pe Instagram 📈',
    '50.000+ vizualizări pe lună pe TikTok 🎥',
    '~1.000 de deschideri la fiecare email 📬',
    '~53% open rate la newsletter 🔥',
    'Săli pline în fiecare săptămână 🍻',
    'Public care plătește bilet 🎟️',
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parteneri – Cursuri la Pahar</title>
    <meta name="description" content="Colaborează cu Cursuri la Pahar: peste 200.000 de vizualizări pe lună pe Instagram și TikTok, un newsletter cu open rate de peste 50% și cursuri săptămânale cu săli pline în București.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Cursuri la Pahar">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:title" content="Parteneri – Cursuri la Pahar">
    <meta property="og:description" content="Peste 200.000 de vizualizări pe lună, newsletter cu open rate de peste 50% și cursuri săptămânale cu săli pline. Vezi cifrele și pachetele.">
    <meta property="og:url" content="https://cursurilapahar.ro/parteneri">
    <meta property="og:image" content="https://cursurilapahar.ro/assets/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Cursuri la Pahar – curs ținut într-un bar plin din București">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Parteneri – Cursuri la Pahar">
    <meta name="twitter:description" content="Peste 200.000 de vizualizări pe lună, newsletter cu open rate de peste 50% și cursuri săptămânale cu săli pline.">
    <meta name="twitter:image" content="https://cursurilapahar.ro/assets/images/og-image.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@800&family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo filemtime(__DIR__.'/assets/css/style.css'); ?>">
    <?php if (!empty($settings['favicon_path'])): ?>
    <link rel="icon" href="<?= htmlspecialchars($settings['favicon_path']) ?>">
    <?php endif; ?>
    <style>
    :root {
        --bg:           <?= htmlspecialchars($settings['color_bg']         ?? '#0D0D0D') ?>;
        --accent:       <?= htmlspecialchars($settings['color_accent']     ?? '#C9A84C') ?>;
        --text:         <?= htmlspecialchars($settings['color_text']       ?? '#E8E4DC') ?>;
        --text-muted:   <?= htmlspecialchars($settings['color_text_muted'] ?? '#9CA3AF') ?>;
        --surface:      <?= htmlspecialchars($settings['color_surface']    ?? '#161616') ?>;
        --btn-hover:    <?= htmlspecialchars($settings['color_btn_hover'] ?? '#b8922e') ?>;
        --banner-bg:    <?= htmlspecialchars($settings['color_banner']    ?? '#FFB000') ?>;
    }
    body { padding-top: 88px; }

    /* ── Parteneri (scoped) — stil advertise.tldr.tech ─────── */
    .sp-wrap { overflow-x: clip; }
    .sp-wrap .container { max-width: 1140px; }

    /* Hero: text stânga + formular dreapta */
    .sp-hero { padding: 64px 0 72px; }
    .sp-hero-grid {
        display: grid; grid-template-columns: 1.05fr .95fr;
        gap: 56px; align-items: start;
    }
    .sp-hero h1 {
        font-family: var(--font-serif);
        font-size: clamp(2.3rem, 4.6vw, 3.7rem);
        font-weight: 900 !important;
        line-height: 1.08; margin: 0 0 22px;
    }
    .sp-hero h1 span { color: var(--accent); }
    .sp-hero-sub { color: var(--text-muted); font-size: 1.05rem; line-height: 1.7; margin: 0 0 18px; }

    /* Card formular — bordură albă + umbră offset pe accent */
    .sp-form-card {
        background: var(--surface);
        border: 1px solid rgba(255,255,255,.92);
        border-radius: 10px;
        box-shadow: 8px 8px 0 0 var(--accent);
        padding: 30px 28px;
    }
    .sp-form-card label {
        display: block; font-weight: 700; font-size: .92rem;
        margin: 0 0 6px; color: var(--text);
    }
    .sp-form-card label em { color: #ff5a5a; font-style: normal; }
    .sp-form-card input,
    .sp-form-card select,
    .sp-form-card textarea {
        width: 100%; background: #fff; color: #141414;
        border: 1px solid #141414; border-radius: 6px;
        box-shadow: 2px 2px 0 0 var(--accent);
        padding: 11px 12px; font-size: .95rem; margin-bottom: 18px;
    }
    .sp-form-card textarea { resize: vertical; }
    .sp-form-card input:focus,
    .sp-form-card select:focus,
    .sp-form-card textarea:focus { outline: 2px solid var(--accent); outline-offset: 1px; }
    .sp-btn {
        display: inline-block; background: var(--accent); color: #141414 !important;
        font-weight: 800; font-size: 1rem; border-radius: 6px;
        padding: 13px 26px; border: 1px solid #141414;
        box-shadow: 3px 3px 0 0 rgba(255,255,255,.85);
        transition: transform .12s, box-shadow .12s;
        cursor: pointer; text-decoration: none;
    }
    .sp-btn:hover { transform: translate(2px,2px); box-shadow: 1px 1px 0 0 rgba(255,255,255,.85); background: var(--btn-hover); }

    /* Banda marquee cu cifre */
    .sp-marquee {
        border-top: 1px solid rgba(255,255,255,.14);
        border-bottom: 1px solid rgba(255,255,255,.14);
        padding: 22px 0; overflow: hidden;
    }
    .sp-marquee-track {
        display: flex; gap: 14px; width: max-content;
        animation: sp-scroll 36s linear infinite;
    }
    .sp-marquee:hover .sp-marquee-track { animation-play-state: paused; }
    .sp-chip {
        white-space: nowrap; font-weight: 700; font-size: .95rem;
        border: 1px solid rgba(255,255,255,.9); border-radius: 7px;
        background: rgba(255,255,255,.03); padding: 11px 18px;
    }
    @keyframes sp-scroll { to { transform: translateX(-50%); } }

    /* Secțiuni */
    .sp-sec { padding: 64px 0; }
    .sp-sec .section-title { margin-bottom: 10px; }
    .sp-lead { color: var(--text-muted); text-align: center; max-width: 60ch; margin: 0 auto 42px; line-height: 1.65; }

    /* Carduri de canale (ca newsletter-cardurile TLDR) */
    .sp-aud { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .sp-aud-card {
        border: 1px solid rgba(255,255,255,.9); border-radius: 9px;
        background: rgba(255,255,255,.03); padding: 24px 22px;
        transition: box-shadow .15s, transform .15s;
    }
    .sp-aud-card:hover { box-shadow: 6px 6px 0 0 var(--accent); transform: translate(-2px,-2px); }
    .sp-aud-card h3 { margin: 0 0 8px; font-size: 1.12rem; font-weight: 800 !important; }
    .sp-aud-card p { margin: 0 0 14px; color: var(--text-muted); font-size: .92rem; line-height: 1.55; min-height: 4.2em; }
    .sp-aud-card .stat { color: var(--accent); font-weight: 800; font-size: .95rem; }

    /* Powered by — text + mockup */
    .sp-powered { display: grid; grid-template-columns: 1.05fr .95fr; gap: 48px; align-items: center; }
    .sp-powered .section-title { text-align: left; }
    .sp-powered p { color: var(--text-muted); line-height: 1.7; margin: 0 0 14px; }
    .sp-screen {
        aspect-ratio: 16/10; border-radius: 10px; position: relative;
        background: linear-gradient(150deg, #1c1c1c, #0a0a0a);
        border: 1px solid rgba(255,255,255,.92);
        box-shadow: 8px 8px 0 0 var(--accent);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    .sp-screen .pw { color: var(--text-muted); letter-spacing: 3px; text-transform: uppercase; font-size: 13px; }
    .sp-screen .brand {
        font-family: var(--font-serif); font-weight: 900;
        font-size: clamp(1.6rem, 4.5vw, 2.4rem);
        color: var(--accent); letter-spacing: 1px; margin-top: 6px;
    }
    .sp-screen .glass { position: absolute; bottom: 14px; right: 18px; font-size: 30px; opacity: .5; }

    /* Tiere (ca plasările TLDR: ✅ + 👑) */
    .sp-tiers { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; align-items: stretch; }
    .sp-tier {
        border: 1px solid rgba(255,255,255,.9); border-radius: 9px;
        background: rgba(255,255,255,.03); padding: 28px 26px;
        display: flex; flex-direction: column;
    }
    .sp-tier.feat {
        border-color: var(--accent);
        box-shadow: 8px 8px 0 0 var(--accent);
        background: color-mix(in srgb, var(--accent) 7%, var(--surface));
    }
    .sp-tier h3 { margin: 0 0 6px; font-size: 1.3rem; font-weight: 800 !important; }
    .sp-tier .who { color: var(--text-muted); font-size: .92rem; margin: 0 0 18px; min-height: 2.4em; }
    .sp-tier ul { list-style: none; margin: 0 0 24px; padding: 0; flex: 1; }
    .sp-tier li { padding: 7px 0; font-size: .95rem; line-height: 1.5; }
    .sp-tier .sp-btn { align-self: flex-start; }

    /* Galerie */
    .sp-wrap .gallery-item img { border-radius: 8px; }

    /* CTA final */
    .sp-cta { text-align: center; padding: 72px 0 84px; }
    .sp-cta h2 {
        font-family: var(--font-serif); font-weight: 900 !important;
        font-size: clamp(1.8rem, 4vw, 2.7rem); margin: 0 0 14px;
    }
    .sp-cta p { color: var(--text-muted); margin: 0 0 28px; }

    @media (max-width: 920px) {
        .sp-hero-grid, .sp-powered { grid-template-columns: 1fr; }
        .sp-aud, .sp-tiers { grid-template-columns: 1fr 1fr; }
        .sp-form-card { box-shadow: 6px 6px 0 0 var(--accent); }
    }
    @media (max-width: 620px) {
        .sp-aud, .sp-tiers { grid-template-columns: 1fr; }
        .sp-aud-card p { min-height: 0; }
    }
    </style>
    <?php include __DIR__ . '/includes/head-scripts.php'; ?>
    <?php include __DIR__ . '/includes/edit-styles.php'; ?>
</head>
<body>
<?php include __DIR__ . '/admin/bar.php'; ?>

<!-- ── NAVBAR ─────────────────────────────── -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/" class="navbar-logo">
            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="<?= htmlspecialchars($settings['nav_brand_text']) ?>">
            <span class="navbar-brand-text"><?php $nb=explode(' ',htmlspecialchars($settings['nav_brand_text']),2); echo '<span>'.$nb[0].'</span><span>'.($nb[1]??'').'</span>'; ?></span>
        </a>
        <div class="navbar-links">
            <?php foreach ($settings['nav_links'] as $nl): ?>
            <a href="<?= htmlspecialchars($nl['url']) ?>"><?= htmlspecialchars($nl['label']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="navbar-right">
            <button class="navbar-hamburger" id="hamburger" aria-label="Meniu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile drawer -->
<div class="navbar-drawer" id="navDrawer">
    <?php foreach ($settings['nav_links'] as $nl): ?>
    <a href="<?= htmlspecialchars($nl['url']) ?>"><?= htmlspecialchars($nl['label']) ?></a>
    <?php endforeach; ?>
</div>

<div class="sp-wrap">

<!-- ── HERO: text + formular ───────────────── -->
<header class="sp-hero" id="oferta">
    <div class="container">
        <div class="sp-hero-grid">
            <div>
                <h1>Hai cu brandul tău <span>la un pahar</span></h1>
                <p class="sp-hero-sub">
                    Organizăm cursuri în fiecare săptămână, în baruri din București. Avem peste
                    <strong style="color:var(--text)">200.000 de vizualizări pe lună</strong> pe Instagram și TikTok,
                    un newsletter citit de aproape 2.000 de oameni și săli pline la fiecare eveniment.
                </p>
                <p class="sp-hero-sub">
                    Completează formularul și îți trimitem kitul de prezentare, cu toate cifrele și
                    o ofertă pe obiectivele tale — de obicei în 24 de ore.
                </p>
            </div>
            <div class="sp-form-card">
                <form class="inner-page-form" data-form-type="sponsorizare" novalidate>
                    <label for="sp_brand">Brand / companie <em>*</em></label>
                    <input type="text" id="sp_brand" name="partner_name" required>

                    <label for="sp_email">Email <em>*</em></label>
                    <input type="email" id="sp_email" name="email" required>

                    <label for="sp_interes">Ce te interesează? <em>*</em></label>
                    <select id="sp_interes" name="interes" required>
                        <option value="">Alege o variantă</option>
                        <option value="newsletter">Mențiune în newsletter</option>
                        <option value="social">Instagram &amp; TikTok</option>
                        <option value="powered-editie">„Powered by" o ediție</option>
                        <option value="curs-colaborare">Un curs în colaborare</option>
                        <option value="partener-sezon">Partener de sezon (4 ediții)</option>
                        <option value="sampling">Sampling / activare la eveniment</option>
                        <option value="nu-stiu">Încă nu știu, hai să discutăm</option>
                    </select>

                    <label for="sp_msg">Ce vrei să obții din colaborare?</label>
                    <textarea id="sp_msg" name="message" rows="3"></textarea>

                    <button type="submit" class="sp-btn">Cere kitul de prezentare</button>
                    <div class="form-message" aria-live="polite"></div>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- ── MARQUEE CIFRE ───────────────────────── -->
<div class="sp-marquee" aria-hidden="true">
    <div class="sp-marquee-track">
        <?php for ($i = 0; $i < 2; $i++): foreach ($spons_chips as $chip): ?>
        <span class="sp-chip"><?= htmlspecialchars($chip) ?></span>
        <?php endforeach; endfor; ?>
    </div>
</div>

<!-- ── CANALE ──────────────────────────────── -->
<section class="sp-sec">
    <div class="container">
        <h2 class="section-title">Patru canale, aceeași comunitate 🚀</h2>
        <p class="sp-lead">Cifre reale, pe care le actualizăm constant.</p>
        <div class="sp-aud">
            <div class="sp-aud-card">
                <h3>Instagram</h3>
                <p>Reels și stories de la fiecare curs, cu mențiunea „Powered by" pentru partenerul ediției.</p>
                <div class="stat">21.2k urmăritori · 150k+ vizualizări/lună</div>
            </div>
            <div class="sp-aud-card">
                <h3>TikTok</h3>
                <p>Clipuri filmate la evenimente, pentru un public tânăr care descoperă cursurile.</p>
                <div class="stat">8.4k urmăritori · 50k+ vizualizări/lună</div>
            </div>
            <div class="sp-aud-card">
                <h3>Newsletter</h3>
                <p>Un email pe săptămână cu următoarele cursuri, citit de o comunitate fidelă.</p>
                <div class="stat">1.943 abonați · ~53% open rate</div>
            </div>
            <div class="sp-aud-card">
                <h3>Evenimente</h3>
                <p>Cursuri săptămânale în baruri din București, cu bilete plătite și săli pline.</p>
                <div class="stat">Săptămânal · public plătitor</div>
            </div>
        </div>
    </div>
</section>

<!-- ── POWERED BY ──────────────────────────── -->
<section class="sp-sec">
    <div class="container">
        <div class="sp-powered">
            <div>
                <h2 class="section-title">Brandul tău, direct în fața lor ✉️</h2>
                <p>Postările de pe Instagram și TikTok și emailul din săptămâna cursului afișează brandul tău ca partener oficial al ediției.</p>
                <p>Oamenii îți văd brandul exact în momentele în care sunt cu ochii pe noi: când își iau bilet, când urmăresc clipurile de la curs și când primesc emailul săptămânal. Iar dacă vrei să mergem mai departe, construim împreună un curs întreg pe o temă aleasă cu tine.</p>
                <a href="#oferta" class="sp-btn">Vreau brandul meu aici</a>
            </div>
            <div class="sp-screen">
                <span class="pw">Powered by</span>
                <span class="brand">BRANDUL TĂU</span>
                <span class="glass">🍷</span>
            </div>
        </div>
    </div>
</section>

<!-- ── TIERE ───────────────────────────────── -->
<section class="sp-sec" id="pachete">
    <div class="container">
        <h2 class="section-title">Alege formatul 👑</h2>
        <p class="sp-lead">De la o apariție punctuală până la parteneriat de sezon. Prețul final îl potrivim pe obiectivele tale.</p>
        <div class="sp-tiers">
            <div class="sp-tier">
                <h3>Spot</h3>
                <p class="who">O apariție punctuală, pe un singur canal.</p>
                <ul>
                    <li>✅ Mențiune în newsletter <em>sau</em> pe social</li>
                    <li>✅ Link cu UTM, îți vezi click-urile</li>
                    <li>✅ Ideal pentru un test rapid</li>
                </ul>
                <a href="#oferta" class="sp-btn">Cere oferta</a>
            </div>
            <div class="sp-tier feat">
                <h3>👑 Powered by ediție</h3>
                <p class="who">Partener oficial al unei ediții, pe toate canalele.</p>
                <ul>
                    <li>✅ „Powered by" pe Instagram &amp; TikTok</li>
                    <li>✅ Mențiune dedicată în newsletter</li>
                    <li>✅ Prezență / sampling la eveniment</li>
                    <li>✅ Raport cu cifrele după ediție</li>
                </ul>
                <a href="#oferta" class="sp-btn">Cere oferta</a>
            </div>
            <div class="sp-tier">
                <h3>Partener de sezon</h3>
                <p class="who">4 ediții, prezență constantă, preț cu discount.</p>
                <ul>
                    <li>✅ Tot din „Powered by ediție", ×4</li>
                    <li>✅ Un curs în colaborare, pe o temă aleasă împreună</li>
                    <li>✅ Prioritate la date și subiecte</li>
                    <li>✅ Discount pentru angajament</li>
                </ul>
                <a href="#oferta" class="sp-btn">Cere oferta</a>
            </div>
        </div>
    </div>
</section>

<!-- ── GALERIE ─────────────────────────────── -->
<section class="sp-sec">
    <div class="container">
        <h2 class="section-title">Așa arată un curs la pahar 🍻</h2>
        <p class="sp-lead">Oameni reali, săli pline, atmosferă bună. Exact contextul în care ajunge brandul tău.</p>
        <div class="gallery-slider-wrap">
            <button class="gslider-btn gslider-prev" aria-label="Anterior">&#8249;</button>
            <div class="gallery-slider">
                <?php foreach ($spons_gallery as $gi => $g): $src = "/assets/images/gallery/$g.webp"; if (!file_exists(__DIR__.$src)) continue; ?>
                <div class="gallery-item" data-index="<?= $gi ?>">
                    <img src="<?= $src ?>" alt="Cursuri la Pahar" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
            <button class="gslider-btn gslider-next" aria-label="Următor">&#8250;</button>
        </div>
    </div>
</section>

<!-- ── FAQ ─────────────────────────────────── -->
<section class="sp-sec">
    <div class="container container-narrow">
        <h2 class="section-title">Întrebări frecvente</h2>
        <div class="faq-list" style="margin-top:36px;">
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span>Cât costă o colaborare?</span>
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Depinde de format și de numărul de ediții. Scrie-ne ce buget și ce obiectiv ai și revenim cu o ofertă concretă, de obicei în 24 de ore.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span>Cum arată o mențiune „Powered by"?</span>
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Numele și logo-ul brandului tău apar în postările și clipurile ediției, pe Instagram și TikTok, plus în emailul trimis în săptămâna cursului.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span>Putem face un curs împreună?</span>
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Da. Alegem împreună o temă care se potrivește și publicului nostru și brandului tău, iar ediția respectivă o construim în colaborare, de la subiect până la promovare.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span>Putem oferi produse participanților?</span>
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Da, sampling-ul e printre cele mai cerute formate: produsul tău ajunge direct în mâna oamenilor, într-o seară relaxată, la un pahar.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span>Cum știu ce rezultate a avut campania?</span>
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Primești link-uri cu UTM și, după fiecare ediție, un raport cu cifrele reale: afișări, click-uri și numărul de participanți.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA FINAL ───────────────────────────── -->
<section class="sp-cta">
    <div class="container">
        <h2>Vrei brandul tău la un pahar? 🍻</h2>
        <p>Completează formularul și primești kitul de prezentare cu toate cifrele.</p>
        <a href="#oferta" class="sp-btn">Hai să vorbim</a>
    </div>
</section>

</div><!-- /.sp-wrap -->

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="/assets/js/main.js?v=<?php echo filemtime(__DIR__.'/assets/js/main.js'); ?>"></script>
<script>history.scrollRestoration = 'manual';</script>
</body>
</html>
