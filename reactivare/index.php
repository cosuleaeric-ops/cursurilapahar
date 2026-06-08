<?php
declare(strict_types=1);

/**
 * Re-engagement landing for cold subscribers.
 * Email link: https://cursurilapahar.ro/reactivare/?e={email}
 * Logs the click so we know who re-engaged.
 */

// ── Track the click ──────────────────────────────────────────────────────────
$email = trim((string)($_GET['e'] ?? $_GET['email'] ?? ''));
$id    = trim((string)($_GET['id'] ?? ''));

$log_file = dirname(__DIR__) . '/data/reengage_clicks.json';
$entry = [
    'ts'    => date('c'),
    'email' => mb_substr($email, 0, 160),
    'id'    => mb_substr($id, 0, 80),
    'ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
];
$dir = dirname($log_file);
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$fh = @fopen($log_file, 'c+');
if ($fh) {
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = json_decode($raw ?: '[]', true);
    if (!is_array($data)) $data = [];
    $data[] = $entry;
    rewind($fh);
    ftruncate($fh, 0);
    fwrite($fh, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    flock($fh, LOCK_UN);
    fclose($fh);
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Rămâi cu noi — Cursuri la Pahar</title>
<link rel="icon" href="/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Rubik', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #0D0D0D; color: #ffffff;
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    padding: 24px; line-height: 1.6;
  }
  .card {
    background: #161616; border: 1px solid rgba(255,255,255,.08);
    border-radius: 20px; padding: 44px 38px; max-width: 460px; width: 100%;
    text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.5);
  }
  .logo { height: 54px; width: auto; margin-bottom: 26px; }
  .badge {
    display: inline-block; background: rgba(255,226,5,.12); color: #ffe205;
    font-size: 12px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
    padding: 5px 13px; border-radius: 999px; margin-bottom: 18px;
  }
  h1 { font-size: 25px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 14px; }
  p { color: #C9CDD3; font-size: 15px; margin-bottom: 10px; }
  p strong { color: #fff; }
  .cta {
    display: inline-block; margin-top: 24px; background: #ffe205; color: #0D0D0D;
    font-weight: 700; font-size: 15px; text-decoration: none;
    padding: 13px 26px; border-radius: 12px; transition: background .15s, transform .1s;
  }
  .cta:hover { background: #ccc500; transform: translateY(-1px); }
  .foot { margin-top: 22px; font-size: 12px; color: #6b7280; }
</style>
</head>
<body>
  <div class="card">
    <img src="/assets/images/uploads/logo-1775903455.png" alt="Cursuri la Pahar" class="logo"
         onerror="this.style.display='none'">
    <div class="badge">Ești pe listă ✦</div>
    <h1>Mă bucur că ești aici 👋</h1>
    <p>Te-am trecut înapoi pe lista activă.</p>
    <p>Vei primi <strong>în continuare</strong> invitații la următoarele
       <strong>Cursuri la Pahar</strong> — fără spam, doar ce contează.</p>
    <a href="/" class="cta">Vezi următoarele cursuri</a>
    <div class="foot">Ne vedem la un pahar. 🍷</div>
  </div>
</body>
</html>
