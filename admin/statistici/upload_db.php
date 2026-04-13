<?php
// Temporary one-time upload script - DELETE AFTER USE
$secret = '683d37e9fe7963185743ba14a98c5441';

if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(404);
    exit('Not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo 'Upload ready. POST a file named "db" to this URL.';
    exit;
}

if (!isset($_FILES['db']) || $_FILES['db']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('No file or upload error');
}

$dest = __DIR__ . '/data/pnl.sqlite';
$dir  = dirname($dest);
if (!is_dir($dir)) {
    mkdir($dir, 0750, true);
}

if (move_uploaded_file($_FILES['db']['tmp_name'], $dest)) {
    chmod($dest, 0640);
    echo "OK - uploaded " . filesize($dest) . " bytes to $dest";
} else {
    http_response_code(500);
    echo 'Failed to move file';
}
