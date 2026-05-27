<?php

function clp_locations_file(): string {
    return dirname(__DIR__) . '/data/locations.json';
}

function load_locations(): array {
    $file = clp_locations_file();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_locations(array $items): void {
    $file = clp_locations_file();
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/** Lista din tab-ul Locații (data/locations.json) */
function load_locations_for_picker(): array {
    $locations = load_locations();
    usort($locations, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
    return array_values(array_filter($locations, fn($l) => trim($l['name'] ?? '') !== ''));
}

/**
 * @return array{items: list<array>, edit: ?array}
 */
function clp_locations_admin_context(string $edit_id = ''): array {
    $items = load_locations();
    $edit = null;
    if ($edit_id !== '') {
        foreach ($items as $loc) {
            if (($loc['id'] ?? '') === $edit_id) {
                $edit = $loc;
                break;
            }
        }
    }
    return ['items' => $items, 'edit' => $edit];
}
