<?php
// One-time repair script — downloads missing assets from GitHub
define('REPAIR_TOKEN', 'clp-repair-2026');
define('GITHUB_TOKEN', '***REMOVED***');
define('REPO',         'cosuleaeric-ops/cursurilapahar');
define('BRANCH',       'main');
define('PUBLIC_HTML',  dirname(__DIR__));

if (($_GET['token'] ?? '') !== REPAIR_TOKEN) {
    http_response_code(403); die('Forbidden');
}

header('Content-Type: text/plain; charset=UTF-8');

$files = [
    'assets/css/style.css',
    'assets/js/main.js',
    'index.php',
    'admin/index.php',
    'api/contact.php',
    'api/newsletter.php',
    '.htaccess',
    'sustine-un-curs.php',
    'gazduieste-un-curs.php',
    'propune-un-parteneriat.php',
    'webhook.php',
];

foreach ($files as $file) {
    $url = 'https://api.github.com/repos/' . REPO . '/contents/' . $file . '?ref=' . BRANCH;
    $ctx = stream_context_create(['http' => [
        'header' => implode("\r\n", [
            'Authorization: token ' . GITHUB_TOKEN,
            'User-Agent: CLP-Repair',
            'Accept: application/vnd.github.v3.raw',
        ]),
    ]]);
    $content = @file_get_contents($url, false, $ctx);
    if ($content === false || strlen($content) === 0) {
        echo "SKIP: $file (download failed)\n";
        continue;
    }
    $dest = PUBLIC_HTML . '/' . $file;
    $dir  = dirname($dest);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            echo "FAIL: cannot create dir $dir\n";
            continue;
        }
    }
    if (file_put_contents($dest, $content) === false) {
        echo "FAIL: cannot write $dest\n";
    } else {
        echo "OK:   $file\n";
    }
}

echo "\nDone.\n";
