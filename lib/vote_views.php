<?php

require_once __DIR__ . '/course_clicks.php';

function clp_vote_views_file(): string
{
    return dirname(__DIR__) . '/data/vote_views.json';
}

/** @return array<string, int> */
function clp_load_vote_views(): array
{
    $file = clp_vote_views_file();
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }
    $out = [];
    foreach ($data as $id => $count) {
        if ($id === '__page__' || !is_string($id) || !is_numeric($count)) {
            continue;
        }
        $out[$id] = (int) $count;
    }
    return $out;
}

function clp_vote_page_view_count(): int
{
    $file = clp_vote_views_file();
    if (!file_exists($file)) {
        return 0;
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return 0;
    }
    return (int) ($data['__page__'] ?? 0);
}

function clp_increment_vote_page_view(): int
{
    $file = clp_vote_views_file();
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
    $views = $raw !== false && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    $views['__page__'] = (int) ($views['__page__'] ?? 0) + 1;
    $new = $views['__page__'];

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($views, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $new;
}

function clp_vote_view_count(string $vote_id): int
{
    if ($vote_id === '') {
        return 0;
    }
    $views = clp_load_vote_views();
    return (int) ($views[$vote_id] ?? 0);
}

function clp_increment_vote_view(string $vote_id): int
{
    if ($vote_id === '') {
        return 0;
    }

    $file = clp_vote_views_file();
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
    $views = $raw !== false && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    $views[$vote_id] = (int) ($views[$vote_id] ?? 0) + 1;
    $new = $views[$vote_id];

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($views, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $new;
}

function clp_vote_conversion_rate(int $likes, int $views): ?float
{
    if ($likes <= 0 || $views <= 0) {
        return null;
    }
    return round($views / $likes * 100, 1);
}

function clp_format_vote_conversion(int $likes, int $views): string
{
    $rate = clp_vote_conversion_rate($likes, $views);
    if ($rate === null) {
        return '—';
    }
    return number_format($rate, 1, ',', '') . '%';
}

function clp_vote_course_exists(string $vote_id): bool
{
    if ($vote_id === '') {
        return false;
    }
    $file = dirname(__DIR__) . '/data/vote_courses.json';
    if (!file_exists($file)) {
        return false;
    }
    $courses = json_decode(file_get_contents($file), true);
    if (!is_array($courses)) {
        return false;
    }
    foreach ($courses as $course) {
        if (($course['id'] ?? '') === $vote_id && ($course['active'] ?? true)) {
            return true;
        }
    }
    return false;
}
