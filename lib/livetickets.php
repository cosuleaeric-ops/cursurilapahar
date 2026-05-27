<?php
declare(strict_types=1);

function lt_http_get(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return ($resp !== false && $resp !== '') ? $resp : null;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout'       => 12,
            'ignore_errors' => true,
            'header'        => "Accept: application/json\r\n",
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    return ($resp !== false && $resp !== '') ? $resp : null;
}

function lt_slug_from_url(string $url): string
{
    $path  = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    $parts = array_values(array_filter(explode('/', $path), 'strlen'));

    $idx = array_search('bilete', $parts, true);
    if ($idx !== false && isset($parts[$idx + 1])) {
        return $parts[$idx + 1];
    }

    $idx = array_search('e', $parts, true);
    if ($idx !== false && isset($parts[$idx + 1])) {
        $resp = lt_http_get('https://api.livetickets.ro/public/events/get-url?code=' . urlencode($parts[$idx + 1]));
        if ($resp) {
            $j = json_decode($resp, true);
            return is_array($j) ? (string)($j['url'] ?? '') : '';
        }
    }

    return '';
}

function lt_get_event_by_url(string $url): ?array
{
    $slug = lt_slug_from_url($url);
    if ($slug === '') {
        return null;
    }

    $resp = lt_http_get('https://api.livetickets.ro/public/events/getbyurl?url=' . urlencode($slug));
    if (!$resp) {
        return null;
    }

    $event = json_decode($resp, true);
    return (is_array($event) && isset($event['id'])) ? $event : null;
}

function lt_image_url_from_event(array $event): string
{
    $image_url = '';
    $fallback_url = '';
    foreach ($event['images'] ?? [] as $img) {
        if (!is_array($img)) {
            continue;
        }
        $path = $img['path'] ?? '';
        if ($path === '') {
            continue;
        }
        $token = $img['token'] ?? '';
        $cdn = 'https://livetickets-cdn.azureedge.net/itemimages/' . $path . ($token ? '?' . $token : '');
        if ($fallback_url === '') {
            $fallback_url = $cdn;
        }
        if (($img['name'] ?? '') === 'Background' && ($img['size'] ?? '') === 'MEDIUM') {
            $image_url = $cdn;
            break;
        }
    }

    return $image_url !== '' ? $image_url : $fallback_url;
}

function lt_is_sold_out(array $event): bool
{
    if (!empty($event['items']) && is_array($event['items'])) {
        foreach ($event['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (empty($item['soldout'])) {
                return false;
            }
        }
        return count($event['items']) > 0;
    }

    $remaining = $event['remaining_count'] ?? null;
    $total     = $event['ticket_count'] ?? null;
    if ($remaining === 0 && is_numeric($total) && (int)$total > 0) {
        return true;
    }

    return false;
}

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

    $event = lt_get_event_by_url($url);
    if (!$event) {
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

    return [
        'success' => true,
        'data' => [
            'title'           => $title,
            'date_display'    => $date_display,
            'date_raw'        => $date_raw,
            'time'            => $time,
            'location'        => $location,
            'image_url'       => lt_image_url_from_event($event),
            'livetickets_url' => $url,
        ],
    ];
}
