<?php
// ── Sync export endpoint ─────────────────────────────────────────────────────
// Returneaza un bundle JSON cu datele "publice" (settings, courses, vote_courses,
// speakers, locations, collaborations) pentru a sincroniza un mediu local.
// Protejat printr-un token din private/secrets.php (SYNC_TOKEN).
// Exclude: users.json (parole), messages.log (privacy).

if (file_exists(dirname(__DIR__) . '/private/secrets.php')) {
    require dirname(__DIR__) . '/private/secrets.php';
}

if (!defined('SYNC_TOKEN') || SYNC_TOKEN === '') {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "SYNC_TOKEN not configured on this server.";
    exit;
}

$provided = $_GET['token'] ?? ($_SERVER['HTTP_X_SYNC_TOKEN'] ?? '');
if (!hash_equals(SYNC_TOKEN, (string)$provided)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Forbidden.";
    exit;
}

$data_dir = dirname(__DIR__) . '/data';
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

// Strip secrets from settings (parole admin etc, daca exista)
if (is_array($bundle['settings'])) {
    foreach (['admin_password', 'auth_secret', 'webhook_secret'] as $secret_key) {
        unset($bundle['settings'][$secret_key]);
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
