<?php
declare(strict_types=1);

require_once __DIR__ . '/courses.php';

/** @return array{participants: list<array<string, mixed>>, stats: array{unique: int, returning: int, tickets: int}} */
function clp_fetch_participants(): array
{
    $empty = ['participants' => [], 'stats' => ['unique' => 0, 'returning' => 0, 'tickets' => 0]];
    $db_path = clp_statistici_db_path();
    if (!file_exists($db_path)) {
        return $empty;
    }

    $participants = [];
    try {
        $db = new SQLite3($db_path);
        $db->exec('PRAGMA journal_mode = WAL;');
        $pr = $db->query(
            'SELECT t.participant_name, COUNT(DISTINCT t.course_id) AS num_courses, COUNT(*) AS total_tickets,
                    GROUP_CONCAT(c.name || \' (\' || c.date || \')\', \'|\') AS course_list
             FROM tickets t
             JOIN courses c ON c.id = t.course_id
             GROUP BY LOWER(TRIM(t.participant_name))
             ORDER BY num_courses DESC, total_tickets DESC, t.participant_name ASC'
        );
        while ($row = $pr->fetchArray(SQLITE3_ASSOC)) {
            $row['courses'] = array_values(array_unique(array_filter(explode('|', $row['course_list'] ?? ''))));
            unset($row['course_list']);
            $participants[] = $row;
        }
        $db->close();
    } catch (Exception $e) {
        return $empty;
    }

    return [
        'participants' => $participants,
        'stats' => [
            'unique' => count($participants),
            'returning' => count(array_filter($participants, fn($p) => (int)($p['num_courses'] ?? 0) > 1)),
            'tickets' => (int)array_sum(array_column($participants, 'total_tickets')),
        ],
    ];
}

/** @param list<array<string, mixed>> $types */
function clp_vandute_for_tarif(array $types, float $tarif): ?int
{
    $key = (string)(float)$tarif;
    foreach ($types as $type) {
        if ((string)(float)($type['pret'] ?? 0) === $key) {
            return isset($type['vandute']) ? (int)$type['vandute'] : null;
        }
    }
    return null;
}
