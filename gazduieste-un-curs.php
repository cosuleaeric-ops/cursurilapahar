<?php
/**
 * Cursuri la Pahar – Găzduiește un curs
 */
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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,600;0,700;0,800;1,400;1,700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo filemtime(__DIR__.'/assets/css/style.css'); ?>">
    <style>body { padding-top: 64px; }</style>
</head>
<body>

<!-- ── NAVBAR ─────────────────────────────── -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/#hero" class="navbar-logo">
            <img src="/assets/images/logo.webp" alt="Cursuri la Pahar">
            <span class="navbar-brand-text">Cursuri la Pahar</span>
        </a>
        <div class="navbar-links">
            <a href="/#cursuri">Cursuri</a>
            <a href="/#cum-functioneaza">Cum funcționează</a>
            <a href="/#faq">FAQ</a>
            <a href="/#colaborare">Colaborare</a>
            <a href="/#contact">Contact</a>
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
    <a href="/#cursuri">Cursuri</a>
    <a href="/#cum-functioneaza">Cum funcționează</a>
    <a href="/#faq">FAQ</a>
    <a href="/#colaborare">Colaborare</a>
    <a href="/#contact">Contact</a>
</div>

<section class="page-hero">
    <div class="container">
        <a href="/" class="page-hero-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Înapoi
        </a>
        <h1>Găzduiește un<br><em style="font-style:italic;color:var(--accent)">Curs la Pahar</em></h1>
        <p>Ai o locație cu vibe fain și vrei să o transformi într-un loc de întâlnire al participanților Cursuri la Pahar? Căutăm parteneri care să devină „acasă" pentru evenimentele noastre!</p>
    </div>
</section>

<section class="page-content-section">
    <div class="container container-narrow">
        <p style="color:var(--text-muted);line-height:1.8;margin-bottom:28px;">
            Ai un bar, un pub, o cafenea sau un spațiu neconvențional care debordează de personalitate? Ne-ar plăcea să aducem conceptul Cursuri la Pahar la tine. Punem preț pe locurile care inspiră creativitate și care oferă cadrul perfect pentru networking și învățare relaxată.
        </p>

        <div class="benefits-list">
            <div class="benefit-item">
                <div class="benefit-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <div class="benefit-item-text">
                    <strong>Vizibilitate</strong>
                    <span>Atragi un public nou, dornic de experiențe de calitate.</span>
                </div>
            </div>
            <div class="benefit-item">
                <div class="benefit-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <div class="benefit-item-text">
                    <strong>Comunitate</strong>
                    <span>Spațiul tău devine un punct de reper pentru educație și socializare.</span>
                </div>
            </div>
            <div class="benefit-item">
                <div class="benefit-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="benefit-item-text">
                    <strong>Vibe</strong>
                    <span>Îți umpli locația cu energie pozitivă și oameni pasionați.</span>
                </div>
            </div>
        </div>

        <div class="inner-form">
            <h2>Hai să punem ceva frumos la cale!</h2>
            <form class="inner-page-form" data-form-type="gazduieste" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_name">Nume și prenume *</label>
                        <input type="text" id="guc_name" name="name" placeholder="Ion Popescu" required>
                    </div>
                    <div class="form-group">
                        <label for="guc_email">Email *</label>
                        <input type="email" id="guc_email" name="email" placeholder="email@exemplu.ro" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_phone">Număr de telefon</label>
                        <input type="tel" id="guc_phone" name="phone" placeholder="07xx xxx xxx">
                    </div>
                    <div class="form-group">
                        <label for="guc_venue">Cum se numește localul? *</label>
                        <input type="text" id="guc_venue" name="venue_name" placeholder="Ex: Twisted Olives" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_city">În ce oraș? *</label>
                        <input type="text" id="guc_city" name="city" placeholder="București" required>
                    </div>
                    <div class="form-group">
                        <label for="guc_capacity">Capacitate (seated)</label>
                        <input type="text" id="guc_capacity" name="capacity" placeholder="Ex: 50 persoane">
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
                    <textarea id="guc_other" name="other" rows="3" placeholder="Orice altceva relevant despre spațiu sau despre tine..."></textarea>
                </div>
                <button type="submit" class="btn btn-accent">Trimite</button>
                <div class="form-message" aria-live="polite"></div>
            </form>
        </div>
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
 
