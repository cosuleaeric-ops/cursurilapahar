<?php
declare(strict_types=1);

// Load settings for favicon (same as admin/index.php)
define('SETTINGS_FILE_HUB', dirname(__DIR__, 2) . '/data/settings.json');
$settings = file_exists(SETTINGS_FILE_HUB)
    ? array_merge(['favicon_path' => ''], json_decode(file_get_contents(SETTINGS_FILE_HUB), true) ?: [])
    : ['favicon_path' => ''];

require __DIR__ . '/../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statistici — Admin</title>
<?php if (!empty($settings['favicon_path'])): ?><link rel="icon" href="<?= htmlspecialchars($settings['favicon_path']) ?>"><?php endif; ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #f0f0f1;
    --surface: #fff;
    --header-bg: #1d2327;
    --header-text: #fff;
    --sidebar-bg: #1d2327;
    --sidebar-text: #a7aaad;
    --sidebar-active: #fff;
    --sidebar-active-bg: #2271b1;
    --accent: #2271b1;
    --text: #1d2327;
    --text-muted: #646970;
    --border: #c3c4c7;
    --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
body { background: var(--bg); color: var(--text); font-family: var(--font); font-size: 13px; line-height: 1.5; min-height: 100vh; }

.wp-header { background: var(--header-bg); color: var(--header-text); height: 46px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 100; }
.wp-header .brand { font-size: 14px; font-weight: 600; color: var(--header-text); text-decoration: none; }
.wp-header .brand span { opacity: .7; font-weight: 400; }
.btn-logout { background: transparent; border: 1px solid rgba(255,255,255,.25); color: rgba(255,255,255,.8); padding: 4px 10px; font-size: 12px; line-height: 1.8; border-radius: 3px; cursor: pointer; text-decoration: none; }
.btn-logout:hover { background: rgba(255,255,255,.1); color: #fff; }

.wp-layout { display: flex; min-height: calc(100vh - 46px); margin-top: 46px; }

.wp-sidebar { width: 200px; background: var(--sidebar-bg); flex-shrink: 0; padding-top: 8px; position: fixed; top: 46px; left: 0; height: calc(100vh - 46px); overflow-y: auto; z-index: 99; }
.wp-sidebar nav a {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px; color: var(--sidebar-text);
    text-decoration: none; font-size: 13px; font-weight: 500;
    border-left: 3px solid transparent; transition: background .1s, color .1s;
}
.wp-sidebar nav a:hover { color: var(--sidebar-active); background: rgba(255,255,255,.07); }
.wp-sidebar nav a.active { color: var(--sidebar-active); background: var(--sidebar-active-bg); border-left-color: rgba(255,255,255,.3); }
.wp-sidebar nav a .nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }

.wp-main { flex: 1; padding: 20px 24px; min-width: 0; margin-left: 200px; }
.wp-page-title { font-size: 22px; font-weight: 400; color: var(--text); margin-bottom: 20px; line-height: 1.3; }

.tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; max-width: 860px; }
.tool-card {
    display: flex; flex-direction: column; align-items: flex-start; gap: 6px;
    background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
    padding: 22px 20px; text-decoration: none; color: var(--text);
    transition: border-color .15s, box-shadow .15s;
}
.tool-card:hover { border-color: var(--accent); box-shadow: 0 1px 6px rgba(0,0,0,.08); }
.tool-icon { font-size: 24px; line-height: 1; margin-bottom: 2px; }
.tool-name { font-size: 15px; font-weight: 600; }
.tool-desc { font-size: 12px; color: var(--text-muted); line-height: 1.4; }

@media (max-width: 782px) {
    .wp-sidebar { display: none; }
    .wp-main { margin-left: 0; }
}
</style>
</head>
<body>

<header class="wp-header">
    <div style="display:flex;align-items:center;gap:10px">
        <a href="/admin/" class="brand">Cursuri la Pahar <span>— Admin</span></a>
        <a href="/" style="color:rgba(255,255,255,.7);font-size:12px;text-decoration:none;padding:4px 10px;border:1px solid rgba(255,255,255,.2);border-radius:3px;transition:background .1s" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background=''">Vezi site</a>
    </div>
    <a href="/admin/?logout=1" class="btn-logout">Deconecteaza-te</a>
</header>

<div class="wp-layout">

    <aside class="wp-sidebar">
        <nav>
            <a href="/admin/?tab=cursuri"><span class="nav-icon">📋</span> Cursuri</a>
            <a href="/admin/?tab=imagini"><span class="nav-icon">🖼️</span> Imagini</a>
            <a href="/admin/?tab=setari"><span class="nav-icon">⚙️</span> Texte</a>
            <a href="/admin/?tab=aspect"><span class="nav-icon">🎨</span> Aspect</a>
            <a href="/admin/?tab=pagini"><span class="nav-icon">📄</span> Pagini</a>
            <a href="/admin/?tab=kit"><span class="nav-icon">📧</span> Kit (Email)</a>
            <a href="/admin/?tab=mesaje"><span class="nav-icon">💬</span> Mesaje</a>
            <a href="/admin/?tab=vot"><span class="nav-icon">❤️</span> Vot cursuri</a>
            <a href="/admin/statistici/" class="active"><span class="nav-icon">📊</span> Statistici</a>
            <a href="/admin/?tab=securitate"><span class="nav-icon">🔒</span> Securitate</a>
            <a href="/admin/?tab=config"><span class="nav-icon">⚙️</span> Setari</a>
        </nav>
    </aside>

    <main class="wp-main">
        <h1 class="wp-page-title">Statistici</h1>

        <div class="tools-grid">
            <a class="tool-card" href="/admin/statistici/cursuri/">
                <div class="tool-icon">📋</div>
                <div class="tool-name">Cursuri</div>
                <div class="tool-desc">toate cursurile, participanti, distributie bilete si viza</div>
            </a>
            <a class="tool-card" href="/admin/statistici/participanti/">
                <div class="tool-icon">👥</div>
                <div class="tool-name">Participanti</div>
                <div class="tool-desc">agregat complet — cine a venit si de cate ori revine</div>
            </a>
            <a class="tool-card" href="/admin/statistici/pnl/">
                <div class="tool-icon">📈</div>
                <div class="tool-name">P&amp;L Cursuri</div>
                <div class="tool-desc">venituri, cheltuieli si profit net pe luni si categorii</div>
            </a>
        </div>
    </main>

</div>

</body>
</html>
