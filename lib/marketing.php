<?php
declare(strict_types=1);

/** @return array{sections: list<array{id: string, title: string, items: list<array{id: string, text: string, link: string, done: bool}>}>} */
function clp_marketing_load(): array
{
    $default = [
        'sections' => [
            ['id' => 'video', 'title' => 'Video', 'items' => []],
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
    $changed = false;
    foreach ($data['sections'] as &$section) {
        if (($section['id'] ?? '') === 'postari') {
            $section['id'] = 'video';
            $section['title'] = 'Video';
            $changed = true;
        }
    }
    unset($section);
    if ($changed) {
        clp_marketing_save($data);
    }
    return $data;
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
