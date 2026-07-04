<?php
declare(strict_types=1);
require __DIR__ . '/../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }

require_once dirname(__DIR__, 2) . '/lib/settings.php';
require_once dirname(__DIR__, 2) . '/lib/ab_headline.php';

$stats = clp_ab_headline_load();
$settings = clp_load_settings();

$headlines = [
    'A' => $settings['hero_title'] ?? '',
    'B' => CLP_AB_HEADLINE_B,
];

$ctr = [];
foreach (['A', 'B'] as $v) {
    $ctr[$v] = $stats[$v]['views'] > 0
        ? $stats[$v]['clicks'] / $stats[$v]['views'] * 100
        : 0.0;
}
$leader = $ctr['A'] === $ctr['B'] ? '' : ($ctr['A'] > $ctr['B'] ? 'A' : 'B');
$total_views = $stats['A']['views'] + $stats['B']['views'];

$__page_title = 'Test A/B Headline';
include __DIR__ . '/layout_header.php';
include __DIR__ . '/layout_nav.php';

function clp_ab_h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
        <h1 class="wp-page-title">Test A/B — Headline hero</h1>
        <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px">
            Fiecare vizitator al paginii principale vede aleatoriu (50/50) una din cele două variante
            și o păstrează la revenire (cookie, 90 de zile). Click = apăsare pe un card de curs din
            secțiunea „Program cursuri". Boții și prefetch-urile nu sunt numărate.
        </p>

        <div style="overflow-x:auto">
        <table class="table" style="max-width:860px">
            <thead>
                <tr>
                    <th>Variantă</th>
                    <th>Headline</th>
                    <th style="text-align:right">Afișări</th>
                    <th style="text-align:right">Click-uri cursuri</th>
                    <th style="text-align:right">CTR</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (['A', 'B'] as $v): ?>
                <tr>
                    <td style="font-weight:700"><?= $v ?><?= $v === $leader && $total_views > 0 ? ' 🏆' : '' ?></td>
                    <td><?= clp_ab_h(str_ireplace('<br>', ' ', $headlines[$v])) ?>
                        <?php if ($v === 'A'): ?><span style="color:var(--text-muted);font-size:11px">(actual)</span><?php endif; ?>
                    </td>
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
            <strong><?= number_format($ctr[$leader], 2) ?>%</strong> față de
            <?= number_format($ctr[$leader === 'A' ? 'B' : 'A'], 2) ?>%.
            <?php if ($total_views < 500): ?>
            <span style="color:var(--text-muted)">Sub ~500 de afișări totale diferența poate fi zgomot — mai lasă testul să ruleze.</span>
            <?php endif; ?>
        </p>
        <?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
