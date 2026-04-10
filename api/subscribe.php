<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) { echo json_encode(['success'=>false,'message'=>'Email invalid.']); exit; }

$settings_file = dirname(__DIR__) . '/data/settings.json';
$settings = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
$api_key = preg_replace('/\s+/', '', $settings['kit_api_key'] ?? '');
$form_id = trim($settings['kit_form_id'] ?? '');

if (!$api_key) {
    echo json_encode(['success'=>false,'message'=>'API key lipsă în setări.']); exit;
}

$api_url = $form_id
    ? 'https://api.kit.com/v4/forms/' . urlencode($form_id) . '/subscribers'
    : 'https://api.kit.com/v4/subscribers';

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['email_address' => $email]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $api_key,
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$curl_err = curl_error($ch);
$code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $code === 0) {
    echo json_encode(['success'=>false,'message'=>'Eroare conexiune: ' . $curl_err]); exit;
}

$data = json_decode($response, true);
if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true]);
} else {
    $msg = $data['errors'][0]['title'] ?? $data['message'] ?? ('HTTP ' . $code . ': ' . substr($response, 0, 200));
    echo json_encode(['success' => false, 'message' => $msg . ' [key_len=' . strlen($api_key) . ' prefix=' . substr($api_key,0,8) . ']']);
}
