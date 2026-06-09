<?php
require __DIR__ . '/../auth_check.php';
require_once dirname(__DIR__, 2) . '/lib/admin.php';
require_once dirname(__DIR__, 2) . '/lib/messages.php';
require_once dirname(__DIR__, 2) . '/lib/todos.php';

if (!is_authenticated()) {
    header('Location: /admin/');
    exit;
}

$current_user = clp_current_user();
$current_username = $current_user['username'] ?? '';
$owner = is_owner();

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_todo') {
        $title = trim($_POST['title'] ?? '');
        $assigned_to = $_POST['assigned_to'] ?? '';
        $valid_users = array_column(load_users(), 'username');
        if ($title !== '' && in_array($assigned_to, $valid_users, true)) {
            clp_add_todo($title, $assigned_to, $current_username);
        }
        header('Location: /admin/todos/');
        exit;
    }

    if ($action === 'toggle_todo') {
        $id = $_POST['id'] ?? '';
        if ($id) clp_toggle_todo($id);
        header('Location: /admin/todos/');
        exit;
    }

    if ($action === 'delete_todo') {
        $id = $_POST['id'] ?? '';
        if ($id) clp_delete_todo($id);
        header('Location: /admin/todos/');
        exit;
    }
}

$settings = load_settings();
$_msg_pending_count = clp_pending_message_count();
$tab = 'todos';

$all_users = load_users();

$_all_todos = clp_load_todos();
$pending = array_values(array_filter($_all_todos, fn($t) => empty($t['completed'])));
$done    = array_values(array_filter($_all_todos, fn($t) => !empty($t['completed'])));
$done_count = count($done);

// Group completed todos by the day they were marked done (fallback: created day)
$done_groups = [];
foreach ($done as $t) {
    $ts  = $t['completed_at'] ?? ($t['created_at'] ?? '');
    $day = $ts !== '' ? substr($ts, 0, 10) : '';
    $done_groups[$day][] = $t;
}
krsort($done_groups);

$_ro_months_full = ['', 'ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
$_today_ymd = date('Y-m-d');
$_yest_ymd  = date('Y-m-d', strtotime('-1 day'));
$day_label = function ($day) use ($_ro_months_full, $_today_ymd, $_yest_ymd) {
    if ($day === '') return 'Mai demult';
    if ($day === $_today_ymd) return 'Azi';
    if ($day === $_yest_ymd) return 'Ieri';
    $p = explode('-', $day);
    return (int)($p[2] ?? 0) . ' ' . ($_ro_months_full[(int)($p[1] ?? 0)] ?? '') . ' ' . ($p[0] ?? '');
};

$user_display  = ['eric6' => 'Eric', 'andy' => 'Andy'];
$user_colors   = ['eric6' => '#2563eb', 'andy' => '#16a34a'];
$user_initials = ['eric6' => 'E', 'andy' => 'A'];

$_av_dir = dirname(__DIR__, 2) . '/assets/images/avatars';
$user_avatars = [];
foreach ($all_users as $u) {
    $un = $u['username'];
    $user_avatars[$un] = '';
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) {
        $p = "$_av_dir/$un.$e";
        if (is_file($p)) { $user_avatars[$un] = "/assets/images/avatars/$un.$e?t=" . filemtime($p); break; }
    }
}

$render_assign = function ($uname) use ($user_display, $user_colors, $user_avatars, $user_initials) {
    if ($uname === '') return '';
    $name = $user_display[$uname] ?? ucfirst($uname);
    $col  = $user_colors[$uname] ?? '#6b7280';
    $av   = $user_avatars[$uname] ?? '';
    $ini  = $user_initials[$uname] ?? mb_strtoupper(mb_substr($name, 0, 1));
    ob_start(); ?>
<span class="todo-assign todo-assign--<?= h($uname) ?>">
    <span class="todo-av" style="background:<?= h($col) ?>"><?= h($ini) ?><?php if ($av): ?><img src="<?= h($av) ?>" alt="" style="object-position:<?= $uname === 'eric6' ? 'top' : 'center' ?>" onerror="this.remove()"><?php endif; ?></span>
    <span class="todo-assign-name"><?= h($name) ?></span>
</span>
<?php return ob_get_clean();
};
?>
<!DOCTYPE html>
<html lang="ro" data-theme="corporate">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>To-dos – Admin</title>
<?php if (!empty($settings['favicon_path'])): ?><link rel="icon" href="<?= h($settings['favicon_path']) ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
<script>tailwind={config:{corePlugins:{preflight:false}}}</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=37">
<style>
.todos-grid { display: flex; flex-direction: column; gap: 26px; }
.todo-list-block { }
.todo-list-head { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
.todo-list-circle { width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
.todo-list-name { font-size: 17px; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
.todo-items { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.todo-item { display: flex; align-items: center; gap: 11px; padding: 5px 8px 5px 3px; border-radius: 8px; transition: background .1s; }
.todo-item:hover { background: var(--bg); }
.todo-assign--eric6 { background: #eff6ff; }
.todo-assign--eric6 .todo-assign-name { color: #2563eb; }
.todo-assign--andy { background: #f0fdf4; }
.todo-assign--andy .todo-assign-name { color: #16a34a; }
.todo-check { flex-shrink: 0; margin: 0; display: flex; }
.todo-check input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); }
.todo-text { font-size: 15px; color: var(--text); line-height: 1.4; }
.todo-text.done { text-decoration: line-through; color: var(--text-muted); }

/* assignment pill (Basecamp-style) */
.todo-assign { display: inline-flex; align-items: center; gap: 7px; background: var(--bg); border-radius: 999px; padding: 3px 12px 3px 4px; white-space: nowrap; }
.todo-av { position: relative; width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: 700; overflow: hidden; }
.todo-av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.todo-assign-name { font-size: 13px; color: var(--text-muted); font-weight: 500; }
.todo-item > form { margin: 0; }
.todo-item > form:last-child { margin-left: auto; }

/* assignee chooser in add form */
.todo-add-assign { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.todo-add-assign-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
.todo-assign-pick { cursor: pointer; display: inline-flex; }
.todo-assign-pick input { position: absolute; opacity: 0; width: 0; height: 0; }
.todo-assign-pick .todo-assign { border: 2px solid transparent; cursor: pointer; transition: border-color .12s, background .12s; }
.todo-assign-pick input:checked + .todo-assign { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.todo-del { opacity: 0; flex-shrink: 0; background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 18px; line-height: 1; padding: 0 4px; transition: opacity .15s, color .15s; }
.todo-item:hover .todo-del { opacity: 1; }
.todo-del:hover { color: var(--danger); }
.todo-empty { color: var(--text-muted); font-size: 14px; padding: 5px 3px; }

/* completed collapsible */
.todo-completed { margin-top: 0; }
.todo-completed > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 6px 3px; font-size: 13px; color: var(--text-muted); user-select: none; outline: none; }
.todo-completed > summary::-webkit-details-marker { display: none; }
.todo-completed > summary:hover { color: var(--text); }
.todo-completed-caret { font-size: 10px; color: var(--text-muted); transition: transform .15s; display: inline-block; }
.todo-completed[open] .todo-completed-caret { transform: rotate(90deg); }
.todo-completed-items { display: flex; flex-direction: column; }
.todo-done-day { font-size: 12px; font-weight: 600; color: var(--text-muted); letter-spacing: .02em; margin: 12px 3px 3px; }
.todo-completed > .todo-done-day:first-of-type { margin-top: 6px; }

.todo-add { margin-top: 6px; }
.todo-add-link { background: none; border: none; cursor: pointer; color: var(--accent); font-size: 15px; padding: 5px 3px; display: inline-flex; align-items: center; gap: 11px; text-decoration: none; }
.todo-add-link:hover { text-decoration: underline; }
.todo-add-checkmark { width: 18px; height: 18px; border: 1.5px solid var(--border-strong); border-radius: 4px; flex-shrink: 0; }
.todo-add-form { display: none; flex-direction: column; gap: 10px; margin-top: 8px; padding: 0 3px; }
.todo-add-form.open { display: flex; }
.todo-add-actions { display: flex; align-items: center; gap: 8px; }
.todo-add-input { width: 100%; padding: 10px 13px; border: 1px solid var(--border-strong); border-radius: 8px; font-size: 14px; background: #fff; color: var(--text); transition: border-color .15s, box-shadow .15s; }
.todo-add-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
.todo-add-submit { padding: 10px 16px; background: var(--accent); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; white-space: nowrap; transition: background .15s; }
.todo-add-submit:hover { background: var(--accent-hover); }
.todo-add-cancel { background: #fff; border: 1px solid var(--border-strong); color: var(--text); border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; padding: 10px 16px; transition: background .15s, border-color .15s; }
.todo-add-cancel:hover { background: var(--bg-warm); }
</style>
</head>
<body>
<?php require dirname(__DIR__) . '/partials/layout-nav.php'; ?>

<h1 class="wp-page-title">To-dos</h1>

<div class="todos-single">
    <ul class="todo-items">
    <?php foreach ($pending as $todo): ?>
        <li class="todo-item a-<?= h($todo['assigned_to'] ?? '') ?>">
            <form method="post" action="/admin/todos/" class="todo-check">
                <input type="hidden" name="action" value="toggle_todo">
                <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                <input type="checkbox" onchange="this.form.submit()" title="Marchează completat">
            </form>
            <span class="todo-text"><?= h($todo['title']) ?></span>
            <?= $render_assign($todo['assigned_to'] ?? '') ?>
            <form method="post" action="/admin/todos/">
                <input type="hidden" name="action" value="delete_todo">
                <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                <button type="submit" class="todo-del" title="Șterge" onclick="return confirm('Sigur ștergi?')">×</button>
            </form>
        </li>
    <?php endforeach; ?>

    <?php if (empty($pending) && empty($done)): ?>
        <li class="todo-empty">Nicio sarcină.</li>
    <?php endif; ?>
    </ul>

    <?php if (!empty($done)): ?>
    <details class="todo-completed">
        <summary><span class="todo-completed-caret">▸</span> <?= $done_count ?> completat<?= $done_count === 1 ? '' : 'e' ?></summary>
        <?php foreach ($done_groups as $_day => $_items): ?>
        <div class="todo-done-day"><?= h($day_label($_day)) ?></div>
        <ul class="todo-items todo-completed-items">
        <?php foreach ($_items as $todo): ?>
            <li class="todo-item a-<?= h($todo['assigned_to'] ?? '') ?>">
                <form method="post" action="/admin/todos/" class="todo-check">
                    <input type="hidden" name="action" value="toggle_todo">
                    <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                    <input type="checkbox" checked onchange="this.form.submit()" title="Marchează incomplet">
                </form>
                <span class="todo-text done"><?= h($todo['title']) ?></span>
                <?= $render_assign($todo['assigned_to'] ?? '') ?>
                <form method="post" action="/admin/todos/">
                    <input type="hidden" name="action" value="delete_todo">
                    <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                    <button type="submit" class="todo-del" title="Șterge" onclick="return confirm('Sigur ștergi?')">×</button>
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php endforeach; ?>
    </details>
    <?php endif; ?>

    <div class="todo-add">
        <button class="todo-add-link" onclick="toggleAddForm(this)">
            Adaugă o sarcină
        </button>
        <form method="post" action="/admin/todos/" class="todo-add-form">
            <input type="hidden" name="action" value="add_todo">
            <input type="text" name="title" class="todo-add-input" autofocus required>
            <div class="todo-add-assign">
                <span class="todo-add-assign-label">Atribuie:</span>
                <?php foreach ($all_users as $u): $un = $u['username']; ?>
                <label class="todo-assign-pick">
                    <input type="radio" name="assigned_to" value="<?= h($un) ?>" required>
                    <?= $render_assign($un) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="todo-add-actions">
                <button type="submit" class="todo-add-submit">Adaugă</button>
                <button type="button" class="todo-add-cancel" onclick="toggleAddForm(this.closest('.todo-add').querySelector('.todo-add-link'), true)">Anulează</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAddForm(btn, forceClose) {
    var area = btn.closest ? btn.closest('.todo-add') : btn.parentElement;
    var form = area.querySelector('.todo-add-form');
    var link = area.querySelector('.todo-add-link');
    if (!form) return;
    var open = form.classList.contains('open');
    if (forceClose || open) {
        form.classList.remove('open');
        link.style.display = '';
        form.querySelector('input[name="title"]').value = '';
    } else {
        form.classList.add('open');
        link.style.display = 'none';
        form.querySelector('input[name="title"]').focus();
    }
}
</script>

    </div><!-- /bc-doc -->
    </main>
</div>

<?php require dirname(__DIR__) . '/partials/scripts-foot.php'; ?>
</body>
</html>
