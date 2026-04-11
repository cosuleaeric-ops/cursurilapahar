<?php
/**
 * Cursuri la Pahar – Votează cursuri
 * v4
 */

$settings_file = __DIR__ . '/data/settings.json';
$_defaults = [
    'logo_path'      => '/assets/images/logo.webp',
    'favicon_path'   => '',
    'nav_brand_text' => 'Cursuri la Pahar',
    'nav_links'      => [
        ['label' => 'Cursuri',          'url' => '/#cursuri'],
        ['label' => 'Cum funcționează', 'url' => '/#cum-functioneaza'],
        ['label' => 'FAQ',              'url' => '/#faq'],
        ['label' => 'Colaborare',       'url' => '/#colaborare'],
        ['label' => 'Contact',          'url' => '/#contact'],
    ],
];
$_loaded  = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
$settings = array_merge($_defaults, $_loaded);

// Load vote courses and shuffle
$vote_courses_file = __DIR__ . '/data/vote_courses.json';
$vote_courses = file_exists($vote_courses_file)
    ? (json_decode(file_get_contents($vote_courses_file), true) ?: [])
    : [];
shuffle($vote_courses);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votează cursuri – Cursuri la Pahar</title>
    <meta name="description" content="Votează temele de curs care te interesează. Cele mai apreciate teme au șanse mai mari să devină cursuri viitoare.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
    $font_heading = $settings['font_heading'] ?? 'Nunito';
    $font_body    = $settings['font_body']    ?? 'Inter';
    $fonts_param  = 'family=' . urlencode($font_heading) . ':ital,wght@0,400;0,600;0,700;0,800;1,400;1,700&family=' . urlencode($font_body) . ':wght@300;400;500&display=swap';
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Anton&<?= $fonts_param ?>" rel="stylesheet">
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
    body { padding-top: 72px; }

    /* ── Vote page layout ── */
    .vote-section {
        max-width: 900px;
        margin: 0 auto;
        padding: 60px 20px 80px;
    }
    .vote-header {
        text-align: center;
        margin-bottom: 48px;
    }
    .vote-header h1 {
        font-family: var(--font-heading);
        font-size: clamp(2rem, 5vw, 3rem);
        font-weight: 800;
        color: var(--text);
        margin-bottom: 14px;
        line-height: 1.15;
    }
    .vote-header p {
        color: var(--text-muted);
        font-size: 1.05rem;
        max-width: 540px;
        margin: 0 auto;
        line-height: 1.65;
    }

    /* ── Grid ── */
    .vote-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        align-items: start;
    }
    @media (min-width: 900px) {
        .vote-grid {
            grid-template-columns: 1fr 1fr;
            align-items: start;
        }
    }

    /* ── Card ── */
    .vote-card {
        background: var(--surface);
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 14px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-self: start;
        transition: border-color .2s, box-shadow .2s;
    }
    .vote-card:hover {
        border-color: rgba(201,168,76,.25);
        box-shadow: 0 4px 24px rgba(0,0,0,.3);
    }

    /* Card header (clickable for desc toggle) */
    .vote-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 22px 22px 20px;
        cursor: pointer;
        user-select: none;
    }
    .vote-emoji {
        font-size: 2rem;
        line-height: 1;
        flex-shrink: 0;
        width: 42px;
        text-align: center;
    }
    .vote-name {
        flex: 1;
        font-family: var(--font-heading);
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1.3;
    }
    .vote-toggle-icon {
        font-size: 1.2rem;
        color: var(--text-muted);
        transition: transform .25s;
        flex-shrink: 0;
        line-height: 1;
    }
    .vote-card.open .vote-toggle-icon {
        transform: rotate(180deg);
    }

    /* Description (collapsible) */
    .vote-desc-wrap {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows .3s ease;
    }
    .vote-card.open .vote-desc-wrap {
        grid-template-rows: 1fr;
    }
    .vote-desc-inner {
        overflow: hidden;
    }
    .vote-desc {
        padding: 0 22px 20px;
        color: var(--text-muted);
        font-size: .95rem;
        line-height: 1.7;
        border-top: 1px solid rgba(255,255,255,.06);
        padding-top: 16px;
    }

    /* Footer with heart */
    .vote-card-footer {
        margin-top: auto;
        padding: 16px 22px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        border-top: 1px solid rgba(255,255,255,.06);
    }
    .vote-btn {
        background: none;
        border: 1.5px solid rgba(255,255,255,.15);
        border-radius: 50px;
        padding: 8px 18px;
        cursor: pointer;
        font-size: 1.1rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
        transition: border-color .2s, color .2s, background .2s;
        font-family: var(--font-sans);
        line-height: 1;
    }
    .vote-btn:hover {
        border-color: rgba(220,53,69,.5);
        color: #e05565;
        background: rgba(220,53,69,.08);
    }
    .vote-btn.voted {
        border-color: #e05565;
        color: #e05565;
        background: rgba(220,53,69,.1);
    }
    .vote-btn .heart {
        font-size: 1.25rem;
        line-height: 1;
        transition: transform .15s;
    }
    .vote-btn.voted .heart {
        animation: heartPop .25s ease;
    }
    @keyframes heartPop {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.4); }
        100% { transform: scale(1); }
    }
    .vote-btn-label {
        font-size: .85rem;
        font-weight: 600;
        letter-spacing: .01em;
    }

    /* Empty state */
    .vote-empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
        font-size: 1rem;
    }
    </style>
</head>
<body>
<?php include __DIR__ . '/admin/bar.php'; ?>

<!-- ── NAVBAR ─────────────────────────────── -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/#hero" class="navbar-logo">
            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="<?= htmlspecialchars($settings['nav_brand_text']) ?>">
            <span class="navbar-brand-text"><?php $nb=explode(' ',htmlspecialchars($settings['nav_brand_text']),2); echo '<span>'.$nb[0].'</span><span>'.($nb[1]??'').'</span>'; ?></span>
        </a>
        <div class="navbar-links">
            <?php foreach ($settings['nav_links'] as $nl): ?>
            <a href="<?= htmlspecialchars($nl['url']) ?>"><?= htmlspecialchars($nl['label']) ?></a>
            <?php endforeach; ?>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Meniu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>
<div class="nav-drawer" id="navDrawer">
    <?php foreach ($settings['nav_links'] as $nl): ?>
    <a href="<?= htmlspecialchars($nl['url']) ?>"><?= htmlspecialchars($nl['label']) ?></a>
    <?php endforeach; ?>
</div>

<!-- ── PAGE CONTENT ─────────────────────── -->
<section class="vote-section">
    <div class="vote-header">
        <h1>Votează cursurile</h1>
        <p>Apasă ❤️ pe temele care te interesează. Cele mai apreciate au șanse mai mari să devină cursuri viitoare.</p>
    </div>

    <?php if (empty($vote_courses)): ?>
    <div class="vote-empty">
        <p>Nu există teme de votat momentan. Revino curând!</p>
    </div>
    <?php else: ?>
    <div class="vote-grid">
        <?php foreach ($vote_courses as $vc):
            $vid  = htmlspecialchars($vc['id'] ?? '');
            $name = htmlspecialchars($vc['name'] ?? '');
            $emoji = htmlspecialchars($vc['emoji'] ?? '📚');
            $desc = htmlspecialchars($vc['description'] ?? '');
        ?>
        <div class="vote-card" id="vc-<?= $vid ?>">
            <div class="vote-card-header" onclick="toggleVoteDesc('<?= $vid ?>')">
                <span class="vote-emoji"><?= $emoji ?></span>
                <span class="vote-name"><?= $name ?></span>
                <span class="vote-toggle-icon">▾</span>
            </div>
            <?php if ($desc): ?>
            <div class="vote-desc-wrap">
                <div class="vote-desc-inner">
                    <div class="vote-desc"><?= $desc ?></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="vote-card-footer">
                <button class="vote-btn" data-id="<?= $vid ?>" onclick="toggleVote(this)">
                    <span class="heart">♡</span>
                    <span class="vote-btn-label">Îmi place</span>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- ── FOOTER ───────────────────────────── -->
<footer class="site-footer">
    <div class="footer-inner">
        <p class="footer-copy">© <?= date('Y') ?> Cursuri la Pahar. Toate drepturile rezervate.</p>
        <div class="footer-social">
            <a href="https://www.instagram.com/cursurilapahar" target="_blank" rel="noopener" aria-label="Instagram">
                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </a>
            <a href="https://www.tiktok.com/@cursurilapahar" target="_blank" rel="noopener" aria-label="TikTok">
                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V9.13a8.19 8.19 0 004.79 1.53V7.19a4.85 4.85 0 01-1.02-.5z"/></svg>
            </a>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js?v=<?php echo filemtime(__DIR__.'/assets/js/main.js'); ?>"></script>
<script>
// ── localStorage key
const VOTED_KEY = 'clp_voted';

function getVoted() {
    try { return JSON.parse(localStorage.getItem(VOTED_KEY) || '[]'); }
    catch { return []; }
}
function setVoted(arr) {
    localStorage.setItem(VOTED_KEY, JSON.stringify(arr));
}

// ── Apply saved state on load
document.addEventListener('DOMContentLoaded', () => {
    const voted = getVoted();
    voted.forEach(id => {
        const btn = document.querySelector('.vote-btn[data-id="' + id + '"]');
        if (btn) applyVoted(btn, true);
    });
});

function applyVoted(btn, isVoted) {
    if (isVoted) {
        btn.classList.add('voted');
        btn.querySelector('.heart').textContent = '♥';
        btn.querySelector('.vote-btn-label').textContent = 'Îți place';
    } else {
        btn.classList.remove('voted');
        btn.querySelector('.heart').textContent = '♡';
        btn.querySelector('.vote-btn-label').textContent = 'Îmi place';
    }
}

async function toggleVote(btn) {
    const id     = btn.dataset.id;
    const voted  = getVoted();
    const isVoted = voted.includes(id);
    const action  = isVoted ? 'remove' : 'add';

    // Optimistic UI
    applyVoted(btn, !isVoted);
    if (!isVoted) {
        const newVoted = [...voted, id];
        setVoted(newVoted);
    } else {
        setVoted(voted.filter(v => v !== id));
    }

    try {
        await fetch('/api/vote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action })
        });
    } catch {
        // Revert on failure
        applyVoted(btn, isVoted);
        if (!isVoted) {
            setVoted(voted);
        } else {
            setVoted([...voted, id]);
        }
    }
}

function toggleVoteDesc(id) {
    const card = document.getElementById('vc-' + id);
    if (card) card.classList.toggle('open');
}
</script>
</body>
</html>
