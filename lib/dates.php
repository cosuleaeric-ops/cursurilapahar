<?php
declare(strict_types=1);

function clp_ro_month_names(bool $titleCase = false): array
{
    $months = [
        1 => 'ianuarie', 2 => 'februarie', 3 => 'martie', 4 => 'aprilie',
        5 => 'mai', 6 => 'iunie', 7 => 'iulie', 8 => 'august',
        9 => 'septembrie', 10 => 'octombrie', 11 => 'noiembrie', 12 => 'decembrie',
    ];
    if (!$titleCase) {
        return $months;
    }
    $titled = [];
    foreach ($months as $n => $name) {
        $titled[$n] = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($name, 1, null, 'UTF-8');
    }
    return $titled;
}

/** 0-indexed list with empty first element (for month number lookups). */
function clp_ro_months_list(bool $titleCase = false): array
{
    $months = clp_ro_month_names($titleCase);
    $list = [''];
    for ($i = 1; $i <= 12; $i++) {
        $list[$i] = $months[$i];
    }
    return $list;
}

/** Nume zi în română, w = 0 (duminică) … 6 (sâmbătă), ca date('w'). */
function clp_ro_weekday_name(int $w): string
{
    $days = [
        0 => 'Duminică', 1 => 'Luni', 2 => 'Marți', 3 => 'Miercuri',
        4 => 'Joi', 5 => 'Vineri', 6 => 'Sâmbătă',
    ];
    return $days[$w] ?? '';
}

/** Prefix zi pentru un curs: „Astăzi" / „Mâine" / numele zilei (fus București). */
function clp_ro_day_prefix(string $date_raw): string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_raw)) {
        return '';
    }
    $tz    = new DateTimeZone('Europe/Bucharest');
    $today = new DateTimeImmutable('today', $tz);
    $date  = DateTimeImmutable::createFromFormat('!Y-m-d', $date_raw, $tz);
    if ($date === false) {
        return '';
    }
    $days = (int) $today->diff($date)->days;
    if ($date >= $today && $days === 0) {
        return 'Astăzi';
    }
    if ($date >= $today && $days === 1) {
        return 'Mâine';
    }
    return clp_ro_weekday_name((int) $date->format('w'));
}

function clp_format_date_ro(string $date_raw, bool $withYear = true, bool $titleCase = false): string
{
    if ($date_raw === '') {
        return '';
    }
    $ts = strtotime($date_raw);
    if (!$ts) {
        return '';
    }
    $months = clp_ro_month_names($titleCase);
    $day = date('j', $ts);
    $month = $months[(int)date('n', $ts)] ?? '';
    if ($month === '') {
        return '';
    }
    return $withYear ? "$day $month " . date('Y', $ts) : "$day $month";
}

function clp_date_display_from_raw(string $date_raw): string
{
    return clp_format_date_ro($date_raw, true, true);
}

function clp_ro_month_label(int $month, int $year = 0): string
{
    $months = clp_ro_month_names(false);
    $name = $months[$month] ?? '';
    if ($name === '') {
        return '';
    }
    $label = ucfirst($name);
    return $year > 0 ? "$label $year" : $label;
}
