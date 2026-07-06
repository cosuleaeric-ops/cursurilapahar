<?php

function clp_speakers_file(): string {
    return dirname(__DIR__) . '/data/speakers.json';
}

function clp_normalize_speaker_email(?string $email): string {
    return strtolower(trim($email ?? ''));
}

function clp_normalize_speaker_phone(?string $phone): string {
    $digits = preg_replace('/\D+/', '', trim($phone ?? ''));
    if ($digits === '') return '';
    if (str_starts_with($digits, '40') && strlen($digits) >= 11) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
        $digits = substr($digits, 1);
    }
    return $digits;
}

function clp_speaker_dedupe_key(array $sp): string {
    $email = clp_normalize_speaker_email($sp['email'] ?? '');
    if ($email !== '') return 'e:' . $email;
    $phone = clp_normalize_speaker_phone($sp['phone'] ?? '');
    if ($phone !== '') return 'p:' . $phone;
    return 'n:' . mb_strtolower(trim($sp['name'] ?? ''));
}

function clp_speaker_status_rank(string $status): int {
    return clp_speaker_status_order()[$status] ?? 2;
}

function clp_pick_speaker_status(string $a, string $b): string {
    return clp_speaker_status_rank($a) <= clp_speaker_status_rank($b) ? $a : $b;
}

function clp_merge_speaker_entries(array $keep, array $other): array {
    $courses = array_values(array_unique(array_filter(array_merge(
        is_array($keep['courses'] ?? null) ? $keep['courses'] : [],
        is_array($other['courses'] ?? null) ? $other['courses'] : [],
    ))));
    $keep['courses'] = $courses;
    $keep['status'] = clp_pick_speaker_status($keep['status'] ?? 'MID', $other['status'] ?? 'MID');

    foreach (['name', 'email', 'phone', 'notes'] as $field) {
        if (trim($keep[$field] ?? '') === '' && trim($other[$field] ?? '') !== '') {
            $keep[$field] = trim($other[$field]);
        }
    }

    $meet_keep = is_array($keep['meet'] ?? null) ? $keep['meet'] : [];
    $meet_other = is_array($other['meet'] ?? null) ? $other['meet'] : [];
    foreach ($meet_other as $k => $v) {
        if (trim($v) !== '' && trim($meet_keep[$k] ?? '') === '') {
            $meet_keep[$k] = trim($v);
        }
    }
    if ($meet_keep) $keep['meet'] = $meet_keep;

    return $keep;
}

function clp_deduplicate_speakers(array $items): array {
    $by_key = [];
    foreach ($items as $sp) {
        if (trim($sp['name'] ?? '') === '') continue;
        $key = clp_speaker_dedupe_key($sp);
        if (!isset($by_key[$key])) {
            $by_key[$key] = $sp;
            continue;
        }
        $by_key[$key] = clp_merge_speaker_entries($by_key[$key], $sp);
    }
    return array_values($by_key);
}

function clp_find_speaker_index_by_contact(array $items, string $email, string $phone): int {
    $email_n = clp_normalize_speaker_email($email);
    $phone_n = clp_normalize_speaker_phone($phone);
    foreach ($items as $i => $sp) {
        if ($email_n !== '' && clp_normalize_speaker_email($sp['email'] ?? '') === $email_n) return $i;
        if ($phone_n !== '' && clp_normalize_speaker_phone($sp['phone'] ?? '') === $phone_n) return $i;
    }
    return -1;
}

function load_speakers(): array {
    $file = clp_speakers_file();
    if (!file_exists($file)) return [];
    $items = json_decode(file_get_contents($file), true) ?: [];
    $deduped = clp_deduplicate_speakers($items);
    if (count($deduped) !== count($items)) {
        save_speakers_raw($deduped);
    }
    return $deduped;
}

function save_speakers_raw(array $items): void {
    $file = clp_speakers_file();
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function save_speakers(array $items): void {
    save_speakers_raw(clp_deduplicate_speakers($items));
}

function clp_speaker_status_order(): array {
    return ['CONTACTAT' => 0, 'URMEAZĂ' => 1, 'RECURENT' => 2, 'MID' => 3, 'NOPE' => 4];
}

function clp_speaker_status_colors(): array {
    return ['CONTACTAT' => '#2271b1', 'URMEAZĂ' => '#7c3aed', 'RECURENT' => '#16a34a', 'MID' => '#d97706', 'NOPE' => '#dc2626'];
}

function clp_sort_speakers(array $speakers): array {
    $order = clp_speaker_status_order();
    usort($speakers, function ($a, $b) use ($order) {
        $cmp = ($order[$a['status'] ?? 'MID'] ?? 2) <=> ($order[$b['status'] ?? 'MID'] ?? 2);
        return $cmp !== 0 ? $cmp : strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
    return $speakers;
}

function clp_find_speaker_by_id(string $speaker_id): ?array {
    foreach (load_speakers() as $sp) {
        if (($sp['id'] ?? '') === $speaker_id) {
            return $sp;
        }
    }
    return null;
}

/** Aceeași listă și ordine ca în tab-ul Speakeri (din data/speakers.json) */
function load_speakers_for_picker(): array {
    $speakers = clp_sort_speakers(load_speakers());
    return array_values(array_filter($speakers, fn($s) => trim($s['id'] ?? '') !== '' && trim($s['name'] ?? '') !== ''));
}

/**
 * @return array{speakers: list<array>, edit: ?array}
 */
function clp_speakers_admin_context(string $edit_id = ''): array {
    $speakers = clp_sort_speakers(load_speakers());
    $edit = null;
    if ($edit_id !== '') {
        foreach ($speakers as $sp) {
            if (($sp['id'] ?? '') === $edit_id) {
                $edit = $sp;
                break;
            }
        }
    }
    return ['speakers' => $speakers, 'edit' => $edit];
}

/** Mesaje „Contactat” fără cei deja în speakers.json (evită rânduri duble) */
function clp_contacted_speaker_leads(): array {
    $speakers = load_speakers();
    $leads = [];
    foreach (clp_contacted_message_leads() as $c) {
        if (clp_find_speaker_index_by_contact($speakers, $c['email'] ?? '', $c['phone'] ?? '') >= 0) {
            continue;
        }
        $leads[] = $c;
    }
    return $leads;
}
