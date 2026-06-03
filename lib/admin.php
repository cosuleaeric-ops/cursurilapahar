<?php

if (!function_exists('h')) {
    function h(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

function load_settings(): array {
    return clp_load_settings();
}

function save_settings(array $settings): bool {
    return clp_save_settings($settings);
}

/** @return list<string> */
function clp_admin_tabs(): array {
    return [
        'dashboard', 'cursuri', 'imagini', 'aspect', 'kit', 'mesaje', 'vot',
        'competitori', 'speakeri', 'locatii', 'colaborari', 'securitate', 'config',
    ];
}

function clp_resolve_admin_tab(string $tab): string {
    return in_array($tab, clp_admin_tabs(), true) ? $tab : 'dashboard';
}

/**
 * @return array{year: int, month: int, ctab: string, ro_months: array<int, string>}
 */
function clp_cursuri_stats_nav(): array {
    $clp_now = new DateTimeImmutable();
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)$clp_now->format('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)$clp_now->format('n');
    $ctab_raw = $_GET['ctab'] ?? 'cursuri';
    $ctab = in_array($ctab_raw, ['cursuri', 'participanti', 'calendar'], true) ? $ctab_raw : 'cursuri';
    return [
        'year'      => $year,
        'month'     => $month,
        'ctab'      => $ctab,
        'ro_months' => clp_ro_months_list(false),
    ];
}
