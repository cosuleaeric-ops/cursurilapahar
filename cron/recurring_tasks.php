<?php
/**
 * Daily job: add owner-defined monthly recurring tasks to the assignee's to-dos.
 *
 * Cron (CLI):  php cron/recurring_tasks.php
 * Test (web, must be admin):  /cron/recurring_tasks.php?date=2026-06-16
 *
 * Idempotent: each task fires once per calendar day (state file).
 */
declare(strict_types=1);

$ROOT = dirname(__DIR__);
require_once $ROOT . '/lib/settings.php';
require_once $ROOT . '/lib/auth.php';
require_once $ROOT . '/lib/todos.php';
require_once $ROOT . '/lib/recurring.php';

$is_cli = (PHP_SAPI === 'cli');
if (!$is_cli && !is_authenticated()) {
    http_response_code(403);
    exit('Forbidden');
}

$tz  = new DateTimeZone('Europe/Bucharest');
$now = new DateTimeImmutable('now', $tz);

$dateStr = (!$is_cli && isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['date']))
    ? (string)$_GET['date']
    : $now->format('Y-m-d');
$day = (int)substr($dateStr, 8, 2);

$state = is_file(RECURRING_STATE_FILE)
    ? (json_decode((string)file_get_contents(RECURRING_STATE_FILE), true) ?: [])
    : [];

$added = [];
foreach (clp_recurring_monthly() as $t) {
    $days = array_map('intval', $t['days'] ?? []);
    if (!in_array($day, $days, true)) continue;

    $title = trim((string)($t['title'] ?? ''));
    if ($title === '') continue;

    $key = ($t['id'] ?? '') . '|' . $dateStr;
    if (in_array($key, $state, true)) continue;

    clp_add_todo($title, (string)($t['assigned_to'] ?? 'eric6'), 'system');
    $state[] = $key;
    $added[] = $title;
}

file_put_contents(RECURRING_STATE_FILE, json_encode(array_values($state), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

$msg = $added
    ? ('Taskuri recurente adăugate: ' . implode(' | ', $added))
    : ("Niciun task recurent pentru {$dateStr}.");

if (!$is_cli) {
    header('Content-Type: text/plain; charset=utf-8');
}
echo $msg . ($is_cli ? "\n" : '');
