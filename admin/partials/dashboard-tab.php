<h1 class="wp-page-title">Dashboard</h1>

<?php
$_dash_todo_user = clp_current_user()['username'] ?? '';
$_dash_todos_all = clp_load_todos();
$_dash_is_owner  = is_owner();

// Todos: my own pending only
$_dash_td_pending = array_values(array_filter($_dash_todos_all, fn($t) => empty($t['completed']) && ($t['assigned_to'] ?? '') === $_dash_todo_user));
$_dash_td_preview = array_slice($_dash_td_pending, 0, 5);
$_dash_td_dot     = ['eric6' => '#2563eb', 'andy' => '#16a34a'][$_dash_todo_user] ?? '#2563eb';

// Cursuri: next upcoming
$_dash_today2   = date('Y-m-d');
$_dash_upcoming = array_values(array_filter($_dash_courses, fn($c) => ($c['date_raw'] ?? '') >= $_dash_today2));
usort($_dash_upcoming, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
$_dash_upcoming = array_slice($_dash_upcoming, 0, 4);

// Mesaje
$_dash_msg_data   = function_exists('clp_load_grouped_messages') ? clp_load_grouped_messages() : ['grouped' => [], 'tab_counts' => []];
$_dash_msg_cats   = function_exists('clp_message_categories') ? clp_message_categories() : [];
$_dash_msg_pend   = array_sum($_dash_msg_data['tab_counts'] ?? []);
?>

<div class="bc-home-grid">

    <!-- Todos -->
    <a class="bc-card" href="/admin/todos/">
        <div class="bc-card-head">
            <span class="bc-card-icon">✅</span>
            <span class="bc-card-title">To-dos</span>
        </div>
        <?php if (!empty($_dash_td_preview)): ?>
            <ul class="bc-card-list">
            <?php foreach ($_dash_td_preview as $_dt): ?>
                <li><span class="bc-li-dot" style="background:<?= h($_dash_td_dot) ?>"></span><?= h(clp_todo_plain_title($_dt['title'])) ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </a>

    <!-- Cursuri -->
    <a class="bc-card" href="/admin/?tab=cursuri">
        <div class="bc-card-head">
            <span class="bc-card-icon">📋</span>
            <span class="bc-card-title">Cursuri</span>
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
    </a>

    <!-- Mesaje -->
    <a class="bc-card" href="/admin/?tab=mesaje">
        <div class="bc-card-head">
            <span class="bc-card-icon">💬</span>
            <span class="bc-card-title">Mesaje</span>
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
    </a>
</div>

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

<?php $_tpls = $settings['templates'] ?? []; ?>
<div class="dash-section" style="margin-bottom:24px">
    <div class="dash-section-title">
        <span>Templates</span>
        <a href="/admin/?tab=templates">Editează →</a>
    </div>
    <?php if (empty($_tpls)): ?>
        <p class="bc-card-empty">Niciun template încă. <a href="/admin/?tab=templates" style="color:var(--accent)">Adaugă unul</a>.</p>
    <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($_tpls as $_tpl): ?>
            <button type="button" class="ql-btn" data-tpl-text="<?= h($_tpl['text'] ?? '') ?>" onclick="clpCopyTemplate(this)">
                <span style="font-size:15px"><?= h($_tpl['icon'] ?? '📋') ?></span>
                <?= h($_tpl['label'] ?? '') ?>
            </button>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


<?php
$_dash_cal_json = [];
foreach ($_dash_courses as $_c) {
    $d = $_c['date_raw'] ?? '';
    if ($d === '') continue;
    $_dash_cal_json[$d][] = ['title' => $_c['title'] ?? ''];
}
$_mc_today_str = (new DateTime('now', new DateTimeZone('Europe/Bucharest')))->format('Y-m-d');
$_dash_ig_posts = clp_load_ig_posts();
$_dash_ig_types = clp_ig_post_types();
?>

<div class="dash-section" style="margin-bottom:20px">
    <div class="dash-section-title" style="margin-bottom:10px">
        <div class="dash-cal-heading">
            <span>Urmatoarele cursuri</span>
            <button type="button" class="dash-cal-arrow" id="dashCalPrev" aria-label="Luna anterioară">&#8592;</button>
            <button type="button" class="dash-cal-arrow" id="dashCalNext" aria-label="Luna următoare">&#8594;</button>
        </div>
    </div>
    <div class="mini-cal" id="dashMiniCal"></div>
</div>

<script>
window.DASH_CAL = <?= json_encode([
    'today' => $_mc_today_str,
    'coursesByDay' => $_dash_cal_json,
    'igPosts' => $_dash_ig_posts,
    'igPostTypes' => $_dash_ig_types,
], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/admin/assets/js/admin-dashboard.js?v=5"></script>
