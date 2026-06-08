<?php
declare(strict_types=1);
/**
 * Re-engagement landing for cold subscribers.
 * Email link: https://cursurilapahar.ro/email
 * (Click tracking is handled by the email marketing tool.)
 */
header('X-Robots-Tag: noindex, nofollow');
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
  .brand { font-size: 15px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #ffffff; margin-bottom: 24px; }
  .brand .glass { color: #ffe205; }
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
    <div class="brand"><span class="glass">🍷</span> Cursuri la Pahar</div>
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
