<?php

function clp_speakers_file(): string {
    return dirname(__DIR__) . '/data/speakers.json';
}

function load_speakers(): array {
    $file = clp_speakers_file();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_speakers(array $items): void {
    $file = clp_speakers_file();
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function clp_speaker_status_order(): array {
    return ['CONTACTAT' => 0, 'RECURENT' => 1, 'MID' => 2, 'NOPE' => 3];
}

function clp_speaker_status_colors(): array {
    return ['CONTACTAT' => '#2271b1', 'RECURENT' => '#16a34a', 'MID' => '#d97706', 'NOPE' => '#dc2626'];
}

function clp_sort_speakers(array $speakers): array {
    $order = clp_speaker_status_order();
    usort($speakers, function ($a, $b) use ($order) {
        $cmp = ($order[$a['status'] ?? 'MID'] ?? 2) <=> ($order[$b['status'] ?? 'MID'] ?? 2);
        return $cmp !== 0 ? $cmp : strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
    return $speakers;
}

function clp_find_speaker_by_id(string $speaker_id): ?array {
    foreach (load_speakers() as $sp) {
        if (($sp['id'] ?? '') === $speaker_id) {
            return $sp;
        }
    }
    return null;
}

/** Aceeași listă și ordine ca în tab-ul Speakeri (din data/speakers.json) */
function load_speakers_for_picker(): array {
    $speakers = clp_sort_speakers(load_speakers());
    return array_values(array_filter($speakers, fn($s) => trim($s['id'] ?? '') !== '' && trim($s['name'] ?? '') !== ''));
}

/**
 * @return array{speakers: list<array>, edit: ?array}
 */
function clp_speakers_admin_context(string $edit_id = ''): array {
    $speakers = clp_sort_speakers(load_speakers());
    $edit = null;
    if ($edit_id !== '') {
        foreach ($speakers as $sp) {
            if (($sp['id'] ?? '') === $edit_id) {
                $edit = $sp;
                break;
            }
        }
    }
    return ['speakers' => $speakers, 'edit' => $edit];
}
