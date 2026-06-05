<h1 class="wp-page-title">Dashboard</h1>

<?php
$_dash_todo_user = clp_current_user()['username'] ?? '';
$_dash_todos_all = clp_load_todos();
$_dash_is_owner  = is_owner();

// Build per-user pending lists (max 5 preview)
$_dash_all_users_td = load_users();
$_dash_td_cols = [];
foreach ($_dash_all_users_td as $_u) {
    $uname = $_u['username'];
    $pending = array_values(array_filter($_dash_todos_all, fn($t) => $t['assigned_to'] === $uname && !$t['completed']));
    $_dash_td_cols[$uname] = array_slice($pending, 0, 5);
}
$_dash_td_user_colors = ['eric6' => '#2563eb', 'andy' => '#16a34a'];
?>

<?php if ($_dash_is_owner): ?>
<!-- OWNER: two-column todos, full width -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
<?php foreach ($_dash_td_cols as $_uname => $_ulist):
    $_dot = $_dash_td_user_colors[$_uname] ?? '#6b7280';
?>
<div class="dash-section" style="margin:0">
    <div class="dash-section-title" style="margin-bottom:10px">
        <span style="display:inline-flex;align-items:center;gap:7px">
            <span style="width:10px;height:10px;border-radius:50%;background:<?= h($_dot) ?>;display:inline-block;flex-shrink:0"></span>
            <?= h(ucfirst($_uname)) ?>
        </span>
        <a href="/admin/todos/" style="font-size:12px;font-weight:500;color:var(--accent);margin-left:auto;text-decoration:none">Toate →</a>
    </div>
    <?php if (empty($_ulist)): ?>
        <p style="color:var(--text-muted);font-size:13px">Nicio sarcină.</p>
    <?php else: ?>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px">
        <?php foreach ($_ulist as $_dt): ?>
            <li style="display:flex;align-items:flex-start;gap:8px;font-size:13px">
                <span style="color:var(--accent);margin-top:1px;flex-shrink:0">☐</span>
                <span style="color:var(--text)"><?= h($_dt['title']) ?></span>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<?php else: ?>
<!-- ANDY: todos 50% + stats 50% in one row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;align-items:start">
    <div class="dash-section" style="margin:0">
        <div class="dash-section-title" style="margin-bottom:10px">
            <span>To-dos ale mele</span>
            <a href="/admin/todos/" style="font-size:12px;font-weight:500;color:var(--accent);margin-left:auto;text-decoration:none">Toate →</a>
        </div>
        <?php $_dash_andy_list = $_dash_td_cols[$_dash_todo_user] ?? []; ?>
        <?php if (empty($_dash_andy_list)): ?>
            <p style="color:var(--text-muted);font-size:13px">Nicio sarcină.</p>
        <?php else: ?>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px">
            <?php foreach ($_dash_andy_list as $_dt): ?>
                <li style="display:flex;align-items:flex-start;gap:8px;font-size:13px">
                    <span style="color:var(--accent);margin-top:1px;flex-shrink:0">☐</span>
                    <span style="color:var(--text)"><?= h($_dt['title']) ?></span>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <!-- Stats cards inline for Andy -->
    <div style="display:flex;flex-direction:column;gap:12px">
        <div class="dash-card accent-blue" style="margin:0">
            <div class="dash-label">Cursuri programate</div>
            <div class="dash-value"><?= (int) $_dash_scheduled ?></div>
            <div class="dash-sub">/ <?= number_format($_dash_total_courses, 0, ',', '.') ?> cursuri totale</div>
        </div>
        <div class="dash-card accent-green" style="margin:0">
            <div class="dash-label">Participanti unici</div>
            <div class="dash-value"><?= number_format($_dash_participants, 0, ',', '.') ?></div>
            <div class="dash-sub"><?= number_format($_dash_total_tickets, 0, ',', '.') ?> bilete total</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$_ql = $settings['quick_links'] ?? [];
$_ql_general = [];
$_ql_canva   = [];
foreach ($_ql as $_ql_item) {
    if (str_contains($_ql_item['url'] ?? '', 'canva.com')) $_ql_canva[] = $_ql_item;
    else $_ql_general[] = $_ql_item;
}
if (!empty($_ql)): ?>
<div class="ql-grid">
    <?php if (!empty($_ql_general)): ?>
    <div class="dash-section" style="margin:0">
        <div class="dash-section-title"><span>Linkuri utile</span></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($_ql_general as $_ql_item): ?>
            <a href="<?= h($_ql_item['url'] ?? '#') ?>" target="_blank" rel="noopener" class="ql-btn">
                <span style="font-size:17px"><?= h($_ql_item['icon'] ?? '🔗') ?></span>
                <?= h($_ql_item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($_ql_canva)): ?>
    <div class="dash-section" style="margin:0">
        <div class="dash-section-title"><span>Canva</span></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($_ql_canva as $_ql_item): ?>
            <a href="<?= h($_ql_item['url'] ?? '#') ?>" target="_blank" rel="noopener" class="ql-btn">
                <span style="font-size:17px"><?= h($_ql_item['icon'] ?? '🔗') ?></span>
                <?= h($_ql_item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($_dash_is_owner): ?>
<!-- Stats cards (owner only — Andy sees these inline next to todos above) -->
<div class="dash-grid">
    <div class="dash-card accent-blue">
        <div class="dash-label">Cursuri programate</div>
        <div class="dash-value"><?= (int) $_dash_scheduled ?></div>
        <div class="dash-sub">/ <?= number_format($_dash_total_courses, 0, ',', '.') ?> cursuri totale</div>
    </div>
    <div class="dash-card accent-green">
        <div class="dash-label">Participanti unici</div>
        <div class="dash-value"><?= number_format($_dash_participants, 0, ',', '.') ?></div>
        <div class="dash-sub"><?= number_format($_dash_total_tickets, 0, ',', '.') ?> bilete total</div>
    </div>
</div>
<?php endif; ?>

<?php
$_dash_cal_json = [];
foreach ($_dash_courses as $_c) {
    $d = $_c['date_raw'] ?? '';
    if ($d === '') continue;
    $_dash_cal_json[$d][] = ['title' => $_c['title'] ?? ''];
}
$_mc_today_str = (new DateTime('now', new DateTimeZone('Europe/Bucharest')))->format('Y-m-d');
?>

<div class="dash-section" style="margin-bottom:20px">
    <div class="dash-section-title" style="margin-bottom:10px">
        <div class="dash-cal-heading">
            <span>Urmatoarele cursuri</span>
            <button type="button" class="dash-cal-arrow" id="dashCalPrev" aria-label="Săptămâni anterioare">&#8592;</button>
            <button type="button" class="dash-cal-arrow" id="dashCalNext" aria-label="Săptămâni următoare">&#8594;</button>
        </div>
    </div>
    <div class="mini-cal" id="dashMiniCal"></div>
</div>

<div class="dash-section" style="margin-bottom:0">
    <div class="dash-section-title"><span>Evolutie participanti</span></div>
    <?php if (empty($_dash_participant_months)): ?>
        <p style="color:var(--text-muted);font-size:13px">Nicio data disponibila.</p>
    <?php else: ?>
        <table class="dash-table">
            <tr style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">
                <td>Luna</td><td style="text-align:right">Unici</td><td style="text-align:right">Bilete</td>
            </tr>
        <?php foreach ($_dash_participant_months as $_pm):
            $pmIdx = (int)substr($_pm['m'], 5, 2);
        ?>
            <tr>
                <td><?= ucfirst($_ro_months_full[$pmIdx]) ?> <?= substr($_pm['m'], 0, 4) ?></td>
                <td style="text-align:right;font-weight:600"><?= $_pm['unici'] ?></td>
                <td style="text-align:right" class="muted"><?= $_pm['bilete'] ?></td>
            </tr>
        <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<script>
window.DASH_CAL = <?= json_encode([
    'today' => $_mc_today_str,
    'coursesByDay' => $_dash_cal_json,
], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/admin/assets/js/admin-dashboard.js?v=3"></script>
