<?php
header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) { echo json_encode(['success'=>false,'message'=>'Email invalid.']); exit; }

$response = file_get_contents('https://api.kit.com/v4/subscribers', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\nAuthorization: Bearer kit_3ad1bb636169002be3359bd1048e0204\r\n",
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
 
