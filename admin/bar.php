<?php
// Admin bar — shown on public pages when logged in as admin
function clp_is_admin(): bool {
    $cookie = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie) return false;
    $settings_file = dirname(__DIR__) . '/data/settings.json';
    $s = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
    $secret = $s['auth_secret'] ?? '';
    if (!$secret) return false;
    $expected = hash_hmac('sha256', 'clp_admin_ok', $secret);
    return hash_equals($expected, $cookie);
}
if (!clp_is_admin()) return;
$current = $_SERVER['REQUEST_URI'] ?? '/';
?>
<style>
#clp-adminbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 99999;
    height: 32px; background: #1d2327; color: #a7aaad;
    display: flex; align-items: center; gap: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.4);
}
#clp-adminbar a {
    color: #a7aaad; text-decoration: none; padding: 0 12px;
    height: 100%; display: flex; align-items: center; gap: 5px;
    border-right: 1px solid rgba(255,255,255,.07); transition: background .15s, color .15s;
    white-space: nowrap;
}
#clp-adminbar a:hover { background: #2c3338; color: #fff; }
#clp-adminbar .bar-brand { font-weight: 600; color: #fff; }
#clp-adminbar .bar-sep { flex: 1; }
#clp-adminbar .bar-logout { border-right: none; border-left: 1px solid rgba(255,255,255,.07); }
body { padding-top: 32px !important; }
.navbar { top: 32px !important; }
</style>
<div id="clp-adminbar">
    <a href="/admin/" class="bar-brand">⚙ Admin</a>
    <a href="/admin/?tab=cursuri">📋 Cursuri</a>
    <a href="/admin/?tab=setari">✏️ Texte</a>
    <a href="/admin/?tab=aspect">🎨 Aspect</a>
    <a href="/admin/?tab=imagini">🖼 Imagini</a>
    <a href="/admin/?tab=pagini">📄 Pagini</a>
    <a href="/admin/?tab=mesaje">💬 Mesaje</a>
    <a href="/admin/?tab=vot">❤️ Vot</a>
    <span class="bar-sep"></span>
    <?php if (str_starts_with($current, '/admin')): ?>
    <a href="/">🌐 Site</a>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="bar-logout">Ieșire</a>
</div>
