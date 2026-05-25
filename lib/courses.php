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

function clp_statistici_db_path(): string
{
    return dirname(__DIR__) . '/admin/statistici/data/clp.sqlite';
}

/** Sincronizează un curs din courses.json în SQLite (tabelul de statistici). */
function clp_sync_course_to_statistici_db(array $entry): ?int
{
    $path = clp_statistici_db_path();
    if (!file_exists(dirname($path))) {
        return null;
    }
    $ext_id = trim($entry['id'] ?? '');
    $title = trim($entry['title'] ?? '');
    $date_raw = trim($entry['date_raw'] ?? '');
    if ($ext_id === '' || $title === '' || $date_raw === '') {
        return null;
    }

    try {
        $sdb = new SQLite3($path);
        $sdb->exec('PRAGMA foreign_keys = ON;');
        $sdb->exec('PRAGMA journal_mode = WAL;');
        $sdb->exec('CREATE TABLE IF NOT EXISTS courses (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, date TEXT NOT NULL, created_at TEXT NOT NULL);');
        @$sdb->exec('ALTER TABLE courses ADD COLUMN external_id TEXT;');
        @$sdb->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_courses_external_id ON courses(external_id) WHERE external_id IS NOT NULL;');

        $existing = $sdb->querySingle(
            "SELECT id FROM courses WHERE external_id = '" . $sdb->escapeString($ext_id) . "' LIMIT 1",
            true
        );
        if ($existing) {
            $stmt = $sdb->prepare('UPDATE courses SET name = :name, date = :date WHERE external_id = :ext');
            $stmt->bindValue(':name', $title, SQLITE3_TEXT);
            $stmt->bindValue(':date', $date_raw, SQLITE3_TEXT);
            $stmt->bindValue(':ext', $ext_id, SQLITE3_TEXT);
            $stmt->execute();
            $sqlite_id = (int)$existing['id'];
        } else {
            $stmt = $sdb->prepare('INSERT INTO courses (name, date, created_at, external_id) VALUES (:name, :date, :created_at, :ext)');
            $stmt->bindValue(':name', $title, SQLITE3_TEXT);
            $stmt->bindValue(':date', $date_raw, SQLITE3_TEXT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
            $stmt->bindValue(':ext', $ext_id, SQLITE3_TEXT);
            $stmt->execute();
            $sqlite_id = (int)$sdb->lastInsertRowID();
        }
        $sdb->close();
        return $sqlite_id;
    } catch (Exception $e) {
        return null;
    }
}

/** @param array<int, array<string, mixed>> $courses */
function clp_sync_all_courses_to_statistici_db(array $courses): void
{
    foreach ($courses as $c) {
        clp_sync_course_to_statistici_db($c);
    }
}

function clp_delete_statistici_course_by_external_id(string $external_id): void
{
    $external_id = trim($external_id);
    if ($external_id === '' || !file_exists(clp_statistici_db_path())) {
        return;
    }
    try {
        $sdb = new SQLite3(clp_statistici_db_path());
        $sdb->exec('PRAGMA foreign_keys = ON;');
        $sdb->exec("DELETE FROM courses WHERE external_id = '" . $sdb->escapeString($external_id) . "'");
        $sdb->close();
    } catch (Exception $e) {
        /* ignore */
    }
}
