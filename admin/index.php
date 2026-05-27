<?php
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');
if (file_exists(dirname(__DIR__) . '/private/secrets.php')) {
    require dirname(__DIR__) . '/private/secrets.php';
}
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', '');
define('COURSES_FILE',        dirname(__DIR__) . '/data/courses.json');
define('SETTINGS_FILE',       dirname(__DIR__) . '/data/settings.json');
require_once dirname(__DIR__) . '/lib/courses.php';
require_once dirname(__DIR__) . '/lib/settings.php';
require_once dirname(__DIR__) . '/lib/dates.php';
require_once dirname(__DIR__) . '/lib/messages.php';
require_once dirname(__DIR__) . '/lib/speakers.php';
require_once dirname(__DIR__) . '/lib/vote.php';
require_once dirname(__DIR__) . '/lib/locations.php';
require_once dirname(__DIR__) . '/lib/collaborations.php';
require_once dirname(__DIR__) . '/lib/competitors.php';
define('UPLOADS_DIR',         dirname(__DIR__) . '/assets/images/uploads');
define('UPLOADS_URL',         '/assets/images/uploads');
define('PUBLIC_HTML',         dirname(__DIR__));
define('USERS_FILE',          dirname(__DIR__) . '/data/users.json');
require_once dirname(__DIR__) . '/lib/images.php';
// ── Auth secret — stored in settings.json, never in code ─────────────────────
function get_auth_secret(): string {
    static $s = null;
    if ($s === null) {
        $s = file_exists(SETTINGS_FILE)
            ? (json_decode(file_get_contents(SETTINGS_FILE), true) ?: [])
            : [];
    }
    return $s['auth_secret'] ?? '';
}

// ── Users ─────────────────────────────────────────────────────────────────────
function load_users(): array {
    if (!file_exists(USERS_FILE)) return [];
    return json_decode(file_get_contents(USERS_FILE), true) ?: [];
}
function save_users(array $users): void {
    file_put_contents(USERS_FILE, json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
function find_user(string $username): ?array {
    foreach (load_users() as $u) {
        if (($u['username'] ?? '') === $username) return $u;
    }
    return null;
}

// ── Cookie-based auth ─────────────────────────────────────────────────────────
function clp_real_user(): ?array {
    $secret = get_auth_secret();
    if (!$secret) return null;
    $cookie = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie || !str_contains($cookie, ':')) return null;
    [$uname, $token] = explode(':', $cookie, 2);
    $expected = hash_hmac('sha256', 'clp_user:' . $uname, $secret);
    if (!hash_equals($expected, $token)) return null;
    return find_user($uname);
}
function clp_current_user(): ?array { // renamed from get_current_user to avoid PHP builtin conflict
    $real = clp_real_user();
    if (!$real) return null;
    if (($real['role'] ?? '') === 'owner') {
        $view_as = $_COOKIE['clp_view_as'] ?? '';
        if ($view_as) {
            $impersonated = find_user($view_as);
            if ($impersonated) return $impersonated;
        }
    }
    return $real;
}
function is_impersonating(): bool {
    $real = clp_real_user();
    if (!$real || ($real['role'] ?? '') !== 'owner') return false;
    $view_as = $_COOKIE['clp_view_as'] ?? '';
    return !empty($view_as) && find_user($view_as) !== null;
}
function is_authenticated(): bool {
    return clp_current_user() !== null;
}
function is_owner(): bool {
    return (clp_current_user()['role'] ?? '') === 'owner';
}
function can_access_tab(string $tab): bool {
    $user = clp_current_user();
    if (!$user) return false;
    if (($user['role'] ?? '') === 'owner') return true;
    return in_array($tab, ['dashboard', 'mesaje', 'vot', 'competitori', 'speakeri', 'locatii', 'colaborari', 'imagini', 'aspect', 'cursuri']);
}
function set_auth_cookie(string $username): void {
    $token = hash_hmac('sha256', 'clp_user:' . $username, get_auth_secret());
    setcookie('clp_auth', $username . ':' . $token, [
        'expires'  => time() + 86400 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
function clear_auth_cookie(): void {
    setcookie('clp_auth', '', ['expires' => time() - 3600, 'path' => '/']);
}

// Ensure secrets exist in settings (generate on first run)
function ensure_secrets(): void {
    $settings = file_exists(SETTINGS_FILE)
        ? (json_decode(file_get_contents(SETTINGS_FILE), true) ?: [])
        : [];
    $changed = false;
    if (empty($settings['auth_secret']))    { $settings['auth_secret']    = bin2hex(random_bytes(32)); $changed = true; }
    if (empty($settings['webhook_secret'])) { $settings['webhook_secret'] = bin2hex(random_bytes(32)); $changed = true; }
    if (empty($settings['sync_token']))     { $settings['sync_token']     = bin2hex(random_bytes(32)); $changed = true; }
    if ($changed) {
        $dir = dirname(SETTINGS_FILE);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
ensure_secrets();

// Auto-create users.json on server if missing
if (!file_exists(USERS_FILE)) {
    $dir = dirname(USERS_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(USERS_FILE, json_encode([
        ['username' => 'eric6', 'password_hash' => '$2y$12$2dWGrc.k7sizuCBC18huu.XgqNkCgfVZ0DCaDS1kZQOFIDzgfLRPC', 'role' => 'owner'],
        ['username' => 'andy',  'password_hash' => '$2y$12$uxs/.33puwE3AmeCbilyve6t33qF3JXeaiObwDSiADFATmxQYzBvq',  'role' => 'city_manager'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

if (isset($_POST['login_username'])) {
    $uname = trim($_POST['login_username'] ?? '');
    $pass  = $_POST['login_password'] ?? '';
    $user  = find_user($uname);
    $ok    = false;
    if ($user) {
        $stored = $user['password_hash'] ?? '';
        $ok = (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2b$'))
            ? password_verify($pass, $stored)
            : ($pass === $stored);
    }
    if ($ok) {
        set_auth_cookie($uname);
        header('Location: /admin/');
        exit;
    } else {
        $login_error = 'Utilizator sau parolă incorecte.';
    }
}
if (isset($_GET['logout'])) {
    clear_auth_cookie();
    header('Location: /admin/');
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function clp_allowed_course_times(): array {
    return ['17:00', '17:30', '18:00', '18:30'];
}

function load_settings(): array { return clp_load_settings(); }
function save_settings(array $settings): bool { return clp_save_settings($settings); }

// ── Actions (only when authenticated) ────────────────────────────────────────
$action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($_POST['action'] ?? '') : '';
if (is_authenticated() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/actions.php';
}


// ── Navbar live (from live site editor) ──────────────────────────────────────
if (is_authenticated() && ($action === 'save_navbar_live')) {
    header('Content-Type: application/json');
    $s = load_settings();
    $color_keys = ['nav_bg','nav_brand_color','nav_link_color'];
    $num_keys   = ['nav_brand_size','nav_brand_weight','nav_link_size','nav_link_weight','nav_logo_h'];
    $font_keys  = ['nav_brand_font'];
    $allowed_fonts = ['Anton','Nunito','Poppins','Rubik','Inter','Playfair Display','Montserrat','Raleway','Oswald','Lora','DM Serif Display','Bebas Neue','Cormorant Garamond'];
    foreach ($color_keys as $k) {
        $v = trim($_POST[$k] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $v)) $s[$k] = $v;
    }
    foreach ($num_keys as $k) {
        $v = (int)($_POST[$k] ?? 0);
        if ($v > 0) $s[$k] = (string)$v;
    }
    foreach ($font_keys as $k) {
        $v = trim($_POST[$k] ?? '');
        if ($v && in_array($v, $allowed_fonts)) $s[$k] = $v;
    }
    save_settings($s);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Global fonts (from live site editor) ─────────────────────────────────────
if (is_authenticated() && ($action === 'save_global_fonts')) {
    $allowed_h = ['Anton','Nunito','Poppins','Rubik','Inter','Playfair Display','Montserrat','Raleway','Oswald','Lora','DM Serif Display','Bebas Neue','Cormorant Garamond'];
    $allowed_b = ['Inter','Roboto','Open Sans','Lato','DM Sans','Nunito','Rubik','Source Sans 3','Mulish','Cabin','Karla','Poppins'];
    header('Content-Type: application/json');
    $s  = load_settings();
    $fh = trim($_POST['font_heading'] ?? '');
    $fb = trim($_POST['font_body']    ?? '');
    if ($fh && in_array($fh, $allowed_h)) $s['font_heading'] = $fh;
    if ($fb && in_array($fb, $allowed_b)) $s['font_body']    = $fb;
    // Weight / italic / sizes
    foreach (['fh_weight','fb_weight'] as $k) {
        $v = (int)($_POST[$k] ?? 0);
        $s[$k] = ($v >= 100 && $v <= 900) ? (string)$v : '';
    }
    $s['fh_italic'] = !empty($_POST['fh_italic']) ? '1' : '';
    foreach (['fh_size_lg','fh_size_md','fh_size_sm','fb_size_lg','fb_size_md','fb_size_sm'] as $k) {
        $v = (int)($_POST[$k] ?? 0);
        $s[$k] = $v > 0 ? (string)$v : '';
    }
    save_settings($s);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Load data for display ─────────────────────────────────────────────────────
$courses  = [];
$settings = load_settings();
$tab      = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, ['dashboard','cursuri','imagini','aspect','kit','mesaje','vot','competitori','speakeri','locatii','colaborari','securitate','config'])) $tab = 'dashboard';
if (is_authenticated() && !can_access_tab($tab)) $tab = 'dashboard';

if (is_authenticated()) {
    $courses = clp_load_courses_for_admin();
    usort($courses, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
}

// ── Statistici cursuri (tab Cursuri: lună + sub-tab) ─────────────────────────
$clp_year = (int)date('Y');
$clp_month = (int)date('n');
$clp_ctab = 'cursuri';
$clp_ro_months = clp_ro_months_list(false);
if (is_authenticated() && $tab === 'cursuri') {
    $clp_now = new DateTimeImmutable();
    $clp_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)$clp_now->format('Y');
    $clp_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)$clp_now->format('n');
    $_ctab_raw = $_GET['ctab'] ?? 'cursuri';
    $clp_ctab = in_array($_ctab_raw, ['cursuri', 'participanti', 'calendar'], true) ? $_ctab_raw : 'cursuri';
}

// ── Unread messages badge ─────────────────────────────────────────────────────
$_msg_unread_count = 0;
if ($tab === 'mesaje' && is_authenticated()) {
    clp_mark_messages_read();
} elseif (is_authenticated()) {
    $_msg_unread_count = clp_unread_message_count();
}
?>
<!DOCTYPE html>
<html lang="ro" data-theme="corporate">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Cursuri la Pahar</title>
<?php if (!empty($settings['favicon_path'])): ?><link rel="icon" href="<?= htmlspecialchars($settings['favicon_path']) ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
<script>tailwind={config:{corePlugins:{preflight:false}}}</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/coloris.min.css">
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=3">
</head>
<body>

<?php if (!is_authenticated()): ?>
<!-- ── LOGIN ─────────────────────────────────────────────────────────────────── -->
<div class="login-wrap">
    <div class="login-box">
        <h1>Cursuri la Pahar<br><small style="font-size:13px;color:var(--text-muted);font-weight:400">Panou de administrare</small></h1>
        <?php if (!empty($login_error)): ?>
        <p class="login-error"><?= h($login_error) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="login_username" autocomplete="username" autofocus style="margin-bottom:8px">
            <input type="password" name="login_password" autocomplete="current-password">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Intră</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── ADMIN PANEL ─────────────────────────────────────────────────────────── -->

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
        <script>
        (function() {
            var btn = document.getElementById('user-switcher-btn');
            var menu = document.getElementById('user-switcher-menu');
            btn.addEventListener('click', function(e) { e.stopPropagation(); menu.style.display = menu.style.display === 'block' ? 'none' : 'block'; });
            document.addEventListener('click', function() { menu.style.display = 'none'; });
        })();
        </script>
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
            <a href="/admin/?tab=competitori" class="<?= $tab === 'competitori' ? 'active' : '' ?>">
                <span class="nav-icon">🔍</span> Competitori
            </a>
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
                <span class="nav-icon">💬</span> Mesaje<?php if ($_msg_unread_count > 0): ?><span class="nav-new-badge"><?= $_msg_unread_count ?> <?= $_msg_unread_count === 1 ? 'nou' : 'noi' ?></span><?php endif; ?>
            </a>
            <?php if (is_owner()): ?>
            <div class="sidebar-section">Sistem</div>
            <a href="/admin/statistici/pnl/">
                <span class="nav-icon">📈</span> P&amp;L Cursuri
            </a>
            <a href="/admin/?tab=config" class="<?= $tab === 'config' || $tab === 'securitate' || $tab === 'kit' ? 'active' : '' ?>">
                <span class="nav-icon">⚙️</span> Setări
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- ── MAIN ── -->
    <main class="wp-main">

<?php /* ======================================================= TAB: DASHBOARD */ ?>
<?php if ($tab === 'dashboard'): ?>

<?php
// ── Dashboard data ───────────────────────────────────────────────────────────
$_dash_courses = clp_load_courses_for_admin();
$_dash_active  = count(clp_filter_public_courses($_dash_courses));

// Upcoming courses (future, sorted by date)
$_dash_today = date('Y-m-d');
$_dash_upcoming = array_filter(clp_filter_public_courses($_dash_courses), fn($c) => ($c['date_raw'] ?? '') >= $_dash_today);
usort($_dash_upcoming, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
$_dash_upcoming = array_slice($_dash_upcoming, 0, 5);

// P&L stats (current month)
$_dash_pnl_profit = 0;
$_dash_pnl_venituri = 0;
$_dash_pnl_cheltuieli = 0;
$_dash_pnl_year  = date('Y');
$_dash_pnl_month = str_pad(date('n'), 2, '0', STR_PAD_LEFT);
$_dash_pnl_db_path = __DIR__ . '/statistici/data/pnl.sqlite';
if (file_exists($_dash_pnl_db_path)) {
    try {
        $_pdb = new SQLite3($_dash_pnl_db_path);
        $_pdb->exec('PRAGMA journal_mode=WAL');
        $_dash_pnl_venituri = (float)$_pdb->querySingle("SELECT COALESCE(SUM(suma),0) FROM venituri WHERE strftime('%Y',data)='{$_dash_pnl_year}' AND strftime('%m',data)='{$_dash_pnl_month}'");
        $_dash_pnl_cheltuieli = (float)$_pdb->querySingle("SELECT COALESCE(SUM(suma),0) FROM cheltuieli WHERE strftime('%Y',data)='{$_dash_pnl_year}' AND strftime('%m',data)='{$_dash_pnl_month}'");
        $_dash_pnl_profit = $_dash_pnl_venituri - $_dash_pnl_cheltuieli;
        $_pdb->close();
    } catch (Exception $e) {}
}

// Participants stats
$_dash_participants = 0;
$_dash_total_tickets = 0;
$_dash_clp_db_path = __DIR__ . '/statistici/data/clp.sqlite';
if (file_exists($_dash_clp_db_path)) {
    try {
        $_cdb = new SQLite3($_dash_clp_db_path);
        $_cdb->exec('PRAGMA journal_mode=WAL');
        $_dash_participants = (int)$_cdb->querySingle("SELECT COUNT(DISTINCT LOWER(TRIM(participant_name))) FROM tickets");
        $_dash_total_tickets = (int)$_cdb->querySingle("SELECT COUNT(*) FROM tickets");
        $_cdb->close();
    } catch (Exception $e) {}
}

// Vote courses (top voted)
$_dash_votes = load_vote_courses();
usort($_dash_votes, fn($a, $b) => ($b['likes'] ?? 0) - ($a['likes'] ?? 0));
$_dash_votes = array_slice($_dash_votes, 0, 5);

// P&L monthly data for chart (current year)
$_dash_pnl_monthly = [];
if (file_exists($_dash_pnl_db_path)) {
    try {
        $_pdb2 = new SQLite3($_dash_pnl_db_path);
        $_pdb2->exec('PRAGMA journal_mode=WAL');
        $_mv = []; $_mc = [];
        $r = $_pdb2->query("SELECT strftime('%m',data) as m, COALESCE(SUM(suma),0) as s FROM venituri WHERE strftime('%Y',data)='{$_dash_pnl_year}' GROUP BY m ORDER BY m");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_mv[$row['m']] = (float)$row['s'];
        $r = $_pdb2->query("SELECT strftime('%m',data) as m, COALESCE(SUM(suma),0) as s FROM cheltuieli WHERE strftime('%Y',data)='{$_dash_pnl_year}' GROUP BY m ORDER BY m");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_mc[$row['m']] = (float)$row['s'];
        for ($i = 1; $i <= (int)date('n'); $i++) {
            $k = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $_dash_pnl_monthly[] = ['v' => $_mv[$k] ?? 0, 'c' => $_mc[$k] ?? 0];
        }
        $_pdb2->close();
    } catch (Exception $e) {}
}

// Participant evolution (last 3 months)
$_dash_participant_months = [];
if (file_exists($_dash_clp_db_path)) {
    try {
        $_cdb2 = new SQLite3($_dash_clp_db_path);
        $_cdb2->exec('PRAGMA journal_mode=WAL');
        $r = $_cdb2->query("SELECT strftime('%Y-%m', c.date) as m, COUNT(DISTINCT LOWER(TRIM(t.participant_name))) as unici, COUNT(*) as bilete
            FROM tickets t JOIN courses c ON c.id = t.course_id
            GROUP BY m ORDER BY m DESC LIMIT 6");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_dash_participant_months[] = $row;
        $_dash_participant_months = array_reverse($_dash_participant_months);
        $_cdb2->close();
    } catch (Exception $e) {}
}

// Top returning participants
$_dash_top_fideli = [];
if (file_exists($_dash_clp_db_path)) {
    try {
        $_cdb3 = new SQLite3($_dash_clp_db_path);
        $_cdb3->exec('PRAGMA journal_mode=WAL');
        $r = $_cdb3->query("SELECT participant_name, COUNT(DISTINCT course_id) as nr_cursuri, COUNT(*) as nr_bilete
            FROM tickets GROUP BY LOWER(TRIM(participant_name)) HAVING nr_cursuri > 1
            ORDER BY nr_cursuri DESC, nr_bilete DESC LIMIT 5");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_dash_top_fideli[] = $row;
        $_cdb3->close();
    } catch (Exception $e) {}
}

// DITL current year
$_dash_ditl_year = 0;
if (file_exists($_dash_clp_db_path)) {
    try {
        $_cdb4 = new SQLite3($_dash_clp_db_path);
        $_cdb4->exec('PRAGMA journal_mode=WAL');
        $_dash_ditl_year = (float)$_cdb4->querySingle("SELECT COALESCE(SUM(total_incasari),0) FROM course_reports r JOIN courses c ON c.id=r.course_id WHERE strftime('%Y',c.date)='{$_dash_pnl_year}'") * 0.02;
        $_cdb4->close();
    } catch (Exception $e) {}
}

$_ro_months_dash = ['','ian','feb','mar','apr','mai','iun','iul','aug','sep','oct','nov','dec'];
$_ro_months_full = ['','ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'];
$_dash_month_label = $_ro_months_full[(int)date('n')] . ' ' . date('Y');
?>

<h1 class="wp-page-title">Dashboard</h1>

<?php
$_ql = $settings['quick_links'] ?? [];
$_ql_general = [];
$_ql_canva   = [];
foreach ($_ql as $_ql_item) {
    if (str_contains($_ql_item['url'] ?? '', 'canva.com')) $_ql_canva[] = $_ql_item;
    else $_ql_general[] = $_ql_item;
}
if (!empty($_ql)): ?>
<div class="ql-grid">
    <?php if (!empty($_ql_general)): ?>
    <div class="dash-section" style="margin:0">
        <div class="dash-section-title"><span>Linkuri utile</span></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($_ql_general as $_ql_item): ?>
            <a href="<?= h($_ql_item['url'] ?? '#') ?>" target="_blank" rel="noopener" class="ql-btn">
                <span style="font-size:17px"><?= h($_ql_item['icon'] ?? '🔗') ?></span>
                <?= h($_ql_item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($_ql_canva)): ?>
    <div class="dash-section" style="margin:0">
        <div class="dash-section-title"><span>Canva</span></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($_ql_canva as $_ql_item): ?>
            <a href="<?= h($_ql_item['url'] ?? '#') ?>" target="_blank" rel="noopener" class="ql-btn">
                <span style="font-size:17px"><?= h($_ql_item['icon'] ?? '🔗') ?></span>
                <?= h($_ql_item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Stats cards -->
<div class="dash-grid">
    <div class="dash-card accent-blue">
        <div class="dash-label">Cursuri active</div>
        <div class="dash-value"><?= $_dash_active ?></div>
        <div class="dash-sub">din <?= count($_dash_courses) ?> total</div>
    </div>
    <div class="dash-card accent-green">
        <div class="dash-label">Participanti unici</div>
        <div class="dash-value"><?= number_format($_dash_participants, 0, ',', '.') ?></div>
        <div class="dash-sub"><?= number_format($_dash_total_tickets, 0, ',', '.') ?> bilete total</div>
    </div>
</div>

<?php
// Mini calendar: 3 weeks starting from Monday of current week
$_mc_today   = new DateTime('now', new DateTimeZone('Europe/Bucharest'));
$_mc_dow     = (int)$_mc_today->format('N'); // 1=Mon
$_mc_start   = clone $_mc_today;
$_mc_start->modify('-' . ($_mc_dow - 1) . ' days'); // Monday of current week
$_mc_by_day  = [];
foreach ($_dash_courses as $_c) {
    $d = $_c['date_raw'] ?? '';
    if ($d) $_mc_by_day[$d][] = $_c;
}
$_mc_today_str = $_mc_today->format('Y-m-d');
?>

<div class="dash-section" style="margin-bottom:20px">
    <div class="dash-section-title" style="margin-bottom:10px">
        <span>Urmatoarele cursuri</span>
        <a href="?tab=cursuri" style="font-size:12px;font-weight:400;color:var(--primary);text-decoration:none;margin-left:10px">+ Adaugă</a>
    </div>
    <div class="mini-cal">
        <?php foreach (['Lu','Ma','Mi','Jo','Vi','Sâ','Du'] as $_dl): ?>
        <div class="mini-cal-dow"><?= $_dl ?></div>
        <?php endforeach; ?>
        <?php
        $_mc_cur = clone $_mc_start;
        for ($i = 0; $i < 21; $i++):
            $ds       = $_mc_cur->format('Y-m-d');
            $day_num  = $_mc_cur->format('j');
            $is_today = $ds === $_mc_today_str;
            $is_past  = $ds < $_mc_today_str;
            $cell_cls = $is_today ? 'today' : ($is_past ? 'past' : '');
        ?>
        <div class="mini-cal-cell <?= $cell_cls ?>">
            <div class="mini-cal-day"><?= $day_num ?></div>
            <?php foreach ($_mc_by_day[$ds] ?? [] as $_mc_c):
                $ev_cls = $is_today ? 'today-ev' : ($is_past ? 'past' : 'future');
            ?>
            <div class="mini-cal-event <?= $ev_cls ?>" title="<?= h($_mc_c['title'] ?? '') ?>"><?= h($_mc_c['title'] ?? '') ?></div>
            <?php endforeach; ?>
        </div>
        <?php $_mc_cur->modify('+1 day'); endfor; ?>
    </div>
</div>

<div class="dash-cols">
    <!-- Left column -->
    <div>

        <!-- Participant evolution -->
        <div class="dash-section">
            <div class="dash-section-title"><span>Evolutie participanti</span></div>
            <?php if (empty($_dash_participant_months)): ?>
                <p style="color:var(--text-muted);font-size:13px">Nicio data disponibila.</p>
            <?php else: ?>
                <table class="dash-table">
                    <tr style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">
                        <td>Luna</td><td style="text-align:right">Unici</td><td style="text-align:right">Bilete</td>
                    </tr>
                <?php foreach ($_dash_participant_months as $_pm):
                    $pmIdx = (int)substr($_pm['m'], 5, 2);
                ?>
                    <tr>
                        <td><?= ucfirst($_ro_months_full[$pmIdx]) ?> <?= substr($_pm['m'], 0, 4) ?></td>
                        <td style="text-align:right;font-weight:600"><?= $_pm['unici'] ?></td>
                        <td style="text-align:right" class="muted"><?= $_pm['bilete'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right column -->
    <div>
        <!-- Vote courses -->
        <div class="dash-section">
            <div class="dash-section-title"><span>Vot cursuri</span></div>
            <?php if (empty($_dash_votes)): ?>
                <p style="color:var(--text-muted);font-size:13px">Nicio propunere de curs.</p>
            <?php else: ?>
                <table class="dash-table">
                <?php foreach ($_dash_votes as $_vc): ?>
                    <tr>
                        <td><?= $_vc['emoji'] ?? '' ?> <?= h($_vc['name'] ?? '') ?></td>
                        <td class="muted" style="text-align:right;white-space:nowrap"><?= (int)($_vc['likes'] ?? 0) ?> voturi</td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>


<?php /* ======================================================= TAB: CURSURI */ ?>
<?php elseif ($tab === 'cursuri'): ?>
    <?php
    $course_speakers = load_speakers_for_picker();
    $course_locations = load_locations_for_picker();
    $course_times = clp_allowed_course_times();
    $course_form_error = trim($_GET['course_error'] ?? '');
    $edit_course = null;
    $edit_course_id = trim($_GET['edit'] ?? '');
    if ($edit_course_id !== '') {
        foreach ($courses as $c) {
            if (($c['id'] ?? '') === $edit_course_id) {
                $edit_course = $c;
                break;
            }
        }
    }
    ?>

    <h1 class="wp-page-title">Cursuri</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success">Curs salvat.</div>
    <?php endif; ?>

    <div class="card" id="course-form-card">
        <div class="card-title"><?= $edit_course ? 'Editează curs' : 'Adaugă curs' ?></div>
        <?php if ($course_form_error): ?>
        <p style="color:var(--danger);font-size:13px;margin:0 0 12px"><?= h($course_form_error) ?></p>
        <?php endif; ?>
        <?php if (empty($course_speakers)): ?>
        <p style="color:var(--text-muted);margin:0">Adaugă mai întâi speakeri în tab-ul <a href="/admin/?tab=speakeri">Speakeri</a>.</p>
        <?php else: ?>
        <form method="post" action="/admin/?tab=cursuri" id="courseForm" class="course-add-form" onsubmit="return validateCourseForm()">
            <input type="hidden" name="action" value="save_course">
            <input type="hidden" name="course_id" id="f_course_id" value="<?= h($edit_course['id'] ?? '') ?>">
            <input type="hidden" name="image_url" id="f_image_url" value="<?= h($edit_course['image_url'] ?? '') ?>">
            <div class="course-add-fields">
                <div class="form-group">
                    <label for="f_title">Nume curs</label>
                    <input type="text" name="title" id="f_title" required oninput="updateCoursePreview()" value="<?= h($edit_course['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="f_date_raw">Dată</label>
                    <input type="date" name="date_raw" id="f_date_raw" required onchange="updateCoursePreview()" value="<?= h($edit_course['date_raw'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="f_time">Oră</label>
                    <select name="time" id="f_time" required onchange="updateCoursePreview()">
                        <option value=""></option>
                        <?php foreach ($course_times as $t): ?>
                        <option value="<?= h($t) ?>" <?= ($edit_course['time'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group speaker-combobox">
                    <label for="f_speaker_input">Speaker</label>
                    <input type="text" id="f_speaker_input" autocomplete="off" required value="<?= h($edit_course ? clp_course_speaker_name($edit_course) : '') ?>">
                    <input type="hidden" name="speaker_id" id="f_speaker_id" value="<?= h($edit_course['speaker_id'] ?? '') ?>">
                    <div id="f_speaker_suggestions" class="speaker-suggestions" hidden></div>
                </div>
                <div class="form-group location-combobox">
                    <label for="f_location_input">Locație</label>
                    <input type="text" name="location" id="f_location_input" autocomplete="off" oninput="updateCoursePreview()" value="<?= h($edit_course['location'] ?? '') ?>">
                    <div id="f_location_suggestions" class="location-suggestions" hidden></div>
                </div>
                <div class="form-group">
                    <label for="f_lt_url">Link LiveTickets</label>
                    <input type="url" name="livetickets_url" id="f_lt_url" onblur="fetchLTImage()" value="<?= h($edit_course['livetickets_url'] ?? '') ?>">
                </div>
            </div>
            <script>
            window.CLP_SPEAKERS_PICKER = <?= json_encode(array_map(fn($s) => [
                'id' => $s['id'] ?? '',
                'name' => $s['name'] ?? '',
                'status' => $s['status'] ?? '',
            ], $course_speakers), JSON_UNESCAPED_UNICODE) ?>;
            window.CLP_LOCATIONS_PICKER = <?= json_encode(array_map(fn($l) => [
                'id' => $l['id'] ?? '',
                'name' => $l['name'] ?? '',
            ], $course_locations), JSON_UNESCAPED_UNICODE) ?>;
            </script>

            <div id="importMsg"></div>

            <div class="course-preview" id="coursePreview" style="display:none">
                <img id="prev_img" src="" alt="" style="display:none">
                <div class="course-preview-body">
                    <div class="course-preview-title" id="prev_title"></div>
                    <div class="course-preview-meta" id="prev_meta"></div>
                </div>
            </div>

            <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary btn-sm"><?= $edit_course ? 'Salvează' : 'Adaugă cursul' ?></button>
                <?php if ($edit_course): ?>
                <a href="/admin/?tab=cursuri&year=<?= (int)$clp_year ?>&month=<?= (int)$clp_month ?>&ctab=cursuri" class="btn btn-secondary btn-sm">Anulează</a>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <?php
    $today_ymd = date('Y-m-d');
    $courses_upcoming = [];
    $courses_past = [];
    foreach ($courses as $c) {
        if (!empty($c['date_raw']) && $c['date_raw'] < $today_ymd) {
            $courses_past[] = $c;
        } else {
            $courses_upcoming[] = $c;
        }
    }
    // Past: most recent first
    usort($courses_past, fn($a, $b) => strcmp($b['date_raw'] ?? '', $a['date_raw'] ?? ''));
    $render_courses_table = function(array $list) {
        ?>
        <table class="wp-table">
            <thead>
                <tr>
                    <th style="width:72px">Imagine</th>
                    <th>Titlu</th>
                    <th>Dată</th>
                    <th style="width:100px">Status</th>
                    <th style="width:240px">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $c):
                    $cid = $c['id'] ?? '';
                    $has_disc = !empty($c['discount_percent']) && !empty($c['discount_ends_at']);
                    $disc_local = '';
                    $disc_active_now = false;
                    if ($has_disc) {
                        try {
                            $dt = new DateTime($c['discount_ends_at']);
                            $dt->setTimezone(new DateTimeZone('Europe/Bucharest'));
                            $disc_local = $dt->format('Y-m-d\TH:i');
                            $disc_active_now = $dt->getTimestamp() > time();
                        } catch (Exception $e) {}
                    }
                ?>
                <tr>
                    <td>
                        <?php if (!empty($c['image_url'])): ?>
                        <img class="course-thumb" src="<?= h($c['image_url']) ?>" alt="">
                        <?php else: ?>
                        <div class="course-thumb-empty"></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:600">
                        <?= h($c['title'] ?? '') ?>
                        <?php $sp_name = clp_course_speaker_name($c); if ($sp_name !== ''): ?>
                        <div style="font-size:12px;font-weight:400;color:var(--text-muted);margin-top:2px"><?= h($sp_name) ?></div>
                        <?php endif; ?>
                        <?php if ($has_disc): ?>
                            <span class="discount-tag <?= $disc_active_now ? 'discount-tag--active' : 'discount-tag--expired' ?>">
                                −<?= (int)$c['discount_percent'] ?>%<?= $disc_active_now ? '' : ' (expirată)' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted)"><?= h($c['date_display'] ?? $c['date_raw'] ?? '') ?></td>
                    <td>
                        <?php if (empty($c['livetickets_url'])): ?>
                        <span class="btn btn-sm status-inactive" style="cursor:default;opacity:.85" title="Adaugă link LiveTickets ca să apară pe site">Draft</span>
                        <?php else: ?>
                        <form method="post" action="/admin/?tab=cursuri" style="display:inline">
                            <input type="hidden" name="action" value="toggle_course">
                            <input type="hidden" name="id" value="<?= h($cid) ?>">
                            <button type="submit" class="btn btn-sm <?= !empty($c['active']) ? 'status-active' : 'status-inactive' ?>">
                                <?= !empty($c['active']) ? 'Activ' : 'Inactiv' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="/admin/?tab=cursuri&edit=<?= h($cid) ?>" class="btn btn-sm btn-secondary">Editează</a>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="toggleDiscountRow('<?= h($cid) ?>')">Reducere ▾</button>
                            <form method="post" action="/admin/?tab=cursuri" onsubmit="return confirm('Ștergi cursul?')" style="display:inline">
                                <input type="hidden" name="action" value="delete_course">
                                <input type="hidden" name="id" value="<?= h($cid) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                            </form>
                            <?php if (!empty($c['livetickets_url'])): ?>
                            <a href="<?= h($c['livetickets_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">LT ↗</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <tr id="discount-row-<?= h($cid) ?>" class="discount-edit-row" style="display:none">
                    <td colspan="5">
                        <form method="post" action="/admin/?tab=cursuri" class="discount-form">
                            <input type="hidden" name="action" value="save_discount">
                            <input type="hidden" name="id" value="<?= h($cid) ?>">
                            <label>Reducere (%):
                                <input type="number" name="discount_percent" min="1" max="100" value="<?= $has_disc ? (int)$c['discount_percent'] : '' ?>" style="width:90px">
                            </label>
                            <label>Expiră la (ora București):
                                <input type="datetime-local" name="discount_ends_at" value="<?= h($disc_local) ?>">
                            </label>
                            <button type="submit" class="btn btn-sm btn-primary">Salvează reducerea</button>
                            <?php if ($has_disc): ?>
                                <button type="submit" name="clear" value="1" class="btn btn-sm btn-danger" onclick="return confirm('Ștergi reducerea?')">Șterge reducerea</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    };
    ?>

    <!-- Courses table (upcoming) -->
    <div class="card">
        <div class="card-title">Cursuri (<?= count($courses_upcoming) ?>)</div>
        <?php if (empty($courses_upcoming)): ?>
        <p style="color:var(--text-muted)">Nu există cursuri adăugate încă.</p>
        <?php else: $render_courses_table($courses_upcoming); endif; ?>
    </div>

    <!-- ── Statistici cursuri (full merge) ─────────────────────────────── -->
    <div class="card" id="clp-stats-card">

        <div class="clp-tabs" style="margin-bottom:16px">
            <button class="clp-tab-btn <?= $clp_ctab === 'cursuri' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'cursuri')">Cursuri</button>
            <button class="clp-tab-btn <?= $clp_ctab === 'calendar' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'calendar')">Calendar</button>
            <button class="clp-tab-btn <?= $clp_ctab === 'participanti' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'participanti')">Participanți</button>
            <span class="clp-tabs-sep" aria-hidden="true"></span>
            <span id="clpMonthNav" style="display:contents">
                <button type="button" onclick="clpNav(-1)" class="clp-tab-btn" style="padding:7px 12px!important;line-height:1" aria-label="Luna anterioară">&#8592;</button>
                <span id="clpMonthLabel" class="clp-tab-btn active" style="cursor:default;min-width:96px;text-align:center;pointer-events:none"><?= ucfirst($clp_ro_months[$clp_month ?? 1]) . ' ' . ($clp_year ?? date('Y')) ?></span>
                <button type="button" onclick="clpNav(+1)" class="clp-tab-btn" style="padding:7px 12px!important;line-height:1" aria-label="Luna următoare">&#8594;</button>
            </span>
        </div>

        <!-- Tab: Cursuri (statistici — încărcat via API) -->
        <div class="clp-tab-panel <?= $clp_ctab === 'cursuri' ? 'active' : '' ?>" id="clp-panel-cursuri">
            <p style="color:var(--text-muted)">Se încarcă…</p>
        </div>

        <!-- Tab: Participanți (încărcat via API) -->
        <div class="clp-tab-panel <?= $clp_ctab === 'participanti' ? 'active' : '' ?>" id="clp-panel-participanti">
            <p style="color:var(--text-muted)">Se încarcă…</p>
        </div>

        <!-- Tab: Calendar -->
        <div class="clp-tab-panel <?= $clp_ctab === 'calendar' ? 'active' : '' ?>" id="clp-panel-calendar">

            <div id="calGrid"></div>
            <div style="display:flex;gap:16px;margin-top:12px;font-size:12px;color:#6b7280;flex-wrap:wrap">
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#dbeafe;border:1px solid #bfdbfe;display:inline-block"></span> Curs viitor</span>
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#1d4ed8;display:inline-block"></span> Curs azi</span>
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#f1f5f9;border:1px solid #e5e7eb;display:inline-block"></span> Curs trecut</span>
            </div>
        </div>
    </div>

    <script>
    window.CLP_STATS = <?= json_encode([
        'year' => (int)($clp_year ?? date('Y')),
        'month' => (int)($clp_month ?? date('n')),
        'calYear' => (int)date('Y'),
        'calMonth' => (int)date('n'),
        'calCourses' => array_map(fn($c) => ['date' => $c['date_raw'] ?? '', 'title' => $c['title'] ?? ''], $courses),
        'initCalendar' => ($clp_ctab ?? '') === 'calendar',
        'scrollToStats' => isset($_GET['saved']),
        'activeTab' => $clp_ctab ?? 'cursuri',
    ], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="/admin/assets/js/admin-cursuri-stats.js?v=2"></script>

<?php /* ======================================================= TAB: IMAGINI */ ?>
<?php elseif ($tab === 'imagini'): ?>

    <h1 class="wp-page-title">Imagini</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success">Setările imaginilor au fost salvate.</div>
    <?php endif; ?>

    <?php if (!empty($upload_ok ?? '')): ?>
    <div class="notice notice-success"><?= h($upload_ok) ?></div>
    <?php endif; ?>
    <?php if (!empty($upload_error ?? '')): ?>
    <div class="notice notice-error"><?= h($upload_error) ?></div>
    <?php endif; ?>

    <!-- Upload -->
    <div class="card">
        <div class="card-title">Încarcă imagine nouă</div>
        <form method="post" action="/admin/?tab=imagini" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_image">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="file" name="image_files[]" accept="image/*" multiple style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
                <button type="submit" class="btn btn-primary">Încarcă</button>
            </div>
            <p class="form-desc">Formate acceptate: JPG, PNG, WEBP, GIF. Poți selecta mai multe fișiere. Imaginile sunt convertite automat în WebP și redimensionate la max 1920px.</p>
        </form>
    </div>

    <!-- Images grid with hero selection -->
    <?php $all_images = get_all_images(); ?>
    <div class="card">
        <div class="card-title">Toate imaginile</div>
        <?php if (empty($all_images)): ?>
        <p style="color:var(--text-muted)">Nu există imagini.</p>
        <?php else: ?>
        <form method="post" action="/admin/?tab=imagini">
            <input type="hidden" name="action" value="save_hero_images">
            <div class="images-grid">
                <?php foreach ($all_images as $img):
                    $is_hero    = in_array($img['url'], $settings['hero_images'] ?? []);
                    $is_gallery = in_array($img['url'], $settings['gallery_featured'] ?? []);
                ?>
                <div class="image-item">
                    <img src="<?= h($img['url']) ?>" alt="<?= h($img['name']) ?>">
                    <div class="image-item-body">
                        <div class="image-item-name"><?= h($img['name']) ?></div>
                        <div class="image-item-actions">
                            <label class="hero-check">
                                <input type="checkbox" name="hero_images[]" value="<?= h($img['url']) ?>" <?= $is_hero ? 'checked' : '' ?>>
                                Hero
                            </label>
                            <label class="hero-check" style="color:#C9A84C">
                                <input type="checkbox" name="gallery_featured[]" value="<?= h($img['url']) ?>" <?= $is_gallery ? 'checked' : '' ?>>
                                Galerie
                            </label>
                            <?php if ($img['deletable']): ?>
                            <button type="button" class="btn btn-sm btn-danger" style="padding:1px 7px"
                                onclick="deleteImage(<?= json_encode($img['name']) ?>)">✕</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary">Salvează</button>
                <span style="font-size:12px;color:var(--text-muted);margin-left:10px">Hero = slideshow pagină principală &nbsp;·&nbsp; Galerie = slider secțiunea Galerie.</span>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <script>
    function deleteImage(filename) {
        if (!confirm('Ștergi imaginea?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_image');
        fd.append('filename', filename);
        fetch('/admin/?tab=imagini', { method: 'POST', body: fd })
            .then(() => location.reload());
    }
    </script>

<?php /* ======================================================= TAB: ASPECT */ ?>
<?php elseif ($tab === 'aspect'): ?>
<h1 class="wp-page-title">Aspect</h1>
<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Setările de aspect au fost salvate.</div>
<?php endif; ?>

<!-- Logo -->
<div class="card">
    <div class="card-title">Logo</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">Logo curent: <code><?= h($settings['logo_path'] ?? '') ?></code></p>
    <?php if (!empty($settings['logo_path'])): ?>
    <img src="<?= h($settings['logo_path']) ?>" alt="Logo" style="max-height:60px;margin-bottom:12px;display:block;background:#1d2327;padding:8px;border-radius:4px;">
    <?php endif; ?>
    <form method="post" action="/admin/?tab=aspect" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_logo">
        <div style="display:flex;gap:8px;align-items:center">
            <input type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.svg" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
            <button type="submit" class="btn btn-primary">Încarcă logo</button>
        </div>
        <p class="form-desc">Formate: JPG, PNG, WEBP, SVG.</p>
    </form>
</div>

<!-- Favicon -->
<div class="card">
    <div class="card-title">Favicon</div>
    <?php if (!empty($settings['favicon_path'])): ?>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">Favicon curent: <code><?= h($settings['favicon_path']) ?></code></p>
    <?php endif; ?>
    <?php if (!empty($favicon_error)): ?>
    <div style="background:#fcf0f1;border:1px solid #f5c6cb;color:#c0392b;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:12px"><?= $favicon_error ?></div>
    <?php endif; ?>
    <form method="post" action="/admin/?tab=aspect" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_favicon">
        <div style="display:flex;gap:8px;align-items:center">
            <input type="file" name="favicon_file" accept=".ico,.png,.jpg,.jpeg,.webp" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
            <button type="submit" class="btn btn-primary">Încarcă favicon</button>
        </div>
        <p class="form-desc">Formate: ICO, PNG, JPG, WEBP. Fișierul va fi salvat în rădăcina site-ului.</p>
    </form>
</div>

<!-- Culori & Fonturi -->
<form method="post" action="/admin/?tab=aspect">
    <input type="hidden" name="action" value="save_design">
    <div class="card" style="margin-top:20px">
        <div class="card-title">Culori &amp; Fonturi</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <?php
            $color_fields_ui = [
                'color_bg'         => ['label' => 'Fundal principal',      'default' => '#0D0D0D'],
                'color_accent'     => ['label' => 'Culoare accent',         'default' => '#C9A84C'],
                'color_text'       => ['label' => 'Culoare text',           'default' => '#E8E4DC'],
                'color_text_muted' => ['label' => 'Text secundar',          'default' => '#9CA3AF'],
                'color_surface'    => ['label' => 'Fundal carduri/secțiuni','default' => '#161616'],
                'color_btn_hover'  => ['label' => 'Hover butoane',          'default' => '#b8922e'],
                'color_banner'     => ['label' => 'Fundal banner anunț',    'default' => '#FFB000'],
            ];
            foreach ($color_fields_ui as $fname => $meta):
                $val = h($settings[$fname] ?? $meta['default']);
            ?>
            <div class="form-group" style="margin:0">
                <label><?= $meta['label'] ?></label>
                <input type="text" name="<?= $fname ?>" value="<?= $val ?>" data-coloris>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
            <div class="form-group" style="margin:0">
                <label>Font titluri</label>
                <select name="font_heading" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:4px;font-size:13px;background:#fff">
                    <?php foreach (['Nunito','Playfair Display','Montserrat','Raleway','Oswald','Lora','Poppins','Rubik','DM Serif Display','Bebas Neue','Cormorant Garamond'] as $f): ?>
                    <option value="<?= h($f) ?>" <?= ($settings['font_heading'] ?? 'Nunito') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-desc">Font folosit pentru titluri și headinguri.</p>
            </div>
            <div class="form-group" style="margin:0">
                <label>Font text</label>
                <select name="font_body" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:4px;font-size:13px;background:#fff">
                    <?php foreach (['Inter','Roboto','Open Sans','Lato','Source Sans 3','DM Sans','Nunito','Rubik','Mulish','Cabin','Karla'] as $f): ?>
                    <option value="<?= h($f) ?>" <?= ($settings['font_body'] ?? 'Inter') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-desc">Font folosit pentru textele din pagină.</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salvează design</button>
    </div>
</form>

<?php elseif ($tab === 'kit'): ?>
<?php header('Location: /admin/?tab=config'); exit; ?>

<?php /* ======================================================= TAB: MESAJE */ ?>
<?php elseif ($tab === 'mesaje'): ?>
<h1 class="wp-page-title">Mesaje</h1>
<?php if (isset($_GET['deleted'])): ?>
<div class="notice notice-success">Mesajul a fost șters.</div>
<?php endif; ?>

<?php
$categories = clp_message_categories();
$_msg_data  = clp_load_grouped_messages();
$grouped    = $_msg_data['grouped'];
$tab_counts = $_msg_data['tab_counts'];
require __DIR__ . '/partials/messages-tab.php';
?>



<?php elseif ($tab === 'vot'): ?>
<?php
$_vc = clp_vote_admin_context($_GET['edit'] ?? '');
$vote_courses = $_vc['courses'];
$edit_vc = $_vc['edit'];
require __DIR__ . '/partials/vot-tab.php';
?>

<?php /* ======================================================= TAB: COMPETITORI */ ?>
<?php /* ======================================================= TAB: COMPETITORI */ ?>
<?php elseif ($tab === 'competitori'): ?>
<?php $_competitors = clp_competitors_list(); require __DIR__ . '/partials/competitori-tab.php'; ?>

<?php /* ======================================================= TAB: SPEAKERI */ ?>
<?php /* ======================================================= TAB: SPEAKERI */ ?>
<?php elseif ($tab === 'speakeri'): ?>

<?php
$_sp_ctx = clp_speakers_admin_context($_GET['edit'] ?? '');
$speakers = $_sp_ctx['speakers'];
$edit_sp = $_sp_ctx['edit'];
$edit_sp_id = $_GET['edit'] ?? '';
$sp_status_colors = clp_speaker_status_colors();
$_sp_contacted = clp_contacted_message_leads();
?>


<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Speakerul a fost salvat.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Speakeri (<?= count($speakers) ?>)</span>
        <button type="button" onclick="document.getElementById('sp-modal').style.display='flex'" class="btn btn-sm btn-primary">+ Adaugă speaker</button>
    </div>
    <?php if (empty($speakers) && empty($_sp_contacted)): ?>
    <p style="color:var(--text-muted)">Nu există speakeri adăugați încă.</p>
    <?php else: ?>
    <div class="sp-filter-bar">
        <button class="sp-filter-btn active" data-status="all" onclick="spFilter(this)">Toți</button>
        <button class="sp-filter-btn" data-status="RECURENT" onclick="spFilter(this)">RECURENT</button>
        <button class="sp-filter-btn" data-status="MID" onclick="spFilter(this)">MID</button>
        <button class="sp-filter-btn" data-status="NOPE" onclick="spFilter(this)">NOPE</button>
        <button class="sp-filter-btn" data-status="CONTACTAT" onclick="spFilter(this)">CONTACTAT</button>
    </div>
    <table class="wp-table crm-table" id="sp-main-table">
        <thead>
            <tr>
                <th>Nume</th>
                <th>Contact</th>
                <th>Cursuri</th>
                <th style="width:90px">Status</th>
                <th style="width:150px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($_sp_contacted as $c): ?>
        <tr data-msg-id="<?= h($c['id']) ?>">
            <td style="font-weight:600"><?= h($c['name']) ?></td>
            <td style="font-size:13px">
                <?php if ($c['email']): ?><div><?= h($c['email']) ?> <button type="button" class="sp-copy-btn" data-copy="<?= h($c['email']) ?>" onclick="spCopy(this)" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><?php endif; ?>
                <?php if ($c['phone']): ?><div><?= h($c['phone']) ?> <button type="button" class="sp-copy-btn" data-copy="<?= h($c['phone']) ?>" onclick="spCopy(this)" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><?php endif; ?>
            </td>
            <td></td>
            <td><span class="crm-status-badge" style="background:#2271b1">CONTACTAT</span></td>
            <td>
                <div class="row-actions">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="spContactatEdit(<?= h(json_encode(['name'=>$c['name'],'email'=>$c['email'],'phone'=>$c['phone']])) ?>)">Editează</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="spScoate(this,'<?= h($c['id']) ?>')">Scoate</button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php foreach ($speakers as $sp): ?>
        <tr>
            <td style="font-weight:600">
                <?= h($sp['name'] ?? '') ?>
                <?php if (!empty($sp['notes'])): ?>
                <div style="font-size:11px;color:var(--text-muted);font-weight:400;margin-top:2px"><?= h(mb_substr($sp['notes'], 0, 60)) ?><?= mb_strlen($sp['notes']) > 60 ? '…' : '' ?></div>
                <?php endif; ?>
            </td>
            <td style="font-size:13px">
                <?php if (!empty($sp['email'])): ?><div><?= h($sp['email']) ?> <button type="button" class="sp-copy-btn" data-copy="<?= h($sp['email']) ?>" onclick="spCopy(this)" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><?php endif; ?>
                <?php if (!empty($sp['phone'])): ?><div><?= h($sp['phone']) ?> <button type="button" class="sp-copy-btn" data-copy="<?= h($sp['phone']) ?>" onclick="spCopy(this)" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><?php endif; ?>
            </td>
            <td>
                <?php
                $sp_c = $sp['courses'] ?? [];
                if (is_string($sp_c)) $sp_c = $sp_c ? [$sp_c] : [];
                foreach (array_filter($sp_c) as $sp_cv):
                ?>
                <span style="display:inline-block;background:#e5e7eb;color:#374151;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:500;margin:2px 2px 2px 0"><?= h($sp_cv) ?></span>
                <?php endforeach; ?>
            </td>
            <td>
                <?php $sc = $sp_status_colors[$sp['status'] ?? 'MID'] ?? '#6b7280'; ?>
                <span class="crm-status-badge" style="background:<?= $sc ?>;cursor:pointer;user-select:none;position:relative" onclick="spStatusPop(this,'<?= h($sp['id'] ?? '') ?>')"><?= h($sp['status'] ?? 'MID') ?></span>
            </td>
            <td>
                <div class="row-actions">
                    <a href="/admin/?tab=speakeri&edit=<?= h($sp['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                    <form method="post" action="/admin/?tab=speakeri" onsubmit="return confirm('Ștergi speakerul?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_speaker">
                        <input type="hidden" name="id" value="<?= h($sp['id'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>


<div id="sp-modal" style="display:<?= $edit_sp ? 'flex' : 'none' ?>;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,.45)" onclick="if(event.target===this)this.style.display='none'">
<div class="card crm-form" style="width:min(640px,95vw);max-height:90vh;overflow-y:auto;margin:0;position:relative">
    <div class="card-title"><?= $edit_sp ? 'Editează speaker' : 'Adaugă speaker' ?></div>
    <form method="post" action="/admin/?tab=speakeri">
        <input type="hidden" name="action" value="save_speaker">
        <input type="hidden" name="speaker_id" value="<?= h($edit_sp['id'] ?? '') ?>">
        <!-- Modal tabs -->
        <div style="display:flex;gap:4px;background:#f1f5f9;border-radius:8px;padding:3px;margin-bottom:20px;width:fit-content">
            <button type="button" id="sp-tab-btn-contact" onclick="spModalTab('contact')" style="padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#1f2937;box-shadow:0 1px 3px rgba(0,0,0,.1)">Contact</button>
            <button type="button" id="sp-tab-btn-meet" onclick="spModalTab('meet')" style="padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:none;color:#6b7280">Meet</button>
        </div>
        <!-- Tab: Contact -->
        <div id="sp-tab-contact">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
            <div class="form-group"><label>Nume *</label><input type="text" name="sp_name" value="<?= h($edit_sp['name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="sp_email" value="<?= h($edit_sp['email'] ?? '') ?>"></div>
            <div class="form-group"><label>Telefon</label><input type="text" name="sp_phone" value="<?= h($edit_sp['phone'] ?? '') ?>"></div>
        </div>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px">
            <div class="form-group">
                <label>Cursuri susținute</label>
                <?php
                $sp_courses_arr = $edit_sp['courses'] ?? [];
                if (is_string($sp_courses_arr)) $sp_courses_arr = $sp_courses_arr ? [$sp_courses_arr] : [];
                if (empty($sp_courses_arr)) $sp_courses_arr = [''];
                ?>
                <div id="sp-courses-list" style="display:flex;flex-direction:column;gap:4px">
                <?php foreach ($sp_courses_arr as $sc_val): ?>
                    <div style="display:flex;gap:4px;align-items:center">
                        <input type="text" name="sp_courses[]" value="<?= h($sc_val) ?>" style="flex:1;padding:5px 9px;font-size:12px">
                        <button type="button" onclick="this.closest('div').remove()" style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:0 7px;height:28px;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1">×</button>
                    </div>
                <?php endforeach; ?>
                </div>
                <button type="button" onclick="spAddCourse()" style="margin-top:4px;background:none;border:1px solid #d1d5db;border-radius:6px;padding:2px 8px;cursor:pointer;font-size:11px;color:#6b7280">+ curs</button>
                            </div>
            <div class="form-group"><label>Status</label>
                <select name="sp_status">
                    <?php foreach (['CONTACTAT','RECURENT','MID','NOPE'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($edit_sp['status'] ?? 'MID') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Note</label><textarea name="sp_notes" rows="2"><?= h($edit_sp['notes'] ?? '') ?></textarea></div>
        </div>
        <!-- Tab: Meet -->
        <div id="sp-tab-meet" style="display:none">
        <?php
        $mf = ['auzit'=>'Cum ai auzit de Cursuri la Pahar?','ocupatie'=>'Cu ce te ocupi?','pasiune'=>'Ce te pasionează cel mai mult la subiectul ăsta și crezi că ar fi valoros pentru oameni?','teme'=>'Ai mai avea alte idei de teme?','dinamica'=>'Cum vezi tu dinamica cu publicul? Cum ți-ar plăcea să arate?','experienta'=>'Unde ai mai ținut cursuri și cum s-au desfășurat? Ai vreo prezentare pe care ai folosit-o?','contract'=>'Contract (prezentare, durata, onorariu)','curiozitati'=>'Curiozități?','program'=>'Program pe perioada următoare'];
        ?>
        <?php foreach ($mf as $k => $lbl): ?>
        <div class="form-group">
            <label><?= h($lbl) ?></label>
            <textarea name="meet_<?= $k ?>" rows="2"><?= h($edit_sp['meet'][$k] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;margin-top:16px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $edit_sp ? 'Salvează' : 'Adaugă speakerul' ?></button>
            <a href="/admin/?tab=speakeri" class="btn btn-secondary btn-sm">Anulează</a>
        </div>
    </form>
</div>
</div>

<!-- Status quick-change popover -->
<div id="sp-status-pop" class="sp-status-popover" style="display:none">
<?php foreach (['CONTACTAT'=>'#2271b1','RECURENT'=>'#16a34a','MID'=>'#d97706','NOPE'=>'#dc2626'] as $_ss=>$_sc): ?>
<button onclick="spSetStatus('<?= $_ss ?>')" style="color:<?= $_sc ?>"><?= $_ss ?></button>
<?php endforeach; ?>
</div>


<?php /* ======================================================= TAB: LOCATII */ ?>
<?php elseif ($tab === 'locatii'): ?>
<?php
$_loc = clp_locations_admin_context($_GET['edit'] ?? '');
$locations = $_loc['items'];
$edit_loc = $_loc['edit'];
require __DIR__ . '/partials/locatii-tab.php';
?>

<?php /* ======================================================= TAB: COLABORARI */ ?>
<?php /* ======================================================= TAB: COLABORARI */ ?>
<?php elseif ($tab === 'colaborari'): ?>
<?php
$_col = clp_collaborations_admin_context($_GET['edit'] ?? '');
$collabs = $_col['items'];
$edit_col = $_col['edit'];
require __DIR__ . '/partials/colaborari-tab.php';
?>

<?php /* Securitate tab redirects to config */ ?>
<?php /* Securitate tab redirects to config */ ?>
<?php elseif ($tab === 'securitate'): ?>
<?php header('Location: /admin/?tab=config'); exit; ?>

<?php /* ======================================================= TAB: CONFIG (Setări) */ ?>
<?php elseif ($tab === 'config'): ?>
<h1 class="wp-page-title">Setări</h1>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Setările au fost salvate.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="notice notice-error">Parolele nu coincid sau sunt prea scurte (minim 6 caractere).</div>
<?php endif; ?>
<?php if (isset($_GET['imported'])): ?>
<div class="notice notice-success">Import reușit! <?= (int)$_GET['imported'] ?> imagini descărcate.</div>
<?php endif; ?>

<!-- Quick links editor (Owner only) -->
<div class="card">
    <div class="card-title">🔗 Linkuri rapide — Dashboard</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">Aceste linkuri apar ca butoane în partea de sus a dashboard-ului.</p>
    <form method="post" action="/admin/?tab=config" id="qlForm">
        <input type="hidden" name="action" value="save_quick_links">
        <div id="qlRows" style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
        <?php foreach ($settings['quick_links'] ?? [] as $idx => $_ql): ?>
            <div class="ql-row" style="display:grid;grid-template-columns:60px 1fr 3fr auto;gap:8px;align-items:center">
                <input type="text" name="ql_icon[]" value="<?= h($_ql['icon'] ?? '🔗') ?>" style="text-align:center;font-size:18px">
                <input type="text" name="ql_label[]" value="<?= h($_ql['label'] ?? '') ?>">
                <input type="text" name="ql_url[]" value="<?= h($_ql['url'] ?? '') ?>">
                <button type="button" onclick="this.closest('.ql-row').remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">✕</button>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" onclick="addQlRow()" class="btn btn-secondary btn-sm">+ Adaugă link</button>
            <button type="submit" class="btn btn-primary btn-sm">Salvează</button>
        </div>
    </form>
</div>


<form method="post" action="/admin/?tab=config">
    <input type="hidden" name="action" value="save_kit">
    <div class="card">
        <div class="card-title">📧 Kit (Email Marketing)</div>
        <div class="form-group">
            <label>API Key</label>
            <input type="text" name="kit_api_key" value="<?= h($settings['kit_api_key'] ?? '') ?>" placeholder="kit_...">
            <p class="form-desc">Găsești API Key-ul în <a href="https://app.kit.com/account_settings/developer_settings" target="_blank" style="color:var(--accent)">Kit → Settings → Developer</a>.</p>
        </div>
        <div class="form-group">
            <label>Form ID (opțional)</label>
            <input type="text" name="kit_form_id" value="<?= h($settings['kit_form_id'] ?? '') ?>" placeholder="ex: 1234567">
            <p class="form-desc">Dacă vrei să adaugi abonații la un form specific. Lasă gol pentru a adăuga direct ca subscriber.</p>
        </div>
        <button type="submit" class="btn btn-primary">Salvează</button>
    </div>
</form>

<!-- Analytics -->
<form method="post" action="/admin/?tab=config">
    <input type="hidden" name="action" value="save_head_scripts">
    <div class="card">
        <div class="card-title">📊 Analytics &amp; Tracking</div>
        <div class="form-group">
            <label>Cod <code>&lt;head&gt;</code></label>
            <textarea name="head_scripts" rows="10" style="font-family:monospace;font-size:12px;line-height:1.7"><?= htmlspecialchars($settings['head_scripts'] ?? '') ?></textarea>
            <p class="form-desc">
                Lipește aici codul de tracking pentru <strong>Umami</strong>, <strong>Google Analytics (GA4)</strong> sau orice alt script.
                Va fi inserat automat în <code>&lt;head&gt;</code> pe <strong>toate paginile</strong> site-ului.<br>
                <span style="color:#d63638">⚠ Codul este inserat fără filtrare — adaugă doar scripturi de încredere.</span>
            </p>
        </div>
        <button type="submit" class="btn btn-primary">Salvează</button>
    </div>
</form>

<!-- Schimba parola -->
<div class="card">
    <div class="card-title">🔒 Schimbă parola de admin</div>
    <form method="post" action="/admin/?tab=config" style="max-width:400px">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
            <label for="new_password">Parolă nouă</label>
            <input type="password" id="new_password" name="new_password" placeholder="Minim 6 caractere" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmă parola</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repetă parola" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Schimbă parola</button>
    </form>
    <p class="form-desc" style="margin-top:12px">Parola este salvată în <code>data/settings.json</code> și nu apare nicăieri în cod sau Git.</p>
</div>

<!-- Sync token (pentru sync.sh local) -->
<div class="card">
    <div class="card-title">🔄 Sync Token</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
        Folosit de scriptul <code>./sync.sh</code> pentru a sincroniza datele din producție în mediul local.
        Pune valoarea într-un fișier <code>.sync-token</code> în root-ul proiectului local.
    </p>
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
        <input type="text" id="sync_token_input" value="<?= h($settings['sync_token'] ?? '') ?>" readonly style="font-family:monospace;font-size:12px;flex:1">
        <button type="button" class="btn btn-secondary btn-sm" onclick="copySyncToken()">Copiază</button>
        <form method="post" action="/admin/?tab=config" style="margin:0" onsubmit="return confirm('Regenerezi tokenul? Va trebui să-l actualizezi local.')">
            <input type="hidden" name="action" value="regenerate_sync_token">
            <button type="submit" class="btn btn-secondary btn-sm">Regenerează</button>
        </form>
    </div>
    <p class="form-desc" style="margin:0">Conținut <code>.sync-token</code>:</p>
    <pre style="background:#f5f5f5;padding:10px;border-radius:4px;font-size:12px;margin:6px 0 0;user-select:all">SYNC_URL=https://cursurilapahar.ro/admin/sync-export.php
SYNC_TOKEN=<?= h($settings['sync_token'] ?? '') ?></pre>
</div>

<?php endif; ?>

    </main>
</div><!-- /wp-layout -->

<script src="/admin/assets/js/admin-common.js?v=2"></script>
<?php if ($tab === 'cursuri'): ?>
<script src="/admin/assets/js/admin-course-form.js?v=1"></script>
<?php elseif ($tab === 'mesaje'): ?>
<script>window.CLP_IS_OWNER = <?= is_owner() ? 'true' : 'false' ?>;</script>
<script src="/admin/assets/js/admin-mesaje.js?v=1"></script>
<?php elseif ($tab === 'speakeri'): ?>
<script src="/admin/assets/js/admin-speakeri.js?v=1"></script>
<?php elseif ($tab === 'aspect'): ?>
<script src="/assets/js/coloris.min.js"></script>
<script src="/admin/assets/js/admin-aspect.js?v=1"></script>
<?php endif; ?>

<?php endif; ?>
</body>
</html>
