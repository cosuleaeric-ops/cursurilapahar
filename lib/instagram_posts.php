<?php
// Instagram posts marked on the courses calendar (e.g. "POSTARE CURSURI").
// Stored server-side as { "YYYY-MM-DD": ["postare_cursuri", ...] }.

function clp_ig_posts_file(): string {
    return dirname(__DIR__) . '/data/instagram_posts.json';
}

// Available Instagram post types shown in the calendar day dropdown.
function clp_ig_post_types(): array {
    return [
        'postare_cursuri' => ['label' => 'POSTARE CURSURI'],
    ];
}

function clp_load_ig_posts(): array {
    $file = clp_ig_posts_file();
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function clp_save_ig_posts(array $map): void {
    $file = clp_ig_posts_file();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $map = array_filter($map, fn($types) => !empty($types)); // drop empty days
    file_put_contents($file, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// Toggle a post type on/off for a date. Returns the resulting list for that date.
function clp_toggle_ig_post(string $date, string $type, bool $on): array {
    $map = clp_load_ig_posts();
    $cur = array_values(array_filter($map[$date] ?? [], fn($t) => $t !== $type));
    if ($on) {
        $cur[] = $type;
    }
    if ($cur) {
        $map[$date] = $cur;
    } else {
        unset($map[$date]);
    }
    clp_save_ig_posts($map);
    return $cur;
}
