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

// Statistici + PnL (SQLite) — pentru analiza pe date reale intr-un mediu local
function sync_fetch_all(SQLite3 $db, string $sql): array {
    $out = [];
    $res = $db->query($sql);
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
    return $out;
}
$stats_dir = __DIR__ . '/statistici/data';
$bundle['statistici'] = null;
$bundle['pnl'] = null;
try {
    if (file_exists($stats_dir . '/clp.sqlite')) {
        $db = new SQLite3($stats_dir . '/clp.sqlite', SQLITE3_OPEN_READONLY);
        $bundle['statistici'] = [
            'courses'        => sync_fetch_all($db, 'SELECT * FROM courses'),
            'tickets'        => sync_fetch_all($db, 'SELECT * FROM tickets'),
            'course_reports' => sync_fetch_all($db, 'SELECT * FROM course_reports'),
        ];
        $db->close();
    }
    if (file_exists($stats_dir . '/pnl.sqlite')) {
        $db = new SQLite3($stats_dir . '/pnl.sqlite', SQLITE3_OPEN_READONLY);
        $bundle['pnl'] = [
            'venituri'   => sync_fetch_all($db, 'SELECT * FROM venituri'),
            'cheltuieli' => sync_fetch_all($db, 'SELECT * FROM cheltuieli'),
        ];
        $db->close();
    }
} catch (Exception $e) {
    // bazele lipsesc sau nu pot fi citite — bundle-ul JSON ramane valid
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
