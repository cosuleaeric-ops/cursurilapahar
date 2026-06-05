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

// helpers + richer previews
$_bc_initials = function ($name) {
    $p = preg_split('/\s+/', trim((string)$name));
    $a = $p[0] ?? ''; $b = count($p) > 1 ? end($p) : '';
    $ini = function_exists('mb_substr') ? mb_substr($a, 0, 1) . mb_substr($b, 0, 1) : substr($a, 0, 1) . substr($b, 0, 1);
    return function_exists('mb_strtoupper') ? mb_strtoupper($ini) : strtoupper($ini);
};
$_bc_av_colors = ['#2563eb', '#7c3aed', '#db2777', '#0891b2', '#16a34a', '#d97706', '#dc2626'];
$_dash_spk_status_colors = function_exists('clp_speaker_status_colors') ? clp_speaker_status_colors() : [];

// Mesaje: recent sender + snippet
$_dash_msg_prev = [];
$_snip_keys = ['Message', 'Mesaj', 'Course name', 'Venue name', 'City', 'Social', 'Other', 'Email'];
foreach (($_dash_msg_data['grouped'] ?? []) as $_cat => $_list) {
    foreach ($_list as $_m) {
        $f = $_m['fields'] ?? [];
        $snip = '';
        foreach ($_snip_keys as $_sk) { if (!empty($f[$_sk])) { $snip = $f[$_sk]; break; } }
        $_dash_msg_prev[] = ['from' => $f['Name'] ?? ($f['Nume'] ?? '—'), 'snip' => $snip];
    }
}
$_dash_msg_prev = array_slice($_dash_msg_prev, 0, 4);
?>

<div class="bc-home-grid">

    <!-- To-dos -->
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/todos/">To-dos<?php if (count($_dash_td_pending)): ?> <span class="bc-col-count">(<?= count($_dash_td_pending) ?>)</span><?php endif; ?></a>
        <a class="bc-card" href="/admin/todos/">
        <?php if (empty($_dash_td_pending)): ?>
            <p class="bc-card-empty">Nicio sarcină în așteptare.</p>
        <?php else:
            $_td_by_user = [];
            foreach (array_slice($_dash_td_pending, 0, 8) as $_dt) { $_td_by_user[$_dt['assigned_to'] ?? '?'][] = $_dt; }
            foreach ($_td_by_user as $_uname => $_items):
                $_c = $_dash_td_colors[$_uname] ?? '#9ca3af'; ?>
            <div class="bc-td-group">
                <?php if ($_dash_is_owner): ?>
                <div class="bc-td-head"><span class="bc-dot2" style="background:<?= h($_c) ?>"></span><?= h(ucfirst($_uname)) ?></div>
                <?php endif; ?>
                <ul class="bc-td-list">
                <?php foreach (array_slice($_items, 0, 5) as $_dt): ?>
                    <li class="bc-check"><span class="bc-checkbox"></span><?= h($_dt['title']) ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </a>
    </div>

    <!-- Cursuri -->
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=cursuri">Cursuri<?php if ($_dash_scheduled): ?> <span class="bc-col-count">(<?= (int)$_dash_scheduled ?>)</span><?php endif; ?></a>
        <a class="bc-card" href="/admin/?tab=cursuri">
        <?php if (empty($_dash_upcoming)): ?>
            <p class="bc-card-empty">Niciun curs programat.</p>
        <?php else: ?>
            <ul class="bc-list2">
            <?php foreach ($_dash_upcoming as $_uc): ?>
                <li class="bc-line"><span class="bc-line-title"><?= h($_uc['title'] ?? '') ?></span><span class="bc-line-meta"><?= h($_uc['date_display'] ?? '') ?></span></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </a>
    </div>

    <!-- Speakeri -->
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=speakeri">Speakeri<?php if (count($_dash_speakers)): ?> <span class="bc-col-count">(<?= count($_dash_speakers) ?>)</span><?php endif; ?></a>
        <a class="bc-card" href="/admin/?tab=speakeri">
        <?php if (empty($_dash_spk_prev)): ?>
            <p class="bc-card-empty">Niciun speaker.</p>
        <?php else: ?>
            <ul class="bc-list2">
            <?php foreach ($_dash_spk_prev as $_i => $_sp):
                $_st = $_sp['status'] ?? '';
                $_stc = $_dash_spk_status_colors[$_st] ?? '#9ca3af'; ?>
                <li class="bc-person">
                    <span class="bc-avatar" style="background:<?= h($_bc_av_colors[$_i % count($_bc_av_colors)]) ?>"><?= h($_bc_initials($_sp['name'] ?? '')) ?></span>
                    <span class="bc-person-name"><?= h($_sp['name'] ?? '') ?></span>
                    <?php if ($_st): ?><span class="bc-person-status" style="background:<?= h($_stc) ?>" title="<?= h($_st) ?>"></span><?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </a>
    </div>

    <!-- Locații -->
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=locatii">Locații<?php if (count($_dash_locations)): ?> <span class="bc-col-count">(<?= count($_dash_locations) ?>)</span><?php endif; ?></a>
        <a class="bc-card" href="/admin/?tab=locatii">
        <?php if (empty($_dash_loc_prev)): ?>
            <p class="bc-card-empty">Nicio locație.</p>
        <?php else: ?>
            <ul class="bc-list2">
            <?php foreach ($_dash_loc_prev as $_lc): ?>
                <li class="bc-line"><span class="bc-line-title"><?= h($_lc['name'] ?? '') ?></span><?php if (!empty($_lc['days'])): ?><span class="bc-line-meta"><?= h($_lc['days']) ?></span><?php endif; ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </a>
    </div>

    <!-- Mesaje -->
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=mesaje">Mesaje<?php if ($_dash_msg_pend): ?> <span class="bc-col-count">(<?= (int)$_dash_msg_pend ?>)</span><?php endif; ?></a>
        <a class="bc-card" href="/admin/?tab=mesaje">
        <?php if (empty($_dash_msg_prev)): ?>
            <p class="bc-card-empty">Niciun mesaj.</p>
        <?php else: ?>
            <ul class="bc-list2">
            <?php foreach ($_dash_msg_prev as $_i => $_mp): ?>
                <li class="bc-msg">
                    <span class="bc-avatar" style="background:<?= h($_bc_av_colors[$_i % count($_bc_av_colors)]) ?>"><?= h($_bc_initials($_mp['from'])) ?></span>
                    <div style="min-width:0">
                        <div class="bc-msg-from"><?= h($_mp['from']) ?></div>
                        <?php if ($_mp['snip'] !== ''): ?><div class="bc-msg-snip"><?= h($_mp['snip']) ?></div><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </a>
    </div>

    <!-- Marketing -->
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/marketing/">Marketing<?php if ($_dash_mkt_items): ?> <span class="bc-col-count">(<?= (int)$_dash_mkt_items ?>)</span><?php endif; ?></a>
        <a class="bc-card" href="/admin/marketing/">
        <?php if (empty($_dash_mkt_secs)): ?>
            <p class="bc-card-empty">Nicio secțiune.</p>
        <?php else: ?>
            <ul class="bc-list2">
            <?php foreach (array_slice($_dash_mkt_secs, 0, 5) as $_msec): ?>
                <li class="bc-line"><span class="bc-line-title"><?= h($_msec['title'] ?? '') ?></span><span class="bc-line-meta"><?= count($_msec['items'] ?? []) ?></span></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </a>
    </div>
</div>

<button type="button" class="bc-more-btn" id="bcMoreBtn" onclick="bcToggleMore(this)">Mai multe ▾</button>

<div class="bc-home-grid bc-more-grid" id="bcMoreGrid">
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=imagini">Imagini</a>
        <a class="bc-card" href="/admin/?tab=imagini"><p class="bc-card-empty">Galeria de imagini</p></a>
    </div>
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=aspect">Aspect</a>
        <a class="bc-card" href="/admin/?tab=aspect"><p class="bc-card-empty">Culori și aspect site</p></a>
    </div>
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=vot">Vot cursuri</a>
        <a class="bc-card" href="/admin/?tab=vot"><p class="bc-card-empty">Propuneri la vot</p></a>
    </div>
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=colaborari">Colaborări</a>
        <a class="bc-card" href="/admin/?tab=colaborari"><p class="bc-card-empty">Parteneri și colaborări</p></a>
    </div>
    <?php if ($_dash_is_owner): ?>
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/statistici/pnl/">P&amp;L Cursuri</a>
        <a class="bc-card" href="/admin/statistici/pnl/"><p class="bc-card-empty">Venituri și cheltuieli</p></a>
    </div>
    <div class="bc-col">
        <a class="bc-col-title" href="/admin/?tab=config">Setări</a>
        <a class="bc-card" href="/admin/?tab=config"><p class="bc-card-empty">Configurare cont și site</p></a>
    </div>
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
