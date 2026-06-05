<h1 class="wp-page-title">Dashboard</h1>

<?php
$_dash_todo_user = clp_current_user()['username'] ?? '';
$_dash_todos_all = clp_load_todos();
$_dash_is_owner  = is_owner();

// Todos: pending for preview
$_dash_td_pending = array_values(array_filter($_dash_todos_all, function ($t) use ($_dash_is_owner, $_dash_todo_user) {
    if (!empty($t['completed'])) return false;
    return $_dash_is_owner ? true : ($t['assigned_to'] ?? '') === $_dash_todo_user;
}));
$_dash_td_preview = array_slice($_dash_td_pending, 0, 5);
$_dash_td_colors  = ['eric6' => '#2563eb', 'andy' => '#16a34a'];

// Cursuri: next upcoming
$_dash_today2   = date('Y-m-d');
$_dash_upcoming = array_values(array_filter($_dash_courses, fn($c) => ($c['date_raw'] ?? '') >= $_dash_today2));
usort($_dash_upcoming, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
$_dash_upcoming = array_slice($_dash_upcoming, 0, 4);

// Speakeri / Locații
$_dash_speakers   = function_exists('load_speakers') ? load_speakers() : [];
$_dash_spk_prev   = array_slice(array_reverse($_dash_speakers), 0, 4);
$_dash_locations  = function_exists('load_locations') ? load_locations() : [];
$_dash_loc_prev   = array_slice($_dash_locations, 0, 5);

// Mesaje
$_dash_msg_data   = function_exists('clp_load_grouped_messages') ? clp_load_grouped_messages() : ['grouped' => [], 'tab_counts' => []];
$_dash_msg_cats   = function_exists('clp_message_categories') ? clp_message_categories() : [];
$_dash_msg_pend   = array_sum($_dash_msg_data['tab_counts'] ?? []);

// Marketing
$_dash_mkt        = function_exists('clp_marketing_load') ? clp_marketing_load() : ['sections' => []];
$_dash_mkt_secs   = $_dash_mkt['sections'] ?? [];
$_dash_mkt_items  = 0;
foreach ($_dash_mkt_secs as $_ms) $_dash_mkt_items += count($_ms['items'] ?? []);
?>

<div class="bc-home-grid">

    <!-- Todos -->
    <a class="bc-card" href="/admin/todos/">
        <div class="bc-card-head">
            <span class="bc-card-icon">✅</span>
            <span class="bc-card-title">To-dos</span>
            <?php if (count($_dash_td_pending) > 0): ?><span class="bc-card-badge"><?= count($_dash_td_pending) ?></span><?php endif; ?>
        </div>
        <?php if (empty($_dash_td_preview)): ?>
            <p class="bc-card-empty">Nicio sarcină în așteptare.</p>
        <?php else: ?>
            <ul class="bc-card-list">
            <?php foreach ($_dash_td_preview as $_dt):
                $_c = $_dash_is_owner ? ($_dash_td_colors[$_dt['assigned_to'] ?? ''] ?? '#9ca3af') : '#2563eb'; ?>
                <li><span class="bc-li-dot" style="background:<?= h($_c) ?>"></span><?= h($_dt['title']) ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <span class="bc-card-foot">Deschide →</span>
    </a>

    <!-- Cursuri -->
    <a class="bc-card" href="/admin/?tab=cursuri">
        <div class="bc-card-head">
            <span class="bc-card-icon">📋</span>
            <span class="bc-card-title">Cursuri</span>
            <?php if ($_dash_scheduled > 0): ?><span class="bc-card-badge"><?= (int)$_dash_scheduled ?></span><?php endif; ?>
        </div>
        <?php if (empty($_dash_upcoming)): ?>
            <p class="bc-card-empty">Niciun curs programat.</p>
        <?php else: ?>
            <ul class="bc-card-list">
            <?php foreach ($_dash_upcoming as $_uc): ?>
                <li><span class="bc-li-dot" style="background:#2563eb"></span><span><?= h($_uc['title'] ?? '') ?><span class="bc-li-meta"> · <?= h($_uc['date_display'] ?? '') ?></span></span></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <span class="bc-card-foot">Vezi toate →</span>
    </a>

    <!-- Speakeri -->
    <a class="bc-card" href="/admin/?tab=speakeri">
        <div class="bc-card-head">
            <span class="bc-card-icon">🎤</span>
            <span class="bc-card-title">Speakeri</span>
            <?php if (count($_dash_speakers) > 0): ?><span class="bc-card-badge"><?= count($_dash_speakers) ?></span><?php endif; ?>
        </div>
        <?php if (empty($_dash_spk_prev)): ?>
            <p class="bc-card-empty">Niciun speaker.</p>
        <?php else: ?>
            <ul class="bc-card-list">
            <?php foreach ($_dash_spk_prev as $_sp): ?>
                <li><span class="bc-li-dot" style="background:#7c3aed"></span><?= h($_sp['name'] ?? '') ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <span class="bc-card-foot">Vezi toți →</span>
    </a>

    <!-- Locații -->
    <a class="bc-card" href="/admin/?tab=locatii">
        <div class="bc-card-head">
            <span class="bc-card-icon">📍</span>
            <span class="bc-card-title">Locații</span>
            <?php if (count($_dash_locations) > 0): ?><span class="bc-card-badge"><?= count($_dash_locations) ?></span><?php endif; ?>
        </div>
        <?php if (empty($_dash_loc_prev)): ?>
            <p class="bc-card-empty">Nicio locație.</p>
        <?php else: ?>
            <ul class="bc-card-list">
            <?php foreach ($_dash_loc_prev as $_lc): ?>
                <li><span class="bc-li-dot" style="background:#0891b2"></span><?= h($_lc['name'] ?? '') ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <span class="bc-card-foot">Vezi toate →</span>
    </a>

    <!-- Mesaje -->
    <a class="bc-card" href="/admin/?tab=mesaje">
        <div class="bc-card-head">
            <span class="bc-card-icon">💬</span>
            <span class="bc-card-title">Mesaje</span>
            <?php if ($_dash_msg_pend > 0): ?><span class="bc-card-badge"><?= (int)$_dash_msg_pend ?></span><?php endif; ?>
        </div>
        <?php
        $_dash_msg_lines = [];
        foreach (($_dash_msg_data['tab_counts'] ?? []) as $_k => $_n) {
            if ($_n > 0) $_dash_msg_lines[] = [($_dash_msg_cats[$_k] ?? ucfirst($_k)), $_n];
        }
        ?>
        <?php if (empty($_dash_msg_lines)): ?>
            <p class="bc-card-empty">Toate mesajele sunt citite.</p>
        <?php else: ?>
            <ul class="bc-card-list">
            <?php foreach (array_slice($_dash_msg_lines, 0, 4) as $_ml): ?>
                <li><span class="bc-li-dot" style="background:#e8a317"></span><span><?= h(is_array($_ml[0]) ? ($_ml[0]['label'] ?? '') : $_ml[0]) ?><span class="bc-li-meta"> · <?= (int)$_ml[1] ?> noi</span></span></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <span class="bc-card-foot">Deschide →</span>
    </a>

    <!-- Marketing -->
    <a class="bc-card" href="/admin/marketing/">
        <div class="bc-card-head">
            <span class="bc-card-icon">📣</span>
            <span class="bc-card-title">Marketing</span>
            <?php if ($_dash_mkt_items > 0): ?><span class="bc-card-badge"><?= (int)$_dash_mkt_items ?></span><?php endif; ?>
        </div>
        <?php if (empty($_dash_mkt_secs)): ?>
            <p class="bc-card-empty">Nicio secțiune.</p>
        <?php else: ?>
            <ul class="bc-card-list">
            <?php foreach (array_slice($_dash_mkt_secs, 0, 4) as $_msec): ?>
                <li><span class="bc-li-dot" style="background:#db2777"></span><span><?= h($_msec['title'] ?? '') ?><span class="bc-li-meta"> · <?= count($_msec['items'] ?? []) ?></span></span></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <span class="bc-card-foot">Deschide →</span>
    </a>
</div>

<button type="button" class="bc-more-btn" id="bcMoreBtn" onclick="bcToggleMore(this)">Mai multe ▾</button>

<div class="bc-home-grid bc-more-grid" id="bcMoreGrid">
    <a class="bc-card bc-card--mini" href="/admin/?tab=imagini">
        <div class="bc-card-head"><span class="bc-card-icon">🖼️</span><span class="bc-card-title">Imagini</span></div>
        <p class="bc-card-empty">Galeria de imagini</p>
    </a>
    <a class="bc-card bc-card--mini" href="/admin/?tab=aspect">
        <div class="bc-card-head"><span class="bc-card-icon">🎨</span><span class="bc-card-title">Aspect</span></div>
        <p class="bc-card-empty">Culori și aspect site</p>
    </a>
    <a class="bc-card bc-card--mini" href="/admin/?tab=vot">
        <div class="bc-card-head"><span class="bc-card-icon">❤️</span><span class="bc-card-title">Vot cursuri</span></div>
        <p class="bc-card-empty">Propuneri la vot</p>
    </a>
    <a class="bc-card bc-card--mini" href="/admin/?tab=colaborari">
        <div class="bc-card-head"><span class="bc-card-icon">🤝</span><span class="bc-card-title">Colaborări</span></div>
        <p class="bc-card-empty">Parteneri și colaborări</p>
    </a>
    <?php if ($_dash_is_owner): ?>
    <a class="bc-card bc-card--mini" href="/admin/statistici/pnl/">
        <div class="bc-card-head"><span class="bc-card-icon">📈</span><span class="bc-card-title">P&amp;L Cursuri</span></div>
        <p class="bc-card-empty">Venituri și cheltuieli</p>
    </a>
    <a class="bc-card bc-card--mini" href="/admin/?tab=config">
        <div class="bc-card-head"><span class="bc-card-icon">⚙️</span><span class="bc-card-title">Setări</span></div>
        <p class="bc-card-empty">Configurare cont și site</p>
    </a>
    <?php endif; ?>
</div>

<script>
function bcToggleMore(btn){
    var g = document.getElementById('bcMoreGrid');
    var open = g.classList.toggle('open');
    btn.innerHTML = open ? 'Mai puține ▴' : 'Mai multe ▾';
}
</script>

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
