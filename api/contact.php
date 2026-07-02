<?php
// Ensure JSON output even on fatal errors
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Catch any fatal errors and return JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Eroare server: ' . $err['message']]);
    }
});

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    echo json_encode(['success' => false, 'message' => 'Date invalide. Input primit: ' . substr($raw, 0, 100)]);
    exit;
}

$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email invalid.']);
    exit;
}

$form_type = preg_replace('/[^a-z0-9-]/', '', $body['form_type'] ?? 'contact');
$subjects = [
    'contact'             => 'Mesaj nou de pe site',
    'sustine'             => 'Cerere nouă: Prezintă un curs',
    'sustine-un-curs'     => 'Cerere nouă: Prezintă un curs',
    'gazduieste'          => 'Cerere nouă: Găzduiește un curs',
    'gazduieste-un-curs'  => 'Cerere nouă: Găzduiește un curs',
    'parteneriat'         => 'Cerere nouă: Propune un parteneriat',
];
$subject = ($subjects[$form_type] ?? 'Mesaj nou') . ' — Cursuri la Pahar';

$lines = [];
foreach ($body as $key => $value) {
    if ($key === 'form_type') continue;
    $label = ucfirst(str_replace('_', ' ', $key));
    $value = is_array($value)
        ? implode(', ', array_map('strip_tags', $value))
        : strip_tags(str_replace(["\r\n", "\r", "\n"], ' ', (string)$value));
    $lines[] = "$label: $value";
}
$body_text = implode("\n", $lines) . "\n\n---\nData: " . date('Y-m-d H:i:s');

// Log to file
$log_dir = dirname(__DIR__) . '/data';
if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
@file_put_contents(
    $log_dir . '/messages.log',
    "=== " . date('Y-m-d H:i:s') . " | $form_type ===\n$body_text\n\n",
    FILE_APPEND | LOCK_EX
);

// Send email (best-effort, skip if mail() not available)
if (function_exists('mail')) {
    $headers  = "From: noreply@robotache.ro\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8";
    @mail('contact@cursurilapahar.ro', $subject, $body_text, $headers);
}

// Confirmare automată către vizitator (Brevo), best-effort
$settings_file = dirname(__DIR__) . '/data/settings.json';
$settings = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
$brevo_key = preg_replace('/\s+/', '', $settings['brevo_api_key'] ?? '');

if ($brevo_key) {
    $visitor_name = strip_tags(trim($body['name'] ?? $body['contact_person'] ?? $body['partner_name'] ?? ''));

    $ig = 'http://instagram.com/cursurilapahar';
    $lnk = 'color:#7a2733;font-weight:bold;text-decoration:none';

    // Titlu + corp diferit în funcție de formular
    $confirmations = [
        'contact' => [
            'subject' => 'Am primit mesajul tău 🍻',
            'body'    => '<p style="margin:0 0 14px">Salutare!</p>'
                       . '<p style="margin:0 0 14px">Îți mulțumim că ne-ai scris. Am primit mesajul tău și îl citim cu atenție.</p>'
                       . '<p style="margin:0 0 14px">Îți răspundem cât mai curând, de obicei în câteva zile lucrătoare. Dacă între timp ai o întrebare rapidă, ne găsești oricând pe <a href="'.$ig.'" style="'.$lnk.'">Instagram</a>.</p>'
                       . '<p style="margin:0 0 14px">Apreciem enorm că faci parte din comunitatea Cursuri la Pahar.</p>'
                       . '<p style="margin:0">Ținem legătura!</p>',
        ],
        'sustine' => [
            'subject' => 'Am primit propunerea ta de curs 🎤',
            'body'    => '<p style="margin:0 0 14px">Salutare!</p>'
                       . '<p style="margin:0 0 14px">Îți mulțumim că ne-ai contactat și ne-ai oferit detaliile referitoare la cursul pe care vrei să îl susții. 🍷</p>'
                       . '<p style="margin:0 0 14px">Primim recurent un număr ridicat de propuneri de cursuri și le revizuim săptămânal, pe măsură ce ne planificăm următoarele evenimente. Dacă subiectul și expertiza ta se potrivesc cu publicul nostru, <b>vom reveni cu un mesaj pe WhatsApp</b>.</p>'
                       . '<p style="margin:0 0 14px">Apreciem enorm interesul tău de a face parte din comunitatea Cursuri la Pahar, precum și dorința de a pune o cărămidă în domeniul educației.</p>'
                       . '<p style="margin:0">Ținem legătura!</p>',
        ],
        'gazduieste' => [
            'subject' => 'Am primit propunerea ta de locație 🏠',
            'body'    => '<p style="margin:0 0 14px">Salutare!</p>'
                       . '<p style="margin:0 0 14px">Îți mulțumim că ne-ai oferit detaliile despre locația ta. Ne bucurăm că vrei să găzduiești un curs la pahar. 🍷</p>'
                       . '<p style="margin:0 0 14px">Analizăm fiecare propunere de spațiu pe măsură ce ne planificăm următoarele evenimente și ne asigurăm că atmosfera se potrivește cu vibe-ul comunității noastre. Dacă e o potrivire, <b>vom reveni cu un mesaj pe WhatsApp</b> ca să punem la punct detaliile.</p>'
                       . '<p style="margin:0 0 14px">Apreciem enorm că vrei să deschizi ușa locației tale către oameni curioși și să faci parte din comunitatea Cursuri la Pahar.</p>'
                       . '<p style="margin:0">Ținem legătura!</p>',
        ],
        'parteneriat' => [
            'subject' => 'Am primit propunerea ta de parteneriat 🤝',
            'body'    => '<p style="margin:0 0 14px">Salutare!</p>'
                       . '<p style="margin:0 0 14px">Îți mulțumim că ne-ai contactat și ne-ai oferit detaliile despre parteneriatul pe care îl ai în minte. 🍷</p>'
                       . '<p style="margin:0 0 14px">Analizăm fiecare propunere de colaborare cu atenție și ne uităm la cum putem construi împreună ceva care aduce valoare reală comunității noastre. Dacă vedem o potrivire, <b>revenim cu un mesaj</b> ca să discutăm pașii următori.</p>'
                       . '<p style="margin:0 0 14px">Apreciem enorm interesul tău de a face parte din povestea Cursuri la Pahar.</p>'
                       . '<p style="margin:0">Ținem legătura!</p>',
        ],
    ];
    $ctype = str_replace('-un-curs', '', $form_type);
    $conf = $confirmations[$ctype] ?? $confirmations['contact'];

    $banner = 'https://cursurilapahar.ro/assets/images/email.jpeg';
    $html =
      '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 0"><tr><td align="center">'
    . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;font-family:Georgia,\'Times New Roman\',serif">'
    . '<tr><td><img src="'.$banner.'" alt="Cursuri la Pahar" width="600" style="display:block;width:100%;height:auto;border:0"></td></tr>'
    . '<tr><td style="padding:28px 32px;color:#2b2b2b;font-size:16px;line-height:1.65">'
    . '<div style="font-size:25px;font-weight:bold;color:#1a1a1a;margin-bottom:18px">'.$conf['subject'].'</div>'
    . $conf['body']
    . '<p style="margin:18px 0 4px">Cu drag,</p>'
    . '<p style="margin:0;font-weight:bold;color:#1a1a1a">Echipa Cursuri la Pahar</p>'
    . '</td></tr>'
    . '<tr><td style="padding:18px 32px 26px;border-top:1px solid #eaeaea;text-align:center;font-family:Arial,Helvetica,sans-serif;font-size:13px">'
    . '<a href="'.$ig.'" style="'.$lnk.';margin:0 10px">Instagram</a>'
    . '<a href="https://facebook.com/cursurilapahar" style="'.$lnk.';margin:0 10px">Facebook</a>'
    . '<a href="https://tiktok.com/@cursurilapahar" style="'.$lnk.';margin:0 10px">TikTok</a>'
    . '</td></tr>'
    . '</table></td></tr></table>';

    $payload = [
        'sender'      => ['name' => 'Cursuri la Pahar', 'email' => 'contact@cursurilapahar.ro'],
        'to'          => [['email' => $email, 'name' => $visitor_name ?: $email]],
        'replyTo'     => ['name' => 'Cursuri la Pahar', 'email' => 'contact@cursurilapahar.ro'],
        'subject'     => $conf['subject'],
        'htmlContent' => $html,
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['accept: application/json', 'api-key: ' . $brevo_key, 'content-type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    @curl_exec($ch);
    curl_close($ch);
}

echo json_encode(['success' => true]);
