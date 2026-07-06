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
    if (in_array($ab, CLP_AB_HEADLINE_VARIANTS, true)) {
        clp_ab_headline_track($ab, 'clicks');
    }

    // Test A/B buton „Vreau să vin": click pe card sau buton = același redirect.
    require_once dirname(__DIR__) . '/lib/ab_button.php';
    $ab_btn = (string) ($_COOKIE[CLP_AB_BUTTON_COOKIE] ?? '');
    if (in_array($ab_btn, CLP_AB_BUTTON_VARIANTS, true)) {
        clp_ab_button_track($ab_btn, 'clicks');
    }
}

header('Location: ' . $url, true, 302);
exit;
