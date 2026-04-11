<?php
// Temporary migration export script - DELETE AFTER USE
define('EXPORT_TOKEN', 'clp-migrate-2026');

if (($_GET['token'] ?? '') !== EXPORT_TOKEN) {
    http_response_code(403); die('Forbidden');
}

$settings_file = __DIR__ . '/data/settings.json';
$settings = file_exists($settings_file) ? file_get_contents($settings_file) : '{}';

header('Content-Type: application/json');
echo $settings;
