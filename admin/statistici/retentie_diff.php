<?php
// Raport comparativ one-off: matching participanti VECHI (LOWER ASCII in SQL)
// vs NOU (cheie normalizata + tokens sortati + merge Levenshtein cu garduri).
// Acceseaza /admin/statistici/retentie_diff.php logat in admin.
require __DIR__ . '/../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }
require_once dirname(__DIR__, 2) . '/lib/statistici.php';

$db_path = clp_statistici_db_path();
$rows = [];
if (file_exists($db_path)) {
    $db = new SQLite3($db_path);
    $db->exec('PRAGMA journal_mode = WAL;');
    $tr = $db->query('SELECT t.participant_name, t.course_id FROM tickets t JOIN courses c ON c.id = t.course_id');
    while ($r = $tr->fetchArray(SQLITE3_ASSOC)) $rows[] = $r;
    $db->close();
}

// ── Algoritmul VECHI: GROUP BY LOWER(TRIM(name)) — LOWER in SQLite e ASCII-only,
//    replicat aici cu strtolower() (tot ASCII-only).
$old = [];
foreach ($rows as $r) {
    $k = strtolower(trim((string)$r['participant_name']));
    $old[$k]['names'][(string)$r['participant_name']] = true;
    $old[$k]['course_ids'][(int)$r['course_id']] = true;
    $old[$k]['tickets'] = ($old[$k]['tickets'] ?? 0) + 1;
}

// ── Algoritmul NOU: aceeasi logica din clp_fetch_participants()
$keyMap = clp_merge_participant_keys(array_values(array_unique(
    array_map(fn($r) => clp_participant_name_key((string)$r['participant_name']), $rows)
)));
$new = [];
foreach ($rows as $r) {
    $k = $keyMap[clp_participant_name_key((string)$r['participant_name'])];
    $ok = strtolower(trim((string)$r['participant_name']));
    $new[$k]['names'][(string)$r['participant_name']] = ($new[$k]['names'][(string)$r['participant_name']] ?? 0) + 1;
    $new[$k]['old_keys'][$ok] = true;
    $new[$k]['course_ids'][(int)$r['course_id']] = true;
    $new[$k]['tickets'] = ($new[$k]['tickets'] ?? 0) + 1;
}

$returning = fn(array $groups) => count(array_filter($groups, fn($g) => count($g['course_ids']) > 1));
$stats = [
    'unici'  => [count($old), count($new)],
    'revin'  => [$returning($old), $returning($new)],
    'bilete' => [count($rows), count($rows)],
];

// Grupurile care s-au UNIT (2+ chei vechi in acelasi grup nou)
$merged = array_filter($new, fn($g) => count($g['old_keys']) > 1);
uasort($merged, fn($a, $b) => count($b['old_keys']) <=> count($a['old_keys']));

function h2(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Raport retenție: înainte vs după</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f6f7f9;color:#1f2937;margin:0;padding:32px 16px}
.wrap{max-width:860px;margin:0 auto}
h1{font-size:20px;margin:0 0 4px}
.sub{color:#6b7280;font-size:13px;margin:0 0 24px}
.boxes{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px}
.box .lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;margin-bottom:8px}
.box .vals{font-size:22px;font-weight:700}
.box .vals .arrow{color:#9ca3af;font-weight:400;margin:0 6px}
.box .delta{font-size:12px;font-weight:600;margin-top:4px}
.up{color:#16a34a}.down{color:#dc2626}.same{color:#6b7280}
table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden}
th,td{padding:10px 12px;text-align:left;font-size:13px;border-bottom:1px solid #f1f5f9;vertical-align:top}
th{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280}
.variant{display:inline-block;background:#f1f5f9;border:1px solid #e5e7eb;border-radius:4px;padding:2px 8px;margin:2px 4px 2px 0;font-size:12px}
.badge{background:#dcfce7;color:#16a34a;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600}
.note{color:#6b7280;font-size:12px;margin-top:20px}
a{color:#1d4ed8}
</style>
</head>
<body>
<div class="wrap">
<h1>Raport retenție: înainte vs după modificarea matching-ului</h1>
<p class="sub">Vechi = grupare pe <code>LOWER(TRIM(nume))</code> (ASCII). Nou = diacritice eliminate + ordinea numelui ignorată + typo-uri unite cu garduri. Aceleași bilete, numărate diferit.</p>

<div class="boxes">
<?php
$labels = ['unici' => 'Participanți unici', 'revin' => 'Revin la 2+ cursuri', 'bilete' => 'Total bilete'];
foreach ($stats as $key => [$before, $after]):
    $d = $after - $before;
    $cls = $d > 0 ? 'up' : ($d < 0 ? 'down' : 'same');
    if ($key === 'unici') $cls = $d < 0 ? 'up' : ($d > 0 ? 'down' : 'same'); // mai putini unici = dubluri unite = bine
?>
    <div class="box">
        <div class="lbl"><?= h2($labels[$key]) ?></div>
        <div class="vals"><?= $before ?><span class="arrow">&rarr;</span><?= $after ?></div>
        <div class="delta <?= $cls ?>"><?= $d === 0 ? 'neschimbat' : ($d > 0 ? '+' : '') . $d ?></div>
    </div>
<?php endforeach; ?>
</div>

<h1 style="font-size:16px">Persoane unite de noul matching (<?= count($merged) ?>)</h1>
<p class="sub">Verifică vizual: dacă vezi două persoane reale diferite unite aici, spune-mi și strâng gardurile.</p>
<?php if (empty($merged)): ?>
<p class="note">Nicio unire — vechiul matching nu rata nimic pe datele curente.</p>
<?php else: ?>
<table>
<tr><th>Variante de scriere unite</th><th style="width:80px;text-align:right">Bilete</th><th style="width:80px;text-align:right">Cursuri</th><th style="width:90px"></th></tr>
<?php foreach ($merged as $g):
    arsort($g['names']);
    $nc = count($g['course_ids']);
?>
<tr>
    <td><?php foreach (array_keys($g['names']) as $n): ?><span class="variant"><?= h2((string)$n) ?></span><?php endforeach; ?></td>
    <td style="text-align:right"><?= (int)$g['tickets'] ?></td>
    <td style="text-align:right"><?= $nc ?></td>
    <td><?= $nc > 1 ? '<span class="badge">revine</span>' : '' ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p class="note">Pagină de diagnostic one-off — poate fi ștearsă după citire. <a href="/admin/?tab=cursuri&ctab=participanti">&larr; Înapoi la Participanți</a></p>
</div>
</body>
</html>
