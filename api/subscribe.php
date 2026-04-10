<?php
header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) { echo json_encode(['success'=>false,'message'=>'Email invalid.']); exit; }

// Load API key from settings
$settings_file = dirname(__DIR__) . '/data/settings.json';
$settings = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
$api_key = $settings['kit_api_key'] ?? 'kit_3ad1bb636169002be3359bd1048e0204';
$form_id = $settings['kit_form_id'] ?? '';

// If form_id set, subscribe via form endpoint; otherwise direct subscriber
if ($form_id) {
    $api_url = 'https://api.kit.com/v4/forms/' . urlencode($form_id) . '/subscribers';
} else {
    $api_url = 'https://api.kit.com/v4/subscribers';
}

$response = file_get_contents($api_url, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\nAuthorization: Bearer " . $api_key . "\r\n",
        'content' => json_encode(['email_address' => $email, 'state' => 'active']),
        'ignore_errors' => true,
    ]
]));
$data = json_decode($response, true);
$code = $http_response_header ? (int)substr($http_response_header[0], 9, 3) : 0;
if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true]);
} else {
    $msg = $data['errors'][0]['title'] ?? 'Eroare la abonare.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
