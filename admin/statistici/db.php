<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/lib/dates.php';

function get_clp_db(): SQLite3 {
    $path = __DIR__ . '/data/clp.sqlite';
    $dir  = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $db = new SQLite3($path);
    $db->exec('PRAGMA foreign_keys = ON;');
    $db->exec('PRAGMA journal_mode = WAL;');
    $db->exec('CREATE TABLE IF NOT EXISTS courses (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        name        TEXT NOT NULL,
        date        TEXT NOT NULL,
        created_at  TEXT NOT NULL
    );');
    $db->exec('CREATE TABLE IF NOT EXISTS tickets (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id        INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
        participant_name TEXT NOT NULL
    );');
    $db->exec('CREATE TABLE IF NOT EXISTS course_files (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id     INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
        filename      TEXT NOT NULL,
        original_name TEXT NOT NULL,
        file_type     TEXT NOT NULL DEFAULT \'viza\',
        uploaded_at   TEXT NOT NULL
    );');
    $db->exec('CREATE TABLE IF NOT EXISTS course_reports (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id      INTEGER NOT NULL UNIQUE REFERENCES courses(id) ON DELETE CASCADE,
        total_bilete   REAL NOT NULL DEFAULT 0,
        total_incasari REAL NOT NULL DEFAULT 0,
        filename       TEXT NOT NULL DEFAULT \'\',
        original_name  TEXT NOT NULL DEFAULT \'\',
        uploaded_at    TEXT NOT NULL
    );');
    @$db->exec('ALTER TABLE course_reports ADD COLUMN types_json TEXT NOT NULL DEFAULT \'[]\';');
    @$db->exec('ALTER TABLE courses ADD COLUMN external_id TEXT;');
    @$db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_courses_external_id ON courses(external_id) WHERE external_id IS NOT NULL;');
    $db->exec('CREATE TABLE IF NOT EXISTS viza_subtips (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id  INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
        seria      TEXT NOT NULL,
        tarif      REAL NOT NULL,
        nr_unitati INTEGER NOT NULL,
        de_la      TEXT NOT NULL,
        pana_la    TEXT NOT NULL
    );');
    return $db;
}

if (!function_exists('h')) {
    function h(string $v): string {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

function ro_date(string $date): string {
    return clp_format_date_ro($date, true, false);
}

function parse_viza_subtips(string $text): array {
    $subtips = [];
    $text = preg_replace('/\r\n?/', "\n", $text);

    // Old format: header-anchored (Tariful pe bucată / Seria De la nr. La nr.)
    $pattern = '/Tariful\s+pe\s+buc[aă]t[aă]\s*\(lei\)[^\n]*\s+(\d+)\s+([\d,.]+)\s+[\d,.]+\s+Seria\s+De\s+la\s+nr\.\s+La\s+nr\.[^\n]*\s+([A-Z]+)\s+(\d+)\s+(\d+)/u';
    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $subtips[] = [
                'nr_unitati' => (int)$m[1],
                'tarif'      => (float)str_replace(',', '.', $m[2]),
                'seria'      => trim($m[3]),
                'de_la'      => $m[4],
                'pana_la'    => $m[5],
            ];
        }
        return $subtips;
    }

    // New format: inline rows "Name Count Price Total SERIES FROM - SERIES TO"
    // e.g. "Bilet standard - ONLINE 57 50.00 2,850.00 SSR 0001 - SSR 0057"
    $pattern2 = '/^.+?\s+(\d+)\s+([\d,.]+)\s+[\d,.]+\s+([A-Z]{2,})\s+(\d+)\s+-\s+[A-Z]{2,}\s+(\d+)/mu';
    $seen = [];
    if (preg_match_all($pattern2, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            // Cheia include tariful: două produse pot împărți aceeași serie+de_la
            // (ex. „Bilet student" și „Bilet standard" ambele OHU 0001-...), fără să fie duplicate.
            $key = trim($m[3]) . '_' . $m[4] . '_' . (string)(float)str_replace(',', '.', $m[2]);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $subtips[] = [
                'nr_unitati' => (int)$m[1],
                'tarif'      => (float)str_replace(',', '.', $m[2]),
                'seria'      => trim($m[3]),
                'de_la'      => $m[4],
                'pana_la'    => $m[5],
            ];
        }
    }

    // Fallback: rows where the seria cell wraps across lines in the PDF, causing pana_la
    // to appear separated (possibly after an intervening row in pdftotext -layout output).
    $pattern_partial = '/^.+?\s+(\d+)\s+([\d,.]+)\s+[\d,.]+\s+([A-Z]{2,})\s+(\d+)\s+-\s+[A-Z]{2,}\s*$/mu';
    if (preg_match_all($pattern_partial, $text, $pm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($pm as $m) {
            $seria = trim($m[3][0]);
            $de_la = $m[4][0];
            $key   = $seria . '_' . $de_la . '_' . (string)(float)str_replace(',', '.', $m[2][0]);
            if (isset($seen[$key])) continue;
            // Search the next 400 chars for a standalone 4+ digit number (the pana_la)
            $after = substr($text, $m[0][1] + strlen($m[0][0]), 400);
            if (preg_match('/^\s*(\d{4,})\s*$/m', $after, $nm)) {
                $seen[$key] = true;
                $subtips[] = [
                    'nr_unitati' => (int)$m[1][0],
                    'tarif'      => (float)str_replace(',', '.', $m[2][0]),
                    'seria'      => $seria,
                    'de_la'      => $de_la,
                    'pana_la'    => $nm[1],
                ];
            }
        }
    }

    // Fallback 2: seria range entirely on its own lines around the row body, e.g.
    //   "WAD 0001 - WAD"  /  "Bilet standard - ONLINE 55 50.00 2,750.00"  /  "0055"
    $lines = explode("\n", $text);
    $n = count($lines);
    for ($i = 0; $i < $n; $i++) {
        if (!preg_match('/^\s*([A-Z]{2,})\s+(\d+)\s+-\s+[A-Z]{2,}\s*$/u', $lines[$i], $sm)) continue;
        $seria = trim($sm[1]);
        $de_la = $sm[2];

        // pana_la: standalone number within the next few lines
        $pana = null;
        for ($j = $i + 1; $j < min($i + 4, $n); $j++) {
            if (preg_match('/^\s*(\d{3,})\s*$/', $lines[$j], $nm)) { $pana = $nm[1]; break; }
        }
        if ($pana === null) continue;

        // row body: adjacent line ending in "count price total" without its own seria range
        $row = null;
        foreach ([$i + 1, $i - 1, $i + 2] as $j) {
            if ($j < 0 || $j >= $n) continue;
            $cand = $lines[$j];
            if (preg_match('/^\s*TOTAL\b/iu', $cand)) continue;
            if (preg_match('/[A-Z]{2,}\s+\d+\s*-/u', $cand)) continue;
            if (preg_match('/^.+?\s+(\d+)\s+([\d,.]+)\s+[\d,.]+\s*$/u', $cand, $rm)) { $row = $rm; break; }
        }
        if (!$row) continue;

        // Cheie cu tarif (vezi pattern2): serii partajate între produse nu sunt duplicate.
        $key = $seria . '_' . $de_la . '_' . (string)(float)str_replace(',', '.', $row[2]);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $subtips[] = [
            'nr_unitati' => (int)$row[1],
            'tarif'      => (float)str_replace(',', '.', $row[2]),
            'seria'      => $seria,
            'de_la'      => $de_la,
            'pana_la'    => $pana,
        ];
    }

    return $subtips;
}

function pdf_to_text(string $filepath): string {
    if (!file_exists($filepath)) return '';
    if (!function_exists('escapeshellarg') || !function_exists('shell_exec')) return '';
    $cmd = 'pdftotext -layout ' . escapeshellarg($filepath) . ' -';
    $out = @shell_exec($cmd);
    return $out ?? '';
}

function ticket_distribution(array $tickets): array {
    $nameCounts = [];
    foreach ($tickets as $t) {
        $name = $t['participant_name'];
        $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;
    }
    $groups = [];
    foreach ($nameCounts as $cnt) {
        $groups[$cnt] = ($groups[$cnt] ?? 0) + 1;
    }
    krsort($groups);
    return [
        'total_tickets' => count($tickets),
        'total_orders'  => count($nameCounts),
        'groups'        => $groups,
        'name_counts'   => $nameCounts,
    ];
}
