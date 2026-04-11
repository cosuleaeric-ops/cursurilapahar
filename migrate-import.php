<?php
// Temporary migration import script - DELETE AFTER USE
define('IMPORT_TOKEN', 'clp-migrate-2026');

if (($_GET['token'] ?? '') !== IMPORT_TOKEN) {
    http_response_code(403); die('Forbidden');
}

$source = 'https://robotache.ro/migrate-export.php?token=clp-migrate-2026';
$json = file_get_contents($source);
if (!$json) die('❌ Nu am putut descărca settings.json de pe robotache.ro');

$settings = json_decode($json, true);
if (!$settings) die('❌ JSON invalid');

// Remove secrets from imported settings - will be regenerated
unset($settings['auth_secret'], $settings['webhook_secret']);

// Keep the new password that was already set
$local_file = __DIR__ . '/data/settings.json';
if (file_exists($local_file)) {
    $local = json_decode(file_get_contents($local_file), true) ?: [];
    if (!empty($local['admin_password'])) {
        $settings['admin_password'] = $local['admin_password'];
    }
    if (!empty($local['auth_secret'])) {
        $settings['auth_secret'] = $local['auth_secret'];
    }
    if (!empty($local['webhook_secret'])) {
        $settings['webhook_secret'] = $local['webhook_secret'];
    }
}

// Download all images referenced in settings
$image_paths = [];
array_walk_recursive($settings, function($val) use (&$image_paths) {
    if (is_string($val) && preg_match('#^(/wp-content/|/assets/uploads/)#', $val)) {
        $image_paths[] = $val;
    }
});

$downloaded = 0;
$failed = [];
foreach (array_unique($image_paths) as $path) {
    $local_path = __DIR__ . $path;
    if (file_exists($local_path)) continue;
    $dir = dirname($local_path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $content = file_get_contents('https://robotache.ro' . $path);
    if ($content) {
        file_put_contents($local_path, $content);
        $downloaded++;
    } else {
        $failed[] = $path;
    }
}

// Save settings
file_put_contents($local_file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ settings.json importat cu succes!<br>";
echo "✅ Imagini descărcate: $downloaded<br>";
if ($failed) {
    echo "⚠️ Imagini care nu au putut fi descărcate:<br>";
    foreach ($failed as $f) echo " - $f<br>";
}
echo "<br><strong>Șterge acum migrate-export.php și migrate-import.php de pe ambele servere!</strong>";
