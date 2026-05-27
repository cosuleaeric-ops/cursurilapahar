<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/admin/auth_check.php';
require_once dirname(__DIR__) . '/lib/statistici.php';
if (!is_authenticated()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

echo json_encode(clp_fetch_participants(), JSON_UNESCAPED_UNICODE);
