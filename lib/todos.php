<?php

define('TODOS_FILE', dirname(__DIR__) . '/data/todos.json');

function clp_load_todos(): array {
    if (!file_exists(TODOS_FILE)) return [];
    $data = json_decode(file_get_contents(TODOS_FILE), true);
    return is_array($data) ? $data : [];
}

function clp_save_todos(array $todos): bool {
    return file_put_contents(TODOS_FILE, json_encode($todos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function clp_todos_for_user(string $username): array {
    return array_values(array_filter(clp_load_todos(), fn($t) => ($t['assigned_to'] ?? '') === $username));
}

function clp_add_todo(string $title, string $assigned_to, string $created_by): array {
    $todos = clp_load_todos();
    $todo = [
        'id'          => bin2hex(random_bytes(8)),
        'title'       => $title,
        'assigned_to' => $assigned_to,
        'created_by'  => $created_by,
        'completed'   => false,
        'created_at'  => (new DateTimeImmutable())->format('Y-m-d\TH:i:s'),
    ];
    $todos[] = $todo;
    clp_save_todos($todos);
    return $todo;
}

function clp_toggle_todo(string $id): bool {
    $todos = clp_load_todos();
    foreach ($todos as &$t) {
        if ($t['id'] === $id) {
            $t['completed'] = !$t['completed'];
            if (!empty($t['completed'])) {
                $t['completed_at'] = (new DateTimeImmutable('now', new DateTimeZone('Europe/Bucharest')))->format('Y-m-d\TH:i:s');
            } else {
                unset($t['completed_at']);
            }
            return clp_save_todos($todos);
        }
    }
    return false;
}

function clp_delete_todo(string $id): bool {
    $todos = clp_load_todos();
    $filtered = array_values(array_filter($todos, fn($t) => $t['id'] !== $id));
    if (count($filtered) === count($todos)) return false;
    return clp_save_todos($filtered);
}

function clp_todos_pending_count(string $username): int {
    return count(array_filter(clp_todos_for_user($username), fn($t) => !$t['completed']));
}
