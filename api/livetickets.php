<?php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/admin/auth_check.php';
require_once dirname(__DIR__) . '/lib/course_image.php';

if (!is_authenticated()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$url = trim($body['url'] ?? '');

echo json_encode(clp_fetch_course_meta_by_url($url));
