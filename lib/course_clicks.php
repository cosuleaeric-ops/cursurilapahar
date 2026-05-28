<?php

function clp_course_clicks_file(): string
{
    return dirname(__DIR__) . '/data/course_clicks.json';
}

/** @return array<string, int> */
function clp_load_course_clicks(): array
{
    $file = clp_course_clicks_file();
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $id => $count) {
        if (is_string($id) && is_numeric($count)) {
            $out[$id] = (int) $count;
        }
    }
    return $out;
}

function clp_course_click_count(string $course_id): int
{
    if ($course_id === '') {
        return 0;
    }
    $clicks = clp_load_course_clicks();
    return (int) ($clicks[$course_id] ?? 0);
}

function clp_increment_course_click(string $course_id): int
{
    if ($course_id === '') {
        return 0;
    }

    $file = clp_course_clicks_file();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $fp = fopen($file, 'c+');
    if (!$fp) {
        return 0;
    }

    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $clicks = $raw !== false && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    $clicks[$course_id] = (int) ($clicks[$course_id] ?? 0) + 1;
    $new = $clicks[$course_id];

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($clicks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $new;
}

function clp_course_go_url(string $course_id): string
{
    return '/go/course.php?id=' . rawurlencode($course_id);
}

function clp_find_course_by_id(string $course_id): ?array
{
    if ($course_id === '') {
        return null;
    }
    require_once __DIR__ . '/courses.php';
    foreach (clp_load_courses_from_json() as $course) {
        if (($course['id'] ?? '') === $course_id) {
            return $course;
        }
    }
    return null;
}
