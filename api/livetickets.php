<?php
header('Content-Type: application/json');

// Cookie-based auth check (same format as admin/index.php: "username:hmac")
$_lt_settings = file_exists(dirname(__DIR__) . '/data/settings.json')
    ? (json_decode(file_get_contents(dirname(__DIR__) . '/data/settings.json'), true) ?: [])
    : [];
$_lt_secret = $_lt_settings['auth_secret'] ?? '';
$cookie = $_COOKIE['clp_auth'] ?? '';
$_lt_authed = false;
if ($cookie && $_lt_secret && str_contains($cookie, ':')) {
    [$_lt_uname, $_lt_token] = explode(':', $cookie, 2);
    $_lt_expected = hash_hmac('sha256', 'clp_user:' . $_lt_uname, $_lt_secret);
    $_lt_authed = hash_equals($_lt_expected, $_lt_token);
}
if (!$_lt_authed) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$url = trim($body['url'] ?? '');

if (!$url) {
    echo json_encode(['success' => false, 'message' => 'URL lipsă.']);
    exit;
}

// Extract slug/id from LiveTickets URL
// Supports: /bilete/some-event-slug  and  /e/5a2896d
$path  = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
$parts = explode('/', $path);
$slug = '';
$event_id = '';
$bilete_idx = array_search('bilete', $parts);
$e_idx = array_search('e', $parts);
if ($bilete_idx !== false && isset($parts[$bilete_idx + 1])) {
    $slug = $parts[$bilete_idx + 1];
} elseif ($e_idx !== false && isset($parts[$e_idx + 1])) {
    $event_id = $parts[$e_idx + 1];
}

if (!$slug && !$event_id) {
    echo json_encode(['success' => false, 'message' => 'URL invalid. Folosește un link de tip livetickets.ro/bilete/... sau livetickets.ro/e/...']);
    exit;
}

// For /e/ short links, resolve the short code via LiveTickets API
if ($event_id && !$slug) {
    $resolve = file_get_contents(
        'https://api.livetickets.ro/public/events/get-url?code=' . urlencode($event_id),
        false, stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]])
    );
    $resolved = $resolve ? json_decode($resolve, true) : null;
    $slug = $resolved['url'] ?? '';
    if (!$slug) {
        echo json_encode(['success' => false, 'message' => 'Nu am putut rezolva linkul scurt LiveTickets.']);
        exit;
    }
}

// Call LiveTickets API
$api_url = 'https://api.livetickets.ro/public/events/getbyurl?url=' . urlencode($slug);
$response = file_get_contents($api_url, false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n",
        'ignore_errors' => true,
        'timeout' => 15,
    ]
]));

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Eroare la apelul API LiveTickets.']);
    exit;
}

$event = json_decode($response, true);
if (!$event || !isset($event['id'])) {
    echo json_encode(['success' => false, 'message' => 'Evenimentul nu a fost găsit în LiveTickets.']);
    exit;
}

// Parse name
$title = $event['name'] ?? '';

// Parse start_date → date_raw (YYYY-MM-DD) and date_display + time
$date_raw = '';
$date_display = '';
$time = '';
$start_date = $event['start_date'] ?? $event['startDate'] ?? '';
if ($start_date) {
    $ts = strtotime($start_date);
    if ($ts) {
        $date_raw = date('Y-m-d', $ts);
        $ro_months = [
            1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
            5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
            9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
        ];
        $day   = date('j', $ts);
        $month = $ro_months[(int)date('n', $ts)];
        $year  = date('Y', $ts);
        $date_display = "$day $month $year";
        $time = date('H:i', $ts);
    }
}

// Parse location (API: location.name, location.address, location.city)
$location = '';
$loc = $event['location'] ?? [];
if (is_array($loc)) {
    $parts = array_filter([$loc['name'] ?? '', $loc['address'] ?? '', $loc['city'] ?? '']);
    $location = implode(', ', $parts);
} elseif (is_string($loc)) {
    $location = $loc;
}

// Find Background MEDIUM image (API fields: name, size, path, token)
$image_url = '';
$images = $event['images'] ?? [];
$fallback_url = '';
foreach ($images as $img) {
    $name = $img['name'] ?? '';
    $size = $img['size'] ?? '';
    $path  = $img['path'] ?? '';
    $token = $img['token'] ?? '';
    if (!$path) continue;
    $cdn = 'https://livetickets-cdn.azureedge.net/itemimages/' . $path . ($token ? '?' . $token : '');
    if (!$fallback_url) $fallback_url = $cdn;
    if ($name === 'Background' && $size === 'MEDIUM') {
        $image_url = $cdn;
        break;
    }
}
if (!$image_url) $image_url = $fallback_url;

echo json_encode([
    'success' => true,
    'data' => [
        'title'          => $title,
        'date_display'   => $date_display,
        'date_raw'       => $date_raw,
        'time'           => $time,
        'location'       => $location,
        'image_url'      => $image_url,
        'livetickets_url' => $url,
    ]
]);
 
