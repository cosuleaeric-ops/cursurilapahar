<?php
declare(strict_types=1);

require_once __DIR__ . '/livetickets.php';
require_once __DIR__ . '/iabilet.php';

/**
 * Fetch course image + metadata from a ticket URL, routing by provider
 * (iaBilet via og:image scraping, LiveTickets via its public API).
 *
 * @return array{success: bool, message?: string, data?: array<string, string>}
 */
function clp_fetch_course_meta_by_url(string $url): array
{
    $url = trim($url);
    if ($url === '') {
        return ['success' => false, 'message' => 'URL lipsă.'];
    }
    if (iabilet_is_url($url)) {
        return iabilet_fetch_image($url);
    }
    return lt_fetch_event_by_url($url);
}
