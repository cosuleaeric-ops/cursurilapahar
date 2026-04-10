<?php
define('WEBHOOK_SECRET', 'clp-deploy-secret-2026');
define('DEPLOY_PATH',    '/home/lsjcloab/public_html/wp-content/themes/cursurilapahar');
define('REPO_ZIP',       'https://github.com/cosuleaeric-ops/cursurilapahar/archive/refs/heads/main.zip');
define('BRANCH',         'main');
define('LOG_FILE',       '/home/lsjcloab/public_html/deploy.log');

// Read payload FIRST (must be before any output)
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Forbidden');
}

$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/' . BRANCH) {
    http_response_code(200);
    die('Ignored');
}

// Close connection immediately so GitHub doesn't timeout
ob_start();
echo 'Deploy started';
$size = ob_get_length();
header('Connection: close');
header('Content-Encoding: none');
header('Content-Length: ' . $size);
http_response_code(200);
ob_end_flush();
flush();
ignore_user_abort(true);
set_time_limit(300);

// --- Deploy work starts here ---

file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Starting deploy...\n", FILE_APPEND);

// Download zip
$zip_content = file_get_contents(REPO_ZIP);
if ($zip_content === false) {
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " ERROR: Could not download zip\n", FILE_APPEND);
    exit;
}

$tmp_zip = tempnam(sys_get_temp_dir(), 'deploy_') . '.zip';
file_put_contents($tmp_zip, $zip_content);

// Extract
$zip = new ZipArchive();
if ($zip->open($tmp_zip) !== true) {
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " ERROR: Could not open zip\n", FILE_APPEND);
    exit;
}
$tmp_dir = sys_get_temp_dir() . '/deploy_' . time();
$zip->extractTo($tmp_dir);
$zip->close();
unlink($tmp_zip);

// Source is the theme/ subfolder inside the repo
$src = $tmp_dir . '/cursurilapahar-main/theme';
if (!is_dir($src)) {
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " ERROR: theme/ folder not found in zip\n", FILE_APPEND);
    exit;
}

// Ensure theme directory exists
if (!is_dir(DEPLOY_PATH)) {
    mkdir(DEPLOY_PATH, 0755, true);
}

// Recursive copy
function deploy_copy($src, $dest) {
    foreach (scandir($src) as $item) {
        if ($item === '.' || $item === '..') continue;
        $s = $src . '/' . $item;
        $d = $dest . '/' . $item;
        if (is_dir($s)) {
            if (!is_dir($d)) mkdir($d, 0755, true);
            deploy_copy($s, $d);
        } else {
            copy($s, $d);
        }
    }
}

deploy_copy($src, DEPLOY_PATH);

// Cleanup
function rrmdir($dir) {
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}
rrmdir($tmp_dir);

file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Deploy OK\n", FILE_APPEND);
