<?php
declare(strict_types=1);

/** @return array{sections: list<array{id: string, title: string, is_default?: bool, items: list<array{id: string, text: string, link: string, done: bool}>}>} */
function clp_marketing_load(): array
{
    $default = [
        'sections' => [
            ['id' => 'video', 'title' => 'Video', 'items' => [], 'is_default' => true],
        ],
    ];
    $file = clp_marketing_file();
    if (!file_exists($file)) {
        return $default;
    }
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data) || empty($data['sections']) || !is_array($data['sections'])) {
        return $default;
    }

    [$data, $changed] = clp_marketing_normalize($data);
    if ($changed) {
        clp_marketing_save($data);
    }
    return $data;
}

/** @param array{sections: list<array<string, mixed>>} $data */
function clp_marketing_normalize(array $data): array
{
    $changed = false;

    // Migrare veche: un singur bloc „postari” devine Video
    if (count($data['sections']) === 1 && ($data['sections'][0]['id'] ?? '') === 'postari') {
        $data['sections'][0]['id'] = 'video';
        $data['sections'][0]['title'] = 'Video';
        $data['sections'][0]['is_default'] = true;
        $changed = true;
    }

    $usedIds = [];
    $hasDefault = false;
    foreach ($data['sections'] as &$section) {
        $id = trim((string)($section['id'] ?? ''));
        if ($id === '') {
            $id = clp_marketing_new_id();
            $section['id'] = $id;
            $changed = true;
        }

        while (isset($usedIds[$id])) {
            $id = $id . '-' . substr(clp_marketing_new_id(), 0, 4);
            $section['id'] = $id;
            $changed = true;
        }
        $usedIds[$id] = true;

        if (!empty($section['is_default'])) {
            if ($hasDefault) {
                $section['is_default'] = false;
                $changed = true;
            } else {
                $hasDefault = true;
            }
        }
    }
    unset($section);

    if (!$hasDefault) {
        foreach ($data['sections'] as &$section) {
            if (($section['id'] ?? '') === 'video') {
                $section['is_default'] = true;
                $hasDefault = true;
                $changed = true;
                break;
            }
        }
        unset($section);
        if (!$hasDefault && !empty($data['sections'])) {
            $data['sections'][0]['is_default'] = true;
            $changed = true;
        }
    }

    return [$data, $changed];
}

function clp_marketing_save(array $data): void
{
    $file = clp_marketing_file();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function clp_marketing_file(): string
{
    return dirname(__DIR__) . '/data/marketing_posts.json';
}

function clp_marketing_new_id(): string
{
    return bin2hex(random_bytes(8));
}

/** @param array{sections: list<array<string, mixed>>} $data */
function clp_marketing_unique_section_id(array $data, string $baseId): string
{
    $existing = array_column($data['sections'], 'id');
    $id = $baseId;
    while (in_array($id, $existing, true)) {
        $id = $baseId . '-' . substr(clp_marketing_new_id(), 0, 4);
    }
    return $id;
}

/** @param array{sections: list<array<string, mixed>>} $data */
function clp_marketing_delete_section(array $data, string $sectionId): array
{
    if ($sectionId === '' || count($data['sections']) <= 1) {
        return $data;
    }
    $removed = false;
    $sections = [];
    foreach ($data['sections'] as $section) {
        if (!$removed && ($section['id'] ?? '') === $sectionId) {
            $removed = true;
            continue;
        }
        $sections[] = $section;
    }
    if ($removed && !empty($sections)) {
        $data['sections'] = array_values($sections);
    }
    return $data;
}

/** @return array{id: string, title: string, items: list<array{id: string, text: string, link: string, done: bool}>}|null */
function clp_marketing_find_section(array $data, string $sectionId): ?array
{
    foreach ($data['sections'] as $section) {
        if (($section['id'] ?? '') === $sectionId) {
            return $section;
        }
    }
    return null;
}

function clp_marketing_slug(string $title): string
{
    $s = strtolower(trim($title));
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? $s : clp_marketing_new_id();
}
