<?php
// Temporary script to add Galerie nav link to settings.json
// DELETE AFTER USE

$settings_file = __DIR__ . '/../data/settings.json';
$settings = file_exists($settings_file)
    ? (json_decode(file_get_contents($settings_file), true) ?: [])
    : [];

// Check if Galerie link already exists
$exists = false;
foreach (($settings['nav_links'] ?? []) as $nl) {
    if (($nl['url'] ?? '') === '/#galerie') { $exists = true; break; }
}

if ($exists) {
    echo json_encode(['success' => true, 'message' => 'Galerie link already exists']);
    exit;
}

// Add Galerie link after FAQ
$new_links = [];
foreach (($settings['nav_links'] ?? []) as $nl) {
    $new_links[] = $nl;
    if (($nl['url'] ?? '') === '/#faq') {
        $new_links[] = ['label' => 'Galerie', 'url' => '/#galerie'];
    }
}

// If FAQ not found, just append
if (!$exists && count($new_links) === count($settings['nav_links'] ?? [])) {
    $new_links[] = ['label' => 'Galerie', 'url' => '/#galerie'];
}

$settings['nav_links'] = $new_links;
file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo json_encode(['success' => true, 'message' => 'Galerie nav link added', 'nav_links' => $new_links]);
