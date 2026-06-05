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
        if (!$owner) $assigned_to = $current_username;
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
$users_to_show = $owner
    ? array_column($all_users, 'username')
    : [$current_username];

$show_completed = isset($_GET['show_completed']);

$todos_by_user = [];
foreach ($users_to_show as $u) {
    $list = clp_todos_for_user($u);
    if (!$show_completed) {
        $pending = array_values(array_filter($list, fn($t) => !$t['completed']));
        $done_count = count($list) - count($pending);
        $todos_by_user[$u] = ['pending' => $pending, 'done_count' => $done_count];
    } else {
        $pending = array_values(array_filter($list, fn($t) => !$t['completed']));
        $done = array_values(array_filter($list, fn($t) => $t['completed']));
        $todos_by_user[$u] = ['pending' => $pending, 'done_count' => count($done), 'done' => $done];
    }
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
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=26">
<style>
.todos-header { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
.todos-header h1 { margin: 0; }
.todos-toggle-completed {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: var(--text-muted); cursor: pointer;
    background: none; border: none; padding: 0; text-decoration: none;
}
.todos-grid { display: flex; flex-direction: column; gap: 34px; }
.todo-section { background: transparent; border: none; }
.todo-section-header {
    display: flex; align-items: center; gap: 10px;
    padding: 0 0 6px;
    margin-bottom: 4px;
}
.todo-dot { width: 13px; height: 13px; border-radius: 50%; flex-shrink: 0; }
.todo-section-title { font-size: 17px; font-weight: 700; color: var(--text); }
.todo-done-count { font-size: 12px; color: var(--text-muted); margin-left: auto; }
.todo-list { padding: 0; }
.todo-item {
    display: flex; align-items: flex-start; gap: 11px;
    padding: 8px 8px 8px 2px;
    border-radius: 8px;
    transition: background .1s;
}
.todo-item:hover { background: var(--bg); }
.todo-item-check { flex-shrink: 0; margin-top: 1px; }
.todo-item-check input[type="checkbox"] {
    width: 16px; height: 16px; cursor: pointer;
    accent-color: var(--accent);
}
.todo-item-label { flex: 1; font-size: 14px; color: var(--text); line-height: 1.4; cursor: pointer; }
.todo-item-label.done { text-decoration: line-through; color: var(--text-muted); }
.todo-item-delete {
    opacity: 0; flex-shrink: 0; background: none; border: none;
    cursor: pointer; color: var(--text-muted); font-size: 16px;
    padding: 0 2px; line-height: 1; transition: opacity .15s, color .15s;
}
.todo-item:hover .todo-item-delete { opacity: 1; }
.todo-item-delete:hover { color: var(--danger); }
.todo-done-group { border-top: 1px solid var(--border); margin-top: 4px; padding-top: 4px; }
.todo-done-group .todo-item { opacity: .65; }
.todo-add-area { padding: 10px 2px 0; }
.todo-add-link {
    background: none; border: none; cursor: pointer;
    color: var(--accent); font-size: 13px; padding: 0;
    display: flex; align-items: center; gap: 5px;
}
.todo-add-link:hover { text-decoration: underline; }
.todo-add-form { display: none; gap: 8px; margin-top: 8px; }
.todo-add-form.open { display: flex; }
.todo-add-input {
    flex: 1; padding: 8px 12px; border: 1px solid var(--border);
    border-radius: var(--radius); font-size: 13px;
    background: var(--surface); color: var(--text);
    transition: border-color .15s, box-shadow .15s;
}
.todo-add-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.todo-add-submit {
    padding: 8px 14px; background: var(--accent); color: #fff;
    border: none; border-radius: var(--radius); cursor: pointer;
    font-size: 13px; font-weight: 600; white-space: nowrap;
    transition: background .15s;
}
.todo-add-submit:hover { background: var(--accent-hover); }
.todo-add-cancel { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 13px; padding: 0 4px; }
</style>
</head>
<body>
<?php require dirname(__DIR__) . '/partials/layout-nav.php'; ?>

<h1 class="wp-page-title">To-dos</h1>

<div class="todos-header">
    <a href="/admin/todos/?<?= $show_completed ? '' : 'show_completed=1' ?>" class="todos-toggle-completed">
        <span style="display:inline-block;width:18px;height:10px;background:<?= $show_completed ? 'var(--accent)' : '#d1d5db' ?>;border-radius:10px;transition:background .2s;position:relative">
            <span style="display:block;width:8px;height:8px;background:#fff;border-radius:50%;position:absolute;top:1px;<?= $show_completed ? 'right:1px' : 'left:1px' ?>;transition:left .2s,right .2s"></span>
        </span>
        Arată completate
    </a>
</div>

<div class="todos-grid">
<?php foreach ($todos_by_user as $uname => $data):
    $dot_color = $user_colors[$uname] ?? '#6b7280';
    $label = $user_labels[$uname] ?? ucfirst($uname);
    $pending = $data['pending'];
    $done_count = $data['done_count'];
    $done_list = $data['done'] ?? [];
    $can_add = $owner || $uname === $current_username;
?>
<div class="todo-section">
    <div class="todo-section-header">
        <span class="todo-dot" style="background:<?= h($dot_color) ?>"></span>
        <span class="todo-section-title"><?= h($label) ?></span>
        <?php if ($done_count > 0): ?>
        <span class="todo-done-count"><?= $done_count ?> completat<?= $done_count === 1 ? '' : 'e' ?></span>
        <?php endif; ?>
    </div>

    <div class="todo-list">
    <?php foreach ($pending as $todo): ?>
        <div class="todo-item">
            <form method="post" action="/admin/todos/" class="todo-item-check">
                <input type="hidden" name="action" value="toggle_todo">
                <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                <input type="checkbox" onchange="this.form.submit()" title="Marchează completat">
            </form>
            <span class="todo-item-label"><?= h($todo['title']) ?></span>
            <form method="post" action="/admin/todos/" style="margin:0">
                <input type="hidden" name="action" value="delete_todo">
                <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                <button type="submit" class="todo-item-delete" title="Șterge" onclick="return confirm('Sigur ștergi?')">×</button>
            </form>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($done_list)): ?>
    <div class="todo-done-group">
        <?php foreach ($done_list as $todo): ?>
        <div class="todo-item">
            <form method="post" action="/admin/todos/" class="todo-item-check">
                <input type="hidden" name="action" value="toggle_todo">
                <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                <input type="checkbox" checked onchange="this.form.submit()" title="Marchează incomplet">
            </form>
            <span class="todo-item-label done"><?= h($todo['title']) ?></span>
            <form method="post" action="/admin/todos/" style="margin:0">
                <input type="hidden" name="action" value="delete_todo">
                <input type="hidden" name="id" value="<?= h($todo['id']) ?>">
                <button type="submit" class="todo-item-delete" title="Șterge" onclick="return confirm('Sigur ștergi?')">×</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($pending) && empty($done_list)): ?>
    <p style="padding:12px 20px;color:var(--text-muted);font-size:13px">Nicio sarcină.</p>
    <?php endif; ?>
    </div>

    <?php if ($can_add): ?>
    <div class="todo-add-area">
        <button class="todo-add-link" onclick="toggleAddForm(this)">
            + Adaugă o sarcină
        </button>
        <form method="post" action="/admin/todos/" class="todo-add-form">
            <input type="hidden" name="action" value="add_todo">
            <input type="hidden" name="assigned_to" value="<?= h($uname) ?>">
            <input type="text" name="title" class="todo-add-input" autofocus required>
            <button type="submit" class="todo-add-submit">Adaugă</button>
            <button type="button" class="todo-add-cancel" onclick="toggleAddForm(this.closest('.todo-add-area').querySelector('.todo-add-link'), true)">Anulează</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<script>
function toggleAddForm(btn, forceClose) {
    var area = btn.closest ? btn.closest('.todo-add-area') : btn.parentElement;
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
