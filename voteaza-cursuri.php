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

function clp_e(string $key, array $settings): string {
    return 'data-edit-key="' . htmlspecialchars($key) . '"';
}
function likes_label(int $n): string {
    return $n . ' ' . ($n === 1 ? 'apreciere' : 'aprecieri');
}

$vote_title    = $settings['vote_title']    ?? 'Votează cursurile';
$vote_subtitle = $settings['vote_subtitle'] ?? 'Apasă ❤️ pe temele care te interesează. Cele mai apreciate au șanse mai mari să devină cursuri viitoare.';

// Load vote courses and sort by likes descending
$vote_courses_file = __DIR__ . '/data/vote_courses.json';
$vote_courses = file_exists($vote_courses_file)
    ? (json_decode(file_get_contents($vote_courses_file), true) ?: [])
    : [];
usort($vote_courses, fn($a, $b) => ($b['likes'] ?? 0) - ($a['likes'] ?? 0));
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

    /* ── Vote page layout ── */
    .vote-section {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 20px 80px;
    }
    .vote-header {
        text-align: left;
        margin-bottom: 32px;
        max-width: 650px;
        margin-left: auto;
        margin-right: auto;
    }
    .vote-header h1 {
        font-family: var(--font-heading);
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 10px;
        line-height: 1.2;
    }
    .vote-header p {
        color: var(--text-muted);
        font-size: 1.05rem;
        line-height: 1.65;
    }

    /* ── Grid ── */
    .vote-grid {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 650px;
        margin: 0 auto;
    }

    /* ── Card — single row ── */
    .vote-card {
        background: var(--surface);
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 12px;
        overflow: hidden;
        transition: border-color .2s;
    }
    .vote-card:hover {
        border-color: rgba(201,168,76,.25);
    }

    /* Card header: emoji + name + heart + toggle — all inline */
    .vote-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        cursor: pointer;
        user-select: none;
    }
    .vote-emoji {
        font-size: 1.6rem;
        line-height: 1;
        flex-shrink: 0;
    }
    .vote-name {
        flex: 1;
        font-family: var(--font-heading);
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1.3;
    }
    .vote-btn {
        background: none;
        border: none;
        padding: 4px;
        cursor: pointer;
        font-size: 1.4rem;
        color: var(--text-muted);
        line-height: 1;
        flex-shrink: 0;
        transition: color .15s, transform .15s;
    }
    .vote-btn:hover { color: #e05565; transform: scale(1.15); }
    .vote-btn.voted { color: #e05565; }
    .vote-btn.voted .heart { animation: heartPop .25s ease; }
    .vote-likes-label {
        display: block;
        font-weight: 700;
        font-size: .9rem;
        color: var(--text-muted);
        margin-bottom: 10px;
    }
    @keyframes heartPop {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.4); }
        100% { transform: scale(1); }
    }
    .vote-toggle-icon {
        font-size: 1.4rem;
        color: var(--text-muted);
        cursor: pointer;
        padding: 4px;
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
        padding: 0 18px 16px;
        color: var(--text-muted);
        font-size: .93rem;
        line-height: 1.7;
        border-top: 1px solid rgba(255,255,255,.06);
        padding-top: 14px;
    }

    /* Empty state */
    .vote-empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
        font-size: 1rem;
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

<!-- ── PAGE CONTENT ─────────────────────── -->
<section class="vote-section">
    <div class="vote-header">
        <a href="/" onclick="if(history.length>1){history.back();return false}" class="page-hero-back" style="display:inline-flex;margin-bottom:20px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Înapoi
        </a>
        <h1 <?= clp_e('vote_title', $settings) ?>><?= htmlspecialchars($vote_title) ?></h1>
        <p <?= clp_e('vote_subtitle', $settings) ?>><?= htmlspecialchars($vote_subtitle) ?></p>
    </div>

    <?php if (empty($vote_courses)): ?>
    <div class="vote-empty">
        <p>Nu există teme de votat momentan. Revino curând!</p>
    </div>
    <?php else: ?>
    <div class="vote-grid">
        <?php foreach ($vote_courses as $vc):
            $vid   = htmlspecialchars($vc['id'] ?? '');
            $name  = htmlspecialchars($vc['name'] ?? '');
            $emoji = htmlspecialchars($vc['emoji'] ?? '📚');
            $desc  = htmlspecialchars($vc['description'] ?? '');
            $likes = (int)($vc['likes'] ?? 0);
        ?>
        <div class="vote-card" id="vc-<?= $vid ?>">
            <div class="vote-card-header" onclick="toggleVoteDesc('<?= $vid ?>')">
                <span class="vote-emoji"><?= $emoji ?></span>
                <span class="vote-name"><?= $name ?></span>
                <button class="vote-btn" data-id="<?= $vid ?>" onclick="event.stopPropagation();toggleVote(this)">
                    <span class="heart">♡</span>
                </button>
                <span class="vote-toggle-icon">▾</span>
            </div>
            <div class="vote-desc-wrap">
                <div class="vote-desc-inner">
                    <div class="vote-desc">
                        <strong class="vote-likes-label" id="vc-count-<?= $vid ?>"><?= likes_label($likes) ?></strong>
                        <?= $desc ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>


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
    } else {
        btn.classList.remove('voted');
        btn.querySelector('.heart').textContent = '♡';
    }
}

async function toggleVote(btn) {
    const id     = btn.dataset.id;
    const voted  = getVoted();
    const isVoted = voted.includes(id);
    const action  = isVoted ? 'remove' : 'add';

    const countEl = document.getElementById('vc-count-' + id);
    const delta = isVoted ? -1 : 1;

    function likesLabel(n) { return n + ' ' + (n === 1 ? 'apreciere' : 'aprecieri'); }
    function currentCount() { return parseInt(countEl?.textContent) || 0; }

    // Optimistic UI
    applyVoted(btn, !isVoted);
    if (countEl) countEl.textContent = likesLabel(currentCount() + delta);
    if (!isVoted) {
        setVoted([...voted, id]);
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
        if (countEl) countEl.textContent = likesLabel(currentCount() - delta);
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
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
