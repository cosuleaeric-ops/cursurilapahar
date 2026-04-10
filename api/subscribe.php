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
$form_id = preg_replace('/\s+/', '', $settings['kit_form_id'] ?? '');

if (!$api_key) { echo json_encode(['success'=>false,'message'=>'API key lipsă în setări Kit.']); exit; }
if (!$form_id) { echo json_encode(['success'=>false,'message'=>'Form ID lipsă în setări Kit.']); exit; }

// ConvertKit v3 API (stable, uses api_key in body)
$api_url = 'https://api.convertkit.com/v3/forms/' . urlencode($form_id) . '/subscribe';

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['api_key' => $api_key, 'email' => $email]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
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
if ($code >= 200 && $code < 300 && isset($data['subscription'])) {
    echo json_encode(['success' => true]);
} else {
    $msg = $data['message'] ?? $data['error'] ?? ('HTTP ' . $code . ': ' . substr($response, 0, 200));
    echo json_encode(['success' => false, 'message' => $msg]);
}
