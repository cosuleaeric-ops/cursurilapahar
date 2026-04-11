<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    echo json_encode(['success' => false, 'message' => 'Date invalide']);
    exit;
}

$id     = preg_replace('/[^a-zA-Z0-9._-]/', '', $body['id'] ?? '');
$action = ($body['action'] ?? 'add') === 'remove' ? 'remove' : 'add';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID invalid']);
    exit;
}

$file = dirname(__DIR__) . '/data/vote_courses.json';
if (!file_exists($file)) {
    echo json_encode(['success' => false, 'message' => 'Fișier negăsit']);
    exit;
}

$fp = fopen($file, 'r+');
if (!$fp) {
    echo json_encode(['success' => false]);
    exit;
}
flock($fp, LOCK_EX);
$data = json_decode(stream_get_contents($fp), true) ?: [];

$found = false;
foreach ($data as &$course) {
    if (($course['id'] ?? '') === $id) {
        if ($action === 'remove') {
            $course['likes'] = max(0, ($course['likes'] ?? 0) - 1);
        } else {
            $course['likes'] = ($course['likes'] ?? 0) + 1;
        }
        $found = true;
        break;
    }
}
unset($course);

if ($found) {
    fseek($fp, 0);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode(['success' => $found]);
