<?php
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');
if (file_exists(dirname(__DIR__) . '/private/secrets.php')) {
    require dirname(__DIR__) . '/private/secrets.php';
}
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', '');
define('COURSES_FILE',        dirname(__DIR__) . '/data/courses.json');
require_once dirname(__DIR__) . '/lib/courses.php';
require_once dirname(__DIR__) . '/lib/settings.php';
require_once dirname(__DIR__) . '/lib/dates.php';
require_once dirname(__DIR__) . '/lib/messages.php';
require_once dirname(__DIR__) . '/lib/speakers.php';
require_once dirname(__DIR__) . '/lib/vote.php';
require_once dirname(__DIR__) . '/lib/locations.php';
require_once dirname(__DIR__) . '/lib/collaborations.php';
require_once dirname(__DIR__) . '/lib/competitors.php';
require_once dirname(__DIR__) . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/dashboard.php';
require_once dirname(__DIR__) . '/lib/design.php';
define('UPLOADS_DIR',         dirname(__DIR__) . '/assets/images/uploads');
define('UPLOADS_URL',         '/assets/images/uploads');
define('PUBLIC_HTML',         dirname(__DIR__));
require_once dirname(__DIR__) . '/lib/images.php';

clp_ensure_secrets();
clp_ensure_default_users();
$login_error = clp_process_auth_request();

// ── Helpers ───────────────────────────────────────────────────────────────────
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function clp_allowed_course_times(): array {
    return ['17:00', '17:30', '18:00', '18:30'];
}

function load_settings(): array { return clp_load_settings(); }
function save_settings(array $settings): bool { return clp_save_settings($settings); }

// ── Actions (only when authenticated) ────────────────────────────────────────
$action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($_POST['action'] ?? '') : '';
if (is_authenticated() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/actions.php';
}


// ── Load data for display ─────────────────────────────────────────────────────
$courses  = [];
$settings = load_settings();
$tab      = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, ['dashboard','cursuri','imagini','aspect','kit','mesaje','vot','competitori','speakeri','locatii','colaborari','securitate','config'])) $tab = 'dashboard';
if (is_authenticated() && !can_access_tab($tab)) $tab = 'dashboard';

if (is_authenticated()) {
    $courses = clp_load_courses_for_admin();
    usort($courses, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
}

// ── Statistici cursuri (tab Cursuri: lună + sub-tab) ─────────────────────────
$clp_year = (int)date('Y');
$clp_month = (int)date('n');
$clp_ctab = 'cursuri';
$clp_ro_months = clp_ro_months_list(false);
if (is_authenticated() && $tab === 'cursuri') {
    $clp_now = new DateTimeImmutable();
    $clp_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)$clp_now->format('Y');
    $clp_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)$clp_now->format('n');
    $_ctab_raw = $_GET['ctab'] ?? 'cursuri';
    $clp_ctab = in_array($_ctab_raw, ['cursuri', 'participanti', 'calendar'], true) ? $_ctab_raw : 'cursuri';
}

// ── Unread messages badge ─────────────────────────────────────────────────────
$_msg_unread_count = 0;
if ($tab === 'mesaje' && is_authenticated()) {
    clp_mark_messages_read();
} elseif (is_authenticated()) {
    $_msg_unread_count = clp_unread_message_count();
}
?>
<!DOCTYPE html>
<html lang="ro" data-theme="corporate">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Cursuri la Pahar</title>
<?php if (!empty($settings['favicon_path'])): ?><link rel="icon" href="<?= htmlspecialchars($settings['favicon_path']) ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
<script>tailwind={config:{corePlugins:{preflight:false}}}</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/coloris.min.css">
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=3">
</head>
<body>

<?php if (!is_authenticated()): ?>
<!-- ── LOGIN ─────────────────────────────────────────────────────────────────── -->
<div class="login-wrap">
    <div class="login-box">
        <h1>Cursuri la Pahar<br><small style="font-size:13px;color:var(--text-muted);font-weight:400">Panou de administrare</small></h1>
        <?php if (!empty($login_error)): ?>
        <p class="login-error"><?= h($login_error) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="login_username" autocomplete="username" autofocus style="margin-bottom:8px">
            <input type="password" name="login_password" autocomplete="current-password">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Intră</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── ADMIN PANEL ─────────────────────────────────────────────────────────── -->

<header class="wp-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="/admin/" class="brand">Cursuri la Pahar <span>— Admin</span></a>
        <a href="/" class="wp-header-site-link">🌐 Vezi site</a>
    </div>
    <?php
    $real_user = clp_real_user();
    $is_imp    = is_impersonating();
    if ($real_user && ($real_user['role'] ?? '') === 'owner'):
        $all_users = load_users();
        $cur_view  = clp_current_user()['username'] ?? '';
    ?>
    <div style="display:flex;align-items:center;gap:8px">
        <?php if ($is_imp): ?>
        <span style="font-size:11px;background:#fef3c7;color:#92400e;padding:3px 8px;border-radius:12px;font-weight:600">
            Vizualizezi ca: <?= h(ucfirst($cur_view)) ?>
        </span>
        <form method="post" action="/admin/" style="margin:0">
            <input type="hidden" name="action" value="switch_user">
            <input type="hidden" name="target_username" value="<?= h($real_user['username']) ?>">
            <button type="submit" style="font-size:11px;padding:3px 8px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer;color:#374151">
                Înapoi la <?= h(ucfirst($real_user['username'])) ?>
            </button>
        </form>
        <?php else: ?>
        <span style="font-size:12px;color:#a0aec0"><?= h(ucfirst($real_user['username'])) ?></span>
        <div style="position:relative" id="user-switcher">
            <button id="user-switcher-btn"
                style="padding:2px 5px;border:none;background:none;cursor:pointer;color:#c0c8d4;font-size:10px;line-height:1" title="Schimbă cont">
                ▾
            </button>
            <div id="user-switcher-menu" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);min-width:140px;z-index:999">
                <?php foreach ($all_users as $u): if ($u['username'] === $real_user['username']) continue; ?>
                <form method="post" action="/admin/" style="margin:0">
                    <input type="hidden" name="action" value="switch_user">
                    <input type="hidden" name="target_username" value="<?= h($u['username']) ?>">
                    <button type="submit" style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;cursor:pointer;font-size:13px;color:#374151">
                        <?= h(ucfirst($u['username'])) ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        (function() {
            var btn = document.getElementById('user-switcher-btn');
            var menu = document.getElementById('user-switcher-menu');
            btn.addEventListener('click', function(e) { e.stopPropagation(); menu.style.display = menu.style.display === 'block' ? 'none' : 'block'; });
            document.addEventListener('click', function() { menu.style.display = 'none'; });
        })();
        </script>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <span style="font-size:12px;color:#a0aec0"><?= h(ucfirst(clp_current_user()['username'] ?? '')) ?></span>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="btn-logout">Deconectează-te</a>
</header>

<div class="wp-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="wp-sidebar">
        <nav>
            <a href="/admin/" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span> Dashboard
            </a>
            <a href="/admin/?tab=imagini" class="<?= $tab === 'imagini' ? 'active' : '' ?>">
                <span class="nav-icon">🖼️</span> Imagini
            </a>
            <a href="/admin/?tab=aspect" class="<?= $tab === 'aspect' ? 'active' : '' ?>">
                <span class="nav-icon">🎨</span> Aspect
            </a>
            <a href="/admin/?tab=vot" class="<?= $tab === 'vot' ? 'active' : '' ?>">
                <span class="nav-icon">❤️</span> Vot cursuri
            </a>
            <a href="/admin/?tab=competitori" class="<?= $tab === 'competitori' ? 'active' : '' ?>">
                <span class="nav-icon">🔍</span> Competitori
            </a>
            <div class="sidebar-section">Management</div>
            <a href="/admin/?tab=cursuri" class="<?= $tab === 'cursuri' ? 'active' : '' ?>">
                <span class="nav-icon">📋</span> Cursuri
            </a>
            <a href="/admin/?tab=speakeri" class="<?= $tab === 'speakeri' ? 'active' : '' ?>">
                <span class="nav-icon">🎤</span> Speakeri
            </a>
            <a href="/admin/?tab=locatii" class="<?= $tab === 'locatii' ? 'active' : '' ?>">
                <span class="nav-icon">📍</span> Locații
            </a>
            <a href="/admin/?tab=colaborari" class="<?= $tab === 'colaborari' ? 'active' : '' ?>">
                <span class="nav-icon">🤝</span> Colaborări
            </a>
            <a href="/admin/?tab=mesaje" class="<?= $tab === 'mesaje' ? 'active' : '' ?>">
                <span class="nav-icon">💬</span> Mesaje<?php if ($_msg_unread_count > 0): ?><span class="nav-new-badge"><?= $_msg_unread_count ?> <?= $_msg_unread_count === 1 ? 'nou' : 'noi' ?></span><?php endif; ?>
            </a>
            <?php if (is_owner()): ?>
            <div class="sidebar-section">Sistem</div>
            <a href="/admin/statistici/pnl/">
                <span class="nav-icon">📈</span> P&amp;L Cursuri
            </a>
            <a href="/admin/?tab=config" class="<?= $tab === 'config' || $tab === 'securitate' || $tab === 'kit' ? 'active' : '' ?>">
                <span class="nav-icon">⚙️</span> Setări
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- ── MAIN ── -->
    <main class="wp-main">

<?php /* ======================================================= TAB: DASHBOARD */ ?>
<?php if ($tab === 'dashboard'): ?>
<?php extract(clp_dashboard_data(__DIR__), EXTR_SKIP); require __DIR__ . '/partials/dashboard-tab.php'; ?>

<?php /* ======================================================= TAB: CURSURI */ ?>
<?php /* ======================================================= TAB: CURSURI */ ?>
<?php elseif ($tab === 'cursuri'): ?>
    <?php
    $course_speakers = load_speakers_for_picker();
    $course_locations = load_locations_for_picker();
    $course_times = clp_allowed_course_times();
    $course_form_error = trim($_GET['course_error'] ?? '');
    $edit_course = null;
    $edit_course_id = trim($_GET['edit'] ?? '');
    if ($edit_course_id !== '') {
        foreach ($courses as $c) {
            if (($c['id'] ?? '') === $edit_course_id) {
                $edit_course = $c;
                break;
            }
        }
    }
    ?>

    <h1 class="wp-page-title">Cursuri</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success">Curs salvat.</div>
    <?php endif; ?>

    <div class="card" id="course-form-card">
        <div class="card-title"><?= $edit_course ? 'Editează curs' : 'Adaugă curs' ?></div>
        <?php if ($course_form_error): ?>
        <p style="color:var(--danger);font-size:13px;margin:0 0 12px"><?= h($course_form_error) ?></p>
        <?php endif; ?>
        <?php if (empty($course_speakers)): ?>
        <p style="color:var(--text-muted);margin:0">Adaugă mai întâi speakeri în tab-ul <a href="/admin/?tab=speakeri">Speakeri</a>.</p>
        <?php else: ?>
        <form method="post" action="/admin/?tab=cursuri" id="courseForm" class="course-add-form" onsubmit="return validateCourseForm()">
            <input type="hidden" name="action" value="save_course">
            <input type="hidden" name="course_id" id="f_course_id" value="<?= h($edit_course['id'] ?? '') ?>">
            <input type="hidden" name="image_url" id="f_image_url" value="<?= h($edit_course['image_url'] ?? '') ?>">
            <div class="course-add-fields">
                <div class="form-group">
                    <label for="f_title">Nume curs</label>
                    <input type="text" name="title" id="f_title" required oninput="updateCoursePreview()" value="<?= h($edit_course['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="f_date_raw">Dată</label>
                    <input type="date" name="date_raw" id="f_date_raw" required onchange="updateCoursePreview()" value="<?= h($edit_course['date_raw'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="f_time">Oră</label>
                    <select name="time" id="f_time" required onchange="updateCoursePreview()">
                        <option value=""></option>
                        <?php foreach ($course_times as $t): ?>
                        <option value="<?= h($t) ?>" <?= ($edit_course['time'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group speaker-combobox">
                    <label for="f_speaker_input">Speaker</label>
                    <input type="text" id="f_speaker_input" autocomplete="off" required value="<?= h($edit_course ? clp_course_speaker_name($edit_course) : '') ?>">
                    <input type="hidden" name="speaker_id" id="f_speaker_id" value="<?= h($edit_course['speaker_id'] ?? '') ?>">
                    <div id="f_speaker_suggestions" class="speaker-suggestions" hidden></div>
                </div>
                <div class="form-group location-combobox">
                    <label for="f_location_input">Locație</label>
                    <input type="text" name="location" id="f_location_input" autocomplete="off" oninput="updateCoursePreview()" value="<?= h($edit_course['location'] ?? '') ?>">
                    <div id="f_location_suggestions" class="location-suggestions" hidden></div>
                </div>
                <div class="form-group">
                    <label for="f_lt_url">Link LiveTickets</label>
                    <input type="url" name="livetickets_url" id="f_lt_url" onblur="fetchLTImage()" value="<?= h($edit_course['livetickets_url'] ?? '') ?>">
                </div>
            </div>
            <script>
            window.CLP_SPEAKERS_PICKER = <?= json_encode(array_map(fn($s) => [
                'id' => $s['id'] ?? '',
                'name' => $s['name'] ?? '',
                'status' => $s['status'] ?? '',
            ], $course_speakers), JSON_UNESCAPED_UNICODE) ?>;
            window.CLP_LOCATIONS_PICKER = <?= json_encode(array_map(fn($l) => [
                'id' => $l['id'] ?? '',
                'name' => $l['name'] ?? '',
            ], $course_locations), JSON_UNESCAPED_UNICODE) ?>;
            </script>

            <div id="importMsg"></div>

            <div class="course-preview" id="coursePreview" style="display:none">
                <img id="prev_img" src="" alt="" style="display:none">
                <div class="course-preview-body">
                    <div class="course-preview-title" id="prev_title"></div>
                    <div class="course-preview-meta" id="prev_meta"></div>
                </div>
            </div>

            <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary btn-sm"><?= $edit_course ? 'Salvează' : 'Adaugă cursul' ?></button>
                <?php if ($edit_course): ?>
                <a href="/admin/?tab=cursuri&year=<?= (int)$clp_year ?>&month=<?= (int)$clp_month ?>&ctab=cursuri" class="btn btn-secondary btn-sm">Anulează</a>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <?php
    $today_ymd = date('Y-m-d');
    $courses_upcoming = [];
    $courses_past = [];
    foreach ($courses as $c) {
        if (!empty($c['date_raw']) && $c['date_raw'] < $today_ymd) {
            $courses_past[] = $c;
        } else {
            $courses_upcoming[] = $c;
        }
    }
    // Past: most recent first
    usort($courses_past, fn($a, $b) => strcmp($b['date_raw'] ?? '', $a['date_raw'] ?? ''));
    $render_courses_table = function(array $list) {
        ?>
        <table class="wp-table">
            <thead>
                <tr>
                    <th style="width:72px">Imagine</th>
                    <th>Titlu</th>
                    <th>Dată</th>
                    <th style="width:100px">Status</th>
                    <th style="width:240px">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $c):
                    $cid = $c['id'] ?? '';
                    $has_disc = !empty($c['discount_percent']) && !empty($c['discount_ends_at']);
                    $disc_local = '';
                    $disc_active_now = false;
                    if ($has_disc) {
                        try {
                            $dt = new DateTime($c['discount_ends_at']);
                            $dt->setTimezone(new DateTimeZone('Europe/Bucharest'));
                            $disc_local = $dt->format('Y-m-d\TH:i');
                            $disc_active_now = $dt->getTimestamp() > time();
                        } catch (Exception $e) {}
                    }
                ?>
                <tr>
                    <td>
                        <?php if (!empty($c['image_url'])): ?>
                        <img class="course-thumb" src="<?= h($c['image_url']) ?>" alt="">
                        <?php else: ?>
                        <div class="course-thumb-empty"></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:600">
                        <?= h($c['title'] ?? '') ?>
                        <?php $sp_name = clp_course_speaker_name($c); if ($sp_name !== ''): ?>
                        <div style="font-size:12px;font-weight:400;color:var(--text-muted);margin-top:2px"><?= h($sp_name) ?></div>
                        <?php endif; ?>
                        <?php if ($has_disc): ?>
                            <span class="discount-tag <?= $disc_active_now ? 'discount-tag--active' : 'discount-tag--expired' ?>">
                                −<?= (int)$c['discount_percent'] ?>%<?= $disc_active_now ? '' : ' (expirată)' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted)"><?= h($c['date_display'] ?? $c['date_raw'] ?? '') ?></td>
                    <td>
                        <?php if (empty($c['livetickets_url'])): ?>
                        <span class="btn btn-sm status-inactive" style="cursor:default;opacity:.85" title="Adaugă link LiveTickets ca să apară pe site">Draft</span>
                        <?php else: ?>
                        <form method="post" action="/admin/?tab=cursuri" style="display:inline">
                            <input type="hidden" name="action" value="toggle_course">
                            <input type="hidden" name="id" value="<?= h($cid) ?>">
                            <button type="submit" class="btn btn-sm <?= !empty($c['active']) ? 'status-active' : 'status-inactive' ?>">
                                <?= !empty($c['active']) ? 'Activ' : 'Inactiv' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="/admin/?tab=cursuri&edit=<?= h($cid) ?>" class="btn btn-sm btn-secondary">Editează</a>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="toggleDiscountRow('<?= h($cid) ?>')">Reducere ▾</button>
                            <form method="post" action="/admin/?tab=cursuri" onsubmit="return confirm('Ștergi cursul?')" style="display:inline">
                                <input type="hidden" name="action" value="delete_course">
                                <input type="hidden" name="id" value="<?= h($cid) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                            </form>
                            <?php if (!empty($c['livetickets_url'])): ?>
                            <a href="<?= h($c['livetickets_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">LT ↗</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <tr id="discount-row-<?= h($cid) ?>" class="discount-edit-row" style="display:none">
                    <td colspan="5">
                        <form method="post" action="/admin/?tab=cursuri" class="discount-form">
                            <input type="hidden" name="action" value="save_discount">
                            <input type="hidden" name="id" value="<?= h($cid) ?>">
                            <label>Reducere (%):
                                <input type="number" name="discount_percent" min="1" max="100" value="<?= $has_disc ? (int)$c['discount_percent'] : '' ?>" style="width:90px">
                            </label>
                            <label>Expiră la (ora București):
                                <input type="datetime-local" name="discount_ends_at" value="<?= h($disc_local) ?>">
                            </label>
                            <button type="submit" class="btn btn-sm btn-primary">Salvează reducerea</button>
                            <?php if ($has_disc): ?>
                                <button type="submit" name="clear" value="1" class="btn btn-sm btn-danger" onclick="return confirm('Ștergi reducerea?')">Șterge reducerea</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    };
    ?>

    <!-- Courses table (upcoming) -->
    <div class="card">
        <div class="card-title">Cursuri (<?= count($courses_upcoming) ?>)</div>
        <?php if (empty($courses_upcoming)): ?>
        <p style="color:var(--text-muted)">Nu există cursuri adăugate încă.</p>
        <?php else: $render_courses_table($courses_upcoming); endif; ?>
    </div>

    <!-- ── Statistici cursuri (full merge) ─────────────────────────────── -->
    <div class="card" id="clp-stats-card">

        <div class="clp-tabs" style="margin-bottom:16px">
            <button class="clp-tab-btn <?= $clp_ctab === 'cursuri' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'cursuri')">Cursuri</button>
            <button class="clp-tab-btn <?= $clp_ctab === 'calendar' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'calendar')">Calendar</button>
            <button class="clp-tab-btn <?= $clp_ctab === 'participanti' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'participanti')">Participanți</button>
            <span class="clp-tabs-sep" aria-hidden="true"></span>
            <span id="clpMonthNav" style="display:contents">
                <button type="button" onclick="clpNav(-1)" class="clp-tab-btn" style="padding:7px 12px!important;line-height:1" aria-label="Luna anterioară">&#8592;</button>
                <span id="clpMonthLabel" class="clp-tab-btn active" style="cursor:default;min-width:96px;text-align:center;pointer-events:none"><?= ucfirst($clp_ro_months[$clp_month ?? 1]) . ' ' . ($clp_year ?? date('Y')) ?></span>
                <button type="button" onclick="clpNav(+1)" class="clp-tab-btn" style="padding:7px 12px!important;line-height:1" aria-label="Luna următoare">&#8594;</button>
            </span>
        </div>

        <!-- Tab: Cursuri (statistici — încărcat via API) -->
        <div class="clp-tab-panel <?= $clp_ctab === 'cursuri' ? 'active' : '' ?>" id="clp-panel-cursuri">
            <p style="color:var(--text-muted)">Se încarcă…</p>
        </div>

        <!-- Tab: Participanți (încărcat via API) -->
        <div class="clp-tab-panel <?= $clp_ctab === 'participanti' ? 'active' : '' ?>" id="clp-panel-participanti">
            <p style="color:var(--text-muted)">Se încarcă…</p>
        </div>

        <!-- Tab: Calendar -->
        <div class="clp-tab-panel <?= $clp_ctab === 'calendar' ? 'active' : '' ?>" id="clp-panel-calendar">

            <div id="calGrid"></div>
            <div style="display:flex;gap:16px;margin-top:12px;font-size:12px;color:#6b7280;flex-wrap:wrap">
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#dbeafe;border:1px solid #bfdbfe;display:inline-block"></span> Curs viitor</span>
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#1d4ed8;display:inline-block"></span> Curs azi</span>
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#f1f5f9;border:1px solid #e5e7eb;display:inline-block"></span> Curs trecut</span>
            </div>
        </div>
    </div>

    <script>
    window.CLP_STATS = <?= json_encode([
        'year' => (int)($clp_year ?? date('Y')),
        'month' => (int)($clp_month ?? date('n')),
        'calYear' => (int)date('Y'),
        'calMonth' => (int)date('n'),
        'calCourses' => array_map(fn($c) => ['date' => $c['date_raw'] ?? '', 'title' => $c['title'] ?? ''], $courses),
        'initCalendar' => ($clp_ctab ?? '') === 'calendar',
        'scrollToStats' => isset($_GET['saved']),
        'activeTab' => $clp_ctab ?? 'cursuri',
    ], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="/admin/assets/js/admin-cursuri-stats.js?v=2"></script>

<?php /* ======================================================= TAB: IMAGINI */ ?>
<?php elseif ($tab === 'imagini'): ?>
<?php $all_images = get_all_images(); require __DIR__ . '/partials/imagini-tab.php'; ?>

<?php /* ======================================================= TAB: ASPECT */ ?>
<?php /* ======================================================= TAB: ASPECT */ ?>
<?php elseif ($tab === 'aspect'): ?>
<?php require __DIR__ . '/partials/aspect-tab.php'; ?>

<?php elseif ($tab === 'kit'): ?>
<?php elseif ($tab === 'kit'): ?>
<?php header('Location: /admin/?tab=config'); exit; ?>

<?php /* ======================================================= TAB: MESAJE */ ?>
<?php elseif ($tab === 'mesaje'): ?>
<h1 class="wp-page-title">Mesaje</h1>
<?php if (isset($_GET['deleted'])): ?>
<div class="notice notice-success">Mesajul a fost șters.</div>
<?php endif; ?>

<?php
$categories = clp_message_categories();
$_msg_data  = clp_load_grouped_messages();
$grouped    = $_msg_data['grouped'];
$tab_counts = $_msg_data['tab_counts'];
require __DIR__ . '/partials/messages-tab.php';
?>



<?php elseif ($tab === 'vot'): ?>
<?php
$_vc = clp_vote_admin_context($_GET['edit'] ?? '');
$vote_courses = $_vc['courses'];
$edit_vc = $_vc['edit'];
require __DIR__ . '/partials/vot-tab.php';
?>

<?php /* ======================================================= TAB: COMPETITORI */ ?>
<?php elseif ($tab === 'competitori'): ?>
<?php $_competitors = clp_competitors_list(); require __DIR__ . '/partials/competitori-tab.php'; ?>

<?php /* ======================================================= TAB: SPEAKERI */ ?>
<?php elseif ($tab === 'speakeri'): ?>
<?php
$_sp_ctx = clp_speakers_admin_context($_GET['edit'] ?? '');
$speakers = $_sp_ctx['speakers'];
$edit_sp = $_sp_ctx['edit'];
$edit_sp_id = $_GET['edit'] ?? '';
$sp_status_colors = clp_speaker_status_colors();
$_sp_contacted = clp_contacted_message_leads();
require __DIR__ . '/partials/speakeri-tab.php';
?>

<?php /* ======================================================= TAB: LOCATII */ ?>
<?php elseif ($tab === 'locatii'): ?>
<?php
$_loc = clp_locations_admin_context($_GET['edit'] ?? '');
$locations = $_loc['items'];
$edit_loc = $_loc['edit'];
require __DIR__ . '/partials/locatii-tab.php';
?>

<?php /* ======================================================= TAB: COLABORARI */ ?>
<?php elseif ($tab === 'colaborari'): ?>
<?php
$_col = clp_collaborations_admin_context($_GET['edit'] ?? '');
$collabs = $_col['items'];
$edit_col = $_col['edit'];
require __DIR__ . '/partials/colaborari-tab.php';
?>

<?php /* Securitate tab redirects to config */ ?>
<?php elseif ($tab === 'securitate'): ?>
<?php header('Location: /admin/?tab=config'); exit; ?>

<?php /* ======================================================= TAB: CONFIG (Setări) */ ?>
<?php elseif ($tab === 'config'): ?>
<?php require __DIR__ . '/partials/config-tab.php'; ?>

<?php endif; ?>

    </main>
</div><!-- /wp-layout -->

<script src="/admin/assets/js/admin-common.js?v=2"></script>
<?php if ($tab === 'cursuri'): ?>
<script src="/admin/assets/js/admin-course-form.js?v=1"></script>
<?php elseif ($tab === 'imagini'): ?>
<script src="/admin/assets/js/admin-imagini.js?v=1"></script>
<?php elseif ($tab === 'mesaje'): ?>
<script>window.CLP_IS_OWNER = <?= is_owner() ? 'true' : 'false' ?>;</script>
<script src="/admin/assets/js/admin-mesaje.js?v=1"></script>
<?php elseif ($tab === 'speakeri'): ?>
<script src="/admin/assets/js/admin-speakeri.js?v=1"></script>
<?php elseif ($tab === 'aspect'): ?>
<script src="/assets/js/coloris.min.js"></script>
<script src="/admin/assets/js/admin-aspect.js?v=1"></script>
<?php endif; ?>

<?php endif; ?>
</body>
</html>
