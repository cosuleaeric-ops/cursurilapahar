<?php
declare(strict_types=1);
require __DIR__ . '/../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }
header('Location: /admin/statistici/ab_headline.php', true, 302);
exit;
