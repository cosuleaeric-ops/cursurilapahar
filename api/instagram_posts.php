<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/admin/auth_check.php';
require_once dirname(__DIR__) . '/lib/instagram_posts.php';
if (!is_authenticated()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['posts' => clp_load_ig_posts()]);
    exit;
}

$date = trim($_POST['date'] ?? '');
$type = trim($_POST['type'] ?? '');
$on   = ($_POST['on'] ?? '') === '1';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !isset(clp_ig_post_types()[$type])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date or type']);
    exit;
}

$types = clp_toggle_ig_post($date, $type, $on);
echo json_encode(['ok' => true, 'date' => $date, 'types' => $types]);
