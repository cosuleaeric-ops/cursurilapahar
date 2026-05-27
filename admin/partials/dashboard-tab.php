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
// Mini calendar: 3 weeks starting from Monday of current week
$_mc_today   = new DateTime('now', new DateTimeZone('Europe/Bucharest'));
$_mc_dow     = (int)$_mc_today->format('N'); // 1=Mon
$_mc_start   = clone $_mc_today;
$_mc_start->modify('-' . ($_mc_dow - 1) . ' days'); // Monday of current week
$_mc_by_day  = [];
foreach ($_dash_courses as $_c) {
    $d = $_c['date_raw'] ?? '';
    if ($d) $_mc_by_day[$d][] = $_c;
}
$_mc_today_str = $_mc_today->format('Y-m-d');
?>

<div class="dash-section" style="margin-bottom:20px">
    <div class="dash-section-title" style="margin-bottom:10px">
        <span>Urmatoarele cursuri</span>
        <a href="?tab=cursuri" style="font-size:12px;font-weight:400;color:var(--primary);text-decoration:none;margin-left:10px">+ Adaugă</a>
    </div>
    <div class="mini-cal">
        <?php foreach (['Lu','Ma','Mi','Jo','Vi','Sâ','Du'] as $_dl): ?>
        <div class="mini-cal-dow"><?= $_dl ?></div>
        <?php endforeach; ?>
        <?php
        $_mc_cur = clone $_mc_start;
        for ($i = 0; $i < 21; $i++):
            $ds       = $_mc_cur->format('Y-m-d');
            $day_num  = $_mc_cur->format('j');
            $is_today = $ds === $_mc_today_str;
            $is_past  = $ds < $_mc_today_str;
            $cell_cls = $is_today ? 'today' : ($is_past ? 'past' : '');
        ?>
        <div class="mini-cal-cell <?= $cell_cls ?>">
            <div class="mini-cal-day"><?= $day_num ?></div>
            <?php foreach ($_mc_by_day[$ds] ?? [] as $_mc_c):
                $ev_cls = $is_today ? 'today-ev' : ($is_past ? 'past' : 'future');
            ?>
            <div class="mini-cal-event <?= $ev_cls ?>" title="<?= h($_mc_c['title'] ?? '') ?>"><?= h($_mc_c['title'] ?? '') ?></div>
            <?php endforeach; ?>
        </div>
        <?php $_mc_cur->modify('+1 day'); endfor; ?>
    </div>
</div>

<div class="dash-cols">
    <!-- Left column -->
    <div>

        <!-- Participant evolution -->
        <div class="dash-section">
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
    </div>

    <!-- Right column -->
    <div>
        <!-- Vote courses -->
        <div class="dash-section">
            <div class="dash-section-title"><span>Vot cursuri</span></div>
            <?php if (empty($_dash_votes)): ?>
                <p style="color:var(--text-muted);font-size:13px">Nicio propunere de curs.</p>
            <?php else: ?>
                <table class="dash-table">
                <?php foreach ($_dash_votes as $_vc): ?>
                    <tr>
                        <td><?= $_vc['emoji'] ?? '' ?> <?= h($_vc['name'] ?? '') ?></td>
                        <td class="muted" style="text-align:right;white-space:nowrap"><?= (int)($_vc['likes'] ?? 0) ?> voturi</td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>
