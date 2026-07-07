<header class="wp-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="/admin/" class="brand">Cursuri la Pahar <span>— Admin</span></a>
        <a href="/" class="wp-header-site-link">🌐 Vezi site</a>
    </div>
    <?php
    $real_user = clp_real_user();
    $is_imp    = is_impersonating();
    if ($real_user && ($real_user['role'] ?? '') === 'owner'):
        $all_users = load_users();
        $cur_view  = clp_current_user()['username'] ?? '';
    ?>
    <div style="display:flex;align-items:center;gap:8px">
        <?php if ($is_imp): ?>
        <span style="font-size:11px;background:#fef3c7;color:#92400e;padding:3px 8px;border-radius:12px;font-weight:600">
            Vizualizezi ca: <?= h(ucfirst($cur_view)) ?>
        </span>
        <form method="post" action="/admin/" style="margin:0">
            <input type="hidden" name="action" value="switch_user">
            <input type="hidden" name="target_username" value="<?= h($real_user['username']) ?>">
            <button type="submit" style="font-size:11px;padding:3px 8px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer;color:#374151">
                Înapoi la <?= h(ucfirst($real_user['username'])) ?>
            </button>
        </form>
        <?php else: ?>
        <span style="font-size:12px;color:#a0aec0"><?= h(ucfirst($real_user['username'])) ?></span>
        <div style="position:relative" id="user-switcher">
            <button id="user-switcher-btn"
                style="padding:2px 5px;border:none;background:none;cursor:pointer;color:#c0c8d4;font-size:10px;line-height:1" title="Schimbă cont">
                ▾
            </button>
            <div id="user-switcher-menu" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);min-width:140px;z-index:999">
                <?php foreach ($all_users as $u): if ($u['username'] === $real_user['username']) continue; ?>
                <form method="post" action="/admin/" style="margin:0">
                    <input type="hidden" name="action" value="switch_user">
                    <input type="hidden" name="target_username" value="<?= h($u['username']) ?>">
                    <button type="submit" style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;cursor:pointer;font-size:13px;color:#374151">
                        <?= h(ucfirst($u['username'])) ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <span style="font-size:12px;color:#a0aec0"><?= h(ucfirst(clp_current_user()['username'] ?? '')) ?></span>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="btn-logout">Deconectează-te</a>
</header>

<nav class="bc-botnav">
    <a href="/admin/marketing/" class="<?= ($tab ?? '') === 'marketing' ? 'active' : '' ?>">Marketing</a>
    <a href="/admin/?tab=speakeri" class="<?= ($tab ?? '') === 'speakeri' ? 'active' : '' ?>">Speakeri</a>
    <a href="/admin/?tab=locatii" class="<?= ($tab ?? '') === 'locatii' ? 'active' : '' ?>">Locații</a>
    <a href="/admin/?tab=vot" class="<?= ($tab ?? '') === 'vot' ? 'active' : '' ?>">Voturi</a>
    <a href="/admin/?tab=cursuri-posibile" class="<?= ($tab ?? '') === 'cursuri-posibile' ? 'active' : '' ?>">Cursuri posibile</a>
    <a href="/admin/?tab=colaborari" class="<?= ($tab ?? '') === 'colaborari' ? 'active' : '' ?>">Colaborări</a>
    <a href="/admin/?tab=imagini" class="<?= ($tab ?? '') === 'imagini' ? 'active' : '' ?>">Imagini</a>
    <a href="/admin/?tab=aspect" class="<?= ($tab ?? '') === 'aspect' ? 'active' : '' ?>">Aspect</a>
    <a href="/admin/?tab=templates" class="<?= ($tab ?? '') === 'templates' ? 'active' : '' ?>">Templates</a>
    <a href="/admin/statistici/ab_headline.php" class="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/statistici') !== false && ($tab ?? '') !== 'pnl' ? 'active' : '' ?>">Test A/B</a>
    <?php if (is_owner()): ?>
    <a href="/admin/statistici/pnl/" class="<?= ($tab ?? '') === 'pnl' ? 'active' : '' ?>">P&amp;L</a>
    <a href="/admin/?tab=config" class="<?= in_array($tab ?? '', ['config','securitate','kit'], true) ? 'active' : '' ?>">Setări</a>
    <?php endif; ?>
</nav>

<div class="wp-layout">

    <main class="wp-main">
<?php
$__bc_is_home = (($tab ?? '') === 'dashboard');
$__bc_labels = [
    'todos' => 'To-dos',
    'cursuri' => 'Cursuri', 'speakeri' => 'Speakeri', 'locatii' => 'Locații',
    'mesaje' => 'Mesaje', 'marketing' => 'Marketing', 'imagini' => 'Imagini',
    'aspect' => 'Aspect', 'vot' => 'Vot cursuri', 'colaborari' => 'Colaborări',
    'config' => 'Setări', 'securitate' => 'Setări', 'kit' => 'Setări',
    'templates' => 'Templates',
    'cursuri-posibile' => 'Cursuri posibile',
    'pnl' => 'P&L Cursuri',
];
$__bc_crumb = $__bc_labels[$tab ?? ''] ?? '';
?>
    <div class="bc-doc <?= $__bc_is_home ? 'bc-doc--home' : '' ?>">
<?php if (!$__bc_is_home): ?>
        <div class="bc-doc-top"><a href="/admin/" class="bc-home-link">Dashboard</a></div>
<?php endif; ?>
