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

<div class="wp-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="wp-sidebar">
        <nav>
            <a href="/admin/" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span> Dashboard
            </a>
            <a href="/admin/?tab=imagini" class="<?= $tab === 'imagini' ? 'active' : '' ?>">
                <span class="nav-icon">🖼️</span> Imagini
            </a>
            <a href="/admin/?tab=aspect" class="<?= $tab === 'aspect' ? 'active' : '' ?>">
                <span class="nav-icon">🎨</span> Aspect
            </a>
            <a href="/admin/?tab=vot" class="<?= $tab === 'vot' ? 'active' : '' ?>">
                <span class="nav-icon">❤️</span> Vot cursuri
            </a>
            <div class="sidebar-nav-tight">
            <a href="/admin/?tab=competitori" class="<?= $tab === 'competitori' ? 'active' : '' ?>">
                <span class="nav-icon">🔍</span> Competitori
            </a>
            <a href="/admin/marketing/" class="<?= ($tab ?? '') === 'marketing' ? 'active' : '' ?>">
                <span class="nav-icon">📣</span> Marketing
            </a>
            </div>
            <div class="sidebar-section">Management</div>
            <a href="/admin/?tab=cursuri" class="<?= $tab === 'cursuri' ? 'active' : '' ?>">
                <span class="nav-icon">📋</span> Cursuri
            </a>
            <a href="/admin/?tab=speakeri" class="<?= $tab === 'speakeri' ? 'active' : '' ?>">
                <span class="nav-icon">🎤</span> Speakeri
            </a>
            <a href="/admin/?tab=locatii" class="<?= $tab === 'locatii' ? 'active' : '' ?>">
                <span class="nav-icon">📍</span> Locații
            </a>
            <a href="/admin/?tab=colaborari" class="<?= $tab === 'colaborari' ? 'active' : '' ?>">
                <span class="nav-icon">🤝</span> Colaborări
            </a>
            <a href="/admin/?tab=mesaje" class="<?= $tab === 'mesaje' ? 'active' : '' ?>">
                <span class="nav-icon">💬</span> Mesaje<?php if ($_msg_pending_count > 0): ?><span class="nav-new-badge"><?= $_msg_pending_count ?></span><?php endif; ?>
            </a>
            <?php if (is_owner()): ?>
            <div class="sidebar-section">Sistem</div>
            <a href="/admin/statistici/pnl/" class="<?= ($tab ?? '') === 'pnl' ? 'active' : '' ?>">
                <span class="nav-icon">📈</span> P&amp;L Cursuri
            </a>
            <a href="/admin/?tab=config" class="<?= $tab === 'config' || $tab === 'securitate' || $tab === 'kit' ? 'active' : '' ?>">
                <span class="nav-icon">⚙️</span> Setări
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="wp-main">
