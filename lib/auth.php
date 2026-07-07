<?php

function clp_users_file(): string {
    return dirname(__DIR__) . '/data/users.json';
}

function get_auth_secret(): string {
    static $s = null;
    if ($s === null) {
        $file = clp_settings_file();
        $s = file_exists($file)
            ? (json_decode(file_get_contents($file), true) ?: [])
            : [];
    }
    return $s['auth_secret'] ?? '';
}

function load_users(): array {
    $file = clp_users_file();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_users(array $users): void {
    file_put_contents(clp_users_file(), json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function find_user(string $username): ?array {
    foreach (load_users() as $u) {
        if (($u['username'] ?? '') === $username) return $u;
    }
    return null;
}

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

function clp_current_user(): ?array {
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

function is_owner_auth(): bool {
    return (clp_real_user()['role'] ?? '') === 'owner';
}

function can_access_tab(string $tab): bool {
    $user = clp_current_user();
    if (!$user) return false;
    if (($user['role'] ?? '') === 'owner') return true;
    return in_array($tab, ['dashboard', 'mesaje', 'vot', 'competitori', 'speakeri', 'locatii', 'colaborari', 'imagini', 'aspect', 'cursuri', 'templates', 'cursuri-posibile'], true);
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

function clp_ensure_secrets(): void {
    $file = clp_settings_file();
    $settings = file_exists($file)
        ? (json_decode(file_get_contents($file), true) ?: [])
        : [];
    $changed = false;
    if (empty($settings['auth_secret']))    { $settings['auth_secret']    = bin2hex(random_bytes(32)); $changed = true; }
    if (empty($settings['webhook_secret'])) { $settings['webhook_secret'] = bin2hex(random_bytes(32)); $changed = true; }
    if (empty($settings['sync_token']))     { $settings['sync_token']     = bin2hex(random_bytes(32)); $changed = true; }
    if ($changed) {
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}

function clp_ensure_default_users(): void {
    $file = clp_users_file();
    if (file_exists($file)) return;
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode([
        ['username' => 'eric6', 'password_hash' => '$2y$12$2dWGrc.k7sizuCBC18huu.XgqNkCgfVZ0DCaDS1kZQOFIDzgfLRPC', 'role' => 'owner'],
        ['username' => 'andy',  'password_hash' => '$2y$12$uxs/.33puwE3AmeCbilyve6t33qF3JXeaiObwDSiADFATmxQYzBvq',  'role' => 'city_manager'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/** @return ?string Login error message, or null on success (redirects on success/logout) */
function clp_process_auth_request(): ?string {
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
        }
        return 'Utilizator sau parolă incorecte.';
    }
    if (isset($_GET['logout'])) {
        clear_auth_cookie();
        header('Location: /admin/');
        exit;
    }
    return null;
}

function csrf_token(): string {
    return hash_hmac('sha256', 'csrf_clp_token', get_auth_secret());
}

function verify_csrf(string $token): bool {
    if (!$token) return false;
    return hash_equals(csrf_token(), $token);
}
