<?php
require_once dirname(__DIR__) . '/auth_check.php';
if (!is_authenticated()) {
    header('Location: /admin/');
    exit;
}

$courses_file = dirname(__DIR__, 2) . '/data/courses.json';
$all_courses  = file_exists($courses_file) ? (json_decode(file_get_contents($courses_file), true) ?: []) : [];

// Month navigation
$now   = new DateTime('now', new DateTimeZone('Europe/Bucharest'));
$year  = (int)($_GET['y'] ?? $now->format('Y'));
$month = (int)($_GET['m'] ?? $now->format('n'));
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$prev_y = $month === 1  ? $year - 1 : $year;
$prev_m = $month === 1  ? 12 : $month - 1;
$next_y = $month === 12 ? $year + 1 : $year;
$next_m = $month === 12 ? 1  : $month + 1;

$ro_months = [
    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
];

// Build a map: date_raw => [courses]
$courses_by_day = [];
$today_ymd = $now->format('Y-m-d');
foreach ($all_courses as $c) {
    $d = $c['date_raw'] ?? '';
    if (!$d) continue;
    $courses_by_day[$d][] = $c;
}

// Calendar grid: first day of month, how many days
$first_dow = (int)(new DateTime("$year-$month-01"))->format('N'); // 1=Mon … 7=Sun
$days_in_month = (int)(new DateTime("$year-$month-01"))->format('t');
$num_rows = (int)ceil(($first_dow - 1 + $days_in_month) / 7);

$__page_title = 'Calendar';
include dirname(__DIR__) . '/statistici/layout_header.php';
?>
<style>
.cal-nav { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
.cal-nav h2 { font-size:18px; font-weight:700; color:#111827; margin:0; flex:1; text-align:center; }
.cal-wrap { height:calc(36px + <?= $num_rows ?> * 120px); }
.cal-grid { display:grid; grid-template-columns:repeat(7,1fr); grid-template-rows:36px repeat(<?= $num_rows ?>,1fr); height:100%; gap:1px; background:#e5e7eb; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
.cal-dow  { background:#f8fafc; padding:0; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.06em; }
.cal-cell { background:#fff; padding:6px 8px; overflow:hidden; }
.cal-cell.other-month { background:#f9fafb; }
.cal-cell.today { background:#eff6ff; }
.cal-day-num { font-size:12px; font-weight:600; color:#6b7280; margin-bottom:4px; line-height:1; }
.cal-cell.today .cal-day-num { display:inline-flex; }
.cal-day-num .today-circle { background:#1d4ed8; color:#fff; width:22px; height:22px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:11px; }
.cal-event { font-size:11px; font-weight:600; padding:2px 6px; border-radius:4px; margin-bottom:3px; line-height:1.4; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cal-event.future { background:#dbeafe; color:#1e40af; }
.cal-event.past   { background:#f1f5f9; color:#9ca3af; }
.cal-event.today-ev { background:#1d4ed8; color:#fff; }
.cal-legend { display:flex; gap:16px; margin-top:12px; font-size:12px; color:#6b7280; align-items:center; flex-wrap:wrap; }
.cal-legend span { display:flex; align-items:center; gap:6px; }
.cal-legend-dot { width:10px; height:10px; border-radius:3px; flex-shrink:0; }
</style>

<?php
$_stat_path = $_SERVER['REQUEST_URI'] ?? '';
include dirname(__DIR__) . '/statistici/layout_nav.php';
?>

<h1 class="wp-page-title">Calendar cursuri</h1>

<div class="card">
    <div class="cal-nav">
        <a href="?y=<?= $prev_y ?>&m=<?= $prev_m ?>" class="btn btn-secondary" style="flex-shrink:0">&#8592;</a>
        <h2><?= $ro_months[$month] ?> <?= $year ?></h2>
        <a href="?y=<?= $next_y ?>&m=<?= $next_m ?>" class="btn btn-secondary" style="flex-shrink:0">&#8594;</a>
        <a href="?y=<?= $now->format('Y') ?>&m=<?= $now->format('n') ?>" class="btn btn-secondary" style="flex-shrink:0;white-space:nowrap">Azi</a>
    </div>

    <div class="cal-wrap">
    <div class="cal-grid">
        <?php
        $dow_labels = ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du'];
        foreach ($dow_labels as $l) {
            echo '<div class="cal-dow">' . $l . '</div>';
        }

        for ($i = 1; $i < $first_dow; $i++) {
            echo '<div class="cal-cell other-month"></div>';
        }

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $is_today = $date_str === $today_ymd;
            $classes  = 'cal-cell' . ($is_today ? ' today' : '');
            echo '<div class="' . $classes . '">';
            if ($is_today) {
                echo '<div class="cal-day-num"><span class="today-circle">' . $day . '</span></div>';
            } else {
                echo '<div class="cal-day-num">' . $day . '</div>';
            }
            if (!empty($courses_by_day[$date_str])) {
                foreach ($courses_by_day[$date_str] as $c) {
                    $is_past  = $date_str < $today_ymd;
                    $ev_class = $is_today ? 'today-ev' : ($is_past ? 'past' : 'future');
                    $title    = htmlspecialchars($c['title'] ?? '', ENT_QUOTES, 'UTF-8');
                    echo '<div class="cal-event ' . $ev_class . '" title="' . $title . '">' . $title . '</div>';
                }
            }
            echo '</div>';
        }

        $total_cells = $first_dow - 1 + $days_in_month;
        $trailing    = (7 - ($total_cells % 7)) % 7;
        for ($i = 0; $i < $trailing; $i++) {
            echo '<div class="cal-cell other-month"></div>';
        }
        ?>
    </div>
    </div>

    <div class="cal-legend">
        <span><span class="cal-legend-dot" style="background:#dbeafe;border:1px solid #bfdbfe"></span> Curs viitor</span>
        <span><span class="cal-legend-dot" style="background:#1d4ed8"></span> Curs azi</span>
        <span><span class="cal-legend-dot" style="background:#f1f5f9;border:1px solid #e5e7eb"></span> Curs trecut</span>
    </div>
</div>

</main>
</div>
</body>
</html>
