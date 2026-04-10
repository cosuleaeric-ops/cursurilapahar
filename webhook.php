<?php
define('WEBHOOK_SECRET', 'clp-deploy-secret-2026');
define('REPO',           'cosuleaeric-ops/cursurilapahar');
define('BRANCH',         'main');
define('PUBLIC_HTML',    '/home/lsjcloab/public_html');
define('LOG_FILE',       '/home/lsjcloab/public_html/deploy.log');

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

// Write heartbeat immediately so we know the path works
@file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Deploy started\n", FILE_APPEND);

// Collect changed files
$changed = []; $removed = [];
foreach (($data['commits'] ?? []) as $commit) {
    foreach (array_merge($commit['added'] ?? [], $commit['modified'] ?? []) as $f) $changed[] = $f;
    foreach (($commit['removed'] ?? []) as $f) $removed[] = $f;
}
$changed = array_unique($changed);

$updated = 0;
$errors  = [];
$commit_sha = $data['after'] ?? BRANCH;
$base_url   = 'https://raw.githubusercontent.com/' . REPO . '/' . $commit_sha . '/';

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

    $ctx = stream_context_create(['http' => ['User-Agent' => 'CLP-Deploy']]);
    $content = @file_get_contents($base_url . $file, false, $ctx);
    if ($content === false || strlen($content) === 0) {
        $errors[] = "DL_FAIL:$file";
        continue;
    }

    $dest = PUBLIC_HTML . '/' . $file;
    $dir  = dirname($dest);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        $errors[] = "MKDIR_FAIL:$dir";
        continue;
    }
    $w = @file_put_contents($dest, $content);
    if ($w === false) {
        $errors[] = "WR_FAIL:$dest";
    } else {
        $updated++;
    }
}

// Remove deleted files
foreach ($removed as $file) {
    foreach ($deploy_prefixes as $prefix) {
        if ($file === $prefix || str_starts_with($file, $prefix)) {
            $dest = PUBLIC_HTML . '/' . $file;
            if (file_exists($dest)) @unlink($dest);
            break;
        }
    }
}

// Create data dir if missing
if (!is_dir(PUBLIC_HTML . '/data')) {
    @mkdir(PUBLIC_HTML . '/data', 0755, true);
    @file_put_contents(PUBLIC_HTML . '/data/courses.json', '[]');
}

$log = date('Y-m-d H:i:s') . " Deploy OK ({$updated} files)";
if (!empty($errors)) $log .= ' | ' . implode(', ', $errors);
@file_put_contents(LOG_FILE, $log . "\n", FILE_APPEND);
