<?php
/**
 * Cursuri la Pahar – Main Page
 */

// ── Serve WebP when available ─────────────────────────────────────────────────
function img_webp(string $path): string {
    $webp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);
    $file = __DIR__ . $webp;
    return (str_starts_with($path, '/assets/') && file_exists($file)) ? $webp : $path;
}

// ── Load settings ─────────────────────────────────────────────────────────────
function clp_default_settings(): array {
    return [
        'announcement'     => '🎉 Peste 1.000 de participanți au descoperit că educația are un gust mai bun la un pahar. Tu ești următorul?',
        'hero_title'       => 'Cursuri ținute de experți<br><em>la un pahar în oraș.</em>',
        'hero_btn'         => 'Vezi următoarele cursuri',
        'courses_title'    => 'Următoarele cursuri',
        'newsletter_title' => 'Fii primul care află când au loc evenimentele Cursuri la Pahar',
        'newsletter_desc'  => 'Vei primi în exclusivitate data și tema viitoarelor evenimente Cursuri la Pahar.',
        'collab_title'     => 'Colaborare',
        'collab_subtitle'  => 'Vrei să faci parte din comunitatea Cursuri la Pahar? Hai să construim ceva frumos împreună.',
        'contact_title'    => 'Contact',
        'contact_subtitle' => 'Ai o întrebare sau o idee? Scrie-ne.',
        'hero_images'      => ['/assets/images/hero1.jpg', '/assets/images/hero2.jpg', '/assets/images/hero3.jpg', '/assets/images/hero4.jpg', '/assets/images/hero5.jpg'],
        'logo_path'        => '/assets/images/logo.webp',
        'favicon_path'     => '',
        'nav_brand_text'   => 'Cursuri la Pahar',
        'nav_links'        => [
            ['label' => 'Cursuri',            'url' => '/#cursuri'],
            ['label' => 'Cum funcționează',   'url' => '/#cum-functioneaza'],
            ['label' => 'FAQ',                'url' => '/#faq'],
            ['label' => 'Colaborare',         'url' => '/#colaborare'],
            ['label' => 'Contact',            'url' => '/#contact'],
        ],
        'steps' => [
            ['title' => 'Verifici calendarul',  'text' => 'Răsfoiești cursurile disponibile și găsești tema care te stârnește curiozitatea.'],
            ['title' => 'Cumperi biletul',       'text' => 'Achiziționezi biletul online prin LiveTickets, simplu și rapid, de pe orice dispozitiv.'],
            ['title' => 'Vii la eveniment',      'text' => 'Te prezinți la locație, îți iei o băutură preferată și ocupi un loc confortabil.'],
            ['title' => 'Înveți & socializezi',  'text' => 'Asculți expertul, pui orice întrebare la Q&A și cunoști oameni faini cu aceleași interese.'],
        ],
        'faq_items' => [
            ['q' => 'Ce este Cursuri la Pahar?',          'a' => 'Cursuri la Pahar este un eveniment care scoate educația din amfiteatre și o aduce în baruri. Experți și profesori vin să discute teme complexe într-un cadru relaxat, la un pahar cu publicul.'],
            ['q' => 'Cât durează un eveniment?',           'a' => 'Rezervăm cam 2 ore pentru întreaga experiență. Primele 60–90 de minute sunt dedicate prezentării, iar restul timpului îl petrecem la un Q&A, unde poți pune orice fel de întrebări.'],
            ['q' => 'Cât costă un bilet?',                 'a' => 'Biletul standard costă 50 de lei, iar biletul pentru studenți costă 30 de lei.'],
            ['q' => 'Despre ce sunt cursurile?',           'a' => 'Alegem teme care stârnesc curiozitatea oricui: de la psihologie și misterele istoriei, până la univers și tehnologie. Practic, încercăm să transformăm subiectele „grele" în povești numai bune de ascultat la un pahar.'],
            ['q' => 'Unde au loc evenimentele?',           'a' => 'Ne vedem în baruri, pub-uri și alte spații relaxate din București (momentan). Alegem locații unde atmosfera este caldă și unde poți savura o băutură în timp ce asculți ceva interesant.'],
            ['q' => 'Cine poate participa?',               'a' => 'Oricine este curios și are peste 16 ani. Nu ai nevoie de pregătire specială sau studii în domeniu; evenimentul este creat pentru toți cei care vor să îmbine socializarea cu o doză de cunoaștere.'],
            ['q' => 'Când va avea loc următorul eveniment?','a' => 'Dacă vrei să te anunțăm direct pe email când punem biletele la vânzare, abonează-te la newsletter-ul nostru. Pe lângă asta, poți vedea calendarul și pe pagina noastră de Instagram.'],
        ],
    ];
}
$settings_file = __DIR__ . '/data/settings.json';
$settings = clp_default_settings();
if (file_exists($settings_file)) {
    $loaded = json_decode(file_get_contents($settings_file), true) ?: [];
    $settings = array_merge($settings, $loaded);
}

// ── Inline edit helper ───────────────────────────────────────────────────────
function clp_e(string $key, array $settings): string {
    return 'data-edit-key="' . htmlspecialchars($key) . '"';
}

function clp_section_bg(string $id, array $settings, string $default_img = ''): string {
    $bg  = $settings['section_bgs'][$id] ?? [];
    $img = !empty($bg['image']) ? $bg['image'] : $default_img;
    $out = 'data-section-bg="' . htmlspecialchars($id) . '"';
    if ($img) {
        $blur    = $bg['blur']    ?? 6;
        $overlay = $bg['overlay'] ?? 0.72;
        $out .= ' style="--section-bg-img:url(\'' . htmlspecialchars($img, ENT_QUOTES) . '\');--section-blur:' . (int)$blur . 'px;--section-overlay:' . (float)$overlay . '"';
    }
    return $out;
}

// ── Load and filter courses ───────────────────────────────────────────────────
$courses = [];
$json_file = __DIR__ . '/data/courses.json';
if (file_exists($json_file)) {
    $courses = json_decode(file_get_contents($json_file), true) ?: [];
}
// Filter active, sort by date_raw ASC
$courses = array_filter($courses, fn($c) => !empty($c['active']));
usort($courses, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));

// ── Sold-out check via LiveTickets API (cached 15 min) ────────────────────────
function lt_slug_from_url(string $url): string {
    $path  = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    $parts = explode('/', $path);
    $idx   = array_search('bilete', $parts);
    return ($idx !== false && isset($parts[$idx + 1])) ? $parts[$idx + 1] : '';
}
function lt_is_sold_out(array $event): bool {
    // Primary: check items[] - all must have soldout=true
    if (!empty($event['items']) && is_array($event['items'])) {
        foreach ($event['items'] as $item) {
            if (empty($item['soldout'])) return false;
        }
        return true;
    }
    // Fallback: remaining_count at event level
    if (isset($event['remaining_count']) && $event['remaining_count'] === 0
        && isset($event['ticket_count'])) return true;
    return false;
}
$soldout_cache_file = __DIR__ . '/data/soldout_cache.json';
$soldout_cache = file_exists($soldout_cache_file)
    ? (json_decode(file_get_contents($soldout_cache_file), true) ?: []) : [];
$cache_dirty = false;
$course_soldout = [];
foreach ($courses as $course) {
    $slug = lt_slug_from_url($course['livetickets_url'] ?? '');
    if (!$slug) { $course_soldout[$course['id'] ?? ''] = false; continue; }
    $ttl = 900;
    $now = time();
    if (isset($soldout_cache[$slug]) && ($now - ($soldout_cache[$slug]['at'] ?? 0)) < $ttl) {
        $course_soldout[$course['id'] ?? ''] = $soldout_cache[$slug]['sold_out'];
        continue;
    }
    $api = 'https://api.livetickets.ro/public/events/getbyurl?url=' . urlencode($slug);
    $resp = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($api);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>5,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_HTTPHEADER=>['Accept: application/json']]);
        $resp = curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'header' => 'Accept: application/json']]);
        $resp = @file_get_contents($api, false, $ctx);
    }
    $sold = false;
    if ($resp) {
        $ev = json_decode($resp, true);
        if ($ev) $sold = lt_is_sold_out($ev);
    }
    $soldout_cache[$slug] = ['sold_out' => $sold, 'at' => $now];
    $course_soldout[$course['id'] ?? ''] = $sold;
    $cache_dirty = true;
}
if ($cache_dirty) @file_put_contents($soldout_cache_file, json_encode($soldout_cache));
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursuri la Pahar – Educație la un pahar în oraș</title>
    <meta name="description" content="Cursuri ținute de experți într-un cadru relaxat, la un pahar în oraș.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
    $font_heading = $settings['font_heading'] ?? 'Nunito';
    $font_body    = $settings['font_body']    ?? 'Inter';
    $fonts_param  = 'family=' . urlencode($font_heading) . ':ital,wght@0,400;0,600;0,700;0,800;1,400;1,700&family=' . urlencode($font_body) . ':wght@300;400;500&display=swap';
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@800&family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400&<?= $fonts_param ?>" rel="stylesheet">
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
    <?php
    // Typography overrides from global font settings
    $fhW  = $settings['fh_weight']  ?? '';
    $fhI  = !empty($settings['fh_italic']);
    $fhLg = $settings['fh_size_lg'] ?? '';
    $fhMd = $settings['fh_size_md'] ?? '';
    $fhSm = $settings['fh_size_sm'] ?? '';
    $fbW  = $settings['fb_weight']  ?? '';
    $fbLg = $settings['fb_size_lg'] ?? '';
    $fbMd = $settings['fb_size_md'] ?? '';
    $fbSm = $settings['fb_size_sm'] ?? '';
    $hSel = '.section-title,h1,h2,h3';
    if ($fhW || $fhI || $fhLg) {
        echo $hSel . '{';
        if ($fhW)  echo 'font-weight:' . (int)$fhW . '!important;';
        if ($fhI)  echo 'font-style:italic!important;';
        if ($fhLg) echo 'font-size:' . (int)$fhLg . 'px!important;';
        echo '}';
    }
    if ($fhMd) echo '@media(max-width:1024px){' . $hSel . '{font-size:' . (int)$fhMd . 'px!important;}}';
    if ($fhSm) echo '@media(max-width:768px){'  . $hSel . '{font-size:' . (int)$fhSm . 'px!important;}}';
    if ($fbW || $fbLg) {
        echo 'body,p{';
        if ($fbW)  echo 'font-weight:' . (int)$fbW . '!important;';
        if ($fbLg) echo 'font-size:' . (int)$fbLg . 'px!important;';
        echo '}';
    }
    if ($fbMd) echo '@media(max-width:1024px){body{font-size:' . (int)$fbMd . 'px!important;}}';
    if ($fbSm) echo '@media(max-width:768px){body{font-size:'  . (int)$fbSm . 'px!important;}}';
    ?>
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
            <span class="navbar-brand-text" <?= clp_e('nav_brand_text', $settings) ?>>
                <?php $nb=explode(' ', $settings['nav_brand_text'], 2); ?>
                <span><?= htmlspecialchars($nb[0]) ?></span><span><?= htmlspecialchars($nb[1] ?? '') ?></span>
            </span>
        </a>
        <div class="navbar-links">
            <?php foreach ($settings['nav_links'] as $nli => $nl): ?>
            <a href="<?= htmlspecialchars($nl['url']) ?>" <?= clp_e('nav_link_' . $nli . '_label', $settings) ?>><?= htmlspecialchars($nl['label']) ?></a>
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

<!-- ── HERO ────────────────────────────────── -->
<section class="hero" id="hero">
    <div class="hero-slides">
        <?php foreach ($settings['hero_images'] as $idx => $hero_img): ?>
        <div class="hero-slide<?= $idx === 0 ? ' active' : '' ?>"
             <?= $idx === 0 ? "style=\"background-image:url('" . htmlspecialchars(img_webp($hero_img)) . "')\"" : "data-bg=\"" . htmlspecialchars(img_webp($hero_img)) . "\"" ?>></div>
        <?php endforeach; ?>
    </div>
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <h1 class="hero-title" <?= clp_e('hero_title',$settings) ?>><?= $settings['hero_title'] ?></h1>
    </div>

    <div class="hero-scroll-hint" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
    </div>
</section>

<!-- ── ANNOUNCEMENT BANNER ────────────────── -->
<div class="announcement-banner" <?= clp_e('announcement',$settings) ?>>
    <?= htmlspecialchars($settings['announcement']) ?>
</div>

<!-- ── CURSURI ─────────────────────────────── -->
<?php $cursuri_bg = $settings['section_bgs']['cursuri'] ?? []; $cursuri_has_bg = !empty($cursuri_bg['image']); ?>
<section class="section<?= $cursuri_has_bg ? ' section-bg-blur section-dark' : '' ?>" id="cursuri" <?= clp_section_bg('cursuri', $settings) ?>>
    <div class="container">
        <h2 class="section-title" <?= clp_e('courses_title',$settings) ?>><?= htmlspecialchars($settings['courses_title']) ?></h2>

        <?php if (empty($courses)): ?>
        <p class="no-events">Nu există cursuri programate momentan.<br>
        Abonează-te la newsletter să fii primul care află!</p>
        <?php else: ?>
        <div class="events-grid">
            <?php foreach ($courses as $course):
                $date_raw = $course['date_raw'] ?? '';
                $badge_day = $date_raw ? date('d', strtotime($date_raw)) : '';
                $badge_month = $date_raw ? strtoupper(date('M', strtotime($date_raw))) : '';
                $card_url = $course['livetickets_url'] ?? '#';
                $target = ($course['livetickets_url'] ?? '') ? '_blank' : '_self';
                $is_sold_out = $course_soldout[$course['id'] ?? ''] ?? false;
            ?>
            <a href="<?= htmlspecialchars($card_url) ?>" target="<?= $target ?>" rel="noopener" class="event-card<?= $is_sold_out ? ' event-card--soldout' : '' ?>">
                <div class="event-card-img">
                    <?php if (!empty($course['image_url'])): ?>
                    <img src="<?= htmlspecialchars($course['image_url']) ?>" alt="<?= htmlspecialchars($course['title'] ?? '') ?>" loading="lazy">
                    <?php else: ?>
                    <div class="event-card-img-placeholder"></div>
                    <?php endif; ?>
                    <?php if ($is_sold_out): ?>
                    <div class="sold-out-badge">Sold Out</div>
                    <?php endif; ?>
                    <?php if ($badge_day): ?>
                    <div class="event-card-date-badge">
                        <span class="badge-day"><?= $badge_day ?></span>
                        <span class="badge-month"><?= $badge_month ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="event-card-body">
                    <h3 class="event-card-title"><?= htmlspecialchars($course['title'] ?? '') ?></h3>
                    <div class="event-card-meta">
                        <?php if (!empty($course['time'])): ?>
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <?= htmlspecialchars($course['time']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($course['location'])): ?>
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= htmlspecialchars($course['location']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── NEWSLETTER ─────────────────────────── -->
<?php
$nl_bg_data = $settings['section_bgs']['newsletter'] ?? [];
$nl_img = !empty($nl_bg_data['image']) ? $nl_bg_data['image'] : img_webp($settings['hero_images'][0] ?? '/assets/images/hero1.jpg');
?>
<section class="section section-dark section-bg-blur" id="newsletter" <?= clp_section_bg('newsletter', $settings, $nl_img) ?>>
    <div class="container container-narrow">
        <div class="newsletter-icon" aria-hidden="true">✉</div>
        <h2 class="section-title" <?= clp_e('newsletter_title',$settings) ?>><?= htmlspecialchars($settings['newsletter_title']) ?></h2>
        <p class="newsletter-desc" <?= clp_e('newsletter_desc',$settings) ?>><?= htmlspecialchars($settings['newsletter_desc']) ?></p>
        <form class="newsletter-form" id="newsletterForm" novalidate>
            <div class="newsletter-fields">
                <input type="email" name="email" id="nlEmail" required autocomplete="email">
                <button type="submit" class="btn btn-accent">Anunță-mă</button>
            </div>
            <p class="newsletter-note">100% gratuit. Te poți dezabona oricând.</p>
            <div class="form-message" id="nlMessage" aria-live="polite"></div>
        </form>
    </div>
</section>

<!-- ── CUM FUNCȚIONEAZĂ ────────────────────── -->
<?php $step_icons = [
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/></svg>',
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
]; ?>
<?php $cum_bg = $settings['section_bgs']['cum-functioneaza'] ?? []; $cum_has_bg = !empty($cum_bg['image']); ?>
<section class="section<?= $cum_has_bg ? ' section-bg-blur section-dark' : '' ?>" id="cum-functioneaza" <?= clp_section_bg('cum-functioneaza', $settings) ?>>
    <div class="container">
        <h2 class="section-title" <?= clp_e('steps_title', $settings) ?>><?= htmlspecialchars($settings['steps_title'] ?? 'Cum funcționează') ?></h2>
        <div class="steps-grid">
            <?php foreach ($settings['steps'] as $i => $step): ?>
            <div class="step">
                <div class="step-number"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
                <div class="step-icon"><?= $step_icons[$i] ?? $step_icons[0] ?></div>
                <h3 <?= clp_e('step_'.$i.'_title', $settings) ?>><?= htmlspecialchars($step['title']) ?></h3>
                <p <?= clp_e('step_'.$i.'_text', $settings) ?>><?= htmlspecialchars($step['text']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── FAQ ────────────────────────────────── -->
<?php
$faq_bg_data = $settings['section_bgs']['faq'] ?? [];
$faq_img = !empty($faq_bg_data['image']) ? $faq_bg_data['image'] : img_webp($settings['hero_images'][1] ?? $settings['hero_images'][0] ?? '/assets/images/hero2.jpg');
?>
<section class="section section-dark section-bg-blur" id="faq" <?= clp_section_bg('faq', $settings, $faq_img) ?>>
    <div class="container container-narrow">
        <h2 class="section-title" <?= clp_e('faq_title', $settings) ?>><?= htmlspecialchars($settings['faq_title'] ?? 'Întrebări frecvente') ?></h2>
        <div class="faq-list">
            <?php foreach ($settings['faq_items'] as $fi => $faq): ?>
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <span <?= clp_e('faq_'.$fi.'_q', $settings) ?>><?= htmlspecialchars($faq['q']) ?></span>
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p <?= clp_e('faq_'.$fi.'_a', $settings) ?>><?= htmlspecialchars($faq['a']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── GALERIE ────────────────────────────── -->
<?php
$gallery_images = $settings['gallery_featured'] ?? [];
$gal_bg = $settings['section_bgs']['galerie'] ?? []; $gal_has_bg = !empty($gal_bg['image']);
?>
<?php if (!empty($gallery_images)): ?>
<section class="section<?= $gal_has_bg ? ' section-bg-blur section-dark' : '' ?>" id="galerie" <?= clp_section_bg('galerie', $settings) ?>>
    <div class="container">
        <h2 class="section-title"><?= htmlspecialchars($settings['gallery_title'] ?? 'Galerie') ?></h2>
        <div class="gallery-slider-wrap">
            <button class="gslider-btn gslider-prev" aria-label="Anterior">&#8249;</button>
            <div class="gallery-slider">
                <?php foreach ($gallery_images as $gi => $gimg): ?>
                <div class="gallery-item" data-index="<?= $gi ?>">
                    <img src="<?= htmlspecialchars($gimg) ?>" alt="Cursuri la Pahar" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
            <button class="gslider-btn gslider-next" aria-label="Următor">&#8250;</button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Lightbox -->
<div class="gallery-lightbox" id="galleryLightbox">
    <button class="lightbox-close" aria-label="Închide">&times;</button>
    <button class="lightbox-prev" aria-label="Anteriorul">&#8249;</button>
    <button class="lightbox-next" aria-label="Următorul">&#8250;</button>
    <div class="lightbox-img-wrap">
        <img src="" alt="" id="lightboxImg">
    </div>
</div>

<!-- ── COLABORARE ─────────────────────────── -->
<?php $col_bg = $settings['section_bgs']['colaborare'] ?? []; $col_has_bg = !empty($col_bg['image']); ?>
<section class="section<?= $col_has_bg ? ' section-bg-blur section-dark' : '' ?>" id="colaborare" <?= clp_section_bg('colaborare', $settings) ?>>
    <div class="container">
        <h2 class="section-title" <?= clp_e('collab_title',$settings) ?>><?= htmlspecialchars($settings['collab_title']) ?></h2>
        <p class="section-subtitle" <?= clp_e('collab_subtitle',$settings) ?>><?= htmlspecialchars($settings['collab_subtitle']) ?></p>
        <div class="collab-grid">
            <a href="/sustine-un-curs" class="collab-card">
                <div class="collab-card-img">
                    <img src="/assets/images/uploads/sustine.webp" alt="Susține un curs" loading="lazy">
                </div>
                <h3>Susține un curs</h3>
                <p>Ai expertiză într-un domeniu care te pasionează? Vino să susții un curs în fața comunității noastre.</p>
                <span class="collab-link">Află mai multe →</span>
            </a>
            <a href="/gazduieste-un-curs" class="collab-card">
                <div class="collab-card-img">
                    <img src="/assets/images/uploads/gazduieste.webp" alt="Găzduiește un curs" loading="lazy">
                </div>
                <h3>Găzduiește un curs</h3>
                <p>Ai o locație cu vibe fain? Transformă-o în spațiul unde se nasc conexiunile și ideile noi.</p>
                <span class="collab-link">Află mai multe →</span>
            </a>
            <a href="/propune-un-parteneriat" class="collab-card">
                <div class="collab-card-img">
                    <img src="/assets/images/uploads/parteneriat.webp" alt="Propune un parteneriat" loading="lazy">
                </div>
                <h3>Propune un parteneriat</h3>
                <p>Reprezinți un brand sau o platformă media? Hai să explorăm ce putem construi împreună.</p>
                <span class="collab-link">Află mai multe →</span>
            </a>
        </div>
    </div>
</section>

<!-- ── CONTACT ────────────────────────────── -->
<?php $contact_bg = $settings['section_bgs']['contact'] ?? []; $contact_has_bg = !empty($contact_bg['image']); ?>
<section class="section section-dark<?= $contact_has_bg ? ' section-bg-blur' : '' ?>" id="contact" <?= clp_section_bg('contact', $settings) ?>>
    <div class="container container-narrow">
        <h2 class="section-title" <?= clp_e('contact_title',$settings) ?>><?= htmlspecialchars($settings['contact_title']) ?></h2>
        <p class="section-subtitle" <?= clp_e('contact_subtitle',$settings) ?>><?= htmlspecialchars($settings['contact_subtitle']) ?></p>
        <form class="contact-form" id="contactForm" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="contactName">Nume</label>
                    <input type="text" id="contactName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="contactEmail">Email</label>
                    <input type="email" id="contactEmail" name="email" required>
                </div>
            </div>
            <div class="form-group">
                <label for="contactMsg">Mesaj</label>
                <textarea id="contactMsg" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-accent">Trimite mesajul</button>
            <div class="form-message" id="contactMessage" aria-live="polite"></div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="/assets/js/main.js?v=<?php echo filemtime(__DIR__.'/assets/js/main.js'); ?>"></script>
</body>
</html>
 
<!-- deploy-fix 1775926122 -->
