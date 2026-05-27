<?php
// ── Sync export endpoint ─────────────────────────────────────────────────────
// Returneaza un bundle JSON cu datele "publice" (settings, courses, vote_courses,
// speakers, locations, collaborations) pentru a sincroniza un mediu local.
// Protejat printr-un token din data/settings.json (sync_token) — auto-generat
// la prima rulare a admin-ului.
// Exclude: users.json (parole), messages.log (privacy).

$data_dir = dirname(__DIR__) . '/data';
$settings_file = $data_dir . '/settings.json';
$settings = file_exists($settings_file)
    ? (json_decode(file_get_contents($settings_file), true) ?: [])
    : [];
$sync_token = $settings['sync_token'] ?? '';

if ($sync_token === '') {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "sync_token not configured. Open the admin UI on this server once to auto-generate it.";
    exit;
}

$provided = $_GET['token'] ?? ($_SERVER['HTTP_X_SYNC_TOKEN'] ?? '');
if (!hash_equals($sync_token, (string)$provided)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Forbidden.";
    exit;
}

// Temporary probe user for automated QA (action=probe_create | probe_delete)
$probe_action = $_GET['action'] ?? '';
if ($probe_action === 'probe_create' || $probe_action === 'probe_delete') {
    require_once dirname(__DIR__) . '/lib/auth.php';
    $probe_user = 'clp_probe';
    if ($probe_action === 'probe_create') {
        $pass  = bin2hex(random_bytes(8));
        $users = array_values(array_filter(
            load_users(),
            fn($u) => ($u['username'] ?? '') !== $probe_user
        ));
        $users[] = [
            'username'      => $probe_user,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role'          => 'city_manager',
        ];
        save_users($users);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['username' => $probe_user, 'password' => $pass], JSON_UNESCAPED_UNICODE);
        exit;
    }
    save_users(array_values(array_filter(
        load_users(),
        fn($u) => ($u['username'] ?? '') !== $probe_user
    )));
    header('Content-Type: text/plain; charset=utf-8');
    echo 'deleted';
    exit;
}

$files = [
    'settings'        => 'settings.json',
    'courses'         => 'courses.json',
    'vote_courses'    => 'vote_courses.json',
    'speakers'        => 'speakers.json',
    'locations'       => 'locations.json',
    'collaborations'  => 'collaborations.json',
];

$bundle = ['exported_at' => date('c')];
foreach ($files as $key => $filename) {
    $path = $data_dir . '/' . $filename;
    $bundle[$key] = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
}

// Strip secrets din settings inainte de a le trimite
if (is_array($bundle['settings'])) {
    foreach (['admin_password','auth_secret','webhook_secret','sync_token'] as $secret_key) {
        unset($bundle['settings'][$secret_key]);
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
