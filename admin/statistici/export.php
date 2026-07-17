<?php
declare(strict_types=1);
require __DIR__ . '/../auth_check.php';
require __DIR__ . '/db.php';
if (!is_authenticated()) { http_response_code(401); exit; }

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="statistici-' . date('Y-m-d') . '.json"');

$clp = get_clp_db();

// ── Participanți ─────────────────────────────────────────────────────────────
$res = $clp->query("
    SELECT
        t.participant_name,
        COUNT(DISTINCT t.course_id) AS num_courses,
        COUNT(*) AS total_tickets,
        GROUP_CONCAT(c.name || ' (' || c.date || ')', '|') AS course_list
    FROM tickets t
    JOIN courses c ON c.id = t.course_id
    GROUP BY LOWER(TRIM(t.participant_name))
    ORDER BY num_courses DESC, t.participant_name ASC
");
$participants = [];
while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
    $r['courses'] = array_values(array_unique(explode('|', $r['course_list'] ?? '')));
    unset($r['course_list']);
    $participants[] = $r;
}
$totalUnique  = count($participants);
$returners    = count(array_filter($participants, fn($p) => $p['num_courses'] > 1));
$totalTickets = array_sum(array_column($participants, 'total_tickets'));

// ── Cursuri ──────────────────────────────────────────────────────────────────
$res = $clp->query("
    SELECT c.id, c.name, c.date,
        (SELECT COUNT(*) FROM tickets t WHERE t.course_id = c.id) AS total_tickets,
        r.total_bilete, r.total_incasari
    FROM courses c
    LEFT JOIN course_reports r ON r.course_id = c.id
    ORDER BY c.date DESC
");
$courses = [];
while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
    $courses[] = $r;
}

// ── P&L ──────────────────────────────────────────────────────────────────────
$pnl_path = __DIR__ . '/data/pnl.sqlite';
$pnl_data = ['venituri' => [], 'cheltuieli' => [], 'sumar_lunar' => []];

if (file_exists($pnl_path)) {
    $pnl = new SQLite3($pnl_path);

    $res = $pnl->query("SELECT data, descriere, suma FROM venituri ORDER BY data DESC");
    while ($r = $res->fetchArray(SQLITE3_ASSOC)) $pnl_data['venituri'][] = $r;

    $res = $pnl->query("SELECT data, categorie, suma FROM cheltuieli ORDER BY data DESC");
    while ($r = $res->fetchArray(SQLITE3_ASSOC)) $pnl_data['cheltuieli'][] = $r;

    $res = $pnl->query("
        SELECT
            strftime('%Y-%m', data) AS luna,
            SUM(CASE WHEN tip='venit'     THEN suma ELSE 0 END) AS venituri,
            SUM(CASE WHEN tip='cheltuiala' THEN suma ELSE 0 END) AS cheltuieli
        FROM (
            SELECT data, suma, 'venit' AS tip FROM venituri
            UNION ALL
            SELECT data, suma, 'cheltuiala' AS tip FROM cheltuieli
        )
        GROUP BY luna
        ORDER BY luna DESC
    ");
    while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
        $r['profit'] = round($r['venituri'] - $r['cheltuieli'], 2);
        $pnl_data['sumar_lunar'][] = $r;
    }

    $totalVenituri   = array_sum(array_column($pnl_data['venituri'],   'suma'));
    $totalCheltuieli = array_sum(array_column($pnl_data['cheltuieli'], 'suma'));
    $pnl_data['total_venituri']   = round($totalVenituri, 2);
    $pnl_data['total_cheltuieli'] = round($totalCheltuieli, 2);
    $pnl_data['profit_net']       = round($totalVenituri - $totalCheltuieli, 2);
}

// ── Output ───────────────────────────────────────────────────────────────────
echo json_encode([
    'exportat_la' => date('Y-m-d H:i:s'),
    'participanti' => [
        'total_unici'    => $totalUnique,
        'revin'          => $returners,
        'rata_revenire'  => $totalUnique > 0 ? round($returners / $totalUnique * 100, 1) : 0,
        'total_bilete'   => $totalTickets,
        'lista'          => $participants,
    ],
    'cursuri' => $courses,
    'pnl'     => $pnl_data,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
