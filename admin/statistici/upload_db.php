<?php
// Temporary upload script - DELETE AFTER USE
$secret = '17a4785ebfef6aa6b03230a229f3514c';

if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(404);
    exit('Not found');
}

$action = $_GET['action'] ?? 'info';

if ($action === 'info') {
    $data_dir = __DIR__ . '/data';
    $uploads_dir = __DIR__ . '/uploads';
    echo "data dir exists: " . (is_dir($data_dir) ? 'yes' : 'no') . "\n";
    echo "uploads dir exists: " . (is_dir($uploads_dir) ? 'yes' : 'no') . "\n";
    if (is_file("$data_dir/clp.sqlite")) {
        echo "clp.sqlite: " . filesize("$data_dir/clp.sqlite") . " bytes\n";
    }
    $files = glob("$uploads_dir/*");
    echo "uploads count: " . count($files) . "\n";
    exit;
}

if ($action === 'db' && isset($_FILES['db'])) {
    $dest = __DIR__ . '/data/clp.sqlite';
    $dir = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    if (move_uploaded_file($_FILES['db']['tmp_name'], $dest)) {
        chmod($dest, 0640);
        echo "OK db: " . filesize($dest) . " bytes";
    } else {
        http_response_code(500);
        echo 'Failed';
    }
    exit;
}

if ($action === 'upload' && isset($_FILES['file'])) {
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $name = basename($_FILES['file']['name']);
    $dest = "$dir/$name";
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        chmod($dest, 0640);
        echo "OK $name: " . filesize($dest) . " bytes";
    } else {
        http_response_code(500);
        echo 'Failed';
    }
    exit;
}

echo 'Unknown action';
