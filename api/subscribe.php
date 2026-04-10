<?php
/**
 * Newsletter subscription via Kit.com (ConvertKit) API v4
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Adresă de email invalidă.']);
    exit;
}

// ── Kit.com API v4 ────────────────────────
$payload = json_encode([
    'email_address' => $email,
    'state'         => 'active',
]);

$ch = curl_init();

// If form ID is configured, use the form endpoint (recommended)
if (!empty(KIT_FORM_ID)) {
    $url = 'https://api.kit.com/v4/forms/' . KIT_FORM_ID . '/subscribers';
} else {
    $url = 'https://api.kit.com/v4/subscribers';
}

curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . KIT_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare de conexiune. Încearcă din nou.']);
    exit;
}

$data = json_decode($response, true);

// Kit.com returns 200/201 on success
if ($httpStatus >= 200 && $httpStatus < 300) {
    echo json_encode(['success' => true]);
} else {
    // Log error server-side (optional)
    // error_log('Kit.com subscribe error: ' . $response);
    $msg = $data['errors'][0]['title'] ?? 'Eroare la abonare. Încearcă din nou.';
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $msg]);
}
