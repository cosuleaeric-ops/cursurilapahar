<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__DIR__) . '/lib/vote_views.php';

if (!clp_should_count_course_click()) {
    echo json_encode(['success' => true, 'skipped' => true]);
    exit;
}

clp_increment_vote_page_view();
echo json_encode(['success' => true]);
