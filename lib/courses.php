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

/** @return array<int, array<string, mixed>> */
function clp_load_courses_from_json(): array
{
    $file = dirname(__DIR__) . '/data/courses.json';
    if (!file_exists($file)) {
        return [];
    }
    return json_decode((string)file_get_contents($file), true) ?: [];
}

function clp_ensure_statistici_db(SQLite3 $sdb): void
{
    $sdb->exec('PRAGMA foreign_keys = ON;');
    $sdb->exec('PRAGMA journal_mode = WAL;');
    $sdb->exec('CREATE TABLE IF NOT EXISTS courses (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, date TEXT NOT NULL, created_at TEXT NOT NULL);');
    @$sdb->exec('ALTER TABLE courses ADD COLUMN external_id TEXT;');
    @$sdb->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_courses_external_id ON courses(external_id) WHERE external_id IS NOT NULL;');
}

/** Curs adăugat/editat manual din admin — singurele care apar în tabelul de statistici. */
function clp_course_has_admin_stats(array $course): bool
{
    return !empty($course['admin_stats']);
}

/** @return list<string> */
function clp_admin_stats_external_ids(): array
{
    $ids = [];
    foreach (clp_load_courses_from_json() as $c) {
        if (clp_course_has_admin_stats($c)) {
            $id = trim($c['id'] ?? '');
            if ($id !== '') {
                $ids[] = $id;
            }
        }
    }
    return $ids;
}

/** Sincronizează un curs din courses.json în SQLite (tabelul de statistici). */
function clp_sync_course_to_statistici_db(array $entry): ?int
{
    if (!clp_course_has_admin_stats($entry)) {
        return null;
    }

    $path = clp_statistici_db_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir)) {
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
        clp_ensure_statistici_db($sdb);

        $existing = $sdb->querySingle(
            "SELECT id FROM courses WHERE external_id = '" . $sdb->escapeString($ext_id) . "' LIMIT 1",
            true
        );

        if ($existing) {
            $stmt = $sdb->prepare('UPDATE courses SET name = :name, date = :date, external_id = :ext WHERE id = :id');
            $stmt->bindValue(':name', $title, SQLITE3_TEXT);
            $stmt->bindValue(':date', $date_raw, SQLITE3_TEXT);
            $stmt->bindValue(':ext', $ext_id, SQLITE3_TEXT);
            $stmt->bindValue(':id', (int)$existing['id'], SQLITE3_INTEGER);
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

/**
 * Cursuri pentru tabelul de statistici (lună) — doar cele adăugate manual din admin.
 *
 * @return array<int, array<string, mixed>>
 */
function clp_fetch_statistici_courses_for_month(int $year, int $month): array
{
    $admin_ids = clp_admin_stats_external_ids();
    if ($admin_ids === []) {
        return [];
    }

    $prefix = $month > 0
        ? $year . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT)
        : (string)$year;

    $path = clp_statistici_db_path();
    if (!file_exists($path)) {
        return [];
    }

    $courses = [];
    try {
        $db = new SQLite3($path);
        $db->exec('PRAGMA journal_mode = WAL;');
        $in_list = implode(',', array_map(
            fn(string $id) => "'" . $db->escapeString($id) . "'",
            $admin_ids
        ));
        $r = $db->query("SELECT c.id, c.external_id, c.name, c.date,
            (SELECT COUNT(*) FROM tickets t WHERE t.course_id = c.id) as total_tickets,
            (SELECT filename FROM course_files f WHERE f.course_id = c.id AND f.file_type = 'viza' ORDER BY f.uploaded_at DESC LIMIT 1) as viza_filename,
            (SELECT 1 FROM course_reports r WHERE r.course_id = c.id LIMIT 1) as has_report
            FROM courses c
            WHERE c.date LIKE '" . $db->escapeString($prefix) . "%'
            AND c.external_id IN (" . $in_list . ")
            ORDER BY c.date DESC");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
            $row['has_report'] = (bool)$row['has_report'];
            $row['has_viza'] = (bool)($row['viza_filename'] ?? '');
            unset($row['viza_filename']);
            $courses[] = $row;
        }
        $db->close();
    } catch (Exception $e) {
        return [];
    }

    return $courses;
}

/** @param array<int, array<string, mixed>> $courses */
function clp_sync_all_courses_to_statistici_db(array $courses): void
{
    foreach ($courses as $c) {
        if (clp_course_has_admin_stats($c)) {
            clp_sync_course_to_statistici_db($c);
        }
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
