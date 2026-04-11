<?php
if (!isset($settings)) {
    $_es_file = dirname(__DIR__) . '/data/settings.json';
    $settings = file_exists($_es_file) ? (json_decode(file_get_contents($_es_file), true) ?: []) : [];
}
$_desktop = $settings['element_styles'] ?? [];
$_mobile  = $settings['element_styles_mobile'] ?? [];
if ($_desktop || $_mobile): ?>
<style>
<?php foreach ($_desktop as $key => $style): ?>
[data-edit-key="<?= htmlspecialchars($key) ?>"] { <?= htmlspecialchars($style) ?>; }
<?php endforeach; ?>
<?php if ($_mobile): ?>
@media (max-width: 768px) {
<?php foreach ($_mobile as $key => $style): ?>
  [data-edit-key="<?= htmlspecialchars($key) ?>"] { <?= htmlspecialchars($style) ?>; }
<?php endforeach; ?>
}
<?php endif; ?>
</style>
<?php endif; ?>
