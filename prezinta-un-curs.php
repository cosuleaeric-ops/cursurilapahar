<?php
/**
 * Cursuri la Pahar – Prezintă un curs
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
    return 'data-edit-key="' . htmlspecialchars($key) . '"';
}

$sustine_title   = $settings['sustine_title']   ?? 'Prezintă un curs';
$sustine_intro_1 = $settings['sustine_intro_1'] ?? 'Căutăm voci noi pentru <strong>Cursuri la Pahar</strong>! Dacă ai experiență într-un domeniu care te pasionează și vrei să dai mai departe din învățăturile tale, te așteptăm să susții un curs în cadrul evenimentelor noastre.';
$sustine_intro_2 = $settings['sustine_intro_2'] ?? 'Punem preț pe calitatea informației și pe vibe-ul bun, așa că, dacă ești gata să inspiri comunitatea cu învățăturile tale, <strong>completează formularul de mai jos</strong>!';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prezintă un curs – Cursuri la Pahar</title>
    <meta name="description" content="Prezintă un curs la Cursuri la Pahar. Vino să împărtășești expertiza ta cu comunitatea noastră.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
    $font_heading = $settings['font_heading'] ?? 'Nunito';
    $font_body    = $settings['font_body']    ?? 'Inter';
    $fonts_param  = 'family=' . urlencode($font_heading) . ':ital,wght@0,400;0,600;0,700;0,800;1,400;1,700&family=' . urlencode($font_body) . ':wght@300;400;500&display=swap';
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@800&<?= $fonts_param ?>" rel="stylesheet">
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
        --font-sans:    '<?= htmlspecialchars($font_body) ?>', system-ui, sans-serif;
        --font-heading: '<?= htmlspecialchars($font_heading) ?>', sans-serif;
        --btn-hover:    <?= htmlspecialchars($settings['color_btn_hover'] ?? '#b8922e') ?>;
        --banner-bg:    <?= htmlspecialchars($settings['color_banner']    ?? '#FFB000') ?>;
        --nav-bg:           <?= htmlspecialchars($settings['nav_bg']           ?? '#000000') ?>;
        --nav-brand-color:  <?= htmlspecialchars($settings['nav_brand_color']  ?? '#ffffff') ?>;
        --nav-brand-size:   <?= htmlspecialchars($settings['nav_brand_size']   ?? '20') ?>px;
        --nav-brand-weight: <?= htmlspecialchars($settings['nav_brand_weight'] ?? '800') ?>;
        --nav-brand-font:   '<?= htmlspecialchars($settings['nav_brand_font']  ?? 'Poppins') ?>', sans-serif;
        --nav-link-color:   <?= htmlspecialchars($settings['nav_link_color']   ?? '#ffffff') ?>;
        --nav-link-size:    <?= htmlspecialchars($settings['nav_link_size']    ?? '13') ?>px;
        --nav-link-weight:  <?= htmlspecialchars($settings['nav_link_weight']  ?? '700') ?>;
        --nav-logo-h:       <?= htmlspecialchars($settings['nav_logo_h']       ?? '40') ?>px;
    }
    body { padding-top: 88px; }
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
    <div class="container container-narrow">
        <a href="/" onclick="if(history.length>1){history.back();return false}" class="page-hero-back" style="margin-bottom:16px;display:inline-flex;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Înapoi
        </a>
        <h1 <?= clp_e('sustine_title', $settings) ?>><?= htmlspecialchars($sustine_title) ?></h1>
        <div style="color:var(--text-muted);line-height:1.8;margin-bottom:32px;">
            <p <?= clp_e('sustine_intro_1', $settings) ?>><?= $sustine_intro_1 ?></p>
            <p <?= clp_e('sustine_intro_2', $settings) ?> style="margin-top:16px;"><?= $sustine_intro_2 ?></p>
        </div>

        <div class="inner-form">
            <form class="inner-page-form" data-form-type="sustine" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="suc_name">Nume și prenume *</label>
                        <input type="text" id="suc_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="suc_email">Email *</label>
                        <input type="email" id="suc_email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="suc_phone">Număr de telefon</label>
                        <input type="tel" id="suc_phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="suc_social">Link profil social media</label>
                        <input type="url" id="suc_social" name="social" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="suc_course_name">Nume curs susținut *</label>
                    <input type="text" id="suc_course_name" name="course_name" required>
                </div>
                <div class="form-group">
                    <label for="suc_desc">Descrie cursul susținut *</label>
                    <textarea id="suc_desc" name="course_desc" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="suc_why">De ce îți dorești să susții acest curs? *</label>
                    <textarea id="suc_why" name="motivation" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="suc_experience">Ce experiențe sau competențe te califică?</label>
                    <textarea id="suc_experience" name="experience" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Ai mai susținut astfel de prezentări?</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="previous_presentations" value="yes_often" required> Da, o fac deseori.
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="previous_presentations" value="yes_few" required> Da, de puține ori.
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="previous_presentations" value="no" required> Nu, dar vreau să încerc.
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="suc_city">În ce oraș ai vrea să susții cursul?</label>
                    <input type="text" id="suc_city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="suc_other">Mai e ceva ce vrei să ne transmiți?</label>
                    <textarea id="suc_other" name="other" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-accent">Trimite</button>
                <div class="form-message" aria-live="polite"></div>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="/assets/js/main.js?v=<?php echo filemtime(__DIR__.'/assets/js/main.js'); ?>"></script>
<script>history.scrollRestoration = 'manual';</script>
</body>
</html>
 
