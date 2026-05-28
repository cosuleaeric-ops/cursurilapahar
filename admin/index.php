<?php
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');
if (file_exists(dirname(__DIR__) . '/private/secrets.php')) {
    require dirname(__DIR__) . '/private/secrets.php';
}
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', '');

define('COURSES_FILE', dirname(__DIR__) . '/data/courses.json');
define('UPLOADS_DIR',  dirname(__DIR__) . '/assets/images/uploads');
define('UPLOADS_URL',  '/assets/images/uploads');
define('PUBLIC_HTML',  dirname(__DIR__));

require_once dirname(__DIR__) . '/lib/admin.php';
require_once dirname(__DIR__) . '/lib/courses.php';
require_once dirname(__DIR__) . '/lib/courses_admin.php';
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
require_once dirname(__DIR__) . '/lib/images.php';

clp_ensure_secrets();
clp_ensure_default_users();
$login_error = clp_process_auth_request();

require __DIR__ . '/bootstrap.php';
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
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=4">
</head>
<body>

<?php if (!is_authenticated()): ?>
<?php require __DIR__ . '/partials/login.php'; ?>

<?php else: ?>
<?php require __DIR__ . '/partials/layout-nav.php'; ?>

<?php if ($tab === 'dashboard'): ?>
<?php extract(clp_dashboard_data(__DIR__), EXTR_SKIP); require __DIR__ . '/partials/dashboard-tab.php'; ?>

<?php elseif ($tab === 'cursuri'): ?>
<?php
extract(clp_courses_admin_context($courses, trim($_GET['edit'] ?? '')));
require __DIR__ . '/partials/cursuri-tab.php';
?>

<?php elseif ($tab === 'imagini'): ?>
<?php $all_images = get_all_images(); require __DIR__ . '/partials/imagini-tab.php'; ?>

<?php elseif ($tab === 'aspect'): ?>
<?php require __DIR__ . '/partials/aspect-tab.php'; ?>

<?php elseif ($tab === 'kit'): ?>
<?php header('Location: /admin/?tab=config'); exit; ?>

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

<?php elseif ($tab === 'competitori'): ?>
<?php $_competitors = clp_competitors_list(); require __DIR__ . '/partials/competitori-tab.php'; ?>

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

<?php elseif ($tab === 'locatii'): ?>
<?php
$_loc = clp_locations_admin_context($_GET['edit'] ?? '');
$locations = $_loc['items'];
$edit_loc = $_loc['edit'];
require __DIR__ . '/partials/locatii-tab.php';
?>

<?php elseif ($tab === 'colaborari'): ?>
<?php
$_col = clp_collaborations_admin_context($_GET['edit'] ?? '');
$collabs = $_col['items'];
$edit_col = $_col['edit'];
require __DIR__ . '/partials/colaborari-tab.php';
?>

<?php elseif ($tab === 'securitate'): ?>
<?php header('Location: /admin/?tab=config'); exit; ?>

<?php elseif ($tab === 'config'): ?>
<?php require __DIR__ . '/partials/config-tab.php'; ?>

<?php endif; ?>

    </main>
</div><!-- /wp-layout -->

<?php require __DIR__ . '/partials/scripts-foot.php'; ?>

<?php endif; ?>
</body>
</html>
