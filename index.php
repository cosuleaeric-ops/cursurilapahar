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
        'newsletter_desc'  => 'Vei primi în exclusivitate data și tema viitoarelor evenimente Cursuri la Pahar, cu 2 săptămâni înainte ca acestea să aibă loc.',
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

// ── Load and filter courses ───────────────────────────────────────────────────
$courses = [];
$json_file = __DIR__ . '/data/courses.json';
if (file_exists($json_file)) {
    $courses = json_decode(file_get_contents($json_file), true) ?: [];
}
// Filter active, sort by date_raw ASC
$courses = array_filter($courses, fn($c) => !empty($c['active']));
usort($courses, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
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
    <link href="https://fonts.googleapis.com/css2?<?= $fonts_param ?>&family=Anton&display=swap" rel="stylesheet">
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
    }
    body { padding-top: 64px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/admin/bar.php'; ?>

<!-- ── NAVBAR ─────────────────────────────── -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/#hero" class="navbar-logo">
            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="<?= htmlspecialchars($settings['nav_brand_text']) ?>">
            <span class="navbar-brand-text"><?= htmlspecialchars($settings['nav_brand_text']) ?></span>
        </a>
        <div class="navbar-links">
            <?php foreach ($settings['nav_links'] as $nl): ?>
            <a href="<?= htmlspecialchars($nl['url']) ?>"><?= htmlspecialchars($nl['label']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="navbar-right">
            <div class="navbar-social">
                <a href="https://www.instagram.com/cursurilapahar" target="_blank" rel="noopener" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
                <a href="https://www.tiktok.com/@cursurilapahar" target="_blank" rel="noopener" aria-label="TikTok">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V9.13a8.19 8.19 0 004.79 1.53V7.19a4.85 4.85 0 01-1.02-.5z"/></svg>
                </a>
                <a href="https://www.facebook.com/profile.php?id=61585669450450" target="_blank" rel="noopener" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
            </div>
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
        <h1 class="hero-title"><?= $settings['hero_title'] ?></h1>
    </div>

    <div class="hero-scroll-hint" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
    </div>
</section>

<!-- ── ANNOUNCEMENT BANNER ────────────────── -->
<div class="announcement-banner">
    <?= htmlspecialchars($settings['announcement']) ?>
</div>

<!-- ── CURSURI ─────────────────────────────── -->
<section class="section" id="cursuri">
    <div class="container">
        <h2 class="section-title"><?= htmlspecialchars($settings['courses_title']) ?></h2>

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
            ?>
            <a href="<?= htmlspecialchars($card_url) ?>" target="<?= $target ?>" rel="noopener" class="event-card">
                <div class="event-card-img">
                    <?php if (!empty($course['image_url'])): ?>
                    <img src="<?= htmlspecialchars($course['image_url']) ?>" alt="<?= htmlspecialchars($course['title'] ?? '') ?>" loading="lazy">
                    <?php else: ?>
                    <div class="event-card-img-placeholder"></div>
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
                    <span class="btn btn-secondary">Cumpără bilet →</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── NEWSLETTER ─────────────────────────── -->
<?php $nl_bg = img_webp($settings['hero_images'][0] ?? '/assets/images/hero1.jpg'); ?>
<section class="section section-dark section-bg-blur" id="newsletter" style="--section-bg-img:url('<?= htmlspecialchars($nl_bg) ?>')">
    <div class="container container-narrow">
        <div class="newsletter-icon" aria-hidden="true">✉</div>
        <h2 class="section-title"><?= htmlspecialchars($settings['newsletter_title']) ?></h2>
        <p class="newsletter-desc"><?= htmlspecialchars($settings['newsletter_desc']) ?></p>
        <form class="newsletter-form" id="newsletterForm" novalidate>
            <div class="newsletter-fields">
                <input type="email" name="email" id="nlEmail" placeholder="Adresa ta de email" required autocomplete="email">
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
<section class="section" id="cum-functioneaza">
    <div class="container">
        <h2 class="section-title">Cum funcționează</h2>
        <div class="steps-grid">
            <?php foreach ($settings['steps'] as $i => $step): ?>
            <div class="step">
                <div class="step-number"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
                <div class="step-icon"><?= $step_icons[$i] ?? $step_icons[0] ?></div>
                <h3><?= htmlspecialchars($step['title']) ?></h3>
                <p><?= htmlspecialchars($step['text']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── FAQ ────────────────────────────────── -->
<?php $faq_bg = img_webp($settings['hero_images'][1] ?? $settings['hero_images'][0] ?? '/assets/images/hero2.jpg'); ?>
<section class="section section-dark section-bg-blur" id="faq" style="--section-bg-img:url('<?= htmlspecialchars($faq_bg) ?>')">
    <div class="container container-narrow">
        <h2 class="section-title">Întrebări frecvente</h2>
        <div class="faq-list">
            <?php foreach ($settings['faq_items'] as $faq): ?>
            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    <?= htmlspecialchars($faq['q']) ?>
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p><?= nl2br(htmlspecialchars($faq['a'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── COLABORARE ─────────────────────────── -->
<section class="section" id="colaborare">
    <div class="container">
        <h2 class="section-title"><?= htmlspecialchars($settings['collab_title']) ?></h2>
        <p class="section-subtitle"><?= htmlspecialchars($settings['collab_subtitle']) ?></p>
        <div class="collab-grid">
            <a href="/sustine-un-curs" class="collab-card">
                <div class="collab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <h3>Susține un curs</h3>
                <p>Ai expertiză într-un domeniu care te pasionează? Vino să susții un curs în fața comunității noastre.</p>
                <span class="collab-link">Află mai mult →</span>
            </a>
            <a href="/gazduieste-un-curs" class="collab-card">
                <div class="collab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/></svg>
                </div>
                <h3>Găzduiește un curs</h3>
                <p>Ai o locație cu vibe fain? Transformă-o în spațiul unde se nasc conexiunile și ideile noi.</p>
                <span class="collab-link">Află mai mult →</span>
            </a>
            <a href="/propune-un-parteneriat" class="collab-card">
                <div class="collab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14l2 2 4-4"/></svg>
                </div>
                <h3>Propune un parteneriat</h3>
                <p>Reprezinți un brand sau o platformă media? Hai să explorăm ce putem construi împreună.</p>
                <span class="collab-link">Află mai mult →</span>
            </a>
        </div>
    </div>
</section>

<!-- ── CONTACT ────────────────────────────── -->
<section class="section section-dark" id="contact">
    <div class="container container-narrow">
        <h2 class="section-title"><?= htmlspecialchars($settings['contact_title']) ?></h2>
        <p class="section-subtitle"><?= htmlspecialchars($settings['contact_subtitle']) ?></p>
        <form class="contact-form" id="contactForm" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="contactName">Nume</label>
                    <input type="text" id="contactName" name="name" placeholder="Numele tău" required>
                </div>
                <div class="form-group">
                    <label for="contactEmail">Email</label>
                    <input type="email" id="contactEmail" name="email" placeholder="email@exemplu.ro" required>
                </div>
            </div>
            <div class="form-group">
                <label for="contactMsg">Mesaj</label>
                <textarea id="contactMsg" name="message" rows="5" placeholder="Scrie mesajul tău aici..." required></textarea>
            </div>
            <button type="submit" class="btn btn-accent">Trimite mesajul</button>
            <div class="form-message" id="contactMessage" aria-live="polite"></div>
        </form>
    </div>
</section>

<!-- ── FOOTER ─────────────────────────────── -->
<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <span class="logo-text footer-logo">Cursuri<br><em>la Pahar</em></span>
                <p>Aducem educația în baruri.</p>
            </div>
            <div class="footer-links">
                <a href="/#cursuri">Cursuri</a>
                <a href="/#cum-functioneaza">Cum funcționează</a>
                <a href="/#faq">FAQ</a>
                <a href="/#colaborare">Colaborare</a>
                <a href="/#contact">Contact</a>
            </div>
            <div class="footer-social">
                <a href="https://www.instagram.com/cursurilapahar" target="_blank" rel="noopener" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
                <a href="https://www.tiktok.com/@cursurilapahar" target="_blank" rel="noopener" aria-label="TikTok">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V9.13a8.19 8.19 0 004.79 1.53V7.19a4.85 4.85 0 01-1.02-.5z"/></svg>
                </a>
                <a href="https://www.facebook.com/profile.php?id=61585669450450" target="_blank" rel="noopener" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Cursuri la Pahar. Toate drepturile rezervate.</p>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js?v=<?php echo filemtime(__DIR__.'/assets/js/main.js'); ?>"></script>
</body>
</html>
 
