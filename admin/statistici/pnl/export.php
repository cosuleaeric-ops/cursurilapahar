<?php
declare(strict_types=1);
require __DIR__ . '/../../auth_check.php';
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautorizat']);
    exit;
}

$db = new SQLite3(__DIR__ . '/../data/pnl.sqlite');
$db->enableExceptions(true);
$db->busyTimeout(5000);

function dump_all(SQLite3 $db, string $sql): array {
    $out = [];
    $res = $db->query($sql);
    while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
        $out[] = $r;
    }
    return $out;
}

$data = [
    'exportat_la'         => date('c'),
    'venituri'            => dump_all($db, "SELECT id, data, descriere, suma FROM venituri ORDER BY data ASC, id ASC"),
    'cheltuieli'          => dump_all($db, "SELECT id, data, descriere, categorie, suma FROM cheltuieli ORDER BY data ASC, id ASC"),
    'venit_categorii'     => array_column(dump_all($db, "SELECT nume FROM venit_categorii ORDER BY nume"), 'nume'),
    'cheltuiala_categorii'=> array_column(dump_all($db, "SELECT nume FROM cheltuiala_categorii ORDER BY nume"), 'nume'),
];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="pnl-export-' . date('Y-m-d') . '.json"');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
