<?php
declare(strict_types=1);
require __DIR__ . '/../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }

require_once dirname(__DIR__, 2) . '/lib/settings.php';
require_once dirname(__DIR__, 2) . '/lib/ab_headline.php';
require_once dirname(__DIR__, 2) . '/lib/ab_button.php';

$stats = clp_ab_headline_load();
$settings = clp_load_settings();

$variants = [
    'A' => ['headline' => $settings['hero_title'] ?? '', 'desc' => 'headline vechi + layout nou (subtitlu, buton)'],
    'B' => ['headline' => CLP_AB_HEADLINE_B,             'desc' => 'headline nou + layout nou (subtitlu, buton)'],
];

$ctr = [];
foreach (array_keys($variants) as $v) {
    $ctr[$v] = $stats[$v]['views'] > 0
        ? $stats[$v]['clicks'] / $stats[$v]['views'] * 100
        : 0.0;
}
$total_views = array_sum(array_column($stats, 'views'));
arsort($ctr);
$leader = $total_views > 0 && count(array_unique($ctr)) > 1 ? array_key_first($ctr) : '';
$ctr_order = array_keys($ctr);

// ── Test A/B buton „Vreau să vin" ─────────────────────────────────────────────
$btn_stats = clp_ab_button_load();
$btn_variants = [
    'off' => 'cardurile ca înainte (fără buton)',
    'on'  => 'cardurile cu butonul „Vreau să vin"',
];
$btn_ctr = [];
foreach (array_keys($btn_variants) as $v) {
    $btn_ctr[$v] = $btn_stats[$v]['views'] > 0
        ? $btn_stats[$v]['clicks'] / $btn_stats[$v]['views'] * 100
        : 0.0;
}
$btn_total_views = array_sum(array_column($btn_stats, 'views'));
$btn_leader = $btn_total_views > 0 && $btn_ctr['on'] !== $btn_ctr['off']
    ? ($btn_ctr['on'] > $btn_ctr['off'] ? 'on' : 'off') : '';

$__page_title = 'Test A/B Headline';
include __DIR__ . '/layout_header.php';
include __DIR__ . '/layout_nav.php';

function clp_ab_h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
        <h1 class="wp-page-title">Test A/B — Hero</h1>
        <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px">
            Fiecare vizitator al paginii principale vede aleatoriu (1/2) una din cele două variante
            și o păstrează la revenire (cookie, 90 de zile). Click = apăsare pe un card de curs din
            secțiunea „Program cursuri". Boții și prefetch-urile nu sunt numărate.
        </p>

        <div style="overflow-x:auto">
        <table class="table" style="max-width:980px">
            <thead>
                <tr>
                    <th>Variantă</th>
                    <th>Headline</th>
                    <th>Descriere</th>
                    <th style="text-align:right">Afișări</th>
                    <th style="text-align:right">Click-uri cursuri</th>
                    <th style="text-align:right">CTR</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variants as $v => $info): ?>
                <tr>
                    <td style="font-weight:700"><?= $v ?><?= $v === $leader ? ' 🏆' : '' ?></td>
                    <td><?= clp_ab_h(str_ireplace('<br>', ' ', $info['headline'])) ?></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= clp_ab_h($info['desc']) ?></td>
                    <td style="text-align:right"><?= number_format($stats[$v]['views']) ?></td>
                    <td style="text-align:right"><?= number_format($stats[$v]['clicks']) ?></td>
                    <td style="text-align:right;font-weight:600"><?= number_format($ctr[$v], 2) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php if ($total_views === 0): ?>
        <p style="color:var(--text-muted);font-size:13px;margin-top:16px">
            Nu există date încă — testul pornește la primele vizite pe pagina principală.
        </p>
        <?php elseif ($leader !== ''): ?>
        <p style="font-size:13px;margin-top:16px">
            Varianta <strong><?= $leader ?></strong> conduce cu un CTR de
            <strong><?= number_format($ctr[$leader], 2) ?>%</strong>
            (față de <?= number_format($ctr[$ctr_order[1]], 2) ?>% varianta <?= $ctr_order[1] ?>).
            <?php if ($total_views < 750): ?>
            <span style="color:var(--text-muted)">Sub ~750 de afișări totale diferența poate fi zgomot — mai lasă testul să ruleze.</span>
            <?php endif; ?>
        </p>
        <?php endif; ?>

        <h1 class="wp-page-title" style="margin-top:40px">Test A/B — Buton „Vreau să vin"</h1>
        <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px">
            Jumătate din vizitatori (aleatoriu, cookie 90 de zile) văd un buton galben „Vreau să vin"
            pe fiecare card de curs, jumătate nu. Click = ajungere pe pagina de bilete prin card sau buton.
            Boții și prefetch-urile nu sunt numărate.
        </p>

        <div style="overflow-x:auto">
        <table class="table" style="max-width:980px">
            <thead>
                <tr>
                    <th>Variantă</th>
                    <th>Descriere</th>
                    <th style="text-align:right">Afișări</th>
                    <th style="text-align:right">Click-uri cursuri</th>
                    <th style="text-align:right">CTR</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($btn_variants as $v => $desc): ?>
                <tr>
                    <td style="font-weight:700"><?= $v === 'on' ? 'Cu buton' : 'Fără buton' ?><?= $v === $btn_leader ? ' 🏆' : '' ?></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= clp_ab_h($desc) ?></td>
                    <td style="text-align:right"><?= number_format($btn_stats[$v]['views']) ?></td>
                    <td style="text-align:right"><?= number_format($btn_stats[$v]['clicks']) ?></td>
                    <td style="text-align:right;font-weight:600"><?= number_format($btn_ctr[$v], 2) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php if ($btn_total_views === 0): ?>
        <p style="color:var(--text-muted);font-size:13px;margin-top:16px">
            Nu există date încă — testul pornește la primele vizite pe pagina principală.
        </p>
        <?php elseif ($btn_leader !== ''): ?>
        <p style="font-size:13px;margin-top:16px">
            Varianta <strong><?= $btn_leader === 'on' ? 'cu buton' : 'fără buton' ?></strong> conduce cu un CTR de
            <strong><?= number_format($btn_ctr[$btn_leader], 2) ?>%</strong>
            (față de <?= number_format($btn_ctr[$btn_leader === 'on' ? 'off' : 'on'], 2) ?>% cealaltă variantă).
            <?php if ($btn_total_views < 750): ?>
            <span style="color:var(--text-muted)">Sub ~750 de afișări totale diferența poate fi zgomot — mai lasă testul să ruleze.</span>
            <?php endif; ?>
        </p>
        <?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
