<?php

function clp_collaborations_file(): string {
    return dirname(__DIR__) . '/data/collaborations.json';
}

function load_collaborations(): array {
    $file = clp_collaborations_file();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_collaborations(array $items): void {
    $file = clp_collaborations_file();
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/**
 * @return array{items: list<array>, edit: ?array}
 */
function clp_collaborations_admin_context(string $edit_id = ''): array {
    $items = load_collaborations();
    $edit = null;
    if ($edit_id !== '') {
        foreach ($items as $col) {
            if (($col['id'] ?? '') === $edit_id) {
                $edit = $col;
                break;
            }
        }
    }
    return ['items' => $items, 'edit' => $edit];
}
