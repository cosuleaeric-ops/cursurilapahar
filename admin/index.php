<?php
define('ADMIN_PASSWORD', 'clp2026admin');
define('AUTH_SECRET',    'clp-auth-xk9p-2026-secret');
define('COURSES_FILE',   dirname(__DIR__) . '/data/courses.json');

// ── Cookie-based auth (no sessions needed) ────────────────────────────────────
function is_authenticated(): bool {
    $cookie = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie) return false;
    $expected = hash_hmac('sha256', 'clp_admin_ok', AUTH_SECRET);
    return hash_equals($expected, $cookie);
}
function set_auth_cookie(): void {
    $token = hash_hmac('sha256', 'clp_admin_ok', AUTH_SECRET);
    setcookie('clp_auth', $token, [
        'expires'  => time() + 86400 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
function clear_auth_cookie(): void {
    setcookie('clp_auth', '', ['expires' => time() - 3600, 'path' => '/']);
}

if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === ADMIN_PASSWORD) {
        set_auth_cookie();
        header('Location: /admin/');
        exit;
    } else {
        $login_error = 'Parolă incorectă.';
    }
}
if (isset($_GET['logout'])) {
    clear_auth_cookie();
    header('Location: /admin/');
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function load_courses(): array {
    if (!file_exists(COURSES_FILE)) return [];
    return json_decode(file_get_contents(COURSES_FILE), true) ?: [];
}
function save_courses(array $courses): void {
    $dir = dirname(COURSES_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(COURSES_FILE, json_encode(array_values($courses), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// ── Actions (only when authenticated) ────────────────────────────────────────
$action_msg = '';
if (!empty(is_authenticated())) {

    // Delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $id = $_POST['id'] ?? '';
        $courses = load_courses();
        $courses = array_filter($courses, fn($c) => ($c['id'] ?? '') !== $id);
        save_courses($courses);
        header('Location: /admin/');
        exit;
    }

    // Toggle active
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
        $id = $_POST['id'] ?? '';
        $courses = load_courses();
        foreach ($courses as &$c) {
            if (($c['id'] ?? '') === $id) {
                $c['active'] = !($c['active'] ?? false);
                break;
            }
        }
        save_courses($courses);
        header('Location: /admin/');
        exit;
    }

    // Save course (add or edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
        $id = trim($_POST['course_id'] ?? '');
        $courses = load_courses();

        $entry = [
            'id'             => $id ?: uniqid('c', true),
            'title'          => trim($_POST['title'] ?? ''),
            'date_display'   => trim($_POST['date_display'] ?? ''),
            'date_raw'       => trim($_POST['date_raw'] ?? ''),
            'time'           => trim($_POST['time'] ?? ''),
            'location'       => trim($_POST['location'] ?? ''),
            'livetickets_url' => trim($_POST['livetickets_url'] ?? ''),
            'image_url'      => trim($_POST['image_url'] ?? ''),
            'active'         => !empty($_POST['active']),
        ];

        if ($id) {
            // Update existing
            $found = false;
            foreach ($courses as &$c) {
                if (($c['id'] ?? '') === $id) {
                    $c = $entry;
                    $found = true;
                    break;
                }
            }
            if (!$found) $courses[] = $entry;
        } else {
            $courses[] = $entry;
        }

        save_courses($courses);
        header('Location: /admin/');
        exit;
    }
}

// ── Load data for display ─────────────────────────────────────────────────────
$courses = [];
$edit_course = null;
if (!empty(is_authenticated())) {
    $courses = load_courses();
    usort($courses, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));

    // Edit mode
    if (isset($_GET['edit'])) {
        foreach ($courses as $c) {
            if (($c['id'] ?? '') === $_GET['edit']) {
                $edit_course = $c;
                break;
            }
        }
    }
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Cursuri la Pahar</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #0D0D0D;
    --surface: #161616;
    --surface2: #1E1E1E;
    --border: #2a2a2a;
    --accent: #C9A84C;
    --accent-dim: #a88836;
    --text: #E8E4DC;
    --text-muted: #888;
    --danger: #c0392b;
    --success: #27ae60;
    --font: 'Segoe UI', system-ui, sans-serif;
}
body { background: var(--bg); color: var(--text); font-family: var(--font); font-size: 14px; line-height: 1.5; min-height: 100vh; }

/* Login */
.login-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.login-box { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 40px; width: 320px; }
.login-box h1 { font-size: 20px; color: var(--accent); margin-bottom: 24px; text-align: center; }
.login-box input { width: 100%; padding: 10px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: 14px; margin-bottom: 12px; }
.login-box input:focus { outline: none; border-color: var(--accent); }
.login-error { color: var(--danger); font-size: 13px; margin-bottom: 10px; }

/* Layout */
.admin-header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
.admin-header .brand { color: var(--accent); font-weight: 700; font-size: 16px; }
.admin-body { max-width: 1100px; margin: 0 auto; padding: 24px 16px; }

/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; transition: opacity .15s; }
.btn:hover { opacity: .85; }
.btn-accent { background: var(--accent); color: #0D0D0D; }
.btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
.btn-outline:hover { border-color: var(--accent); color: var(--accent); }
.btn-danger { background: var(--danger); color: #fff; }
.btn-sm { padding: 5px 10px; font-size: 12px; }
.btn-success-toggle { background: #1a3a2a; border: 1px solid var(--success); color: var(--success); }
.btn-inactive-toggle { background: #2a1a1a; border: 1px solid #555; color: #888; }

/* Cards / sections */
.card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 20px; margin-bottom: 24px; }
.card h2 { font-size: 16px; color: var(--accent); margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }

/* Table */
table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 8px 10px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--border); }
td { padding: 10px; border-bottom: 1px solid var(--border); vertical-align: middle; }
tr:last-child td { border-bottom: none; }
.course-title { font-weight: 600; }
.course-date { color: var(--text-muted); font-size: 13px; }
.actions { display: flex; gap: 6px; flex-wrap: wrap; }

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group.full { grid-column: 1 / -1; }
label { font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
input[type="text"], input[type="url"], input[type="date"], input[type="time"], textarea {
    width: 100%; padding: 9px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: 14px; font-family: inherit;
}
input:focus, textarea:focus { outline: none; border-color: var(--accent); }
.checkbox-row { display: flex; align-items: center; gap: 8px; padding: 8px 0; }
.checkbox-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--accent); }
.checkbox-row label { font-size: 14px; color: var(--text); text-transform: none; letter-spacing: 0; cursor: pointer; }

/* Import section */
.import-row { display: flex; gap: 10px; }
.import-row input { flex: 1; }
#importMsg { margin-top: 8px; font-size: 13px; }
#importMsg.success { color: var(--success); }
#importMsg.error { color: var(--danger); }
.image-preview { margin-top: 8px; }
.image-preview img { max-width: 200px; max-height: 120px; border-radius: 6px; border: 1px solid var(--border); }

/* Responsive */
@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
    .form-group.full { grid-column: 1; }
}
</style>
</head>
<body>

<?php if (empty(is_authenticated())): ?>
<!-- ── LOGIN ── -->
<div class="login-wrap">
    <div class="login-box">
        <h1>Cursuri la Pahar<br><small style="font-size:13px;color:var(--text-muted)">Admin</small></h1>
        <?php if (!empty($login_error)): ?>
        <p class="login-error"><?= h($login_error) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="password" name="login_password" placeholder="Parolă" autofocus>
            <button type="submit" class="btn btn-accent" style="width:100%;justify-content:center">Intră</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── ADMIN PANEL ── -->
<header class="admin-header">
    <span class="brand">Cursuri la Pahar – Admin</span>
    <a href="/admin/?logout=1" class="btn btn-outline btn-sm">Deconectează-te</a>
</header>

<div class="admin-body">

    <!-- Add course -->
    <div class="card">
        <h2><?= $edit_course ? 'Editează cursul' : 'Adaugă curs nou' ?></h2>

        <?php if (!$edit_course): ?>
        <!-- Import row -->
        <div class="import-row" style="margin-bottom:16px;">
            <input type="url" id="ltUrl" placeholder="https://www.livetickets.ro/bilete/slug-eveniment" style="flex:1;">
            <button class="btn btn-accent" onclick="importLT()">Importă</button>
        </div>
        <div id="importMsg" style="margin-bottom:12px;font-size:13px;"></div>
        <?php endif; ?>

        <form method="post" action="/admin/" id="courseForm" <?= !$edit_course ? 'style="display:none"' : '' ?>>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="course_id"       id="courseId"      value="<?= h($edit_course['id'] ?? '') ?>">
            <input type="hidden" name="title"           id="f_title"       value="<?= h($edit_course['title'] ?? '') ?>">
            <input type="hidden" name="date_display"    id="f_date_display" value="<?= h($edit_course['date_display'] ?? '') ?>">
            <input type="hidden" name="date_raw"        id="f_date_raw"    value="<?= h($edit_course['date_raw'] ?? '') ?>">
            <input type="hidden" name="time"            id="f_time"        value="<?= h($edit_course['time'] ?? '') ?>">
            <input type="hidden" name="location"        id="f_location"    value="<?= h($edit_course['location'] ?? '') ?>">
            <input type="hidden" name="livetickets_url" id="f_lt_url"      value="<?= h($edit_course['livetickets_url'] ?? '') ?>">
            <input type="hidden" name="image_url"       id="f_image_url"   value="<?= h($edit_course['image_url'] ?? '') ?>">
            <input type="hidden" name="active"          id="f_active"      value="1">

            <!-- Preview (shown after import or in edit mode) -->
            <div id="coursePreview" style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:16px;<?= !$edit_course ? 'display:none' : '' ?>">
                <?php if ($edit_course): ?>
                <div style="display:flex;gap:16px;align-items:flex-start;">
                    <?php if (!empty($edit_course['image_url'])): ?>
                    <img src="<?= h($edit_course['image_url']) ?>" style="width:100px;height:60px;object-fit:cover;border-radius:6px;flex-shrink:0;" alt="">
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:700;margin-bottom:4px;" id="prev_title"><?= h($edit_course['title'] ?? '') ?></div>
                        <div style="color:var(--text-muted);font-size:13px;" id="prev_meta"><?= h(($edit_course['date_display'] ?? '') . ' · ' . ($edit_course['time'] ?? '') . ' · ' . ($edit_course['location'] ?? '')) ?></div>
                    </div>
                </div>
                <?php else: ?>
                <div style="display:flex;gap:16px;align-items:flex-start;">
                    <img id="prev_img" src="" style="width:100px;height:60px;object-fit:cover;border-radius:6px;flex-shrink:0;display:none;" alt="">
                    <div>
                        <div style="font-weight:700;margin-bottom:4px;" id="prev_title"></div>
                        <div style="color:var(--text-muted);font-size:13px;" id="prev_meta"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-accent"><?= $edit_course ? 'Salvează modificările' : 'Adaugă cursul' ?></button>
                <?php if ($edit_course): ?>
                <a href="/admin/" class="btn btn-outline">Anulează</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Courses list -->
    <div class="card">
        <h2>Cursuri (<?= count($courses) ?>)</h2>
        <?php if (empty($courses)): ?>
        <p style="color:var(--text-muted)">Nu există cursuri adăugate încă.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Titlu</th>
                    <th>Dată</th>
                    <th>Locație</th>
                    <th>Status</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): ?>
                <tr>
                    <td>
                        <div class="course-title"><?= h($c['title'] ?? '') ?></div>
                        <?php if (!empty($c['time'])): ?>
                        <div class="course-date"><?= h($c['time']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="course-date"><?= h($c['date_display'] ?? $c['date_raw'] ?? '') ?></td>
                    <td class="course-date"><?= h($c['location'] ?? '') ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= h($c['id'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm <?= !empty($c['active']) ? 'btn-success-toggle' : 'btn-inactive-toggle' ?>">
                                <?= !empty($c['active']) ? 'Activ' : 'Inactiv' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="/admin/?edit=<?= urlencode($c['id'] ?? '') ?>" class="btn btn-outline btn-sm">Editează</a>
                            <form method="post" onsubmit="return confirm('Ștergi cursul?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= h($c['id'] ?? '') ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Șterge</button>
                            </form>
                            <?php if (!empty($c['livetickets_url'])): ?>
                            <a href="<?= h($c['livetickets_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">LiveTickets ↗</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div><!-- /admin-body -->

<script>
async function importLT() {
    const url = document.getElementById('ltUrl').value.trim();
    const msg = document.getElementById('importMsg');
    if (!url) { msg.style.color='var(--danger)'; msg.textContent = 'Introdu un URL.'; return; }

    msg.style.color = 'var(--text-muted)'; msg.textContent = 'Se importă…';

    try {
        const res  = await fetch('/api/livetickets.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url })
        });
        const data = await res.json();

        if (data.success && data.data) {
            const d = data.data;
            document.getElementById('f_title').value         = d.title || '';
            document.getElementById('f_date_display').value  = d.date_display || '';
            document.getElementById('f_date_raw').value      = d.date_raw || '';
            document.getElementById('f_time').value          = d.time || '';
            document.getElementById('f_location').value      = d.location || '';
            document.getElementById('f_lt_url').value        = d.livetickets_url || '';
            document.getElementById('f_image_url').value     = d.image_url || '';
            document.getElementById('courseId').value        = '';

            // Update preview
            document.getElementById('prev_title').textContent = d.title || '';
            document.getElementById('prev_meta').textContent  =
                [d.date_display, d.time, d.location].filter(Boolean).join(' · ');
            const img = document.getElementById('prev_img');
            if (d.image_url) { img.src = d.image_url; img.style.display = 'block'; }

            document.getElementById('coursePreview').style.display = 'block';
            document.getElementById('courseForm').style.display    = 'block';

            msg.style.color = 'var(--success)'; msg.textContent = '✓ Import reușit!';
        } else {
            msg.style.color = 'var(--danger)';
            msg.textContent = data.message || 'Eroare la import.';
        }
    } catch (err) {
        msg.style.color = 'var(--danger)';
        msg.textContent = 'Eroare: ' + err.message;
    }
}
</script>

<?php endif; ?>
</body>
</html>
 
