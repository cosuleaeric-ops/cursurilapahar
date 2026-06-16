<?php
/**
 * Daily job: after a course finishes, add follow-up tasks to Andy.
 *
 * Recurring (cron, CLI): processes courses that took place YESTERDAY.
 *   php cron/andy_course_tasks.php
 *
 * Manual/test (web, must be logged in as admin):
 *   /cron/andy_course_tasks.php?last=1     -> last past course
 *   /cron/andy_course_tasks.php?date=2026-06-04
 *
 * Idempotent: each course id is processed only once (state file).
 */
declare(strict_types=1);

$ROOT = dirname(__DIR__);
require_once $ROOT . '/lib/settings.php';
require_once $ROOT . '/lib/auth.php';
require_once $ROOT . '/lib/speakers.php';
require_once $ROOT . '/lib/courses.php';
require_once $ROOT . '/lib/courses_admin.php';
require_once $ROOT . '/lib/todos.php';
require_once $ROOT . '/lib/recurring.php';

$is_cli = (PHP_SAPI === 'cli');

// Web access requires an authenticated admin session.
if (!$is_cli && !is_authenticated()) {
    http_response_code(403);
    exit('Forbidden');
}

$tz = new DateTimeZone('Europe/Bucharest');

// Resolve target: ?last=1 (most recent past course) or ?date=Y-m-d, default = yesterday.
$want_last = !$is_cli && isset($_GET['last']);
$date = null;
if ($is_cli) {
    foreach ($argv as $a) {
        if (preg_match('/^--date=(\d{4}-\d{2}-\d{2})$/', $a, $m)) $date = $m[1];
        if ($a === '--last') $want_last = true;
    }
} elseif (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['date'])) {
    $date = (string)$_GET['date'];
}
if ($date === null && !$want_last) {
    $date = (new DateTimeImmutable('yesterday', $tz))->format('Y-m-d');
}

$today = (new DateTimeImmutable('now', $tz))->format('Y-m-d');

$courses = clp_load_courses_for_admin();

if ($want_last) {
    $past = array_filter($courses, fn($c) => ($c['date_raw'] ?? '') !== '' && ($c['date_raw'] ?? '') < $today);
    usort($past, fn($a, $b) => strcmp($b['date_raw'] ?? '', $a['date_raw'] ?? ''));
    $matches = $past ? [$past[0]] : [];
} else {
    $matches = array_values(array_filter($courses, fn($c) => ($c['date_raw'] ?? '') === $date));
}

// State (server-side only, gitignored) to avoid duplicates.
$state_file = $ROOT . '/data/andy_course_tasks_done.json';
$done = is_file($state_file) ? (json_decode((string)file_get_contents($state_file), true) ?: []) : [];

$clean_title = function (string $t): string {
    $t = preg_replace('/^\s*Curs la Pahar\s*[-–]\s*/u', '', $t);
    $t = preg_replace('#\s*//.*$#u', '', $t);
    return trim($t);
};

$added = [];
foreach ($matches as $c) {
    $cid = (string)($c['id'] ?? md5(($c['title'] ?? '') . ($c['date_raw'] ?? '')));
    if (in_array($cid, $done, true)) continue;

    $curs    = $clean_title((string)($c['title'] ?? ''));
    $speaker = trim(clp_course_speaker_name($c));
    $sp      = $speaker !== '' ? $speaker : '[speaker]';

    // Titles come from the editable recurring store (system "post_course" tasks).
    foreach (clp_recurring_post_course() as $tpl) {
        $title = str_replace(['{curs}', '{speaker}'], [$curs, $sp], $tpl['title']);
        clp_add_todo($title, $tpl['assigned_to'] !== '' ? $tpl['assigned_to'] : 'andy', 'system');
    }
    $done[] = $cid;
    $added[] = $curs . ($speaker !== '' ? " (speaker: {$speaker})" : ' (fără speaker)');
}

file_put_contents($state_file, json_encode(array_values($done), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

// Safety net: also queue "publish on partner sites" tasks for newly-linked courses.
$published = clp_process_course_publish_tasks();
if ($published) $added = array_merge($added, array_map(fn($n) => "$n (publicare)", $published));

$target = $want_last ? 'ultimul curs' : $date;
$msg = $added
    ? ("Taskuri adăugate lui Andy pentru: " . implode(' | ', $added))
    : ("Niciun curs nou de procesat pentru {$target}.");

if ($is_cli) {
    echo $msg . "\n";
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
}
