<?php
/**
 * Cursuri la Pahar – Sponsorizare (media kit)
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
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsorizare – Cursuri la Pahar</title>
    <meta name="description" content="Pune-ți brandul în fața unei comunități implicate: 1.900+ abonați care chiar deschid emailul, 29.000+ urmăritori pe social și evenimente săptămânale cu public plătitor în București.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Cursuri la Pahar">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:title" content="Sponsorizare – Cursuri la Pahar">
    <meta property="og:description" content="Newsletter cu open rate dublu față de medie, social în creștere și evenimente fizice cu public plătitor. Vezi cifrele și pachetele.">
    <meta property="og:url" content="https://cursurilapahar.ro/parteneri">
    <meta property="og:image" content="https://cursurilapahar.ro/assets/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Cursuri la Pahar – curs ținut într-un bar plin din București">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Sponsorizare – Cursuri la Pahar">
    <meta name="twitter:description" content="Newsletter cu open rate dublu față de medie, social în creștere și evenimente fizice cu public plătitor.">
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

    /* ── Sponsorizare (scoped) ─────────────────────────────── */
    .sp-wrap { overflow-x: clip; }

    .sp-hero {
        position: relative;
        padding: 72px 0 56px;
        text-align: center;
        background:
            radial-gradient(120% 90% at 50% 0%, color-mix(in srgb, var(--accent) 14%, transparent) 0%, transparent 60%),
            var(--bg);
        border-bottom: 1px solid color-mix(in srgb, var(--accent) 22%, transparent);
    }
    .sp-eyebrow {
        display: inline-block;
        font-size: 13px; letter-spacing: 2px; text-transform: uppercase;
        color: var(--accent); font-weight: 700; margin-bottom: 18px;
        padding: 6px 14px; border-radius: 999px;
        border: 1px solid color-mix(in srgb, var(--accent) 40%, transparent);
    }
    .sp-hero h1 {
        font-family: 'Anton', sans-serif; font-weight: 400;
        font-size: clamp(2.1rem, 6vw, 4rem); line-height: 1.02;
        letter-spacing: .5px; margin: 0 auto 20px; max-width: 14ch;
        text-transform: uppercase;
    }
    .sp-hero h1 span { color: var(--accent); }
    .sp-hero-sub {
        color: var(--text-muted); font-size: clamp(1rem, 2.2vw, 1.22rem);
        line-height: 1.6; max-width: 62ch; margin: 0 auto 32px;
    }
    .sp-hero-cta { display: inline-flex; gap: 14px; flex-wrap: wrap; justify-content: center; }

    /* Reach strip */
    .sp-reach {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 14px; max-width: 900px; margin: 44px auto 0; padding: 0 20px;
    }
    .sp-reach .rc {
        background: var(--surface);
        border: 1px solid color-mix(in srgb, var(--accent) 16%, transparent);
        border-radius: 16px; padding: 22px 12px; text-align: center;
    }
    .sp-reach .rc b {
        display: block; font-family: 'Anton', sans-serif; font-weight: 400;
        font-size: clamp(1.6rem, 4vw, 2.4rem); color: var(--accent); line-height: 1;
    }
    .sp-reach .rc small { display: block; color: var(--text-muted); font-size: 13px; margin-top: 8px; }

    /* Section shell */
    .sp-sec { padding: 66px 0; }
    .sp-sec.alt { background: color-mix(in srgb, var(--surface) 55%, var(--bg)); }
    .sp-lead { color: var(--text-muted); text-align: center; max-width: 60ch; margin: -10px auto 40px; line-height: 1.65; }

    /* Value grid */
    .sp-values { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
    .sp-value {
        background: var(--surface); border: 1px solid rgba(255,255,255,.06);
        border-radius: 16px; padding: 26px 26px 24px;
    }
    .sp-value .ic { font-size: 26px; margin-bottom: 12px; display: block; }
    .sp-value h3 { margin: 0 0 8px; font-size: 1.15rem; }
    .sp-value p { margin: 0; color: var(--text-muted); line-height: 1.6; font-size: .96rem; }

    /* Channel cards (media kit numbers) */
    .sp-channels { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .sp-chan {
        background: var(--surface); border: 1px solid rgba(255,255,255,.07);
        border-radius: 18px; padding: 26px 22px; position: relative; overflow: hidden;
    }
    .sp-chan::before {
        content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
        background: linear-gradient(90deg, var(--accent), transparent);
    }
    .sp-chan .lbl { font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); }
    .sp-chan .big {
        font-family: 'Anton', sans-serif; font-weight: 400;
        font-size: 2.5rem; line-height: 1; margin: 10px 0 4px; color: var(--text);
    }
    .sp-chan .unit { color: var(--accent); font-weight: 700; font-size: .95rem; }
    .sp-chan ul { list-style: none; margin: 16px 0 0; padding: 0; }
    .sp-chan li { color: var(--text-muted); font-size: .9rem; padding: 5px 0 5px 20px; position: relative; line-height: 1.4; }
    .sp-chan li::before { content: '✓'; position: absolute; left: 0; color: var(--accent); font-weight: 700; }

    /* Formats grid (what we can do) */
    .sp-formats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .sp-fmt {
        background: var(--surface); border: 1px solid rgba(255,255,255,.06);
        border-radius: 16px; padding: 24px; transition: border-color .2s, transform .2s;
    }
    .sp-fmt:hover { border-color: color-mix(in srgb, var(--accent) 45%, transparent); transform: translateY(-3px); }
    .sp-fmt .tag {
        display: inline-block; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
        color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
        border-radius: 999px; padding: 3px 10px; margin-bottom: 14px;
    }
    .sp-fmt h3 { margin: 0 0 8px; font-size: 1.1rem; }
    .sp-fmt p { margin: 0; color: var(--text-muted); font-size: .95rem; line-height: 1.6; }

    /* Powered-by demo */
    .sp-powered {
        display: grid; grid-template-columns: 1.1fr .9fr; gap: 40px; align-items: center;
    }
    .sp-powered h2 { text-align: left; margin: 0 0 16px; }
    .sp-powered p { color: var(--text-muted); line-height: 1.7; margin: 0 0 14px; }
    .sp-screen {
        aspect-ratio: 16/10; border-radius: 18px; position: relative; overflow: hidden;
        background: linear-gradient(150deg, #1b1b1b, #0a0a0a);
        border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        box-shadow: 0 30px 60px -30px rgba(0,0,0,.8);
    }
    .sp-screen .pw { color: var(--text-muted); letter-spacing: 3px; text-transform: uppercase; font-size: 13px; }
    .sp-screen .brand {
        font-family: 'Anton', sans-serif; font-size: clamp(1.8rem, 5vw, 2.8rem);
        color: var(--accent); letter-spacing: 1px; margin-top: 6px;
    }
    .sp-screen .glass { position: absolute; bottom: 14px; right: 18px; font-size: 30px; opacity: .5; }

    /* Packages */
    .sp-pkgs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; align-items: stretch; }
    .sp-pkg {
        background: var(--surface); border: 1px solid rgba(255,255,255,.08);
        border-radius: 20px; padding: 30px 26px; display: flex; flex-direction: column;
    }
    .sp-pkg.feat {
        border-color: var(--accent);
        background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 10%, var(--surface)), var(--surface));
        box-shadow: 0 20px 50px -30px color-mix(in srgb, var(--accent) 70%, transparent);
    }
    .sp-pkg .pop {
        align-self: flex-start; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase;
        background: var(--accent); color: #1a1400; font-weight: 800; border-radius: 999px;
        padding: 4px 12px; margin-bottom: 14px;
    }
    .sp-pkg h3 { margin: 0 0 4px; font-size: 1.35rem; }
    .sp-pkg .who { color: var(--text-muted); font-size: .9rem; margin: 0 0 18px; min-height: 2.4em; }
    .sp-pkg ul { list-style: none; margin: 0 0 22px; padding: 0; flex: 1; }
    .sp-pkg li { padding: 8px 0 8px 24px; position: relative; font-size: .94rem; line-height: 1.45; border-top: 1px solid rgba(255,255,255,.05); }
    .sp-pkg li:first-child { border-top: 0; }
    .sp-pkg li::before { content: '✓'; position: absolute; left: 0; top: 8px; color: var(--accent); font-weight: 700; }
    .sp-pkg .price { font-family: 'Anton', sans-serif; font-size: 1.5rem; color: var(--accent); margin-bottom: 4px; }
    .sp-pkg .price small { display: block; font-family: 'Rubik', sans-serif; font-size: .8rem; color: var(--text-muted); font-weight: 400; letter-spacing: 0; }

    /* CTA band */
    .sp-band {
        text-align: center;
        background:
            radial-gradient(100% 120% at 50% 0%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 65%),
            var(--surface);
        border-top: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
        border-bottom: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
    }

    @media (max-width: 900px) {
        .sp-channels, .sp-formats, .sp-pkgs { grid-template-columns: 1fr 1fr; }
        .sp-powered { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .sp-reach { grid-template-columns: 1fr 1fr; }
        .sp-values, .sp-channels, .sp-formats, .sp-pkgs { grid-template-columns: 1fr; }
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

<!-- ── HERO ───────────────────────────────── -->
<header class="sp-hero">
    <div class="container">
        <span class="sp-eyebrow">Pentru branduri</span>
        <h1>Pune-ți brandul în fața unor oameni care <span>chiar ascultă</span></h1>
        <p class="sp-hero-sub">
            Nu vindem impresii pierdute într-un feed. La Cursuri la Pahar ajungi la o comunitate care deschide emailul,
            vine la eveniment și plătește bilet ca să învețe — online <strong>și</strong> față în față, în București.
        </p>
        <div class="sp-hero-cta">
            <a href="#pachete" class="btn btn-accent">Vezi pachetele</a>
            <a href="#contact" class="btn btn-secondary">Cere kitul complet</a>
        </div>

        <div class="sp-reach">
            <div class="rc"><b>~31.000</b><small>reach lunar combinat</small></div>
            <div class="rc"><b>~53%</b><small>open rate newsletter</small></div>
            <div class="rc"><b>Săptămânal</b><small>eveniment fizic</small></div>
            <div class="rc"><b>București</b><small>curând în toată țara</small></div>
        </div>
    </div>
</header>

<!-- ── DE CE NOI ──────────────────────────── -->
<section class="sp-sec">
    <div class="container">
        <h2 class="section-title">De ce merită brandul tău aici</h2>
        <p class="sp-lead">Trei lucruri pe care un simplu banner sau un influencer nu ți le pot da în același loc.</p>
        <div class="sp-values">
            <div class="sp-value">
                <span class="ic">👀</span>
                <h3>Audiență implicată, nu doar cifre</h3>
                <p>Newsletterul are un open rate în jur de 50% — aproape dublul mediei din industrie. Oamenii chiar deschid, citesc și dau click, nu doar „primesc".</p>
            </div>
            <div class="sp-value">
                <span class="ic">🎟️</span>
                <h3>Public care deja plătește</h3>
                <p>Participanții cumpără bilet ca să vină la curs. Ajungi la oameni cu intenție de cumpărare și buget, nu la trafic rece.</p>
            </div>
            <div class="sp-value">
                <span class="ic">🍷</span>
                <h3>Online + fizic în aceeași ediție</h3>
                <p>Brandul tău apare în inbox, pe social și la evenimentul fizic, printre oameni reali, la un pahar. Combinația asta nu se poate copia digital.</p>
            </div>
            <div class="sp-value">
                <span class="ic">✨</span>
                <h3>Context premium și pozitiv</h3>
                <p>Ești asociat cu educație și o atmosferă relaxată, nu îngropat între zece reclame agresive. Un cadru în care brandul tău arată bine.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── CIFRELE / MEDIA KIT ─────────────────── -->
<section class="sp-sec alt">
    <div class="container">
        <h2 class="section-title">Canalele și cifrele</h2>
        <p class="sp-lead">Numere reale, actualizate. Pe scurt: ai unde să fii văzut, de câte ori ai nevoie.</p>
        <div class="sp-channels">
            <div class="sp-chan">
                <div class="lbl">Newsletter</div>
                <div class="big">1.943</div>
                <div class="unit">abonați</div>
                <ul>
                    <li>~53% open rate mediu</li>
                    <li>Un email în fiecare săptămână</li>
                    <li>Slot de sponsor dedicat</li>
                </ul>
            </div>
            <div class="sp-chan">
                <div class="lbl">Instagram</div>
                <div class="big">21.2k</div>
                <div class="unit">urmăritori</div>
                <ul>
                    <li>Reels, stories, postări</li>
                    <li>Mențiune „Powered by"</li>
                    <li>Public tânăr din București</li>
                </ul>
            </div>
            <div class="sp-chan">
                <div class="lbl">TikTok</div>
                <div class="big">8.4k</div>
                <div class="unit">urmăritori · 37k aprecieri</div>
                <ul>
                    <li>Conținut din sală, la eveniment</li>
                    <li>Mențiune „Powered by"</li>
                    <li>Format video în creștere</li>
                </ul>
            </div>
            <div class="sp-chan">
                <div class="lbl">Evenimente</div>
                <div class="big">Săpt.</div>
                <div class="unit">public plătitor, live</div>
                <ul>
                    <li>Prezență fizică în București</li>
                    <li>Sampling / activare la fața locului</li>
                    <li>Contact direct cu participanții</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ── FORMATE DE PROMOVARE ────────────────── -->
<section class="sp-sec">
    <div class="container">
        <h2 class="section-title">Cum îți promovăm brandul</h2>
        <p class="sp-lead">Alegi un format sau le combini. De la o mențiune simplă până la o ediție întreagă „Powered by".</p>
        <div class="sp-formats">
            <div class="sp-fmt">
                <span class="tag">Newsletter</span>
                <h3>Slot dedicat în email</h3>
                <p>O mențiune a brandului tău către 1.900+ abonați care chiar deschid emailul săptămânal.</p>
            </div>
            <div class="sp-fmt">
                <span class="tag">Social</span>
                <h3>Instagram &amp; TikTok</h3>
                <p>Story, reel sau mențiune „Powered by [brandul tău]" în descrierea postărilor de la curs.</p>
            </div>
            <div class="sp-fmt">
                <span class="tag">Title sponsor</span>
                <h3>Curs „Powered by [brand]"</h3>
                <p>O ediție întreagă poartă numele tău: în social și în newsletter, pe toate canalele noastre.</p>
            </div>
            <div class="sp-fmt">
                <span class="tag">Co-creat</span>
                <h3>Curs în colaborare</h3>
                <p>Construim împreună un curs pe o temă legată de brandul tău — de la subiect până la eveniment.</p>
            </div>
            <div class="sp-fmt">
                <span class="tag">Activare</span>
                <h3>Sampling la eveniment</h3>
                <p>Produsul tău ajunge direct în mâna participanților, într-un context relaxat și pozitiv.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── POWERED BY DEMO ─────────────────────── -->
<section class="sp-sec alt">
    <div class="container">
        <div class="sp-powered">
            <div>
                <h2 class="section-title">„Powered by" — cel mai vizibil loc</h2>
                <p>Descrierile postărilor de pe Instagram și TikTok și emailul din săptămâna cursului pot afișa brandul tău ca sponsor oficial al ediției.</p>
                <p>E asocierea care contează: brandul tău lângă un moment în care oamenii sunt atenți, deschiși și de bună dispoziție. Nu o reclamă pe care o dau skip — parte din experiență.</p>
                <a href="#contact" class="btn btn-accent">Vreau brandul meu aici</a>
            </div>
            <div class="sp-screen">
                <span class="pw">Powered by</span>
                <span class="brand">BRANDUL TĂU</span>
                <span class="glass">🍷</span>
            </div>
        </div>
    </div>
</section>

<!-- ── PACHETE ─────────────────────────────── -->
<section class="sp-sec" id="pachete">
    <div class="container">
        <h2 class="section-title">Pachete</h2>
        <p class="sp-lead">Trei niveluri, de la o apariție punctuală până la parteneriat de sezon. Prețul final îl potrivim pe obiectivele tale.</p>
        <div class="sp-pkgs">
            <div class="sp-pkg">
                <h3>Spot</h3>
                <p class="who">O apariție punctuală, pe un singur canal.</p>
                <ul>
                    <li>Mențiune în newsletter <em>sau</em> pe social</li>
                    <li>Link cu UTM (îți vezi click-urile)</li>
                    <li>Ideal pentru un test rapid</li>
                </ul>
                <div class="price">Preț la cerere<small>ofertă în 24h</small></div>
                <a href="#contact" class="btn btn-secondary">Cere oferta</a>
            </div>
            <div class="sp-pkg feat">
                <span class="pop">Cel mai ales</span>
                <h3>Powered by ediție</h3>
                <p class="who">Sponsor oficial al unei ediții, pe toate canalele.</p>
                <ul>
                    <li>„Powered by" pe Instagram &amp; TikTok</li>
                    <li>Mențiune dedicată în newsletter</li>
                    <li>Prezență / sampling la eveniment</li>
                </ul>
                <div class="price">Preț la cerere<small>pe ediție</small></div>
                <a href="#contact" class="btn btn-accent">Cere oferta</a>
            </div>
            <div class="sp-pkg">
                <h3>Partener de sezon</h3>
                <p class="who">4 ediții, prezență constantă, preț cu discount.</p>
                <ul>
                    <li>Tot din „Powered by ediție", ×4</li>
                    <li>Un curs în colaborare cu brandul tău</li>
                    <li>Prioritate la date și subiecte</li>
                    <li>Discount pentru angajament</li>
                </ul>
                <div class="price">Preț la cerere<small>pachet cu discount</small></div>
                <a href="#contact" class="btn btn-secondary">Cere oferta</a>
            </div>
        </div>
    </div>
</section>

<!-- ── GALERIE ─────────────────────────────── -->
<section class="sp-sec alt">
    <div class="container">
        <h2 class="section-title">Așa arată un curs la pahar</h2>
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

<!-- ── FORMULAR ────────────────────────────── -->
<section class="sp-sec sp-band" id="contact">
    <div class="container container-narrow">
        <h2 class="section-title">Hai să-ți promovăm brandul</h2>
        <p class="sp-lead">Spune-ne ce brand reprezinți și ce vrei să obții. Revenim cu kitul complet și o ofertă potrivită, de obicei în 24 de ore.</p>

        <div class="inner-form">
            <form class="inner-page-form" data-form-type="sponsorizare" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="sp_brand">Brand / companie *</label>
                        <input type="text" id="sp_brand" name="partner_name" required>
                    </div>
                    <div class="form-group">
                        <label for="sp_contact">Persoana de contact *</label>
                        <input type="text" id="sp_contact" name="contact_person" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="sp_email">Email *</label>
                        <input type="email" id="sp_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="sp_phone">Număr de telefon</label>
                        <input type="tel" id="sp_phone" name="phone" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ce te interesează</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="interes[]" value="newsletter"> Slot în newsletter
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="interes[]" value="social"> Instagram &amp; TikTok
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="interes[]" value="powered-editie"> „Powered by" o ediție
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="interes[]" value="partener-sezon"> Partener de sezon (4 ediții)
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="interes[]" value="sampling"> Sampling / activare la eveniment
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="interes[]" value="altceva"> Încă nu știu, hai să discutăm
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="sp_msg">Ce vrei să obții din colaborare? *</label>
                    <textarea id="sp_msg" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-accent">Trimite cererea</button>
                <div class="form-message" aria-live="polite"></div>
            </form>
        </div>
    </div>
</section>

</div><!-- /.sp-wrap -->

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="/assets/js/main.js?v=<?php echo filemtime(__DIR__.'/assets/js/main.js'); ?>"></script>
<script>history.scrollRestoration = 'manual';</script>
</body>
</html>
