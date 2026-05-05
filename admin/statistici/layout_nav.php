
<header class="wp-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="/admin/" class="brand">Cursuri la Pahar <span>— Admin</span></a>
        <a href="/" class="wp-header-site-link">🌐 Vezi site</a>
    </div>
    <div style="display:flex;align-items:center;gap:16px">
        <span style="font-size:12px;color:#a0aec0"><?= htmlspecialchars(ucfirst(_auth_current_user()['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/admin/?logout=1" class="btn-logout">Deconectează-te</a>
    </div>
</header>
<div class="wp-layout">
    <aside class="wp-sidebar">
        <nav>
            <a href="/admin/"><span class="nav-icon">🏠</span> Dashboard</a>
            <?php if (is_owner_auth()): ?>
            <div class="sidebar-section collapsible collapsed" onclick="clpToggleSidebarSection(this,'continut')">Conținut</div>
            <div class="sidebar-collapse-content collapsed" id="sidebar-continut">
            <a href="/admin/?tab=cursuri"><span class="nav-icon">📋</span> Cursuri</a>
            <a href="/admin/?tab=imagini"><span class="nav-icon">🖼️</span> Imagini</a>
            <a href="/admin/?tab=aspect"><span class="nav-icon">🎨</span> Aspect</a>
            <a href="/admin/?tab=vot"><span class="nav-icon">❤️</span> Vot cursuri</a>
            </div>
            <?php endif; ?>
            <div class="sidebar-section">Comunitate</div>
            <a href="/admin/?tab=mesaje"><span class="nav-icon">💬</span> Mesaje</a>
            <a href="/admin/?tab=competitori"><span class="nav-icon">🔍</span> Competitori</a>
            <div class="sidebar-section">CRM</div>
            <a href="/admin/?tab=speakeri"><span class="nav-icon">🎤</span> Speakeri</a>
            <a href="/admin/?tab=locatii"><span class="nav-icon">📍</span> Locații</a>
            <a href="/admin/?tab=colaborari"><span class="nav-icon">🤝</span> Colaborări</a>
            <?php if (is_owner_auth()): ?>
            <?php $_stat_path = $_SERVER['REQUEST_URI'] ?? ''; ?>
            <div class="sidebar-section">Statistici</div>
            <a href="/admin/statistici/cursuri/" class="<?= strpos($_stat_path, '/admin/statistici/cursuri') === 0 ? 'active' : '' ?>"><span class="nav-icon">📋</span> Cursuri</a>
            <a href="/admin/statistici/participanti/" class="<?= strpos($_stat_path, '/admin/statistici/participanti') === 0 ? 'active' : '' ?>"><span class="nav-icon">👥</span> Participanti</a>
            <a href="/admin/statistici/pnl/" class="<?= strpos($_stat_path, '/admin/statistici/pnl') === 0 ? 'active' : '' ?>"><span class="nav-icon">📈</span> P&amp;L Cursuri</a>
            <div class="sidebar-section">Sistem</div>
            <a href="/admin/?tab=config"><span class="nav-icon">⚙️</span> Setări</a>
            <?php endif; ?>
        </nav>
    </aside>
    <script>
    function clpToggleSidebarSection(header, id) {
        header.classList.toggle('collapsed');
        document.getElementById('sidebar-' + id).classList.toggle('collapsed');
    }
    </script>
    <main class="wp-main">
