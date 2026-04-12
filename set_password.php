<?php
// One-time: seteaza parola admin si se auto-sterge
$settings_file = __DIR__ . '/data/settings.json';
$dir = dirname($settings_file);
if (!is_dir($dir)) mkdir($dir, 0755, true);

$settings = file_exists($settings_file)
    ? (json_decode(file_get_contents($settings_file), true) ?: [])
    : [];

$settings['admin_password'] = 'clp2026admin';

if (empty($settings['auth_secret'])) $settings['auth_secret'] = bin2hex(random_bytes(32));
if (empty($settings['webhook_secret'])) $settings['webhook_secret'] = bin2hex(random_bytes(32));

file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

echo "Parola setata. Sterge set_password.php...";
unlink(__FILE__);
echo " Done. Mergi la /admin/";
