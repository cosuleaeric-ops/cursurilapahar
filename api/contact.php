<?php
/**
 * Contact form handler (also used by colaborare sub-pages)
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date invalide.']);
    exit;
}

// Determine form type
$formType = $body['form_type'] ?? 'contact';

// Basic validation: require email
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email invalid.']);
    exit;
}

// Build email body
$subjectMap = [
    'contact'           => 'Mesaj nou de pe site',
    'sustine-un-curs'   => 'Cerere nouă: Susține un curs',
    'gazduieste-un-curs'=> 'Cerere nouă: Găzduiește un curs',
    'parteneriat'       => 'Cerere nouă: Propune un parteneriat',
];
$subject = ($subjectMap[$formType] ?? 'Mesaj nou') . ' — Cursuri la Pahar';

// Sanitize and format all fields
$lines = [];
foreach ($body as $key => $value) {
    if ($key === 'form_type') continue;
    $label = ucfirst(str_replace('_', ' ', $key));
    if (is_array($value)) {
        $value = implode(', ', array_map('strip_tags', $value));
    } else {
        $value = strip_tags((string)$value);
    }
    $lines[] = "{$label}: {$value}";
}
$bodyText = implode("\n", $lines);
$bodyText .= "\n\n---\nTrims de pe: " . ($_SERVER['HTTP_HOST'] ?? 'site') . "\nData: " . date('Y-m-d H:i:s');

// Also save to file as backup (in data/ folder)
$logFile = __DIR__ . '/../data/messages.log';
$logEntry = "=== " . date('Y-m-d H:i:s') . " | {$formType} ===\n{$bodyText}\n\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Send email
$headers = implode("\r\n", [
    'From: noreply@cursurilapahar.ro',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: CursuriLaPahar',
]);

$sent = @mail(CONTACT_EMAIL, $subject, $bodyText, $headers);

// Even if mail() fails, we saved to log — return success
echo json_encode(['success' => true]);
