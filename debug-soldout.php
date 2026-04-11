<?php
// TEMPORARY DEBUG - DELETE AFTER USE
if (($_GET['token'] ?? '') !== 'clp-debug-2026') { http_response_code(403); die('Forbidden'); }

$courses_file = __DIR__ . '/data/courses.json';
$courses = file_exists($courses_file) ? json_decode(file_get_contents($courses_file), true) : [];

foreach ($courses as $course) {
    $url = $course['livetickets_url'] ?? '';
    if (!$url) continue;

    $path  = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    $parts = explode('/', $path);
    $idx   = array_search('bilete', $parts);
    $slug  = ($idx !== false && isset($parts[$idx + 1])) ? $parts[$idx + 1] : '';
    if (!$slug) continue;

    $api = 'https://api.livetickets.ro/public/events/getbyurl?url=' . urlencode($slug);

    $ch = curl_init($api);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_HTTPHEADER=>['Accept: application/json']]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    echo "<h2>" . htmlspecialchars($course['title'] ?? $slug) . "</h2>";
    echo "<p><strong>Slug:</strong> $slug</p>";
    if ($err) { echo "<p style='color:red'>CURL error: $err</p>"; continue; }
    $ev = json_decode($resp, true);
    echo "<pre style='background:#111;color:#0f0;padding:16px;overflow:auto'>" . htmlspecialchars(json_encode($ev, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    echo "<hr>";
}
