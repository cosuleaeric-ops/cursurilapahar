<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/admin/auth_check.php';
require_once dirname(__DIR__) . '/lib/courses.php';
if (!is_authenticated()) { http_response_code(403); echo json_encode(['error' => 'Unauthorized']); exit; }

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$ro_months = ['','ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'];
$prefix = $month > 0
    ? $year . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT)
    : (string)$year;

function ro_date_str(string $d, array $m): string {
    if (!$d) return '';
    [$y, $mo, $day] = explode('-', $d . '--');
    return ltrim($day, '0') . ' ' . ($m[(int)$mo] ?? '') . ' ' . $y;
}

$db_path = dirname(__DIR__) . '/admin/statistici/data/clp.sqlite';
$courses = clp_fetch_statistici_courses_for_month($year, $month);
foreach ($courses as &$row) {
    $row['date_ro'] = ro_date_str($row['date'], $ro_months);
}
unset($row);
$ditl_rows = $viza_subtips = [];
$sum_incasari = 0.0;

if (file_exists($db_path) && !empty($courses)) {
    $db = new SQLite3($db_path);
    $db->exec('PRAGMA journal_mode = WAL;');

    $course_ids = implode(',', array_map(fn($c) => (int)$c['id'], $courses));
    $dr = $db->query("SELECT c.id, c.name, c.date, r.total_bilete, r.total_incasari, r.types_json
        FROM courses c JOIN course_reports r ON r.course_id = c.id
        WHERE c.id IN ({$course_ids}) ORDER BY c.date DESC");
    while ($row = $dr->fetchArray(SQLITE3_ASSOC)) {
        $row['date_ro'] = ro_date_str($row['date'], $ro_months);
        $row['types'] = json_decode($row['types_json'] ?? '[]', true) ?: [];
        unset($row['types_json']);
        $ditl_rows[] = $row;
        $sum_incasari += (float)$row['total_incasari'];
    }

    if (!empty($ditl_rows)) {
        $ids = implode(',', array_map(fn($r) => (int)$r['id'], $ditl_rows));
        $vs = $db->query("SELECT * FROM viza_subtips WHERE course_id IN ({$ids}) ORDER BY course_id, tarif DESC");
        while ($row = $vs->fetchArray(SQLITE3_ASSOC)) {
            $viza_subtips[(int)$row['course_id']][] = $row;
        }
    }
    $db->close();
}

// Group DITL by month
$by_month = [];
foreach ($ditl_rows as $r) {
    $mk = substr($r['date'], 0, 7);
    $mn = (int)substr($mk, 5, 2);
    if (!isset($by_month[$mk])) {
        $by_month[$mk] = ['label' => ucfirst($ro_months[$mn]) . ' ' . $year, 'incasari' => 0, 'rows' => []];
    }
    $by_month[$mk]['incasari'] += (float)$r['total_incasari'];
    $r['subtips'] = $viza_subtips[(int)$r['id']] ?? [];
    $by_month[$mk]['rows'][] = $r;
}

echo json_encode([
    'year'         => $year,
    'month'        => $month,
    'month_label'  => ucfirst($ro_months[$month]) . ' ' . $year,
    'courses'      => $courses,
    'sum_incasari' => $sum_incasari,
    'by_month'     => array_values($by_month),
]);
