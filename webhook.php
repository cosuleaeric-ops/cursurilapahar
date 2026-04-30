<?php
// Load webhook secret from settings.json (managed by admin, not in code)
$_wh_settings_file = __DIR__ . '/data/settings.json';
$_wh_settings = file_exists($_wh_settings_file)
    ? (json_decode(file_get_contents($_wh_settings_file), true) ?: [])
    : [];
define('WEBHOOK_SECRET', $_wh_settings['webhook_secret'] ?? '');

define('REPO',        $_wh_settings['webhook_repo']   ?? 'cosuleaeric-ops/cursurilapahar');
define('BRANCH',      $_wh_settings['webhook_branch'] ?? 'main');
define('PUBLIC_HTML', $_wh_settings['webhook_path']   ?? __DIR__);
define('LOG_FILE',    (defined('PUBLIC_HTML') ? PUBLIC_HTML : __DIR__) . '/deploy.log');

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

if (!hash_equals($expected, $signature)) { http_response_code(403); die('Forbidden'); }

$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/' . BRANCH) { http_response_code(200); die('Ignored'); }

ob_start(); echo 'OK'; $size = ob_get_length();
header('Connection: close'); header('Content-Encoding: none'); header('Content-Length: ' . $size);
http_response_code(200); ob_end_flush(); flush();
ignore_user_abort(true); set_time_limit(90);

@file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Deploy started\n", FILE_APPEND);

function gh_get(string $url): string|false {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'CLP-Deploy',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && $body !== false && strlen($body) > 0) return $body;
        return false;
    }
    $ctx = stream_context_create(['http' => ['User-Agent' => 'CLP-Deploy']]);
    return @file_get_contents($url, false, $ctx);
}

$changed = []; $removed = [];
foreach (($data['commits'] ?? []) as $commit) {
    foreach (array_merge($commit['added'] ?? [], $commit['modified'] ?? []) as $f) $changed[] = $f;
    foreach (($commit['removed'] ?? []) as $f) $removed[] = $f;
}
$changed = array_unique($changed);

$updated    = 0;
$errors     = [];
$commit_sha = $data['after'] ?? BRANCH;
$base_url   = 'https://raw.githubusercontent.com/' . REPO . '/' . $commit_sha . '/';

$deploy_prefixes = [
    'index.php', 'api/', 'admin/', 'assets/', '.htaccess', 'private/',
    'prezinta-un-curs.php', 'sustine-un-curs.php', 'gazduieste-un-curs.php', 'propune-un-parteneriat.php',
    'voteaza-cursuri.php',
];

foreach ($changed as $file) {
    // Never overwrite secrets file
    if ($file === 'private/secrets.php') continue;

    $is_webhook    = ($file === 'webhook.php');
    $is_deployable = false;
    foreach ($deploy_prefixes as $prefix) {
        if ($file === $prefix || str_starts_with($file, $prefix)) {
            $is_deployable = true; break;
        }
    }
    if (!$is_webhook && !$is_deployable) continue;

    $content = gh_get($base_url . $file);
    if ($content === false) { $errors[] = "DL_FAIL:$file"; continue; }

    $dest = PUBLIC_HTML . '/' . $file;
    $dir  = dirname($dest);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { $errors[] = "MKDIR_FAIL:$dir"; continue; }
    $w = @file_put_contents($dest, $content);
    if ($w === false) { $errors[] = "WR_FAIL:$dest"; } else { $updated++; }
}

foreach ($removed as $file) {
    if ($file === 'private/secrets.php') continue;
    foreach ($deploy_prefixes as $prefix) {
        if ($file === $prefix || str_starts_with($file, $prefix)) {
            $dest = PUBLIC_HTML . '/' . $file;
            if (file_exists($dest)) @unlink($dest);
            break;
        }
    }
}

if (!is_dir(PUBLIC_HTML . '/data')) {
    @mkdir(PUBLIC_HTML . '/data', 0755, true);
    @file_put_contents(PUBLIC_HTML . '/data/courses.json', '[]');
}
if (!file_exists(PUBLIC_HTML . '/data/vote_courses.json')) {
    $seed = gh_get($base_url . 'data/vote_courses.json');
    if ($seed) @file_put_contents(PUBLIC_HTML . '/data/vote_courses.json', $seed);
}
if (!file_exists(PUBLIC_HTML . '/data/users.json')) {
    $seed = gh_get($base_url . 'data/users.json');
    if ($seed) @file_put_contents(PUBLIC_HTML . '/data/users.json', $seed);
}

$log = date('Y-m-d H:i:s') . " Deploy OK ({$updated} files)";
if (!empty($errors)) $log .= ' | ' . implode(', ', $errors);
@file_put_contents(LOG_FILE, $log . "\n", FILE_APPEND);
