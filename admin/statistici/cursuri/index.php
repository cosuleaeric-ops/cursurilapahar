<?php
declare(strict_types=1);
require __DIR__ . '/../../auth_check.php';
require __DIR__ . '/../db.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }

$db = get_clp_db();

// ── An + lună comune pentru ambele tab-uri ────────────────────────────────────
$now       = new DateTimeImmutable();
$ditlYear  = (int)($_GET['year']  ?? $now->format('Y'));
$ditlMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0; // 0 = tot anul
$datePrefix = $ditlMonth > 0
    ? $ditlYear . '-' . str_pad((string)$ditlMonth, 2, '0', STR_PAD_LEFT)
    : (string)$ditlYear;

// ── Lista cursuri (filtrat pe an/lună) ────────────────────────────────────────
$result = $db->query("SELECT c.id, c.name, c.date,
    (SELECT COUNT(*) FROM tickets t WHERE t.course_id = c.id) as total_tickets,
    (SELECT filename FROM course_files f WHERE f.course_id = c.id AND f.file_type = 'viza' ORDER BY f.uploaded_at DESC LIMIT 1) as viza_filename,
    (SELECT 1 FROM course_reports r WHERE r.course_id = c.id LIMIT 1) as has_report
    FROM courses c WHERE c.date LIKE '{$datePrefix}%' ORDER BY c.date DESC");
$courses = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) $courses[] = $row;

$ditlRes = $db->query("
    SELECT c.id, c.name, c.date,
           r.total_bilete, r.total_incasari, r.types_json
    FROM courses c
    JOIN course_reports r ON r.course_id = c.id
    WHERE c.date LIKE '{$datePrefix}%'
    ORDER BY c.date DESC
");
$ditlRows = [];
while ($r = $ditlRes->fetchArray(SQLITE3_ASSOC)) $ditlRows[] = $r;

// Ani disponibili
$yearsRes = $db->query("SELECT DISTINCT strftime('%Y', c.date) AS y FROM courses c JOIN course_reports r ON r.course_id = c.id ORDER BY y DESC");
$ditlYears = [];
while ($yr = $yearsRes->fetchArray(SQLITE3_ASSOC)) $ditlYears[] = $yr['y'];
if (!in_array((string)$ditlYear, $ditlYears)) $ditlYears[] = (string)$ditlYear;

// Subtipuri viță + vândute
$vizaSubtipsByCourse   = [];
$reportByPriceByCourse = [];
if (!empty($ditlRows)) {
    $ids   = implode(',', array_map(fn($r) => (int)$r['id'], $ditlRows));
    $vsRes = $db->query("SELECT * FROM viza_subtips WHERE course_id IN ({$ids}) ORDER BY course_id, tarif DESC");
    while ($vs = $vsRes->fetchArray(SQLITE3_ASSOC)) {
        $vizaSubtipsByCourse[(int)$vs['course_id']][] = $vs;
    }
    foreach ($ditlRows as $r) {
        $types = json_decode($r['types_json'] ?? '[]', true) ?: [];
        $byPrice = [];
        foreach ($types as $t) {
            $byPrice[(string)(float)($t['pret'] ?? 0)] = $t;
        }
        $reportByPriceByCourse[(int)$r['id']] = $byPrice;
    }
}

// Grupare pe luni
$byMonth = [];
foreach ($ditlRows as $r) {
    $monthKey = substr($r['date'], 0, 7); // YYYY-MM
    $byMonth[$monthKey][] = $r;
}

$sumBilete   = array_sum(array_column($ditlRows, 'total_bilete'));
$sumIncasari = array_sum(array_column($ditlRows, 'total_incasari'));

$roMonths = ['', 'ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie',
             'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];

function fmt(float $v): string { return number_format($v, 2, ',', '.'); }

$tab = $_GET['tab'] ?? 'cursuri';
?>
<?php
$__page_title = 'Cursuri';
include __DIR__ . '/../layout_header.php';
?>
<link rel="stylesheet" href="/admin/statistici/style.css">
<style>
    /* Tabs */
    .tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 24px; }
    .tab { padding: 10px 20px; font-size: 14px; font-weight: 600; color: var(--text-muted); cursor: pointer; border: none; background: none; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.15s; }
    .tab:hover { color: var(--text); }
    .tab.active { color: var(--accent); border-bottom-color: var(--accent); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    /* Filters */
    .filters { display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap; }
    .filters select { appearance: none; background: var(--surface); border: 1px solid var(--border); border-radius: 4px; padding: 6px 28px 6px 10px; font-size: 14px; color: var(--text); cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23888'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 8px center; }

    /* Course list */
    .course-row { display: flex; align-items: center; gap: 16px; padding: 14px 18px; background: var(--surface); border: 1px solid var(--border); border-radius: 4px; margin-bottom: 8px; text-decoration: none; color: var(--text); transition: all 0.12s; }
    .course-row:hover { border-color: var(--accent); transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .course-date { font-size: 13px; color: var(--text-muted); min-width: 100px; }
    .course-name { flex: 1; font-weight: 600; font-size: 15px; }
    .course-meta { display: flex; gap: 12px; align-items: center; }
    .course-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-tickets { background: #d4edda; color: #155724; }
    .badge-viza { background: #EDE9FE; color: #7C3AED; }
    .badge-report { background: #fff3cd; color: #856404; }
    .badge-none { background: #f0f0f1; color: var(--text-muted); }

    /* DITL */
    .ditl-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .month-group { margin-bottom: 28px; }
    .month-group h3 { font-size: 18px; font-weight: 600; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
    .ditl-card { background: var(--surface); border: 1px solid var(--border); border-radius: 4px; padding: 18px 20px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
    .ditl-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .ditl-name { font-weight: 600; font-size: 15px; }
    .ditl-date { font-size: 13px; color: var(--text-muted); }
    .ditl-stats { display: flex; gap: 20px; font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
    .ditl-stats strong { color: var(--text); }
    .viza-toggle { font-size: 12px; color: var(--accent); cursor: pointer; border: none; background: none; font-weight: 600; padding: 0; }
    .viza-toggle:hover { text-decoration: underline; }
    .viza-details { display: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .viza-details.open { display: block; }
    .viza-table { width: 100%; font-size: 13px; border-collapse: collapse; }
    .viza-table th { text-align: left; padding: 4px 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); }
    .viza-table td { padding: 4px 8px; }
    .viza-table .right { text-align: right; }
    .sold-match { color: var(--success); font-weight: 600; }
    .sold-mismatch { color: var(--danger); font-weight: 600; }

    .empty { text-align: center; padding: 48px; color: var(--text-muted); }

    .btn-green { display: inline-flex; align-items: center; gap: 4px; padding: 6px 14px; background: var(--accent); color: #fff; border: none; border-radius: 4px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; transition: background .15s; }
    .btn-green:hover { background: var(--accent-hover); }

    @media (max-width: 640px) {
      .course-row { flex-wrap: wrap; gap: 8px; }
      .course-date { min-width: auto; }
      .course-meta { width: 100%; }
    }
</style>
</head>
<?php include __DIR__ . '/../layout_nav.php'; ?>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <h1 class="wp-page-title" style="margin-bottom:0">Cursuri</h1>
      <a href="/admin/statistici/cursuri/add.php" class="btn-green">+ Adauga curs</a>
    </div>

    <!-- Filters -->
    <form class="filters" method="get">
      <input type="hidden" name="tab" value="<?= h($tab) ?>">
      <select name="year" onchange="this.form.submit()">
        <?php
          $allYearsRes = $db->query("SELECT DISTINCT strftime('%Y', date) AS y FROM courses ORDER BY y DESC");
          $allYears = [];
          while ($yr2 = $allYearsRes->fetchArray(SQLITE3_ASSOC)) $allYears[] = $yr2['y'];
          if (!in_array((string)$ditlYear, $allYears)) $allYears[] = (string)$ditlYear;
          sort($allYears); $allYears = array_reverse($allYears);
          foreach ($allYears as $y): ?>
          <option value="<?= $y ?>" <?= (int)$y === $ditlYear ? 'selected' : '' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
      <select name="month" onchange="this.form.submit()">
        <option value="0" <?= $ditlMonth === 0 ? 'selected' : '' ?>>Tot anul</option>
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $ditlMonth === $m ? 'selected' : '' ?>><?= $roMonths[$m] ?></option>
        <?php endfor; ?>
      </select>
    </form>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab <?= $tab === 'cursuri' ? 'active' : '' ?>" onclick="switchTab('cursuri')">Cursuri (<?= count($courses) ?>)</button>
      <button class="tab <?= $tab === 'ditl' ? 'active' : '' ?>" onclick="switchTab('ditl')">Rapoarte DITL (<?= count($ditlRows) ?>)</button>
    </div>

    <!-- Tab: Cursuri -->
    <div id="tab-cursuri" class="tab-content <?= $tab === 'cursuri' ? 'active' : '' ?>">
      <?php if (empty($courses)): ?>
        <div class="empty">Niciun curs in aceasta perioada.</div>
      <?php else: ?>
        <?php foreach ($courses as $c): ?>
          <a class="course-row" href="/admin/statistici/cursuri/view.php?id=<?= $c['id'] ?>">
            <span class="course-date"><?= h(ro_date($c['date'])) ?></span>
            <span class="course-name"><?= h($c['name']) ?></span>
            <span class="course-meta">
              <span class="course-badge badge-tickets"><?= (int)$c['total_tickets'] ?> bilete</span>
              <?php if ($c['viza_filename']): ?>
                <span class="course-badge badge-viza">viza</span>
              <?php endif; ?>
              <?php if ($c['has_report']): ?>
                <span class="course-badge badge-report">raport</span>
              <?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Tab: DITL -->
    <div id="tab-ditl" class="tab-content <?= $tab === 'ditl' ? 'active' : '' ?>">
      <?php if (empty($ditlRows)): ?>
        <div class="empty">Niciun raport DITL in aceasta perioada.</div>
      <?php else: ?>
        <!-- Summary -->
        <div class="ditl-summary">
          <div class="stat-card accent-green">
            <div class="label">Total bilete</div>
            <div class="value green"><?= number_format($sumBilete, 0, ',', '.') ?></div>
          </div>
          <div class="stat-card accent-gold">
            <div class="label">Total incasari</div>
            <div class="value"><?= fmt($sumIncasari) ?> lei</div>
          </div>
          <div class="stat-card">
            <div class="label">Cursuri cu raport</div>
            <div class="value"><?= count($ditlRows) ?></div>
          </div>
        </div>

        <!-- Grouped by month -->
        <?php foreach ($byMonth as $monthKey => $rows):
          $mIdx = (int)substr($monthKey, 5, 2);
          $mYear = substr($monthKey, 0, 4);
        ?>
          <div class="month-group">
            <h3><?= ucfirst($roMonths[$mIdx]) ?> <?= $mYear ?></h3>
            <?php foreach ($rows as $r):
              $cId = (int)$r['id'];
              $vizaSubs = $vizaSubtipsByCourse[$cId] ?? [];
              $byPrice  = $reportByPriceByCourse[$cId] ?? [];
            ?>
              <div class="ditl-card">
                <div class="ditl-header">
                  <span class="ditl-name"><?= h($r['name']) ?></span>
                  <span class="ditl-date"><?= h(ro_date($r['date'])) ?></span>
                </div>
                <div class="ditl-stats">
                  <span>Bilete: <strong><?= number_format((float)$r['total_bilete'], 0, ',', '.') ?></strong></span>
                  <span>Incasari: <strong><?= fmt((float)$r['total_incasari']) ?> lei</strong></span>
                </div>
                <?php if (!empty($vizaSubs)): ?>
                  <button class="viza-toggle" onclick="toggleViza(this)">&#9654; Detalii viza</button>
                  <div class="viza-details">
                    <table class="viza-table">
                      <thead>
                        <tr>
                          <th>Seria</th>
                          <th>Tarif</th>
                          <th class="right">Nr. unitati (viza)</th>
                          <th class="right">Vandute (raport)</th>
                          <th class="right">Diferenta</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($vizaSubs as $vs):
                          $tarif = (float)$vs['tarif'];
                          $nrViza = (int)$vs['nr_unitati'];
                          $priceKey = (string)$tarif;
                          $sold = isset($byPrice[$priceKey]) ? (int)($byPrice[$priceKey]['cantitate'] ?? 0) : 0;
                          $diff = $nrViza - $sold;
                          $diffClass = $diff === 0 ? 'sold-match' : 'sold-mismatch';
                        ?>
                          <tr>
                            <td><?= h($vs['seria']) ?></td>
                            <td><?= fmt($tarif) ?> lei</td>
                            <td class="right"><?= $nrViza ?></td>
                            <td class="right"><?= $sold ?></td>
                            <td class="right <?= $diffClass ?>"><?= $diff > 0 ? '+' . $diff : $diff ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  <script>
    function switchTab(tab) {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      document.getElementById('tab-' + tab).classList.add('active');
      document.querySelector('.tab[onclick*="' + tab + '"]').classList.add('active');
      // Update URL without reload
      const url = new URL(window.location);
      url.searchParams.set('tab', tab);
      history.replaceState(null, '', url);
    }

    function toggleViza(btn) {
      const details = btn.nextElementSibling;
      if (details.classList.contains('open')) {
        details.classList.remove('open');
        btn.innerHTML = '&#9654; Detalii viza';
      } else {
        details.classList.add('open');
        btn.innerHTML = '&#9660; Ascunde detalii';
      }
    }
  </script>
    </main>
</div>
</body>
</html>
