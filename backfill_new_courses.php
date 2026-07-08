<?php
/**
 * One-time: marchează „NOU" cursurile puse azi (19/23/28 iulie), ca și cum
 * linkul ar fi fost pus azi la ora 18:00 (București). Se rulează o dată pe live:
 *   https://cursurilapahar.ro/backfill_new_courses.php?token=new_clp_2026_jul
 * După ce ai văzut „SUCCES", șterge acest fișier.
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['token'] ?? '') !== 'new_clp_2026_jul') {
    http_response_code(403);
    echo "Token invalid.\n";
    exit;
}

require_once __DIR__ . '/lib/courses.php';

$target_dates = ['2026-07-19', '2026-07-23', '2026-07-28'];
$added = (new DateTimeImmutable('today 18:00', new DateTimeZone('Europe/Bucharest')))->format('c');

$courses = clp_load_courses_from_json();
$updated = [];
foreach ($courses as &$c) {
    $date = clp_resolve_course_date_raw($c);
    if (!in_array($date, $target_dates, true)) {
        continue;
    }
    if (!clp_course_has_ticket_link($c) || trim($c['link_added_at'] ?? '') !== '') {
        continue;
    }
    $c['link_added_at'] = $added;
    $updated[] = ($c['title'] ?? $c['id'] ?? '?') . ' (' . $date . ')';
}
unset($c);

if ($updated) {
    clp_save_courses($courses);
    echo "--- SUCCES: link_added_at = $added ---\n";
    foreach ($updated as $u) {
        echo " • $u\n";
    }
    echo "\nȘterge acum backfill_new_courses.php.\n";
} else {
    echo "Nimic de actualizat (cursurile fie nu există, fie n-au link, fie au deja marcaj).\n";
}
