<?php

// Test A/B pentru headline-ul din hero (index.php).
// Varianta A = hero_title din setări; Varianta B = textul de mai jos.
// Datele (views/clicks per variantă) stau în data/ab_headline.json.

const CLP_AB_HEADLINE_COOKIE = 'clp_ab_hl';
const CLP_AB_HEADLINE_B = 'Cursuri de la care<br>nu vrei să chiulești';

function clp_ab_headline_file(): string
{
    return dirname(__DIR__) . '/data/ab_headline.json';
}

/** @return array{A: array{views:int, clicks:int}, B: array{views:int, clicks:int}} */
function clp_ab_headline_load(): array
{
    $empty = ['A' => ['views' => 0, 'clicks' => 0], 'B' => ['views' => 0, 'clicks' => 0]];
    $file = clp_ab_headline_file();
    if (!file_exists($file)) {
        return $empty;
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return $empty;
    }
    foreach (['A', 'B'] as $v) {
        $empty[$v]['views']  = (int) ($data[$v]['views']  ?? 0);
        $empty[$v]['clicks'] = (int) ($data[$v]['clicks'] ?? 0);
    }
    return $empty;
}

/** Returnează varianta din cookie sau atribuie una nouă 50/50 (setează cookie). */
function clp_ab_headline_assign(): string
{
    $v = (string) ($_COOKIE[CLP_AB_HEADLINE_COOKIE] ?? '');
    if ($v === 'A' || $v === 'B') {
        return $v;
    }
    $v = random_int(0, 1) === 0 ? 'A' : 'B';
    setcookie(CLP_AB_HEADLINE_COOKIE, $v, [
        'expires'  => time() + 90 * 86400,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
    $_COOKIE[CLP_AB_HEADLINE_COOKIE] = $v;
    return $v;
}

/** Incrementează 'views' sau 'clicks' pentru varianta dată (cu lock pe fișier). */
function clp_ab_headline_track(string $variant, string $metric): void
{
    if (!in_array($variant, ['A', 'B'], true) || !in_array($metric, ['views', 'clicks'], true)) {
        return;
    }

    $file = clp_ab_headline_file();
    $fp = fopen($file, 'c+');
    if (!$fp) {
        return;
    }

    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = $raw !== false && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    $data[$variant][$metric] = (int) ($data[$variant][$metric] ?? 0) + 1;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}
