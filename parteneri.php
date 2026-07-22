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
    <meta property="og:description" content="Peste 200.000 de vizualizări pe lună, newsletter cu open rate de peste 50% și cursuri săptămânale cu săli pline. Vezi cifrele și scrie-ne.">
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

    /* ── Parteneri — layout TLDR, stil Morning Brew ─────────
       Paletă: alb, text #231F20, pastele #BDE1FF / #F1E8D5 /
       #FFCFFA / #BCF46E, colțuri 16-20px, umbre moi */
    .sp-wrap {
        /* re-scopez variabilele site-ului pe tema deschisă,
           ca galeria să se adapteze singură */
        --text: #231F20;
        --text-muted: #55504c;
        --text-faint: rgba(0,0,0,.3);
        --surface: #FAF9F5;
        --accent: #231F20;
        background: #fff; color: #231F20; overflow-x: clip;
    }
    .sp-wrap .container { max-width: 1140px; }
    .sp-wrap .section-title { color: #231F20; letter-spacing: -.02em; }
    .sp-lead { color: #6f6a66; text-align: center; max-width: 60ch; margin: 0 auto 42px; line-height: 1.65; }

    /* Hero: text stânga + formular dreapta */
    .sp-hero { padding: 72px 0; }
    .sp-hero-grid {
        display: grid; grid-template-columns: 1.05fr .95fr;
        gap: 56px; align-items: start;
    }
    .sp-hero h1 {
        font-family: var(--font-serif);
        font-size: clamp(2.3rem, 4.6vw, 3.8rem);
        line-height: 1.12; letter-spacing: -.02em;
        margin: 0 0 22px; color: #231F20;
    }
    .sp-hero h1 span {
        background: #FFE86B; border-radius: 10px;
        padding: .04em .18em; box-decoration-break: clone; -webkit-box-decoration-break: clone;
    }
    .sp-hero-sub { color: #55504c; font-size: 1.05rem; line-height: 1.7; margin: 0 0 18px; }
    .sp-hero-sub strong { color: #231F20; }

    /* Card formular — off-white, rotunjit, umbră moale */
    .sp-form-card {
        background: #FAF9F5;
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 20px;
        box-shadow: 0 15px 27px -4px rgba(0,0,0,.09), 0 5px 9px -3px rgba(0,0,0,.05);
        padding: 32px 30px;
    }
    .sp-form-card label {
        display: block; font-weight: 700; font-size: .92rem;
        margin: 0 0 6px; color: #231F20;
    }
    .sp-form-card label em { color: #d0454c; font-style: normal; }
    .sp-form-card input,
    .sp-form-card textarea {
        width: 100%; background: #fff; color: #231F20;
        border: 1px solid rgba(0,0,0,.16); border-radius: 10px;
        padding: 12px 13px; font-size: .95rem; margin-bottom: 18px;
    }
    .sp-form-card textarea { resize: vertical; }
    .sp-form-card input:focus,
    .sp-form-card textarea:focus { outline: 2px solid #231F20; outline-offset: 1px; }
    .sp-btn {
        display: inline-block; background: #231F20; color: #fff !important;
        font-weight: 700; font-size: 1rem; border-radius: 999px;
        padding: 13px 30px; border: none; cursor: pointer; text-decoration: none;
        transition: transform .15s, background .15s, box-shadow .15s;
    }
    .sp-btn:hover { transform: translateY(-2px); background: #000; box-shadow: 0 10px 18px -8px rgba(0,0,0,.4); }

    /* Secțiuni */
    .sp-sec { padding: 64px 0; }
    .sp-sec .section-title { margin-bottom: 10px; }

    /* Carduri de canale — blocuri pastel rotunjite */
    .sp-aud { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .sp-aud-card {
        border-radius: 18px; padding: 26px 24px; color: #231F20;
        transition: transform .15s, box-shadow .15s;
    }
    .sp-aud-card:nth-child(1) { background: #BDE1FF; }
    .sp-aud-card:nth-child(2) { background: #F1E8D5; }
    .sp-aud-card:nth-child(3) { background: #FFCFFA; }
    .sp-aud-card:nth-child(4) { background: #BCF46E; }
    .sp-aud-card:hover { transform: translateY(-4px); box-shadow: 0 15px 27px -6px rgba(0,0,0,.15); }
    .sp-aud-card h3 { margin: 0 0 8px; font-size: 1.12rem; color: #231F20; }
    .sp-aud-card p { margin: 0 0 14px; color: #3f3a37; font-size: .92rem; line-height: 1.55; min-height: 4.2em; }
    .sp-aud-card .stat { color: #231F20; font-weight: 800; font-size: .95rem; }

    /* Galerie */
    .sp-wrap .gallery-item img { border-radius: 14px; }
    .sp-wrap .gslider-btn { background: #FAF9F5; border: 1px solid rgba(0,0,0,.12); color: #231F20; }
    .sp-wrap .gslider-btn:hover { background: #F1E8D5; }

    /* CTA final */
    .sp-cta { text-align: center; padding: 72px 0 84px; background: #FAF9F5; }
    .sp-cta h2 {
        font-family: var(--font-serif); letter-spacing: -.02em;
        font-size: clamp(1.8rem, 4vw, 2.7rem); margin: 0 0 14px; color: #231F20;
    }
    .sp-cta p { color: #6f6a66; margin: 0 0 28px; }

    @media (max-width: 920px) {
        .sp-hero-grid { grid-template-columns: 1fr; }
        .sp-aud { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 620px) {
        .sp-aud { grid-template-columns: 1fr; }
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
                <h1>Căutăm parteneri care <span>susțin educația</span></h1>
                <p class="sp-hero-sub">
                    Organizăm cursuri în fiecare săptămână, în baruri din București. Avem peste
                    <strong>200.000 de vizualizări pe lună</strong> pe Instagram și TikTok,
                    un newsletter citit de aproape 2.000 de oameni și săli pline la fiecare eveniment.
                </p>
                <p class="sp-hero-sub">
                    Dacă vrei ca brandul tău să ajungă la oamenii ăștia, scrie-ne. Stabilim un call și
                    găsim împreună cea mai bună formă de colaborare.
                </p>
            </div>
            <div class="sp-form-card">
                <form class="inner-page-form" data-form-type="sponsorizare" novalidate>
                    <label for="sp_brand">Brand / companie <em>*</em></label>
                    <input type="text" id="sp_brand" name="partner_name" required>

                    <label for="sp_email">Email <em>*</em></label>
                    <input type="email" id="sp_email" name="email" required>

                    <label for="sp_msg">Spune-ne pe scurt ce ai în minte</label>
                    <textarea id="sp_msg" name="message" rows="4"></textarea>

                    <button type="submit" class="sp-btn">Hai să vorbim</button>
                    <div class="form-message" aria-live="polite"></div>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- ── CANALE ──────────────────────────────── -->
<section class="sp-sec">
    <div class="container">
        <h2 class="section-title">Patru canale, aceeași comunitate</h2>
        <p class="sp-lead">Cifre reale, pe care le actualizăm constant.</p>
        <div class="sp-aud">
            <div class="sp-aud-card">
                <h3>Instagram</h3>
                <p>Reels și stories de la fiecare curs, pentru un public tânăr din București.</p>
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
                <div class="stat">50-70 participanți · în fiecare săptămână</div>
            </div>
        </div>
    </div>
</section>

<!-- ── GALERIE ─────────────────────────────── -->
<section class="sp-sec">
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

<!-- ── CTA FINAL ───────────────────────────── -->
<section class="sp-cta">
    <div class="container">
        <h2>Vrei brandul tău la un pahar?</h2>
        <p>Scrie-ne și stabilim un call ca să vedem cum colaborăm.</p>
        <a href="#oferta" class="sp-btn">Hai să vorbim</a>
    </div>
</section>

</div><!-- /.sp-wrap -->

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="/assets/js/main.js?v=<?php echo filemtime(__DIR__.'/assets/js/main.js'); ?>"></script>
<script>history.scrollRestoration = 'manual';</script>
</body>
</html>
