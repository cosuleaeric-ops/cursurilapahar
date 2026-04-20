<?php
if (!isset($settings)) {
    $_es_file = dirname(__DIR__) . '/data/settings.json';
    $settings = file_exists($_es_file) ? (json_decode(file_get_contents($_es_file), true) ?: []) : [];
}
$_desktop = $settings['element_styles'] ?? [];
$_tablet  = $settings['element_styles_tablet'] ?? [];
$_mobile  = $settings['element_styles_mobile'] ?? [];

function clp_important(string $style): string {
    return implode(';', array_filter(array_map(function($p) {
        $p = trim($p);
        return $p ? $p . ' !important' : '';
    }, explode(';', $style))));
}

if ($_desktop || $_tablet || $_mobile): ?>
<style>
<?php foreach ($_desktop as $key => $style): ?>
[data-edit-key="<?= htmlspecialchars($key) ?>"] { <?= clp_important($style) ?>; }
<?php endforeach; ?>
<?php if ($_tablet): ?>
@media (max-width: 1024px) {
<?php foreach ($_tablet as $key => $style): ?>
  [data-edit-key="<?= htmlspecialchars($key) ?>"] { <?= clp_important($style) ?>; }
<?php endforeach; ?>
}
<?php endif; ?>
<?php if ($_mobile): ?>
@media (max-width: 768px) {
<?php foreach ($_mobile as $key => $style): ?>
  [data-edit-key="<?= htmlspecialchars($key) ?>"] { <?= clp_important($style) ?>; }
<?php endforeach; ?>
}
<?php endif; ?>
</style>
<?php endif; ?>
