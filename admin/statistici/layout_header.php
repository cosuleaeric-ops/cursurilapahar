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
<html lang="ro" data-theme="corporate">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($__page_title ?? 'Statistici') ?> — Admin</title>
<?php if ($__favicon): ?><link rel="icon" href="<?= htmlspecialchars($__favicon) ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
<script>tailwind={config:{corePlugins:{preflight:false}}}</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root { --text-muted:#6b7280; --border:#e5e7eb; --danger:#dc2626; --success:#16a34a; --accent:#1d4ed8; --surface:#fff; --bg:#f1f5f9; --text:#1f2937; }
body { background:#f1f5f9; color:#1f2937; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; font-size:13px; line-height:1.5; min-height:100vh; }
.wp-header { background:#1d232a; color:#fff; height:52px; display:flex; align-items:center; justify-content:space-between; padding:0 20px; position:fixed; top:0; left:0; right:0; z-index:100; box-shadow:0 1px 4px rgba(0,0,0,.25); }
.wp-header .brand { font-size:14px; font-weight:700; color:#fff; text-decoration:none; }
.wp-header .brand span { opacity:.5; font-weight:400; }
.wp-header-site-link { color:rgba(255,255,255,.65); font-size:12px; text-decoration:none; padding:5px 12px; border:1px solid rgba(255,255,255,.2); border-radius:8px; transition:background .15s,color .15s; }
.wp-header-site-link:hover { background:rgba(255,255,255,.1); color:#fff; }
.btn-logout { background:transparent; border:1px solid rgba(255,255,255,.25); color:rgba(255,255,255,.75); padding:5px 12px; font-size:12px; border-radius:8px; cursor:pointer; text-decoration:none; transition:background .15s; }
.btn-logout:hover { background:rgba(255,255,255,.1); color:#fff; }
.wp-layout { display:flex; min-height:calc(100vh - 52px); margin-top:52px; }
.wp-sidebar { width:220px; background:#1d232a; flex-shrink:0; padding-top:8px; position:fixed; top:52px; left:0; height:calc(100vh - 52px); overflow-y:auto; z-index:99; }
.wp-sidebar nav a { display:flex; align-items:center; gap:10px; padding:9px 16px; color:#a6adba; text-decoration:none; font-size:13px; font-weight:500; border-left:3px solid transparent; transition:background .15s,color .15s; }
.wp-sidebar nav a:hover { color:#fff; background:rgba(255,255,255,.06); }
.wp-sidebar nav a.active { color:#fff; background:#1d4ed8; border-left-color:#93c5fd; }
.wp-sidebar nav a .nav-icon { font-size:15px; width:20px; text-align:center; flex-shrink:0; }
.sidebar-section { padding:18px 16px 4px; font-size:9px; text-transform:uppercase; letter-spacing:.1em; color:#4a5568; font-weight:700; }
.sidebar-section.collapsible { cursor:pointer; user-select:none; display:flex; justify-content:space-between; align-items:center; padding-right:14px; }
.sidebar-section.collapsible::after { content:'▾'; font-size:11px; transition:transform .2s; }
.sidebar-section.collapsible.collapsed::after { transform:rotate(-90deg); }
.sidebar-collapse-content { overflow:hidden; transition:max-height .25s ease; max-height:400px; }
.sidebar-collapse-content.collapsed { max-height:0; }
.wp-main { flex:1; padding:24px 28px; min-width:0; margin-left:220px; }
.wp-page-title { font-size:20px; font-weight:700; color:#111827; margin-bottom:20px; }
.card { background:#fff !important; border:1px solid #e5e7eb !important; border-radius:12px !important; padding:20px !important; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,.04); display:block !important; }
.card-title { font-size:11px; font-weight:700; color:#6b7280; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid #f1f5f9; text-transform:uppercase; letter-spacing:.06em; }
.btn { display:inline-flex !important; align-items:center !important; gap:5px; padding:7px 16px !important; border-radius:8px !important; border:1px solid transparent; cursor:pointer; font-size:13px !important; font-weight:600; text-decoration:none; line-height:1.4 !important; height:auto !important; min-height:auto !important; transition:background .15s; }
.btn-primary { background:#1d4ed8 !important; border-color:#1d4ed8 !important; color:#fff !important; }
.btn-primary:hover { background:#1e40af !important; }
.btn-secondary { background:#f8fafc; border:1px solid #e5e7eb !important; color:#374151 !important; }
.btn-secondary:hover { background:#f1f5f9; }
.btn-danger { background:#dc2626 !important; border-color:#dc2626 !important; color:#fff !important; }
.btn-danger:hover { background:#b91c1c !important; }
.btn-sm { padding:3px 10px !important; font-size:12px !important; }
.notice { padding:12px 16px; border-radius:8px; border-left:4px solid; margin-bottom:16px; font-size:13px; }
.notice-success { background:#f0fdf4; border-left-color:#16a34a; color:#15803d; }
.notice-error { background:#fef2f2; border-left-color:#dc2626; color:#991b1b; }
.wp-table { width:100%; border-collapse:collapse; }
.wp-table th { text-align:left; padding:9px 12px; font-size:10px; font-weight:700; color:#9ca3af; background:#f8fafc; border-bottom:1px solid #f1f5f9; text-transform:uppercase; letter-spacing:.06em; }
.wp-table td { padding:11px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:13px; color:#374151; }
.wp-table tbody tr:last-child td { border-bottom:none; }
.wp-table tbody tr:hover td { background:#f8fafc; }
.row-actions { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.form-group { margin-bottom:16px; }
.form-group label { display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:.04em; }
.form-group input[type="text"],.form-group input[type="number"],.form-group input[type="date"],.form-group input[type="url"],.form-group input[type="email"],.form-group textarea,.form-group select { width:100%; padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; font-family:inherit; color:#1f2937; background:#fff; transition:border-color .15s,box-shadow .15s; }
.form-group input:focus,.form-group textarea:focus,.form-group select:focus { outline:none; border-color:#1d4ed8; box-shadow:0 0 0 3px rgba(29,78,216,.1); }
.form-group textarea { resize:vertical; min-height:80px; }
</style>
