<?php
declare(strict_types=1);

require __DIR__ . '/../auth_check.php';
require_once dirname(__DIR__, 2) . '/lib/admin.php';
require_once dirname(__DIR__, 2) . '/lib/messages.php';
require_once dirname(__DIR__, 2) . '/lib/marketing.php';
require_once dirname(__DIR__, 2) . '/lib/competitors.php';

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
            $id = clp_marketing_unique_section_id($data, clp_marketing_slug($title));
            $data['sections'][] = [
                'id'          => $id,
                'title'       => $title,
                'items'       => [],
                'is_default'  => false,
            ];
            clp_marketing_save($data);
        }
    }

    if ($action === 'delete_section') {
        $sectionId = trim($_POST['section_id'] ?? '');
        $before = count($data['sections']);
        $data = clp_marketing_delete_section($data, $sectionId);
        if (count($data['sections']) < $before) {
            clp_marketing_save($data);
        }
    }

    header('Location: /admin/marketing/');
    exit;
}

$marketing = clp_marketing_load();
$_competitors = clp_competitors_list();
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ro" data-theme="corporate">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marketing — Admin</title>
<?php if (!empty($settings['favicon_path'])): ?><link rel="icon" href="<?= h($settings['favicon_path']) ?>"><?php endif; ?>
<?php $adminCssVer = (string)@filemtime(dirname(__DIR__) . '/assets/css/admin.css'); ?>
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=<?= h($adminCssVer ?: '19') ?>">
<style>
/* DaisyUI nu e încărcat aici; forțăm înălțime Notion pe inputs */
.mkt-page .mkt-add-fields input {
    min-height: 0 !important;
    height: 22px !important;
    padding: 0 4px !important;
    margin: 0 !important;
    line-height: 22px !important;
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    border-radius: 0 !important;
}
.mkt-page .mkt-add-fields { flex-direction: row !important; gap: 12px !important; row-gap: 0 !important; }
.mkt-page .mkt-add-fields input[name="link"] { flex: 0 0 240px !important; }
.mkt-page .mkt-add-form { align-items: flex-start !important; padding: 2px 4px !important; }
.mkt-page .mkt-check-box--ghost { margin-top: 2px !important; }
</style>
</head>
<body>

<?php require dirname(__DIR__) . '/partials/layout-nav.php'; ?>

<div class="mkt-page">
    <h1 class="mkt-title">Marketing</h1>
    <p class="mkt-lead">Idei de postări — bifează când e gata, adaugă text și opțional un link.</p>

    <?php foreach ($marketing['sections'] as $section): ?>
    <?php
        $sectionItems = $section['items'] ?? [];
        $openItems = array_values(array_filter($sectionItems, fn($item) => empty($item['done'])));
        $doneItems = array_values(array_filter($sectionItems, fn($item) => !empty($item['done'])));
    ?>
    <section class="mkt-section" data-section="<?= h($section['id']) ?>">
        <div class="mkt-section-head">
            <h2 class="mkt-section-title"><?= h($section['title']) ?></h2>
            <button type="button" class="mkt-add-toggle" aria-expanded="false">+</button>
        </div>

        <form method="post" class="mkt-add-form" hidden>
            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="add_item">
            <input type="hidden" name="section_id" value="<?= h($section['id']) ?>">
            <span class="mkt-check-box mkt-check-box--ghost" aria-hidden="true"></span>
            <div class="mkt-add-fields">
                <input type="text" name="text" placeholder="Ideea de postare…" autocomplete="off">
                <input type="text" name="link" placeholder="Link (opțional)" autocomplete="off" inputmode="url">
            </div>
        </form>

        <ul class="mkt-list">
            <?php foreach ($openItems as $item): ?>
            <li class="mkt-item">
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
                    <?php if (!empty($item['link'])): ?>
                    <a href="<?= h($item['link']) ?>" target="_blank" rel="noopener" class="mkt-item-text mkt-item-text-link"><?= h($item['text'] ?: $item['link']) ?></a>
                    <?php elseif (!empty($item['text'])): ?>
                    <span class="mkt-item-text"><?= h($item['text']) ?></span>
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

        <?php if (!empty($doneItems)): ?>
        <button type="button" class="mkt-show-done" data-show-label="Arată postările finalizate (<?= count($doneItems) ?>)" data-hide-label="Ascunde postările finalizate">
            Arată postările finalizate (<?= count($doneItems) ?>)
        </button>
        <ul class="mkt-list mkt-done-list" hidden>
            <?php foreach ($doneItems as $item): ?>
            <li class="mkt-item is-done">
                <form method="post" class="mkt-toggle-form">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="toggle_item">
                    <input type="hidden" name="section_id" value="<?= h($section['id']) ?>">
                    <input type="hidden" name="item_id" value="<?= h($item['id']) ?>">
                    <label class="mkt-check">
                        <input type="checkbox" checked onchange="this.form.submit()">
                        <span class="mkt-check-box"></span>
                    </label>
                </form>
                <div class="mkt-item-body">
                    <?php if (!empty($item['link'])): ?>
                    <a href="<?= h($item['link']) ?>" target="_blank" rel="noopener" class="mkt-item-text mkt-item-text-link"><?= h($item['text'] ?: $item['link']) ?></a>
                    <?php elseif (!empty($item['text'])): ?>
                    <span class="mkt-item-text"><?= h($item['text']) ?></span>
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
        <?php endif; ?>
    </section>
    <?php endforeach; ?>

    <form method="post" class="mkt-add-section">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="add_section">
        <input type="text" name="title" placeholder="Nume secțiune nouă (ex. Video)" autocomplete="off">
        <button type="submit" class="btn btn-secondary btn-sm">+ Secțiune</button>
    </form>

    <section id="competitori" class="mkt-competitori">
        <h2 class="mkt-section-title">Competitori</h2>
        <?php require dirname(__DIR__) . '/partials/competitori-grid.inc.php'; ?>
    </section>
</div>

    </div><!-- /bc-doc -->
    </main>
</div>

<script src="/admin/assets/js/admin-common.js?v=3"></script>
<script src="/admin/assets/js/admin-marketing.js?v=4"></script>
</body>
</html>
