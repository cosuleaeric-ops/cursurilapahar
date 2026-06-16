<?php
/**
 * Shared admin layout header for Statistici sub-pages.
 * Sets $__page_title before including.
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
<title><?= htmlspecialchars($__page_title ?? 'Statistici', ENT_QUOTES, 'UTF-8') ?> — Admin</title>
<?php if ($__favicon): ?><link rel="icon" href="<?= htmlspecialchars($__favicon, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
<script>tailwind={config:{corePlugins:{preflight:false}}}</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=38">
</head>
<body>
