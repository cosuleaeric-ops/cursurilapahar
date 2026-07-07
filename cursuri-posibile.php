<?php
/**
 * Cursuri la Pahar – Cursuri posibile (idei de teme pentru speakeri)
 */

require_once __DIR__ . '/lib/course_ideas.php';
$ideas = clp_load_course_ideas();

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
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursuri posibile – Cursuri la Pahar</title>
    <meta name="description" content="Idei de teme pentru un curs la pahar: știință, istorie, psihologie, film, muzică și multe altele. Caută inspirație și prezintă un curs.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Cursuri la Pahar">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:title" content="Cursuri posibile – Cursuri la Pahar">
    <meta property="og:description" content="Idei de teme pentru un curs la pahar: știință, istorie, psihologie, film, muzică și multe altele. Caută inspirație și prezintă un curs.">
    <meta property="og:url" content="https://cursurilapahar.ro/cursuri-posibile">
    <meta property="og:image" content="https://cursurilapahar.ro/assets/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Cursuri la Pahar – curs ținut într-un bar plin din București">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Cursuri posibile – Cursuri la Pahar">
    <meta name="twitter:description" content="Idei de teme pentru un curs la pahar: știință, istorie, psihologie, film, muzică și multe altele.">
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
    .ideas-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 36px 64px;
        margin-top: 40px;
    }
    @media (min-width: 768px) {
        .ideas-grid { grid-template-columns: 1fr 1fr; }
    }
    .idea-card h2 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.2rem;
        margin: 0 0 14px;
        color: var(--text);
    }
    .idea-card ul {
        list-style: disc;
        margin: 0;
        padding-left: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        color: var(--text-muted);
        line-height: 1.55;
    }
    .idea-card li::marker {
        color: var(--accent);
    }
    .ideas-cta {
        margin-top: 48px;
        text-align: center;
    }
    .ideas-cta p {
        color: var(--text-muted);
        margin-bottom: 20px;
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

<section class="page-content-section">
    <div class="container">
        <a href="/" onclick="if(history.length>1){history.back();return false}" class="page-hero-back" style="margin-bottom:16px;display:inline-flex;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Înapoi
        </a>
        <h1>Cursuri posibile</h1>
        <div style="color:var(--text-muted);line-height:1.8;">
            <p><?= nl2br(htmlspecialchars($ideas['intro'] ?? '')) ?></p>
        </div>

        <div class="ideas-grid">
            <?php foreach ($ideas['categories'] as $cat): ?>
            <div class="idea-card">
                <h2><?php if (!empty($cat['emoji'])): ?><span><?= htmlspecialchars($cat['emoji']) ?></span><?php endif; ?><?= htmlspecialchars($cat['title'] ?? '') ?></h2>
                <ul>
                    <?php foreach ($cat['topics'] ?? [] as $topic): ?>
                    <li><?= htmlspecialchars($topic) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="ideas-cta">
            <p>Ai o temă în spiritul lor — sau una la care nu ne-am gândit?</p>
            <a href="/prezinta-un-curs" class="btn btn-accent">Prezintă un curs</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="/assets/js/main.js?v=<?php echo filemtime(__DIR__.'/assets/js/main.js'); ?>"></script>
<script>history.scrollRestoration = 'manual';</script>
</body>
</html>
