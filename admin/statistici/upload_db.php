<?php
// Temporary upload script - DELETE AFTER USE
$secret = '17a4785ebfef6aa6b03230a229f3514c';
if (($_GET['key'] ?? '') !== $secret) { http_response_code(404); exit('Not found'); }

if (isset($_FILES['file'])) {
    $target = $_GET['target'] ?? '';
    $allowed = [
        'vote_courses'  => __DIR__ . '/../../data/vote_courses.json',
        'courses'       => __DIR__ . '/../../data/courses.json',
        'soldout_cache' => __DIR__ . '/../../data/soldout_cache.json',
    ];
    if (!isset($allowed[$target])) { http_response_code(400); exit('Bad target'); }
    $dest = $allowed[$target];
    $dir = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        chmod($dest, 0644);
        echo "OK: " . filesize($dest) . " bytes -> $dest";
    } else {
        http_response_code(500);
        echo "Failed: dir=$dir exists=" . (is_dir($dir)?'y':'n') . " writable=" . (is_writable($dir)?'y':'n');
    }
} else {
    echo 'POST a file';
}
