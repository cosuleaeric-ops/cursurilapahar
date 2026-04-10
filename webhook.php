<?php
define('WEBHOOK_SECRET', 'clp-deploy-secret-2026');
define('REPO',           'cosuleaeric-ops/cursurilapahar');
define('BRANCH',         'main');
$public_html = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/home/lsjcloab/public_html', '/');
define('PUBLIC_HTML', $public_html);
define('LOG_FILE',    $public_html . '/deploy.log');
define('REAL_DIR',    __DIR__);

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

if (!hash_equals($expected, $signature)) { http_response_code(403); die('Forbidden'); }

$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/' . BRANCH) { http_response_code(200); die('Ignored'); }

// Respond immediately
ob_start(); echo 'OK'; $size = ob_get_length();
header('Connection: close'); header('Content-Encoding: none'); header('Content-Length: ' . $size);
http_response_code(200); ob_end_flush(); flush();
ignore_user_abort(true); set_time_limit(60);

// Collect changed files from all commits
$changed = []; $removed = [];
foreach (($data['commits'] ?? []) as $commit) {
    foreach (array_merge($commit['added'] ?? [], $commit['modified'] ?? []) as $f) $changed[] = $f;
    foreach (($commit['removed'] ?? []) as $f) $removed[] = $f;
}
$changed = array_unique($changed);

$updated = 0;
$errors  = [];
$commit_sha = $data['after'] ?? BRANCH;
$base_url = 'https://raw.githubusercontent.com/' . REPO . '/' . $commit_sha . '/';

// Files/prefixes to deploy
$deploy_prefixes = [
    'index.php', 'api/', 'admin/', 'assets/', '.htaccess',
    'sustine-un-curs.php', 'gazduieste-un-curs.php', 'propune-un-parteneriat.php'
];

foreach ($changed as $file) {
    $is_webhook   = ($file === 'webhook.php');
    $is_deployable = false;
    foreach ($deploy_prefixes as $prefix) {
        if ($file === $prefix || str_starts_with($file, $prefix)) {
            $is_deployable = true; break;
        }
    }
    if (!$is_webhook && !$is_deployable) continue;

    $content = @file_get_contents($base_url . $file);
    if ($content === false || strlen($content) === 0) {
        $errors[] = "DOWNLOAD_FAIL: $file";
        continue;
    }

    $dest = PUBLIC_HTML . '/' . $file;
    $dir  = dirname($dest);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            $errors[] = "MKDIR_FAIL: $dir";
            continue;
        }
    }
    $written = @file_put_contents($dest, $content);
    if ($written === false) {
        $errors[] = "WRITE_FAIL: $dest";
    } else {
        $updated++;
    }
}

// Remove deleted deployable files
foreach ($removed as $file) {
    $is_deployable = false;
    foreach ($deploy_prefixes as $prefix) {
        if ($file === $prefix || str_starts_with($file, $prefix)) {
            $is_deployable = true; break;
        }
    }
    if (!$is_deployable) continue;
    $dest = PUBLIC_HTML . '/' . $file;
    if (file_exists($dest)) @unlink($dest);
}

// Create data dir + empty courses.json ONLY if it doesn't exist yet
$data_dir = PUBLIC_HTML . '/data';
if (!is_dir($data_dir)) {
    @mkdir($data_dir, 0755, true);
    @file_put_contents($data_dir . '/courses.json', '[]');
}

$log = date('Y-m-d H:i:s') . " Deploy OK ({$updated} files) __DIR__=" . REAL_DIR;
if (!empty($errors)) $log .= " | ERRORS: " . implode(', ', $errors);
$log .= "\n";
@file_put_contents(LOG_FILE, $log, FILE_APPEND);
