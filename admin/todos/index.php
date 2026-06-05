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
        $assigned_to = $_POST['assigned_to'] ?? $current_username;
        if ($title !== '') {
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
$users_to_show = array_column($all_users, 'username');

$todos_by_user = [];
foreach ($users_to_show as $u) {
    $list = clp_todos_for_user($u);
    $pending = array_values(array_filter($list, fn($t) => empty($t['completed'])));
    $done    = array_values(array_filter($list, fn($t) => !empty($t['completed'])));
    $todos_by_user[$u] = ['pending' => $pending, 'done' => $done, 'done_count' => count($done)];
}

$user_labels = [];
foreach ($all_users as $u) {
    $user_labels[$u['username']] = ucfirst($u['username']);
}

$user_colors = ['eric6' => '#2563eb', 'andy' => '#16a34a'];
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
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=35">
<style>
.todos-grid { display: flex; flex-direction: column; gap: 26px; }
.todo-list-block { }
.todo-list-head { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
.todo-list-circle { width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
.todo-list-name { font-size: 17px; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
.todo-items { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.todo-item { display: flex; align-items: flex-start; gap: 11px; padding: 5px 8px 5px 3px; border-radius: 8px; transition: background .1s; }
.todo-item:hover { background: var(--bg); }
.todo-check { flex-shrink: 0; margin: 1px 0 0; display: flex; }
.todo-check input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); }
.todo-text { flex: 1; font-size: 15px; color: var(--text); line-height: 1.4; }
.todo-text.done { text-decoration: line-through; color: var(--text-muted); }
.todo-del { opacity: 0; flex-shrink: 0; background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 18px; line-height: 1; padding: 0 4px; transition: opacity .15s, color .15s; }
.todo-item:hover .todo-del { opacity: 1; }
.todo-del:hover { color: var(--danger); }
.todo-empty { color: var(--text-muted); font-size: 14px; padding: 5px 3px; }

/* completed collapsible */
.todo-completed { margin-top: 0; }
.todo-completed > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 11px; padding: 5px 3px; font-size: 14px; color: var(--text-muted); user-select: none; }
.todo-completed > summary::-webkit-details-marker { display: none; }
.todo-completed-check { width: 18px; height: 18px; border-radius: 4px; background: var(--success); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; line-height: 1; flex-shrink: 0; }
.todo-completed-items { display: flex; flex-direction: column; }

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

<div class="todos-grid">
<?php foreach ($todos_by_user as $uname => $data):
    $dot_color = $user_colors[$uname] ?? '#16a34a';
    $label = $user_labels[$uname] ?? ucfirst($uname);
    $pending = $data['pending'];
    $done_list = $data['done'] ?? [];
    $done_count = $data['done_count'];
    $can_add = true;
?>
<div class="todo-list-block">
    <div class="todo-list-head">
        <span class="todo-list-circle" style="background:<?= h($dot_color) ?>"></span>
        <span class="todo-list-name"><?= h($label) ?></span>
    </div>

    <ul class="todo-items">
    <?php foreach ($pending as $todo): ?>
        <li class="todo-item">
            <form method="post" action="/admin/todos/" class="todo-check">
                <input type="hidden" name="action" value="toggle_todo">
                <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                <input type="checkbox" onchange="this.form.submit()" title="Marchează completat">
            </form>
            <span class="todo-text"><?= h($todo['title']) ?></span>
            <form method="post" action="/admin/todos/" style="margin:0">
                <input type="hidden" name="action" value="delete_todo">
                <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                <button type="submit" class="todo-del" title="Șterge" onclick="return confirm('Sigur ștergi?')">×</button>
            </form>
        </li>
    <?php endforeach; ?>

    <?php if (empty($pending) && empty($done_list)): ?>
        <li class="todo-empty">Nicio sarcină.</li>
    <?php endif; ?>
    </ul>

    <?php if (!empty($done_list)): ?>
    <details class="todo-completed">
        <summary><span class="todo-completed-check">✓</span> <?= $done_count ?> completat<?= $done_count === 1 ? '' : 'e' ?></summary>
        <ul class="todo-items todo-completed-items">
        <?php foreach ($done_list as $todo): ?>
            <li class="todo-item">
                <form method="post" action="/admin/todos/" class="todo-check">
                    <input type="hidden" name="action" value="toggle_todo">
                    <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                    <input type="checkbox" checked onchange="this.form.submit()" title="Marchează incomplet">
                </form>
                <span class="todo-text done"><?= h($todo['title']) ?></span>
                <form method="post" action="/admin/todos/" style="margin:0">
                    <input type="hidden" name="action" value="delete_todo">
                    <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                    <button type="submit" class="todo-del" title="Șterge" onclick="return confirm('Sigur ștergi?')">×</button>
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
    </details>
    <?php endif; ?>

    <?php if ($can_add): ?>
    <div class="todo-add">
        <button class="todo-add-link" onclick="toggleAddForm(this)">
            Adaugă o sarcină
        </button>
        <form method="post" action="/admin/todos/" class="todo-add-form">
            <input type="hidden" name="action" value="add_todo">
            <input type="hidden" name="assigned_to" value="<?= h($uname) ?>">
            <input type="text" name="title" class="todo-add-input" autofocus required>
            <div class="todo-add-actions">
                <button type="submit" class="todo-add-submit">Adaugă</button>
                <button type="button" class="todo-add-cancel" onclick="toggleAddForm(this.closest('.todo-add').querySelector('.todo-add-link'), true)">Anulează</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
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
