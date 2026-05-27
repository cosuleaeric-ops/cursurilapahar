<?php
/**
 * One-time probe: create/delete temporary admin user (city_manager).
 * Protected by sync_token — DELETE this file after use.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/settings.php';
require_once dirname(__DIR__) . '/lib/auth.php';

const CLP_PROBE_USERNAME = 'clp_probe';

$settings   = clp_load_settings();
$sync_token = $settings['sync_token'] ?? '';
$provided   = (string)($_GET['token'] ?? '');

if ($sync_token === '' || !hash_equals($sync_token, $provided)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $pass  = bin2hex(random_bytes(8));
    $users = array_values(array_filter(
        load_users(),
        fn($u) => ($u['username'] ?? '') !== CLP_PROBE_USERNAME
    ));
    $users[] = [
        'username'      => CLP_PROBE_USERNAME,
        'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
        'role'          => 'city_manager',
    ];
    save_users($users);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['username' => CLP_PROBE_USERNAME, 'password' => $pass], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete') {
    save_users(array_values(array_filter(
        load_users(),
        fn($u) => ($u['username'] ?? '') !== CLP_PROBE_USERNAME
    )));
    header('Content-Type: text/plain; charset=utf-8');
    echo 'deleted';
    exit;
}

http_response_code(400);
header('Content-Type: text/plain; charset=utf-8');
echo 'Use action=create or action=delete';
