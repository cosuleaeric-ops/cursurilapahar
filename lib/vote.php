<?php

function clp_vote_courses_file(): string {
    return dirname(__DIR__) . '/data/vote_courses.json';
}

function load_vote_courses(): array {
    $file = clp_vote_courses_file();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_vote_courses(array $courses): void {
    $file = clp_vote_courses_file();
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode(array_values($courses), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function clp_sort_vote_courses(array $courses): array {
    usort($courses, function ($a, $b) {
        $aActive = ($a['active'] ?? true) ? 1 : 0;
        $bActive = ($b['active'] ?? true) ? 1 : 0;
        if ($aActive !== $bActive) return $bActive <=> $aActive;
        return ($b['likes'] ?? 0) <=> ($a['likes'] ?? 0);
    });
    return $courses;
}

/**
 * @return array{courses: list<array>, edit: ?array}
 */
function clp_vote_admin_context(string $edit_id = ''): array {
    $courses = clp_sort_vote_courses(load_vote_courses());
    $edit = null;
    if ($edit_id !== '') {
        foreach ($courses as $vc) {
            if (($vc['id'] ?? '') === $edit_id) {
                $edit = $vc;
                break;
            }
        }
    }
    return ['courses' => $courses, 'edit' => $edit];
}
