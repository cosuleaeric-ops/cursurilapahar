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
