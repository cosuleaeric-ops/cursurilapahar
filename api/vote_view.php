<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__DIR__) . '/lib/vote_views.php';

if (!clp_should_count_course_click()) {
    echo json_encode(['success' => true, 'skipped' => true]);
    exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    echo json_encode(['success' => false, 'message' => 'Date invalide']);
    exit;
}

$id = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['id'] ?? '');
if ($id === '' || !clp_vote_course_exists($id)) {
    echo json_encode(['success' => false, 'message' => 'ID invalid']);
    exit;
}

clp_increment_vote_view($id);
echo json_encode(['success' => true]);
