<?php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/lib/livetickets.php';

$_lt_settings = file_exists(dirname(__DIR__) . '/data/settings.json')
    ? (json_decode(file_get_contents(dirname(__DIR__) . '/data/settings.json'), true) ?: [])
    : [];
$_lt_secret = $_lt_settings['auth_secret'] ?? '';
$cookie = $_COOKIE['clp_auth'] ?? '';
$_lt_authed = false;
if ($cookie && $_lt_secret && str_contains($cookie, ':')) {
    [$_lt_uname, $_lt_token] = explode(':', $cookie, 2);
    $_lt_expected = hash_hmac('sha256', 'clp_user:' . $_lt_uname, $_lt_secret);
    $_lt_authed = hash_equals($_lt_expected, $_lt_token);
}
if (!$_lt_authed) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$url = trim($body['url'] ?? '');

echo json_encode(lt_fetch_event_by_url($url));
