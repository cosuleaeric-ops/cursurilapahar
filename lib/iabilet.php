<?php
declare(strict_types=1);

function iabilet_is_url(string $url): bool
{
    $host = strtolower((string) parse_url(trim($url), PHP_URL_HOST));
    return $host !== '' && (str_ends_with($host, 'iabilet.ro') || $host === 'iabilet.ro');
}

function iabilet_http_get(string $url): ?string
{
    $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => $ua,
        ]);
        $resp = curl_exec($ch);
        return ($resp !== false && $resp !== '') ? $resp : null;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout'       => 12,
            'ignore_errors' => true,
            'header'        => "User-Agent: {$ua}\r\n",
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    return ($resp !== false && $resp !== '') ? $resp : null;
}

function iabilet_og_content(string $html, string $property): string
{
    if (preg_match('/<meta[^>]+property=["\']og:' . preg_quote($property, '/') . '["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
    }
    if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:' . preg_quote($property, '/') . '["\']/i', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
    }
    return '';
}

/**
 * Fetch event image (and title) from an iaBilet page via og: meta tags.
 *
 * @return array{success: bool, message?: string, data?: array<string, string>}
 */
function iabilet_fetch_image(string $url): array
{
    $url = trim($url);
    if ($url === '') {
        return ['success' => false, 'message' => 'URL lipsă.'];
    }

    $html = iabilet_http_get($url);
    if ($html === null) {
        return ['success' => false, 'message' => 'Pagina iaBilet nu a putut fi accesată.'];
    }

    $image_url = iabilet_og_content($html, 'image');
    if ($image_url === '') {
        return ['success' => false, 'message' => 'Nu s-a găsit imagine pe pagina iaBilet.'];
    }

    return [
        'success' => true,
        'data' => [
            'title'           => iabilet_og_content($html, 'title'),
            'image_url'       => $image_url,
            'livetickets_url' => $url,
        ],
    ];
}
