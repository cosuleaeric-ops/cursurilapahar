<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/course_clicks.php';

$id = preg_replace('/[^a-zA-Z0-9._-]/', '', (string) ($_GET['id'] ?? ''));
$course = clp_find_course_by_id($id);
$url = trim($course['livetickets_url'] ?? '');

if ($url === '' || empty($course['active'])) {
    header('Location: /#cursuri', true, 302);
    exit;
}

clp_increment_course_click($id);
header('Location: ' . $url, true, 302);
exit;
