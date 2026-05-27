<?php
declare(strict_types=1);

require_once __DIR__ . '/dates.php';

/**
 * Reguli cursuri (obligatorii):
 * - Fără link LiveTickets → nu e activ, nu apare pe site.
 * - Pe site: doar active + link.
 * - Speaker: denormalizat în speaker_name (admin / statistici).
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

function clp_get_statistici_db(): SQLite3
{
    require_once dirname(__DIR__) . '/admin/statistici/db.php';
    return get_clp_db();
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

function clp_resolve_course_date_raw(array $course): string
{
    $raw = trim($course['date_raw'] ?? '');
    if ($raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }
    $display = trim($course['date_display'] ?? '');
    if ($display !== '') {
        $ts = strtotime($display);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
    }
    return '';
}

function clp_course_date_short(array $course): string
{
    return clp_format_date_ro(clp_resolve_course_date_raw($course), false, false);
}

function clp_course_datetime_label(array $course): string
{
    $date = clp_course_date_short($course);
    $time = trim($course['time'] ?? '');
    if ($date !== '' && $time !== '') {
        return "$date, $time";
    }
    return $date !== '' ? $date : $time;
}

function clp_course_title_for_card(array $course): string
{
    $title = trim($course['title'] ?? '');
    if ($title === '') {
        return '';
    }
    $stripped = preg_replace('/\s+\/\/\s+.+$/u', '', $title);
    return is_string($stripped) && $stripped !== '' ? $stripped : $title;
}

function clp_month_date_prefix(int $year, int $month): string
{
    return $month > 0
        ? $year . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT)
        : (string)$year;
}

/** @return list<string> ID-uri cursuri din courses.json (site + admin). */
function clp_json_course_external_ids(): array
{
    $ids = [];
    foreach (clp_load_courses_from_json() as $c) {
        $id = trim($c['id'] ?? '');
        if ($id !== '') {
            $ids[] = $id;
        }
    }
    return $ids;
}

/** Sincronizează cursurile din JSON pentru luna selectată în SQLite. */
function clp_sync_json_courses_for_month(int $year, int $month): void
{
    $prefix = clp_month_date_prefix($year, $month);

    foreach (clp_load_courses_from_json() as $c) {
        $date_raw = clp_resolve_course_date_raw($c);
        if ($date_raw !== '' && str_starts_with($date_raw, $prefix)) {
            $c['date_raw'] = $date_raw;
            clp_sync_course_to_statistici_db($c);
        }
    }
}

/** Rând vechi din SQLite (aceeași dată) care are bilete/raport/viză — păstrăm statisticile. */
function clp_find_legacy_statistici_course_id(SQLite3 $sdb, string $date_raw, string $exclude_ext_id): ?int
{
    $sql = "SELECT c.id FROM courses c
        WHERE c.date = '" . $sdb->escapeString($date_raw) . "'
        AND (c.external_id IS NULL OR c.external_id = '' OR c.external_id != '" . $sdb->escapeString($exclude_ext_id) . "')
        AND (
            EXISTS (SELECT 1 FROM course_reports r WHERE r.course_id = c.id)
            OR EXISTS (SELECT 1 FROM tickets t WHERE t.course_id = c.id)
            OR EXISTS (SELECT 1 FROM course_files f WHERE f.course_id = c.id AND f.file_type = 'viza')
        )
        ORDER BY c.id ASC LIMIT 1";
    $row = $sdb->querySingle($sql, true);
    return $row ? (int)$row['id'] : null;
}

function clp_statistici_course_has_data(SQLite3 $sdb, int $course_id): bool
{
    $n = (int)$sdb->querySingle(
        "SELECT (
            (SELECT COUNT(*) FROM course_reports r WHERE r.course_id = " . (int)$course_id . ")
            + (SELECT COUNT(*) FROM tickets t WHERE t.course_id = " . (int)$course_id . ")
            + (SELECT COUNT(*) FROM course_files f WHERE f.course_id = " . (int)$course_id . " AND f.file_type = 'viza')
        )"
    );
    return $n > 0;
}

/** Șterge duplicate goale (fără bilete/raport/viză) lăsate de sync-uri vechi. */
function clp_remove_empty_statistici_duplicates(SQLite3 $sdb, int $keep_id, string $ext_id, string $date_raw): void
{
    $sdb->exec("DELETE FROM courses WHERE id != " . (int)$keep_id . "
        AND NOT EXISTS (SELECT 1 FROM course_reports r WHERE r.course_id = courses.id)
        AND NOT EXISTS (SELECT 1 FROM tickets t WHERE t.course_id = courses.id)
        AND NOT EXISTS (SELECT 1 FROM course_files f WHERE f.course_id = courses.id)
        AND (
            external_id = '" . $sdb->escapeString($ext_id) . "'
            OR (date = '" . $sdb->escapeString($date_raw) . "' AND (external_id IS NULL OR external_id = ''))
        )");
}

/** Sincronizează un curs din courses.json în SQLite (tabelul de statistici). */
function clp_sync_course_to_statistici_db(array $entry): ?int
{
    $ext_id = trim($entry['id'] ?? '');
    $title = trim($entry['title'] ?? '');
    $date_raw = clp_resolve_course_date_raw($entry);
    if ($ext_id === '' || $title === '' || $date_raw === '') {
        return null;
    }

    try {
        $sdb = clp_get_statistici_db();

        $existing = $sdb->querySingle(
            "SELECT id FROM courses WHERE external_id = '" . $sdb->escapeString($ext_id) . "' LIMIT 1",
            true
        );
        $sqlite_id = $existing ? (int)$existing['id'] : null;

        if ($sqlite_id === null) {
            $legacy_id = clp_find_legacy_statistici_course_id($sdb, $date_raw, $ext_id);
            if ($legacy_id !== null) {
                $sqlite_id = $legacy_id;
            }
        } elseif (!clp_statistici_course_has_data($sdb, $sqlite_id)) {
            $legacy_id = clp_find_legacy_statistici_course_id($sdb, $date_raw, $ext_id);
            if ($legacy_id !== null && $legacy_id !== $sqlite_id) {
                $sdb->exec("UPDATE courses SET external_id = NULL WHERE id = " . (int)$sqlite_id);
                $sqlite_id = $legacy_id;
            }
        }

        if ($sqlite_id !== null) {
            $stmt = $sdb->prepare('UPDATE courses SET name = :name, date = :date, external_id = :ext WHERE id = :id');
            $stmt->bindValue(':name', $title, SQLITE3_TEXT);
            $stmt->bindValue(':date', $date_raw, SQLITE3_TEXT);
            $stmt->bindValue(':ext', $ext_id, SQLITE3_TEXT);
            $stmt->bindValue(':id', $sqlite_id, SQLITE3_INTEGER);
            $stmt->execute();
        } else {
            $stmt = $sdb->prepare('INSERT INTO courses (name, date, created_at, external_id) VALUES (:name, :date, :created_at, :ext)');
            $stmt->bindValue(':name', $title, SQLITE3_TEXT);
            $stmt->bindValue(':date', $date_raw, SQLITE3_TEXT);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
            $stmt->bindValue(':ext', $ext_id, SQLITE3_TEXT);
            $stmt->execute();
            $sqlite_id = (int)$sdb->lastInsertRowID();
        }

        clp_remove_empty_statistici_duplicates($sdb, $sqlite_id, $ext_id, $date_raw);
        return $sqlite_id;
    } catch (Exception $e) {
        return null;
    }
}

/** @param array<int, array<string, mixed>> $rows */
function clp_dedupe_statistici_course_rows(array $rows): array
{
    $by_date = [];
    $score = static function (array $r): int {
        $s = !empty($r['external_id']) ? 8 : 0;
        if (!empty($r['has_report'])) {
            $s += 4;
        }
        if (!empty($r['has_viza'])) {
            $s += 2;
        }
        $s += min(3, (int)($r['total_tickets'] ?? 0) > 0 ? 3 : 0);
        return $s;
    };
    foreach ($rows as $row) {
        $d = $row['date'] ?? '';
        if ($d === '') {
            continue;
        }
        if (!isset($by_date[$d]) || $score($row) > $score($by_date[$d])) {
            $by_date[$d] = $row;
        }
    }
    $out = array_values($by_date);
    usort($out, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $out;
}

/** Ultima lună cu cursuri în SQLite (pentru navigare implicită). */
function clp_latest_statistici_year_month(): ?array
{
    try {
        $db = clp_get_statistici_db();
        $row = $db->querySingle(
            "SELECT CAST(strftime('%Y', date) AS INTEGER) AS y,
                    CAST(strftime('%m', date) AS INTEGER) AS m
             FROM courses ORDER BY date DESC LIMIT 1",
            true
        );
        if (!$row) {
            return null;
        }
        return ['year' => (int)$row['y'], 'month' => (int)$row['m']];
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Cursuri pentru tabelul de statistici (lună).
 * - din courses.json (external_id)
 * - plus cursuri încărcate lunar în SQLite (viza / raport / bilete), fără duplicate pe dată
 *
 * @return array<int, array<string, mixed>>
 */
function clp_fetch_statistici_courses_for_month(int $year, int $month): array
{
    $json_ids = clp_json_course_external_ids();
    if ($json_ids !== []) {
        clp_sync_json_courses_for_month($year, $month);
    }

    $prefix = clp_month_date_prefix($year, $month);
    $has_stats = "(
        EXISTS (SELECT 1 FROM course_reports r WHERE r.course_id = c.id)
        OR EXISTS (SELECT 1 FROM tickets t WHERE t.course_id = c.id)
        OR EXISTS (SELECT 1 FROM course_files f WHERE f.course_id = c.id AND f.file_type = 'viza')
    )";

    $courses = [];
    try {
        $db = clp_get_statistici_db();
        $visibility = [];
        if ($json_ids !== []) {
            $in_list = implode(',', array_map(
                fn(string $id) => "'" . $db->escapeString($id) . "'",
                $json_ids
            ));
            $visibility[] = "c.external_id IN (" . $in_list . ")";
            $hide_legacy = "NOT EXISTS (
                SELECT 1 FROM courses c2
                WHERE c2.date = c.date AND c2.external_id IN (" . $in_list . ")
            )";
        } else {
            $in_list = '';
            $hide_legacy = '1=1';
        }
        $visibility[] = "((c.external_id IS NULL OR c.external_id = '') AND {$has_stats} AND {$hide_legacy})";

        $sql = "SELECT c.id, c.external_id, c.name, c.date,
            (SELECT COUNT(*) FROM tickets t WHERE t.course_id = c.id) as total_tickets,
            (SELECT filename FROM course_files f WHERE f.course_id = c.id AND f.file_type = 'viza' ORDER BY f.uploaded_at DESC LIMIT 1) as viza_filename,
            (SELECT 1 FROM course_reports r WHERE r.course_id = c.id LIMIT 1) as has_report,
            (SELECT r.total_incasari FROM course_reports r WHERE r.course_id = c.id LIMIT 1) as total_incasari
            FROM courses c
            WHERE c.date LIKE '" . $db->escapeString($prefix) . "%'
            AND (" . implode(' OR ', $visibility) . ")
            ORDER BY c.date DESC";
        $r = $db->query($sql);
        if ($r === false) {
            $r = $db->query("SELECT c.id, c.external_id, c.name, c.date,
                0 as total_tickets, NULL as viza_filename, 0 as has_report, NULL as total_incasari
                FROM courses c
                WHERE c.date LIKE '" . $db->escapeString($prefix) . "%'
                AND (" . implode(' OR ', $visibility) . ")
                ORDER BY c.date DESC");
        }
        if ($r !== false) {
            while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
                $row['has_report'] = (bool)$row['has_report'];
                $row['has_viza'] = (bool)($row['viza_filename'] ?? '');
                unset($row['viza_filename']);
                $courses[] = $row;
            }
        }
    } catch (Exception $e) {
        return [];
    }

    return clp_dedupe_statistici_course_rows($courses);
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
    if ($external_id === '') {
        return;
    }
    try {
        $sdb = clp_get_statistici_db();
        $sdb->exec("DELETE FROM courses WHERE external_id = '" . $sdb->escapeString($external_id) . "'");
    } catch (Exception $e) {
        /* ignore */
    }
}
