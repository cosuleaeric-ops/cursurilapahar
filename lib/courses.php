<?php
declare(strict_types=1);

/**
 * Reguli cursuri (obligatorii):
 * - Fără link LiveTickets → nu e activ, nu apare pe site.
 * - Pe site: doar active + link.
 * - Speaker: denormalizat în speaker_name; afișat în meta card.
 */

function clp_speakers_by_id(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = [];
    $file = dirname(__DIR__) . '/data/speakers.json';
    if (file_exists($file)) {
        foreach (json_decode(file_get_contents($file), true) ?: [] as $sp) {
            $id = $sp['id'] ?? '';
            if ($id !== '') {
                $map[$id] = trim($sp['name'] ?? '');
            }
        }
    }
    return $map;
}

function clp_course_speaker_name(array $course): string
{
    $name = trim($course['speaker_name'] ?? '');
    if ($name !== '') {
        return $name;
    }
    $id = $course['speaker_id'] ?? '';
    return $id !== '' ? (clp_speakers_by_id()[$id] ?? '') : '';
}

function clp_course_has_ticket_link(array $course): bool
{
    return trim($course['livetickets_url'] ?? '') !== '';
}

/** Curs vizibil pe site-ul public (activ + link LiveTickets obligatoriu) */
function clp_course_is_public(array $course): bool
{
    if (empty($course['active']) || !clp_course_has_ticket_link($course)) {
        return false;
    }
    return true;
}

/** Aplică regulile pe un curs; returnează true dacă s-a modificat */
function clp_normalize_course(array &$course): bool
{
    $changed = false;

    if (!clp_course_has_ticket_link($course)) {
        if (!empty($course['active'])) {
            $course['active'] = false;
            $changed = true;
        }
    }

    $speaker_id = trim($course['speaker_id'] ?? '');
    if ($speaker_id !== '') {
        $resolved = clp_speakers_by_id()[$speaker_id] ?? '';
        if ($resolved !== '' && ($course['speaker_name'] ?? '') !== $resolved) {
            $course['speaker_name'] = $resolved;
            $changed = true;
        }
    }

    return $changed;
}

function clp_enforce_course_rules(array &$courses): bool
{
    $changed = false;
    foreach ($courses as &$course) {
        if (clp_normalize_course($course)) {
            $changed = true;
        }
    }
    unset($course);
    return $changed;
}

/** @return array<int, array<string, mixed>> */
function clp_filter_public_courses(array $courses): array
{
    return array_values(array_filter($courses, 'clp_course_is_public'));
}
