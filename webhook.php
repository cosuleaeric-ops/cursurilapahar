<?php
define('WEBHOOK_SECRET', 'clp-deploy-secret-2026');
define('REPO',           'cosuleaeric-ops/cursurilapahar');
define('BRANCH',         'main');
define('PUBLIC_HTML',    '/home/lsjcloab/public_html');
define('LOG_FILE',       PUBLIC_HTML . '/deploy.log');
define('THEME_PATH',     'wp-content/themes/cursurilapahar');

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

// Deploy changed files (theme + webhook self-update)
foreach ($changed as $file) {
    $is_theme   = str_starts_with($file, THEME_PATH . '/');
    $is_webhook = ($file === 'webhook.php');
    if (!$is_theme && !$is_webhook) continue;

    $content = file_get_contents($base_url . $file);
    if ($content === false) continue;

    $dest = PUBLIC_HTML . '/' . $file;
    $dir  = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dest, $content);
    $updated++;
}

// Remove deleted theme files
foreach ($removed as $file) {
    if (!str_starts_with($file, THEME_PATH . '/')) continue;
    $dest = PUBLIC_HTML . '/' . $file;
    if (file_exists($dest)) unlink($dest);
}

file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Deploy OK ({$updated} files)\n", FILE_APPEND);
