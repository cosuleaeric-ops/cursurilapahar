<?php
/**
 * Include this file just before </head> on every page.
 * Outputs head_scripts (analytics/tracking code) from settings.json.
 *
 * Usage:
 *   <?php include __DIR__ . '/includes/head-scripts.php'; ?>
 *
 * Assumes $settings array is already loaded in the calling scope.
 * If not, it loads settings.json itself as a fallback.
 */
if (!isset($settings)) {
    $_hs_file = dirname(__DIR__) . '/data/settings.json';
    $settings = file_exists($_hs_file) ? (json_decode(file_get_contents($_hs_file), true) ?: []) : [];
}
if (!empty($settings['head_scripts'])) {
    echo $settings['head_scripts'] . "\n";
}
