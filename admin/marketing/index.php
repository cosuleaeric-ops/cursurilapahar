<?php
declare(strict_types=1);

require __DIR__ . '/../auth_check.php';
require_once dirname(__DIR__, 2) . '/lib/admin.php';
require_once dirname(__DIR__, 2) . '/lib/messages.php';
require_once dirname(__DIR__, 2) . '/lib/marketing.php';

if (!is_authenticated() || !can_access_tab('competitori')) {
    header('Location: /admin/');
    exit;
}

$tab = 'marketing';
$_msg_pending_count = clp_pending_message_count();
$settings = load_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        exit('CSRF invalid');
    }

    $action = $_POST['action'] ?? '';
    $data = clp_marketing_load();

    if ($action === 'add_item') {
        $sectionId = trim($_POST['section_id'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $link = trim($_POST['link'] ?? '');
        if ($link !== '' && !preg_match('#^https?://#i', $link)) {
            $link = 'https://' . $link;
        }
        if ($text !== '' || $link !== '') {
            foreach ($data['sections'] as &$section) {
                if (($section['id'] ?? '') !== $sectionId) {
                    continue;
                }
                $section['items'][] = [
                    'id'   => clp_marketing_new_id(),
                    'text' => $text,
                    'link' => $link,
                    'done' => false,
                ];
                break;
            }
            unset($section);
            clp_marketing_save($data);
        }
    }

    if ($action === 'toggle_item') {
        $sectionId = trim($_POST['section_id'] ?? '');
        $itemId = trim($_POST['item_id'] ?? '');
        foreach ($data['sections'] as &$section) {
            if (($section['id'] ?? '') !== $sectionId) {
                continue;
            }
            foreach ($section['items'] as &$item) {
                if (($item['id'] ?? '') === $itemId) {
                    $item['done'] = !($item['done'] ?? false);
                    break 2;
                }
            }
        }
        unset($section, $item);
        clp_marketing_save($data);
    }

    if ($action === 'delete_item') {
        $sectionId = trim($_POST['section_id'] ?? '');
        $itemId = trim($_POST['item_id'] ?? '');
        foreach ($data['sections'] as &$section) {
            if (($section['id'] ?? '') !== $sectionId) {
                continue;
            }
            $section['items'] = array_values(array_filter(
                $section['items'],
                fn($item) => ($item['id'] ?? '') !== $itemId
            ));
            break;
        }
        unset($section);
        clp_marketing_save($data);
    }

    if ($action === 'add_section') {
        $title = trim($_POST['title'] ?? '');
        if ($title !== '') {
            $id = clp_marketing_slug($title);
            $existing = array_column($data['sections'], 'id');
            if (in_array($id, $existing, true)) {
                $id .= '-' . substr(clp_marketing_new_id(), 0, 4);
            }
            $data['sections'][] = [
                'id'    => $id,
                'title' => $title,
                'items' => [],
            ];
            clp_marketing_save($data);
        }
    }

    header('Location: /admin/marketing/');
    exit;
}

$marketing = clp_marketing_load();
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ro" data-theme="corporate">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marketing — Admin</title>
<?php if (!empty($settings['favicon_path'])): ?><link rel="icon" href="<?= h($settings['favicon_path']) ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
<script>tailwind={config:{corePlugins:{preflight:false}}}</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=15">
</head>
<body>

<?php require dirname(__DIR__) . '/partials/layout-nav.php'; ?>

<div class="mkt-page">
    <nav class="mkt-breadcrumb">
        <a href="/admin/?tab=competitori">Competitori</a>
        <span>/</span>
        <span>Marketing</span>
    </nav>

    <h1 class="mkt-title">Marketing</h1>
    <p class="mkt-lead">Idei de postări — bifează când e gata, adaugă text și opțional un link.</p>

    <?php foreach ($marketing['sections'] as $section): ?>
    <section class="mkt-section" data-section="<?= h($section['id']) ?>">
        <h2 class="mkt-section-title"><?= h($section['title']) ?></h2>

        <ul class="mkt-list">
            <?php foreach ($section['items'] as $item): ?>
            <li class="mkt-item<?= !empty($item['done']) ? ' is-done' : '' ?>">
                <form method="post" class="mkt-toggle-form">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="toggle_item">
                    <input type="hidden" name="section_id" value="<?= h($section['id']) ?>">
                    <input type="hidden" name="item_id" value="<?= h($item['id']) ?>">
                    <label class="mkt-check">
                        <input type="checkbox"<?= !empty($item['done']) ? ' checked' : '' ?> onchange="this.form.submit()">
                        <span class="mkt-check-box"></span>
                    </label>
                </form>
                <div class="mkt-item-body">
                    <?php if (!empty($item['text'])): ?>
                    <span class="mkt-item-text"><?= h($item['text']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['link'])): ?>
                    <a href="<?= h($item['link']) ?>" target="_blank" rel="noopener" class="mkt-item-link"><?= h($item['link']) ?></a>
                    <?php endif; ?>
                </div>
                <form method="post" class="mkt-delete-form" onsubmit="return confirm('Ștergi această idee?');">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="delete_item">
                    <input type="hidden" name="section_id" value="<?= h($section['id']) ?>">
                    <input type="hidden" name="item_id" value="<?= h($item['id']) ?>">
                    <button type="submit" class="mkt-delete" title="Șterge" aria-label="Șterge">&times;</button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>

        <form method="post" class="mkt-add-form">
            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="add_item">
            <input type="hidden" name="section_id" value="<?= h($section['id']) ?>">
            <span class="mkt-check-box mkt-check-box--ghost" aria-hidden="true"></span>
            <div class="mkt-add-fields">
                <input type="text" name="text" placeholder="Ideea de postare…" autocomplete="off">
                <input type="url" name="link" placeholder="Link (opțional)" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Adaugă</button>
        </form>
    </section>
    <?php endforeach; ?>

    <form method="post" class="mkt-add-section">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="add_section">
        <input type="text" name="title" placeholder="Nume secțiune nouă (ex. Video)" autocomplete="off">
        <button type="submit" class="btn btn-secondary btn-sm">+ Secțiune</button>
    </form>
</div>

    </main>
</div>

<script src="/admin/assets/js/admin-common.js?v=3"></script>
<script src="/admin/assets/js/admin-marketing.js?v=1"></script>
</body>
</html>
