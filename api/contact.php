<?php
header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) { echo json_encode(['success'=>false,'message'=>'Date invalide.']); exit; }

$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) { echo json_encode(['success'=>false,'message'=>'Email invalid.']); exit; }

$form_type = preg_replace('/[^a-z0-9-]/', '', $body['form_type'] ?? 'contact');
$subjects = [
    'contact' => 'Mesaj nou de pe site',
    'sustine' => 'Cerere nouă: Susține un curs',
    'sustine-un-curs' => 'Cerere nouă: Susține un curs',
    'gazduieste' => 'Cerere nouă: Găzduiește un curs',
    'gazduieste-un-curs' => 'Cerere nouă: Găzduiește un curs',
    'parteneriat' => 'Cerere nouă: Propune un parteneriat',
];
$subject = ($subjects[$form_type] ?? 'Mesaj nou') . ' — Cursuri la Pahar';

$lines = [];
foreach ($body as $key => $value) {
    if ($key === 'form_type') continue;
    $label = ucfirst(str_replace('_', ' ', $key));
    $value = is_array($value) ? implode(', ', array_map('strip_tags', $value)) : strip_tags((string)$value);
    $lines[] = "$label: $value";
}
$body_text = implode("\n", $lines) . "\n\n---\nData: " . date('Y-m-d H:i:s');

// Log
$log_dir = dirname(__DIR__) . '/data';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
file_put_contents($log_dir . '/messages.log', "=== ".date('Y-m-d H:i:s')." | $form_type ===\n$body_text\n\n", FILE_APPEND | LOCK_EX);

// Send email
$headers = "From: noreply@cursurilapahar.ro\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
mail('contact@cursurilapahar.ro', $subject, $body_text, $headers);

echo json_encode(['success' => true]);
 
