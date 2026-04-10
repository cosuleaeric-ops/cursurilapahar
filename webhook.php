<?php
define('WEBHOOK_SECRET', 'clp-deploy-secret-2026');
define('REPO',           'cosuleaeric-ops/cursurilapahar');
define('BRANCH',         'main');
define('PUBLIC_HTML',    '/home/lsjcloab/public_html');
define('LOG_FILE',       PUBLIC_HTML . '/deploy.log');
define('GITHUB_TOKEN',   '***REMOVED***');

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
            $is_deployable = true;
            break;
        }
    }
    if (!$is_webhook && !$is_deployable) continue;

    $api_url = 'https://api.github.com/repos/' . REPO . '/contents/' . $file . '?ref=' . $commit_sha;
    $ctx = stream_context_create(['http' => [
        'header' => implode("\r\n", [
            'Authorization: token ' . GITHUB_TOKEN,
            'User-Agent: CLP-Deploy',
            'Accept: application/vnd.github.v3.raw',
        ]),
    ]]);
    $content = @file_get_contents($api_url, false, $ctx);
    if ($content === false || strlen($content) === 0) {
        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " FAIL: $file\n", FILE_APPEND);
        continue;
    }

    $dest = PUBLIC_HTML . '/' . $file;
    $dir  = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dest, $content);
    $updated++;
}

// Remove deleted deployable files
foreach ($removed as $file) {
    $is_deployable = false;
    foreach ($deploy_prefixes as $prefix) {
        if ($file === $prefix || str_starts_with($file, $prefix)) {
            $is_deployable = true;
            break;
        }
    }
    if (!$is_deployable) continue;
    $dest = PUBLIC_HTML . '/' . $file;
    if (file_exists($dest)) unlink($dest);
}

// Create data dir + empty courses.json ONLY if it doesn't exist yet on server
$data_dir = PUBLIC_HTML . '/data';
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
    file_put_contents($data_dir . '/courses.json', '[]');
}

file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Deploy OK ({$updated} files)\n", FILE_APPEND);
 
