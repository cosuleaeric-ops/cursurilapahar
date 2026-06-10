<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/admin/auth_check.php';
require_once dirname(__DIR__) . '/lib/courses.php';
require_once dirname(__DIR__) . '/lib/dates.php';
require_once dirname(__DIR__) . '/lib/statistici.php';
if (!is_authenticated()) { http_response_code(403); echo json_encode(['error' => 'Unauthorized']); exit; }

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$ro_months = clp_ro_months_list(false);
$prefix = $month > 0
    ? $year . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT)
    : (string)$year;

$db_path = dirname(__DIR__) . '/admin/statistici/data/clp.sqlite';
$courses = clp_fetch_statistici_courses_for_month($year, $month);
foreach ($courses as &$row) {
    $row['date_ro'] = clp_format_date_ro($row['date'], true, false);
}
unset($row);
$ditl_rows = $viza_subtips = [];
$sum_incasari  = 0.0;
$sum_ditl_base = 0.0; // face value of sold tickets minus refunds — DITL base

if (file_exists($db_path) && !empty($courses)) {
    $db = new SQLite3($db_path);
    $db->exec('PRAGMA journal_mode = WAL;');

    $course_ids = implode(',', array_map(fn($c) => (int)$c['id'], $courses));
    $dr = $db->query("SELECT c.id, c.name, c.date, r.total_bilete, r.total_incasari, r.types_json
        FROM courses c JOIN course_reports r ON r.course_id = c.id
        WHERE c.id IN ({$course_ids}) ORDER BY c.date DESC");
    while ($row = $dr->fetchArray(SQLITE3_ASSOC)) {
        $row['date_ro'] = clp_format_date_ro($row['date'], true, false);
        $row['types'] = json_decode($row['types_json'] ?? '[]', true) ?: [];
        unset($row['types_json']);
        $row['ditl_base'] = clp_ditl_base($row['types'], (float)$row['total_bilete']);
        $ditl_rows[] = $row;
        $sum_incasari  += (float)$row['total_incasari'];
        $sum_ditl_base += $row['ditl_base'];
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
        $by_month[$mk] = ['label' => clp_ro_month_label($mn, $year), 'incasari' => 0, 'rows' => []];
    }
    $by_month[$mk]['incasari'] += (float)$r['total_incasari'];
    $types = $r['types'] ?? [];
    unset($r['types']);
    $subtips = $viza_subtips[(int)$r['id']] ?? [];
    foreach ($subtips as &$sub) {
        $sub['vandute'] = clp_vandute_for_tarif($types, (float)$sub['tarif']);
    }
    unset($sub);
    $r['subtips'] = $subtips;
    $by_month[$mk]['rows'][] = $r;
}

echo json_encode([
    'year'         => $year,
    'month'        => $month,
    'month_label'  => clp_ro_month_label($month, $year),
    'courses'      => $courses,
    'sum_incasari'  => $sum_incasari,
    'sum_ditl_base' => $sum_ditl_base,
    'by_month'     => array_values($by_month),
]);
