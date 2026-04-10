<?php
session_start();
header('Content-Type: application/json');

// Simple admin session check
if (empty($_SESSION['clp_admin'])) {
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

// Extract slug from LiveTickets URL
// e.g. https://www.livetickets.ro/bilete/some-event-slug
$slug = '';
if (preg_match('#livetickets\.ro/bilete/([^/?#]+)#i', $url, $m)) {
    $slug = $m[1];
}

if (!$slug) {
    echo json_encode(['success' => false, 'message' => 'Nu am putut extrage slug-ul din URL.']);
    exit;
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

// Parse location
$location = '';
if (!empty($event['venue']['name'])) {
    $location = $event['venue']['name'];
    if (!empty($event['venue']['city'])) {
        $location .= ', ' . $event['venue']['city'];
    }
} elseif (!empty($event['location'])) {
    $location = $event['location'];
}

// Find Background MEDIUM image
$image_url = '';
$images = $event['images'] ?? $event['itemImages'] ?? [];
foreach ($images as $img) {
    $type = strtolower($img['type'] ?? $img['imageType'] ?? '');
    $size = strtolower($img['size'] ?? $img['imageSize'] ?? '');
    if (str_contains($type, 'background') && str_contains($size, 'medium')) {
        // Build CDN URL
        $path  = $img['path'] ?? $img['imagePath'] ?? '';
        $token = $img['token'] ?? $img['sasToken'] ?? '';
        if ($path) {
            $image_url = 'https://livetickets-cdn.azureedge.net/itemimages/' . $path;
            if ($token) $image_url .= '?' . $token;
        }
        break;
    }
}
// Fallback: first image with a path if no background medium found
if (!$image_url) {
    foreach ($images as $img) {
        $path  = $img['path'] ?? $img['imagePath'] ?? '';
        $token = $img['token'] ?? $img['sasToken'] ?? '';
        if ($path) {
            $image_url = 'https://livetickets-cdn.azureedge.net/itemimages/' . $path;
            if ($token) $image_url .= '?' . $token;
            break;
        }
    }
}

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
 
