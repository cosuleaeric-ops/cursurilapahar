<h1 class="wp-page-title">Dashboard</h1>

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

<!-- Stats cards -->
<div class="dash-grid">
    <div class="dash-card accent-blue">
        <div class="dash-label">Cursuri active</div>
        <div class="dash-value"><?= $_dash_active ?></div>
        <div class="dash-sub">din <?= count($_dash_courses) ?> total</div>
    </div>
    <div class="dash-card accent-green">
        <div class="dash-label">Participanti unici</div>
        <div class="dash-value"><?= number_format($_dash_participants, 0, ',', '.') ?></div>
        <div class="dash-sub"><?= number_format($_dash_total_tickets, 0, ',', '.') ?> bilete total</div>
    </div>
</div>

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
        <span>Urmatoarele cursuri</span>
        <div class="dash-cal-nav">
            <button type="button" class="dash-cal-arrow" id="dashCalPrev" aria-label="Săptămâni anterioare">&#8592;</button>
            <span class="dash-cal-label" id="dashCalLabel"></span>
            <button type="button" class="dash-cal-arrow" id="dashCalNext" aria-label="Săptămâni următoare">&#8594;</button>
            <button type="button" class="dash-cal-today" id="dashCalToday" title="Săptămâna curentă">Azi</button>
            <a href="?tab=cursuri" class="dash-cal-add">+ Adaugă</a>
        </div>
    </div>
    <div class="mini-cal" id="dashMiniCal"></div>
</div>

<div class="dash-section dash-stats-section">
    <div class="clp-tabs dash-stats-tabs">
        <button type="button" class="clp-tab-btn active" data-dash-tab="evolutie">Evolutie participanti</button>
        <button type="button" class="clp-tab-btn" data-dash-tab="fideli">Top fideli</button>
    </div>

    <div class="dash-tab-panel active" id="dash-tab-evolutie">
        <?php if (empty($_dash_participant_months)): ?>
            <p style="color:var(--text-muted);font-size:13px;margin-top:14px">Nicio data disponibila.</p>
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

    <div class="dash-tab-panel" id="dash-tab-fideli">
        <?php if (empty($_dash_top_fideli)): ?>
            <p style="color:var(--text-muted);font-size:13px;margin-top:14px">Niciun participant cu mai multe cursuri.</p>
        <?php else: ?>
            <table class="dash-table">
                <tr style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">
                    <td>Nume</td><td style="text-align:right">Cursuri</td><td style="text-align:right">Bilete</td>
                </tr>
            <?php foreach ($_dash_top_fideli as $_tf): ?>
                <tr>
                    <td><?= h($_tf['participant_name'] ?? '') ?></td>
                    <td style="text-align:right;font-weight:600"><?= (int)($_tf['nr_cursuri'] ?? 0) ?></td>
                    <td style="text-align:right" class="muted"><?= (int)($_tf['nr_bilete'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
window.DASH_CAL = <?= json_encode([
    'today' => $_mc_today_str,
    'coursesByDay' => $_dash_cal_json,
], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/admin/assets/js/admin-dashboard.js?v=1"></script>
