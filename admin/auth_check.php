<?php
/**
 * Shared auth check for admin sub-sections (e.g. Statistici).
 * Provides: is_authenticated(), csrf_token(), verify_csrf()
 *
 * Uses the same HMAC-SHA256 cookie auth as admin/index.php,
 * plus a stateless CSRF implementation derived from the auth secret.
 */
declare(strict_types=1);

define('SETTINGS_FILE_AUTH', dirname(__DIR__) . '/data/settings.json');

function _auth_secret(): string {
    static $s = null;
    if ($s === null) {
        $s = file_exists(SETTINGS_FILE_AUTH)
            ? (json_decode(file_get_contents(SETTINGS_FILE_AUTH), true) ?: [])
            : [];
    }
    return $s['auth_secret'] ?? '';
}

function is_authenticated(): bool {
    $secret = _auth_secret();
    if (!$secret) return false;
    $cookie   = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie) return false;
    $expected = hash_hmac('sha256', 'clp_admin_ok', $secret);
    return hash_equals($expected, $cookie);
}

/**
 * Stateless CSRF: derived from auth_secret so it's consistent across requests
 * without needing PHP sessions. Safe because only someone with the auth cookie
 * (which proves they know the password) can obtain this token.
 */
function csrf_token(): string {
    return hash_hmac('sha256', 'csrf_clp_token', _auth_secret());
}

function verify_csrf(string $token): bool {
    if (!$token) return false;
    return hash_equals(csrf_token(), $token);
}
