<?php

// Test A/B pentru butonul „Vreau să vin" de pe cardurile de curs (index.php).
// off = cardurile ca acum (fără buton); on = cardurile cu buton „Vreau să vin".
// Metrica de click e aceeași ca la testul de headline: orice ajungere pe
// /go/course.php (click pe card SAU pe buton) numără drept click pentru varianta.
// Datele (views/clicks per variantă) stau în data/ab_button.json.

const CLP_AB_BUTTON_COOKIE = 'clp_ab_btn';
const CLP_AB_BUTTON_VARIANTS = ['off', 'on'];

function clp_ab_button_file(): string
{
    return dirname(__DIR__) . '/data/ab_button.json';
}

/** @return array<string, array{views:int, clicks:int}> */
function clp_ab_button_load(): array
{
    $empty = [];
    foreach (CLP_AB_BUTTON_VARIANTS as $v) {
        $empty[$v] = ['views' => 0, 'clicks' => 0];
    }
    $file = clp_ab_button_file();
    if (!file_exists($file)) {
        return $empty;
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return $empty;
    }
    foreach (CLP_AB_BUTTON_VARIANTS as $v) {
        $empty[$v]['views']  = (int) ($data[$v]['views']  ?? 0);
        $empty[$v]['clicks'] = (int) ($data[$v]['clicks'] ?? 0);
    }
    return $empty;
}

/** Returnează varianta din cookie sau atribuie una nouă (1/2 fiecare, setează cookie). */
function clp_ab_button_assign(): string
{
    $v = (string) ($_COOKIE[CLP_AB_BUTTON_COOKIE] ?? '');
    if (in_array($v, CLP_AB_BUTTON_VARIANTS, true)) {
        return $v;
    }
    $v = CLP_AB_BUTTON_VARIANTS[random_int(0, count(CLP_AB_BUTTON_VARIANTS) - 1)];
    setcookie(CLP_AB_BUTTON_COOKIE, $v, [
        'expires'  => time() + 90 * 86400,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
    $_COOKIE[CLP_AB_BUTTON_COOKIE] = $v;
    return $v;
}

/** Incrementează 'views' sau 'clicks' pentru varianta dată (cu lock pe fișier). */
function clp_ab_button_track(string $variant, string $metric): void
{
    if (!in_array($variant, CLP_AB_BUTTON_VARIANTS, true) || !in_array($metric, ['views', 'clicks'], true)) {
        return;
    }

    $file = clp_ab_button_file();
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
