<?php
declare(strict_types=1);

require_once __DIR__ . '/courses.php';

/**
 * Normalized matching key: lowercase, Romanian diacritics stripped (both
 * comma-below and legacy cedilla forms), separators unified, tokens sorted
 * alphabetically so "Popescu Ion" === "Ion Popescu".
 */
function clp_participant_name_key(string $name): string
{
    $n = mb_strtolower(trim($name), 'UTF-8');
    $n = strtr($n, ['ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't', '-' => ' ', '.' => ' ']);
    $toks = preg_split('/\s+/u', $n, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    sort($toks);
    return implode(' ', $toks);
}

/**
 * Fuzzy-merge normalized keys to absorb typos ("ionesc" vs "ionescu").
 * Guarded so short names never merge: distance ≤1 only above 10 chars,
 * ≤2 only above 15, and always same first letter.
 *
 * @param list<string> $keys
 * @return array<string, string> key => canonical key
 */
function clp_merge_participant_keys(array $keys): array
{
    sort($keys);
    $reps = [];
    $map = [];
    foreach ($keys as $k) {
        $target = $k;
        $len = strlen($k);
        if ($len > 10) {
            $max = $len > 15 ? 2 : 1;
            foreach ($reps as $r) {
                if ($r[0] !== $k[0] || abs(strlen($r) - $len) > $max) continue;
                if (levenshtein($k, $r) <= $max) { $target = $r; break; }
            }
        }
        if ($target === $k) $reps[] = $k;
        $map[$k] = $target;
    }
    return $map;
}

/** @return array{participants: list<array<string, mixed>>, stats: array{unique: int, returning: int, tickets: int}} */
function clp_fetch_participants(): array
{
    $empty = ['participants' => [], 'stats' => ['unique' => 0, 'returning' => 0, 'tickets' => 0], 'evolution' => []];
    $db_path = clp_statistici_db_path();
    if (!file_exists($db_path)) {
        return $empty;
    }

    $rows = [];
    try {
        $db = new SQLite3($db_path);
        $db->exec('PRAGMA journal_mode = WAL;');
        $tr = $db->query(
            "SELECT t.participant_name, t.course_id, c.name AS course_name, c.date AS course_date,
                    strftime('%Y-%m', c.date) AS m
             FROM tickets t
             JOIN courses c ON c.id = t.course_id"
        );
        while ($row = $tr->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        $db->close();
    } catch (Exception $e) {
        return $empty;
    }

    $keyMap = clp_merge_participant_keys(array_values(array_unique(
        array_map(fn($r) => clp_participant_name_key((string)$r['participant_name']), $rows)
    )));

    $groups = [];
    $evoMonths = [];
    foreach ($rows as $r) {
        $key = $keyMap[clp_participant_name_key((string)$r['participant_name'])];
        $g = &$groups[$key];
        $g['names'][(string)$r['participant_name']] = ($g['names'][(string)$r['participant_name']] ?? 0) + 1;
        $g['course_ids'][(int)$r['course_id']] = true;
        $g['total_tickets'] = ($g['total_tickets'] ?? 0) + 1;
        $g['courses'][$r['course_name'] . ' (' . $r['course_date'] . ')'] = true;
        unset($g);

        $m = (string)$r['m'];
        $evoMonths[$m]['keys'][$key] = true;
        $evoMonths[$m]['bilete'] = ($evoMonths[$m]['bilete'] ?? 0) + 1;
    }

    $participants = [];
    foreach ($groups as $g) {
        arsort($g['names']);
        $participants[] = [
            'participant_name' => (string)array_key_first($g['names']),
            'num_courses' => count($g['course_ids']),
            'total_tickets' => (int)$g['total_tickets'],
            'courses' => array_keys($g['courses']),
        ];
    }
    usort($participants, fn($a, $b) =>
        [$b['num_courses'], $b['total_tickets'], $a['participant_name']]
        <=> [$a['num_courses'], $a['total_tickets'], $b['participant_name']]
    );

    krsort($evoMonths);
    $evolution = [];
    foreach (array_slice($evoMonths, 0, 12, true) as $m => $e) {
        $evolution[] = ['m' => (string)$m, 'unici' => count($e['keys']), 'bilete' => (int)$e['bilete']];
    }

    return [
        'participants' => $participants,
        'stats' => [
            'unique' => count($participants),
            'returning' => count(array_filter($participants, fn($p) => (int)($p['num_courses'] ?? 0) > 1)),
            'tickets' => (int)array_sum(array_column($participants, 'total_tickets')),
        ],
        'evolution' => $evolution,
    ];
}

/**
 * DITL taxable base: face value of sold tickets (pret × vandute per type).
 * The authority taxes the viza'd ticket value — refunds, promo discounts and
 * platform commission do NOT reduce the base. Falls back to the stored
 * total_bilete when no type breakdown exists.
 *
 * @param list<array<string, mixed>> $types
 */
function clp_ditl_base(array $types, float $fallback): float
{
    if (empty($types)) return $fallback;
    $base = 0.0;
    foreach ($types as $t) {
        $base += (float)($t['pret'] ?? 0) * (int)($t['vandute'] ?? 0);
    }
    return $base;
}

/** @param list<array<string, mixed>> $types */
function clp_vandute_for_tarif(array $types, float $tarif, ?int $nrUnitati = null): ?int
{
    $key = (string)(float)$tarif;
    $cands = [];
    foreach ($types as $type) {
        if ((string)(float)($type['pret'] ?? 0) === $key) {
            $cands[] = $type;
        }
    }
    if (!$cands) return null;
    if (count($cands) === 1) {
        return isset($cands[0]['vandute']) ? (int)$cands[0]['vandute'] : null;
    }
    // mai multe tipuri la acelasi pret: dezambiguizeaza dupa cantitate (nr. bilete = vandute)
    if ($nrUnitati !== null) {
        foreach ($cands as $c) {
            if ((int)($c['vandute'] ?? -1) === $nrUnitati) return (int)$c['vandute'];
        }
    }
    return isset($cands[0]['vandute']) ? (int)$cands[0]['vandute'] : null;
}
