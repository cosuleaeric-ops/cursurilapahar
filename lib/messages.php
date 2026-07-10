<?php

require_once __DIR__ . '/speakers.php';

function clp_message_meta_file(): string {
    return dirname(__DIR__) . '/data/message_meta.json';
}

function clp_messages_log_file(): string {
    return dirname(__DIR__) . '/data/messages.log';
}

function clp_messages_last_read_file(): string {
    return dirname(__DIR__) . '/data/messages_last_read.txt';
}

function msg_id_from_block(string $block): string {
    return substr(md5($block), 0, 12);
}

function load_msg_meta(): array {
    $file = clp_message_meta_file();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_msg_meta(array $meta): void {
    $file = clp_message_meta_file();
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function clp_message_categories(): array {
    return [
        'sustine'     => ['label' => 'Speakeri',     'icon' => '🎤'],
        'contact'     => ['label' => 'Contact',      'icon' => '💬'],
        'gazduieste'  => ['label' => 'Locații',      'icon' => '📍'],
        'parteneriat' => ['label' => 'Parteneriate', 'icon' => '🤝'],
    ];
}

function clp_sustine_field_labels(): array {
    return [
        'Name'                   => 'Nume și prenume',
        'Email'                  => 'Email',
        'Phone'                  => 'Număr de telefon',
        'Social'                 => 'Link profil social media',
        'Course name'            => 'Nume curs susținut',
        'Course desc'            => 'Descrie cursul susținut',
        'Motivation'             => 'De ce îți dorești să susții acest curs?',
        'Experience'             => 'Ce experiențe sau competențe te califică?',
        'Previous presentations' => 'Ai mai susținut astfel de prezentări?',
        'City'                   => 'În ce oraș ai vrea să susții cursul?',
        'Other'                  => 'Mai e ceva ce vrei să ne transmiți?',
    ];
}

/** @return array<string, string> */
function clp_parse_message_block_fields(string $block): array {
    $body  = trim(preg_replace('/^===.*===\n?/m', '', $block));
    $lines = array_values(array_filter(array_map('trim', explode("\n", $body))));
    $fields = [];
    $last_key = null;
    foreach ($lines as $l) {
        if ($l === '---') break;
        $sep = strpos($l, ':');
        if ($sep !== false && $sep <= 40) {
            $key = trim(substr($l, 0, $sep));
            $fields[$key] = trim(substr($l, $sep + 1));
            $last_key = $key;
        } elseif ($last_key !== null && $l !== '') {
            $fields[$last_key] .= ' ' . $l;
        }
    }
    return $fields;
}

/** @return list<string> */
function clp_read_message_log_blocks(): array {
    $log_file = clp_messages_log_file();
    if (!file_exists($log_file) || !filesize($log_file)) return [];
    $raw = file_get_contents($log_file);
    $blocks = preg_split('/(?=^===)/m', $raw);
    $blocks = array_values(array_filter(array_map('trim', $blocks)));
    return array_reverse($blocks);
}

/**
 * @return array{grouped: array<string, list<array>>, tab_counts: array<string, int>}
 */
function clp_load_grouped_messages(): array {
    $categories = clp_message_categories();
    $grouped = array_fill_keys(array_keys($categories), []);
    $meta = load_msg_meta();
    $speakers = load_speakers();

    foreach (clp_read_message_log_blocks() as $block) {
        preg_match('/^===\s*(.*?)\s*\|\s*(\S+)\s*===/m', $block, $m);
        $type = trim($m[2] ?? 'contact');
        if (!isset($grouped[$type])) $type = 'contact';
        $mid = msg_id_from_block($block);
        $fields = clp_parse_message_block_fields($block);
        // Speakerii deja gestionați în /speakeri nu mai apar în triajul Mesaje
        if ($type === 'sustine') {
            $em = $fields['Email'] ?? $fields['email'] ?? '';
            $ph = $fields['Phone'] ?? $fields['Telefon'] ?? $fields['telefon'] ?? '';
            if (clp_find_speaker_index_by_contact($speakers, $em, $ph) >= 0) continue;
        }
        $grouped[$type][] = [
            'date'   => trim($m[1] ?? ''),
            'fields' => $fields,
            'id'     => $mid,
            'meta'   => $meta[$mid] ?? [],
        ];
    }

    $tab_counts = [];
    foreach ($grouped as $k => $list) {
        if ($k === 'sustine') {
            $tab_counts[$k] = count(array_filter($list, fn($m) => empty($m['meta']['evaluation'])));
        } else {
            $tab_counts[$k] = count(array_filter($list, fn($m) => empty($m['meta']['read'])));
        }
    }

    return ['grouped' => $grouped, 'tab_counts' => $tab_counts];
}

/** Mesaje fără răspuns: Speakeri neevaluați + Contact/Locații/Parteneriate necitite. */
function clp_pending_message_count(): int {
    $data = clp_load_grouped_messages();
    return array_sum($data['tab_counts']);
}

/** @return list<array{id: string, name: string, email: string, phone: string}> */
function clp_contacted_message_leads(): array {
    $meta = load_msg_meta();
    $leads = [];
    foreach (clp_read_message_log_blocks() as $block) {
        preg_match('/^===\s*(.*?)\s*\|\s*(\S+)\s*===/m', $block, $m);
        $type = trim($m[2] ?? 'contact');
        if ($type !== 'sustine') continue;

        $mid = msg_id_from_block($block);
        if (empty($meta[$mid]['contacted'])) continue;
        $fields = clp_parse_message_block_fields($block);
        $leads[] = [
            'id'    => $mid,
            'name'  => $fields['Nume'] ?? $fields['Name'] ?? $fields['Organizație'] ?? $fields['organizatie'] ?? '—',
            'email' => $fields['Email'] ?? $fields['email'] ?? '',
            'phone' => $fields['Phone'] ?? $fields['Telefon'] ?? $fields['telefon'] ?? '',
        ];
    }
    return $leads;
}

/**
 * Submisiile din formularul „Prezintă un curs”, mapate pe speakerii existenți (după email/telefon).
 * Blocurile sunt newest-first, deci prima potrivire = cea mai recentă submisie.
 * @return array<string, array{date: string, fields: array<string, string>}> speaker_id => submisie
 */
function clp_speaker_form_submissions_by_speaker(array $speakers): array {
    $map = [];
    foreach (clp_read_message_log_blocks() as $block) {
        preg_match('/^===\s*(.*?)\s*\|\s*(\S+)\s*===/m', $block, $m);
        if (trim($m[2] ?? '') !== 'sustine') continue;
        $fields = clp_parse_message_block_fields($block);
        $em = $fields['Email'] ?? $fields['email'] ?? '';
        $ph = $fields['Phone'] ?? $fields['Telefon'] ?? $fields['telefon'] ?? '';
        $idx = clp_find_speaker_index_by_contact($speakers, $em, $ph);
        if ($idx < 0) continue;
        $sid = $speakers[$idx]['id'] ?? '';
        if ($sid === '' || isset($map[$sid])) continue;
        $map[$sid] = ['date' => trim($m[1] ?? ''), 'fields' => $fields];
    }
    return $map;
}

function clp_mark_messages_read(): void {
    file_put_contents(clp_messages_last_read_file(), date('Y-m-d H:i:s'), LOCK_EX);
}

function clp_unread_message_count(): int {
    $log_file = clp_messages_log_file();
    if (!file_exists($log_file)) return 0;
    $last_read = file_exists(clp_messages_last_read_file())
        ? trim(file_get_contents(clp_messages_last_read_file()))
        : '1970-01-01 00:00:00';
    $raw_log = file_get_contents($log_file);
    preg_match_all('/^=== (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \|/m', $raw_log, $ts_matches);
    $count = 0;
    foreach ($ts_matches[1] as $ts) {
        if ($ts > $last_read) $count++;
    }
    return $count;
}

function clp_render_message_card(string $key, int $i, array $msg, bool $is_owner, callable $h): void {
    $sustine_questions = clp_sustine_field_labels();
    $name = $msg['fields']['Nume'] ?? $msg['fields']['nume'] ?? $msg['fields']['Name']
         ?? $msg['fields']['Organizație'] ?? $msg['fields']['organizatie'] ?? '—';
    $uid  = $key . '_' . $i;
    $is_read = !empty($msg['meta']['read']);
    $eval    = $msg['meta']['evaluation'] ?? '';
    $comments = $msg['meta']['comments'] ?? [];
    $is_contacted = !empty($msg['meta']['contacted']);
    $card_classes = ['msg-card'];
    if ($key !== 'sustine' && $is_read) $card_classes[] = 'is-read';
    if ($key === 'sustine' && $eval)    $card_classes[] = 'eval-' . $eval;
    if ($is_contacted)                  $card_classes[] = 'is-contacted';

    $name_extra = '';
    if ($key === 'sustine' && !empty($msg['fields']['Course name'])) {
        $cn = trim($msg['fields']['Course name']);
        $cn_first = preg_split('/(?<=[.!?])\s+|\s*[\r\n]+\s*/u', $cn, 2)[0];
        $name_extra = ' — ' . $cn_first;
    }
    ?>
    <div class="<?= implode(' ', $card_classes) ?>" data-msg-id="<?= $h($msg['id']) ?>" onclick="toggleMsg('<?= $uid ?>')">
        <div class="msg-card-head">
            <span class="msg-card-name"><?= $h($name) ?><?php if ($name_extra): ?><span class="msg-card-course"><?= $h($name_extra) ?></span><?php endif; ?></span>
            <span class="msg-card-date"><?= $h($msg['date']) ?></span>
        </div>
        <div class="msg-detail" id="msg-<?= $uid ?>">
            <?php foreach ($msg['fields'] as $lbl => $val):
                $lbl_lc = strtolower($lbl);
                if ($lbl_lc === 'trimis de pe' || $lbl_lc === 'data') continue;
                $tooltip = ($key === 'sustine' && isset($sustine_questions[$lbl])) ? $sustine_questions[$lbl] : '';
            ?>
            <div class="msg-detail-row">
                <span class="msg-detail-lbl"><?= $h($lbl) ?><?php if ($tooltip): ?><span class="msg-info" data-tooltip="<?= $h($tooltip) ?>">i</span><?php endif; ?></span>
                <span class="msg-detail-val"><?= $h($val) ?><?php if ($val): ?><button type="button" class="msg-copy-btn" onclick="event.stopPropagation();copyField(this,'<?= addslashes($val) ?>')" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button><?php endif; ?></span>
            </div>
            <?php endforeach; ?>

            <div class="msg-detail-actions">
                <?php if ($key === 'sustine'): ?>
                    <button type="button" class="msg-eval-btn <?= $eval === 'nope' ? 'is-active' : '' ?>" data-eval="nope" onclick="event.stopPropagation();evalMsg(this,'nope')">Nope</button>
                    <button type="button" class="msg-eval-btn <?= $eval === 'meh' ? 'is-active' : '' ?>"  data-eval="meh"  onclick="event.stopPropagation();evalMsg(this,'meh')">Meh</button>
                    <button type="button" class="msg-eval-btn <?= $eval === 'top' ? 'is-active' : '' ?>"  data-eval="top"  onclick="event.stopPropagation();evalMsg(this,'top')">Top</button>
                    <button type="button" class="msg-comment-btn" onclick="event.stopPropagation();toggleCommentForm(this)">💬 Comentariu</button>
                    <button type="button" class="msg-contact-btn <?= $is_contacted ? 'is-active' : '' ?>" onclick="event.stopPropagation();markContacted(this)"><?= $is_contacted ? '✓ Contactat' : 'Contactat' ?></button>
                <?php else: ?>
                    <button type="button" class="msg-read-btn <?= $is_read ? 'is-active' : '' ?>" onclick="event.stopPropagation();markRead(this)">
                        <?= $is_read ? '✓ Citit' : 'Citit' ?>
                    </button>
                    <button type="button" class="msg-delete-btn" onclick="event.stopPropagation();deleteMsg(this,'<?= $h($key) ?>',<?= $i ?>)">Șterge</button>
                <?php endif; ?>
            </div>

            <?php if ($key === 'sustine'): ?>
            <div class="msg-comments">
                <div class="msg-comments-title">Comentarii</div>
                <div class="msg-comments-list">
                    <?php foreach ($comments as $cidx => $c): ?>
                    <div class="msg-comment-item" data-comment-idx="<?= $cidx ?>">
                        <span class="msg-comment-when"><?= $h($c['at'] ?? '') ?><?php if (!empty($c['by'])): ?> · <?= $h($c['by']) ?><?php endif; ?></span>
                        <?= $h($c['text'] ?? '') ?>
                        <?php if ($is_owner): ?>
                        <button type="button" class="msg-comment-del" onclick="event.stopPropagation();deleteComment(this)" title="Șterge comentariu">×</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="msg-comment-form" style="display:none">
                    <textarea placeholder="Scrie un comentariu..." rows="2" onclick="event.stopPropagation()"></textarea>
                    <button type="button" onclick="event.stopPropagation();saveComment(this)">Adaugă</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
