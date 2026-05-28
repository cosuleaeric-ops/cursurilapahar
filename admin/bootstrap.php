<?php

$action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($_POST['action'] ?? '') : '';
if (is_authenticated() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/actions.php';
}

$courses  = [];
$settings = load_settings();
$tab      = clp_resolve_admin_tab($_GET['tab'] ?? 'dashboard');
if (is_authenticated() && !can_access_tab($tab)) {
    $tab = 'dashboard';
}

if (is_authenticated()) {
    $courses = clp_load_courses_for_admin();
    usort($courses, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
}

$clp_year = (int)date('Y');
$clp_month = (int)date('n');
$clp_ctab = 'cursuri';
$clp_ro_months = clp_ro_months_list(false);
if (is_authenticated() && $tab === 'cursuri') {
    $_stats_nav = clp_cursuri_stats_nav();
    $clp_year = $_stats_nav['year'];
    $clp_month = $_stats_nav['month'];
    $clp_ctab = $_stats_nav['ctab'];
    $clp_ro_months = $_stats_nav['ro_months'];
}

$_msg_pending_count = is_authenticated() ? clp_pending_message_count() : 0;
if ($tab === 'mesaje' && is_authenticated()) {
    clp_mark_messages_read();
}
