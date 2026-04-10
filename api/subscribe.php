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

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['email_address' => $email, 'state' => 'active']),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $api_key,
    ],
    CURLOPT_TIMEOUT        => 10,
]);
$response = curl_exec($ch);
$code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($response, true);
if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true]);
} else {
    $msg = $data['errors'][0]['title'] ?? $data['message'] ?? 'Eroare la abonare.';
    // Debug: log full response
    @file_put_contents(dirname(__DIR__) . '/data/subscribe_debug.log',
        date('Y-m-d H:i:s') . " | code=$code | url=$api_url | key=" . substr($api_key,0,12) . "... | response=" . $response . "\n",
        FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $msg, '_debug_code' => $code]);
}
