<?php
/**
 * Cursuri la Pahar – Găzduiește un curs
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

$gazduieste_title   = $settings['gazduieste_title']   ?? 'Găzduiește un curs';
$gazduieste_intro_1 = $settings['gazduieste_intro_1'] ?? 'Ai o locație cu vibe fain și vrei să o transformi într-un loc de întâlnire al participanților Cursuri la Pahar? Well, noi căutăm parteneri care să devină „acasă" pentru evenimentele noastre!';
$gazduieste_intro_2 = $settings['gazduieste_intro_2'] ?? 'Ai un <strong>bar, un pub, o cafenea</strong> sau un spațiu neconvențional care debordează de personalitate? Ne-ar plăcea să aducem conceptul <strong>Cursuri la Pahar</strong> la tine. Punem preț pe locurile care inspiră creativitate și care oferă cadrul perfect pentru networking și învățare relaxată.';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Găzduiește un curs – Cursuri la Pahar</title>
    <meta name="description" content="Găzduiește un curs la Cursuri la Pahar. Transformă-ți locația într-un spațiu de educație și socializare.">
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
        <h1 <?= clp_e('gazduieste_title', $settings) ?>><?= htmlspecialchars($gazduieste_title) ?></h1>
        <div style="color:var(--text-muted);line-height:1.8;margin-bottom:28px;">
            <p <?= clp_e('gazduieste_intro_1', $settings) ?>><?= $gazduieste_intro_1 ?></p>
            <p <?= clp_e('gazduieste_intro_2', $settings) ?> style="margin-top:16px;"><?= $gazduieste_intro_2 ?></p>
            <p style="margin-top:16px;"><strong>De ce să devii locație parteneră?</strong></p>
            <ul style="margin-top:8px;padding-left:20px;">
                <li><strong>Vizibilitate:</strong> Atragi un public nou, dornic de experiențe de calitate.</li>
                <li><strong>Comunitate:</strong> Spațiul tău devine un punct de reper pentru educație și socializare.</li>
                <li><strong>Vibe:</strong> Îți umpli locația cu energie pozitivă și oameni pasionați.</li>
            </ul>
            <p style="margin-top:16px;">Dacă crezi că spațiul tău se potrivește cu conceptul nostru și vrei să fim parteneri în organizarea următoarelor ediții, abia așteptăm să te cunoaștem! <strong>Completează formularul de mai jos și hai să punem ceva frumos la cale!</strong></p>
        </div>

        <div class="inner-form">
            <form class="inner-page-form" data-form-type="gazduieste" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_name">Nume și prenume *</label>
                        <input type="text" id="guc_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="guc_email">Email *</label>
                        <input type="email" id="guc_email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_phone">Număr de telefon</label>
                        <input type="tel" id="guc_phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="guc_venue">Cum se numește localul? *</label>
                        <input type="text" id="guc_venue" name="venue_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_city">În ce oraș? *</label>
                        <input type="text" id="guc_city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="guc_capacity">Capacitate (seated)</label>
                        <input type="text" id="guc_capacity" name="capacity" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ce facilități deține locația?</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="facilities[]" value="audio"> Sistem audio cu microfon
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="facilities[]" value="projector"> Videoproiector
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="facilities[]" value="screen"> Ecran de proiecție
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="facilities[]" value="tv"> Televizor pentru proiecție
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="guc_other">Mai e ceva ce vrei să ne transmiți?</label>
                    <textarea id="guc_other" name="other" rows="3"></textarea>
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
 
