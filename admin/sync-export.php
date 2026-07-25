<?php
// ── Sync export endpoint ─────────────────────────────────────────────────────
// Returneaza un bundle JSON cu datele "publice" (settings, courses, vote_courses,
// speakers, locations, collaborations) pentru a sincroniza un mediu local.
// Protejat printr-un token din data/settings.json (sync_token) — auto-generat
// la prima rulare a admin-ului.
// Exclude: users.json (parole).

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

// ── Descarcare binar din admin/statistici/uploads ────────────────────────────
// ?token=...&file=<nume>  ->  intoarce fisierul brut (viza PDF / raport XLSX).
// Doar basename, doar fisiere care exista chiar in uploads/, doar extensiile
// folosite de admin. Fara asta nu se pot muta binarele in Blob.
if (isset($_GET['file'])) {
    $stats_uploads_dir = __DIR__ . '/statistici/uploads';
    $req = (string)$_GET['file'];

    $valid = $req !== ''
        && strpos($req, "\0") === false
        && $req === basename($req)          // taie orice "/" sau "\" si "../"
        && $req !== '.' && $req !== '..'
        && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $req) === 1;

    $ext = $valid ? strtolower(pathinfo($req, PATHINFO_EXTENSION)) : '';
    $types = [
        'pdf'  => 'application/pdf',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls'  => 'application/vnd.ms-excel',
        'txt'  => 'text/plain; charset=utf-8',
    ];
    if (!$valid || !isset($types[$ext])) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo "Bad file name.";
        exit;
    }

    // realpath: fisierul trebuie sa fie chiar in uploads/, nu un symlink in afara
    $real = realpath($stats_uploads_dir . '/' . $req);
    $base = realpath($stats_uploads_dir);
    if ($real === false || $base === false || dirname($real) !== $base || !is_file($real)) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo "Not found.";
        exit;
    }

    header('Content-Type: ' . $types[$ext]);
    header('Content-Length: ' . filesize($real));
    header('Content-Disposition: attachment; filename="' . $req . '"');
    readfile($real);
    exit;
}

$files = [
    'settings'        => 'settings.json',
    'courses'         => 'courses.json',
    'vote_courses'    => 'vote_courses.json',
    'speakers'        => 'speakers.json',
    'locations'       => 'locations.json',
    'collaborations'  => 'collaborations.json',
    'course_ideas'    => 'course_ideas.json',
    'marketing'       => 'marketing_posts.json',
    'recurring'       => 'recurring_tasks.json',
    'ab_button'       => 'ab_button.json',
    'course_clicks'   => 'course_clicks.json',
    'todos'           => 'todos.json',
    'vote_views'      => 'vote_views.json',
];

$bundle = ['exported_at' => date('c')];
foreach ($files as $key => $filename) {
    $path = $data_dir . '/' . $filename;
    $bundle[$key] = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
}

// Mesajele din formulare (log brut + meta cu evaluări/contactați/comentarii).
// Necesare pentru admin-ul nou; bundle-ul e deja protejat de sync_token.
$log_path  = $data_dir . '/messages.log';
$meta_path = $data_dir . '/message_meta.json';
$bundle['messages_log']  = file_exists($log_path) ? file_get_contents($log_path) : '';
$bundle['messages_meta'] = file_exists($meta_path)
    ? (json_decode(file_get_contents($meta_path), true) ?: [])
    : [];

// Postările marcate pe calendarul de cursuri (POSTARE CURSURI)
$ig_path = $data_dir . '/instagram_posts.json';
$bundle['instagram_posts'] = file_exists($ig_path)
    ? (json_decode(file_get_contents($ig_path), true) ?: [])
    : [];

// Lista fișierelor din uploads (nume + mtime) — pt migrarea imaginilor în Blob
$uploads_dir = dirname(__DIR__) . '/assets/images/uploads';
$bundle['uploads_list'] = [];
if (is_dir($uploads_dir)) {
    foreach (scandir($uploads_dir) as $f) {
        if ($f === '.' || $f === '..' || !is_file($uploads_dir . '/' . $f)) continue;
        $bundle['uploads_list'][] = ['name' => $f, 'size' => filesize($uploads_dir . '/' . $f)];
    }
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
        // Fisierele (viza PDF / raport) si subtipurile din viza. Separat, ca o tabela
        // lipsa sa nu darame restul exportului.
        foreach (['course_files' => 'course_files', 'viza_subtips' => 'viza_subtips'] as $key => $table) {
            try {
                $bundle['statistici'][$key] = sync_fetch_all($db, 'SELECT * FROM ' . $table);
            } catch (Throwable $e) {
                $bundle['statistici'][$key] = [];
            }
        }
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

// Fisierele fizice din admin/statistici/uploads (viza PDF, raport XLSX). SQLite tine
// doar numele (course_files.filename / course_reports.filename); binarul se ia separat
// cu ?token=...&file=<name>.
$stats_uploads_dir = __DIR__ . '/statistici/uploads';
$bundle['stats_uploads_list'] = [];
if (is_dir($stats_uploads_dir)) {
    $by_name = [];
    foreach (($bundle['statistici']['course_files'] ?? []) as $cf) {
        if (($cf['filename'] ?? '') === '') continue;
        $by_name[$cf['filename']] = [
            'kind'           => $cf['file_type'] ?? 'viza',
            'course_id'      => (int)$cf['course_id'],
            'course_file_id' => (int)$cf['id'],
            'original_name'  => $cf['original_name'] ?? '',
            'uploaded_at'    => $cf['uploaded_at'] ?? '',
        ];
    }
    foreach (($bundle['statistici']['course_reports'] ?? []) as $cr) {
        if (($cr['filename'] ?? '') === '') continue;
        $by_name[$cr['filename']] = [
            'kind'          => 'raport',
            'course_id'     => (int)$cr['course_id'],
            'report_id'     => (int)$cr['id'],
            'original_name' => $cr['original_name'] ?? '',
            'uploaded_at'   => $cr['uploaded_at'] ?? '',
        ];
    }
    foreach (scandir($stats_uploads_dir) as $f) {
        if ($f === '.' || $f === '..' || $f === '.htaccess') continue;
        $p = $stats_uploads_dir . '/' . $f;
        if (!is_file($p)) continue;
        $entry = ['name' => $f, 'size' => filesize($p), 'mtime' => date('c', filemtime($p))];
        if (isset($by_name[$f])) {
            $entry += $by_name[$f];
        } elseif (preg_match('/^viza_debug_(\d+)\.txt$/', $f, $m)) {
            // text brut salvat la parsarea vizei, legat de curs prin nume
            $entry['kind'] = 'viza_debug';
            $entry['course_id'] = (int)$m[1];
        } else {
            $entry['kind'] = 'orphan'; // fisier fara rand in SQLite
        }
        $bundle['stats_uploads_list'][] = $entry;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
