<?php
// Admin bar — shown on public pages when logged in as admin
function clp_is_admin(): bool {
    $cookie = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie || !str_contains($cookie, ':')) return false;
    $settings_file = dirname(__DIR__) . '/data/settings.json';
    $s = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
    $secret = $s['auth_secret'] ?? '';
    if (!$secret) return false;
    [$uname, $token] = explode(':', $cookie, 2);
    $expected = hash_hmac('sha256', 'clp_user:' . $uname, $secret);
    if (!hash_equals($expected, $token)) return false;
    $users_file = dirname(__DIR__) . '/data/users.json';
    if (!file_exists($users_file)) return false;
    $users = json_decode(file_get_contents($users_file), true) ?: [];
    foreach ($users as $u) {
        if (($u['username'] ?? '') === $uname) return ($u['role'] ?? '') === 'owner';
    }
    return false;
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
#clp-adminbar a, #clp-adminbar button.bar-link {
    color: #a7aaad; text-decoration: none; padding: 0 12px;
    height: 100%; display: flex; align-items: center; gap: 5px;
    border-right: 1px solid rgba(255,255,255,.07); transition: background .15s, color .15s;
    white-space: nowrap; background: none; border-top: none; border-bottom: none; cursor: pointer;
    font-size: 12px; font-family: inherit;
}
#clp-adminbar a:hover, #clp-adminbar button.bar-link:hover { background: #2c3338; color: #fff; }
#clp-adminbar .bar-brand { font-weight: 600; color: #fff; }
#clp-adminbar .bar-sep { flex: 1; }
#clp-adminbar .bar-logout { border-right: none; border-left: 1px solid rgba(255,255,255,.07); }
body { padding-top: 120px !important; } /* 32px admin bar + 88px navbar */
.navbar { top: 32px !important; }
</style>

<div id="clp-adminbar">
    <a href="/admin/" class="bar-brand">⚙ Admin</a>
    <a href="/admin/?tab=cursuri">📋 Cursuri</a>
    <a href="/admin/?tab=aspect">🎨 Aspect</a>
    <a href="/admin/?tab=imagini">🖼 Imagini</a>
    <a href="/admin/?tab=mesaje">💬 Mesaje</a>
    <a href="/admin/?tab=vot">❤️ Vot</a>
    <?php if (preg_match('#^/cursuri-posibile#', $current)): ?>
    <a href="/admin/?tab=cursuri-posibile">✏️ Editează pagina</a>
    <?php endif; ?>
    <span class="bar-sep"></span>
    <?php if (str_starts_with($current, '/admin')): ?>
    <a href="/">🌐 Site</a>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="bar-logout">Logout</a>
</div>
