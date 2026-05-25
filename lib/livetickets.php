<?php
declare(strict_types=1);

/**
 * Fetch event metadata from LiveTickets by public URL.
 *
 * @return array{success: bool, message?: string, data?: array<string, string>}
 */
function lt_fetch_event_by_url(string $url): array
{
    $url = trim($url);
    if ($url === '') {
        return ['success' => false, 'message' => 'URL lipsă.'];
    }

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
        return ['success' => false, 'message' => 'URL invalid. Folosește un link de tip livetickets.ro/bilete/... sau livetickets.ro/e/...'];
    }

    if ($event_id && !$slug) {
        $resolve = file_get_contents(
            'https://api.livetickets.ro/public/events/get-url?code=' . urlencode($event_id),
            false,
            stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]])
        );
        $resolved = $resolve ? json_decode($resolve, true) : null;
        $slug = $resolved['url'] ?? '';
        if (!$slug) {
            return ['success' => false, 'message' => 'Nu am putut rezolva linkul scurt LiveTickets.'];
        }
    }

    $api_url = 'https://api.livetickets.ro/public/events/getbyurl?url=' . urlencode($slug);
    $response = file_get_contents($api_url, false, stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]));

    if ($response === false) {
        return ['success' => false, 'message' => 'Eroare la apelul API LiveTickets.'];
    }

    $event = json_decode($response, true);
    if (!$event || !isset($event['id'])) {
        return ['success' => false, 'message' => 'Evenimentul nu a fost găsit în LiveTickets.'];
    }

    $title = $event['name'] ?? '';
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
                9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie',
            ];
            $day   = date('j', $ts);
            $month = $ro_months[(int)date('n', $ts)];
            $year  = date('Y', $ts);
            $date_display = "$day $month $year";
            $time = date('H:i', $ts);
        }
    }

    $location = '';
    $loc = $event['location'] ?? [];
    if (is_array($loc)) {
        $loc_parts = array_filter([$loc['name'] ?? '', $loc['address'] ?? '', $loc['city'] ?? '']);
        $location = implode(', ', $loc_parts);
    } elseif (is_string($loc)) {
        $location = $loc;
    }

    $image_url = '';
    $images = $event['images'] ?? [];
    $fallback_url = '';
    foreach ($images as $img) {
        $name = $img['name'] ?? '';
        $size = $img['size'] ?? '';
        $path  = $img['path'] ?? '';
        $token = $img['token'] ?? '';
        if (!$path) {
            continue;
        }
        $cdn = 'https://livetickets-cdn.azureedge.net/itemimages/' . $path . ($token ? '?' . $token : '');
        if (!$fallback_url) {
            $fallback_url = $cdn;
        }
        if ($name === 'Background' && $size === 'MEDIUM') {
            $image_url = $cdn;
            break;
        }
    }
    if (!$image_url) {
        $image_url = $fallback_url;
    }

    return [
        'success' => true,
        'data' => [
            'title'           => $title,
            'date_display'    => $date_display,
            'date_raw'        => $date_raw,
            'time'            => $time,
            'location'        => $location,
            'image_url'       => $image_url,
            'livetickets_url' => $url,
        ],
    ];
}
