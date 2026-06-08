<?php
declare(strict_types=1);

/** Admin-only view of who clicked the re-engagement link. */
require __DIR__ . '/../admin/auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$log_file = dirname(__DIR__) . '/data/reengage_clicks.json';
$rows = is_file($log_file) ? (json_decode((string)file_get_contents($log_file), true) ?: []) : [];

// Aggregate by email (or id) → first/last click + count.
$people = [];
foreach ($rows as $r) {
    $key = $r['email'] !== '' ? mb_strtolower($r['email']) : ($r['id'] !== '' ? $r['id'] : '(anonim)');
    if (!isset($people[$key])) {
        $people[$key] = ['label' => $r['email'] ?: ($r['id'] ?: '(anonim)'), 'count' => 0, 'first' => $r['ts'], 'last' => $r['ts']];
    }
    $people[$key]['count']++;
    if ($r['ts'] < $people[$key]['first']) $people[$key]['first'] = $r['ts'];
    if ($r['ts'] > $people[$key]['last'])  $people[$key]['last']  = $r['ts'];
}
uasort($people, fn($a, $b) => strcmp($b['last'], $a['last']));

$total_clicks = count($rows);
$unique = count($people);

$fmt = function (string $iso): string {
    $t = strtotime($iso);
    return $t ? date('d.m.Y H:i', $t) : $iso;
};
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Re-engagement — clickuri</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f1f5f9; color: #111827; padding: 32px; }
  .wrap { max-width: 760px; margin: 0 auto; }
  h1 { font-size: 24px; margin-bottom: 6px; }
  .sub { color: #6b7280; font-size: 14px; margin-bottom: 22px; }
  .stats { display: flex; gap: 14px; margin-bottom: 22px; }
  .stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px; }
  .stat .n { font-size: 26px; font-weight: 700; }
  .stat .l { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
  table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
  th, td { text-align: left; padding: 11px 16px; font-size: 14px; border-bottom: 1px solid #eef0f2; }
  th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; }
  tr:last-child td { border-bottom: none; }
  td.num { text-align: right; }
  .empty { color: #6b7280; padding: 24px; text-align: center; }
  a.back { color: #2563eb; text-decoration: none; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="/admin/">&larr; Admin</a>
  <h1>Re-engagement — clickuri</h1>
  <p class="sub">Cine a apăsat pe linkul din email (link: <code>/reactivare/?e={email}</code>)</p>

  <div class="stats">
    <div class="stat"><div class="n"><?= $unique ?></div><div class="l">persoane</div></div>
    <div class="stat"><div class="n"><?= $total_clicks ?></div><div class="l">clickuri total</div></div>
  </div>

  <?php if (empty($people)): ?>
    <p class="empty">Niciun click încă.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Email / ID</th><th class="num">Clickuri</th><th>Primul</th><th>Ultimul</th></tr></thead>
    <tbody>
    <?php foreach ($people as $p): ?>
      <tr>
        <td><?= h($p['label']) ?></td>
        <td class="num"><?= (int)$p['count'] ?></td>
        <td><?= h($fmt($p['first'])) ?></td>
        <td><?= h($fmt($p['last'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
</body>
</html>
