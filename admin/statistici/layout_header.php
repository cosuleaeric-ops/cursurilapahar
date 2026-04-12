<?php
/**
 * Shared admin layout header for Statistici sub-pages.
 * Sets $__page_title before including, e.g.:
 *   $__page_title = 'Cursuri';
 *   include __DIR__ . '/layout_header.php';   (or adjust path)
 */
$__settings_path = dirname(__DIR__, 2) . '/data/settings.json';
$__s = file_exists($__settings_path) ? (json_decode(file_get_contents($__settings_path), true) ?: []) : [];
$__favicon = $__s['favicon_path'] ?? '';
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($__page_title ?? 'Statistici') ?> — Admin</title>
<?php if ($__favicon): ?><link rel="icon" href="<?= htmlspecialchars($__favicon) ?>"><?php endif; ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #f0f0f1; --surface: #fff;
    --header-bg: #1d2327; --header-text: #fff;
    --sidebar-bg: #1d2327; --sidebar-text: #a7aaad;
    --sidebar-active: #fff; --sidebar-active-bg: #2271b1;
    --accent: #2271b1; --accent-hover: #135e96;
    --text: #1d2327; --text-muted: #646970;
    --border: #c3c4c7; --danger: #d63638; --success: #00a32a;
    --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
body { background: var(--bg); color: var(--text); font-family: var(--font); font-size: 13px; line-height: 1.5; min-height: 100vh; }

.wp-header { background: var(--header-bg); color: var(--header-text); height: 46px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 100; }
.wp-header .brand { font-size: 14px; font-weight: 600; color: var(--header-text); text-decoration: none; }
.wp-header .brand span { opacity: .7; font-weight: 400; }
.btn-logout { background: transparent; border: 1px solid rgba(255,255,255,.25); color: rgba(255,255,255,.8); padding: 4px 10px; font-size: 12px; line-height: 1.8; border-radius: 3px; cursor: pointer; text-decoration: none; }
.btn-logout:hover { background: rgba(255,255,255,.1); color: #fff; }

.wp-layout { display: flex; min-height: calc(100vh - 46px); margin-top: 46px; }

.wp-sidebar { width: 200px; background: var(--sidebar-bg); flex-shrink: 0; padding-top: 8px; position: fixed; top: 46px; left: 0; height: calc(100vh - 46px); overflow-y: auto; z-index: 99; }
.wp-sidebar nav a { display: flex; align-items: center; gap: 8px; padding: 8px 14px; color: var(--sidebar-text); text-decoration: none; font-size: 13px; font-weight: 500; border-left: 3px solid transparent; transition: background .1s, color .1s; }
.wp-sidebar nav a:hover { color: var(--sidebar-active); background: rgba(255,255,255,.07); }
.wp-sidebar nav a.active { color: var(--sidebar-active); background: var(--sidebar-active-bg); border-left-color: rgba(255,255,255,.3); }
.wp-sidebar nav a .nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }

.wp-main { flex: 1; padding: 20px 24px; min-width: 0; margin-left: 200px; }
.wp-page-title { font-size: 22px; font-weight: 400; color: var(--text); margin-bottom: 20px; line-height: 1.3; }

@media (max-width: 782px) {
    .wp-sidebar { display: none; }
    .wp-main { margin-left: 0; }
}
</style>
