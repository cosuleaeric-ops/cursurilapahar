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

if (clp_should_count_course_click()) {
    clp_increment_course_click($id);

    // Test A/B headline: atribuie click-ul variantei din cookie.
    require_once dirname(__DIR__) . '/lib/ab_headline.php';
    $ab = (string) ($_COOKIE[CLP_AB_HEADLINE_COOKIE] ?? '');
    if ($ab === 'A' || $ab === 'B') {
        clp_ab_headline_track($ab, 'clicks');
    }
}

header('Location: ' . $url, true, 302);
exit;
