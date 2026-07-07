<?php

function clp_get_all_images(string $public_html, string $uploads_dir, string $uploads_url): array {
    $imgs = [];
    $collect = function (string $dir, string $url_prefix, bool $deletable) use (&$imgs) {
        if (!is_dir($dir)) return;
        $files = scandir($dir);
        $names = array_map(fn($f) => strtolower($f), $files);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            if (!is_file($dir . '/' . $f)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'])) continue;
            if ($ext === 'webp') {
                $base = strtolower(pathinfo($f, PATHINFO_FILENAME));
                if (in_array($base . '.jpg', $names) || in_array($base . '.jpeg', $names) || in_array($base . '.png', $names)) continue;
            }
            // thumb = varianta .webp mai ușoară pentru afișare în admin (URL-ul canonic rămâne neschimbat)
            $thumb = $url_prefix . $f;
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $webp_name = pathinfo($f, PATHINFO_FILENAME) . '.webp';
                if (is_file($dir . '/' . $webp_name)) $thumb = $url_prefix . $webp_name;
            }
            $imgs[] = ['url' => $url_prefix . $f, 'name' => $f, 'thumb' => $thumb, 'deletable' => $deletable, 'mtime' => @filemtime($dir . '/' . $f) ?: 0];
        }
    };
    $collect($public_html . '/assets/images/', '/assets/images/', false);
    $collect($public_html . '/assets/images/gallery/', '/assets/images/gallery/', true);
    $collect($uploads_dir, $uploads_url . '/', true);
    usort($imgs, fn($a, $b) => $b['mtime'] <=> $a['mtime']); // cele mai noi primele
    return $imgs;
}

function get_all_images(): array {
    return clp_get_all_images(PUBLIC_HTML, UPLOADS_DIR, UPLOADS_URL);
}
