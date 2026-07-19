<?php

/** @return array<string, string> */
function clp_cheltuiala_emoji_map(): array
{
    return [
        'Onorariu curs'      => '🎤',
        'Banca'              => '🏦',
        'Impozit curs'       => '🏛️',
        'Avans'              => '💰',
        'Decont personal'    => '🧾',
        'Echipament'         => '💻',
        'Contabilitate'      => '📊',
        'Google Workspace'   => '📧',
        'Hosting'            => '🌐',
        'AI'                 => '🤖',
        'Salariu'            => '👥',
        'Altele'             => '📦',
        'Dividende'          => '💵',
        'Taxe stat lunare'   => '📋',
        'World Class'        => '🏋️',
        'Fotograf'           => '📷',
        'Chirie'             => '🏠',
        'Rata PC'            => '🖥️',
        'Fast-food'          => '🍔',
        'Teambuilding'       => '☘️',
        'Fun'                => '🤘',
        'Promovare iaBilet'  => '🎟️',
        'Backlinks'          => '🔗',
    ];
}

/** @return array<string, string> */
function clp_cheltuiala_emoji_keywords(): array
{
    return [
        'chirie'       => '🏠',
        'banca'        => '🏦',
        'impozit'      => '🏛️',
        'tax'          => '📋',
        'salariu'      => '👥',
        'hosting'      => '🌐',
        'google'       => '📧',
        'contabilit'   => '📊',
        'echipament'   => '💻',
        'onorariu'     => '🎤',
        'foto'         => '📷',
        'world class'  => '🏋️',
        'fast-food'    => '🍔',
        'mancare'      => '🍔',
        'team'         => '☘️',
        'dividend'     => '💵',
        'avans'        => '💰',
        'decont'       => '🧾',
        'rata pc'      => '🖥️',
        'fun'          => '🤘',
        'iabilet'      => '🎟️',
        'backlink'     => '🔗',
    ];
}

function clp_cheltuiala_category_emoji(string $name): string
{
    $map = clp_cheltuiala_emoji_map();
    if (isset($map[$name])) {
        return $map[$name];
    }

    $n = mb_strtolower(trim($name));
    foreach (clp_cheltuiala_emoji_keywords() as $key => $emoji) {
        if (str_contains($n, $key)) {
            return $emoji;
        }
    }

    return '💸';
}

function clp_cheltuiala_category_label(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '—';
    }

    return clp_cheltuiala_category_emoji($name) . ' ' . $name;
}
