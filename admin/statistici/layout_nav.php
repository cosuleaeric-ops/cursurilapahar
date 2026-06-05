<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/../lib/admin.php';
require_once dirname(__DIR__) . '/../lib/messages.php';
require_once dirname(__DIR__) . '/../lib/todos.php';

$_stat_path = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($_stat_path, '/admin/statistici/pnl') !== false) {
    $tab = 'pnl';
} elseif (strpos($_stat_path, '/admin/statistici/cursuri') !== false) {
    $tab = 'cursuri';
} else {
    $tab = 'dashboard';
}

$_msg_pending_count = clp_pending_message_count();

require dirname(__DIR__) . '/partials/layout-nav.php';
