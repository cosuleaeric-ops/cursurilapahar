<?php

require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/speakers.php';
require_once __DIR__ . '/locations.php';
require_once __DIR__ . '/course_clicks.php';

function clp_allowed_course_times(): array {
    return ['17:00', '17:30', '18:00', '18:30'];
}

/**
 * @return array<string, mixed>
 */
function clp_courses_admin_context(array $courses, string $edit_id = ''): array {
    $edit_course = null;
    if ($edit_id !== '') {
        foreach ($courses as $c) {
            if (($c['id'] ?? '') === $edit_id) {
                $edit_course = $c;
                break;
            }
        }
    }

    $today = date('Y-m-d');
    $courses_upcoming = [];
    $courses_past = [];
    foreach ($courses as $c) {
        if (!empty($c['date_raw']) && $c['date_raw'] < $today) {
            $courses_past[] = $c;
        } else {
            $courses_upcoming[] = $c;
        }
    }
    usort($courses_past, fn($a, $b) => strcmp($b['date_raw'] ?? '', $a['date_raw'] ?? ''));

    return [
        'course_speakers'   => load_speakers_for_picker(),
        'course_locations'  => load_locations_for_picker(),
        'course_times'      => clp_allowed_course_times(),
        'course_form_error' => trim($_GET['course_error'] ?? ''),
        'edit_course'       => $edit_course,
        'courses_upcoming'  => $courses_upcoming,
        'courses_past'      => $courses_past,
    ];
}

/** @param list<array<string, mixed>> $list */
function clp_render_admin_courses_table(array $list): void {
    $clicks = clp_load_course_clicks();
    ?>
    <table class="wp-table">
        <thead>
            <tr>
                <th style="width:72px">Imagine</th>
                <th>Titlu</th>
                <th>Dată</th>
                <th style="width:100px">Status</th>
                <th style="width:56px;white-space:nowrap">clicks</th>
                <th style="width:240px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $c):
                $cid = $c['id'] ?? '';
                $has_disc = !empty($c['discount_percent']) && !empty($c['discount_ends_at']);
                $disc_local = '';
                $disc_active_now = false;
                if ($has_disc) {
                    try {
                        $dt = new DateTime($c['discount_ends_at']);
                        $dt->setTimezone(new DateTimeZone('Europe/Bucharest'));
                        $disc_local = $dt->format('Y-m-d\TH:i');
                        $disc_active_now = $dt->getTimestamp() > time();
                    } catch (Exception $e) {}
                }
            ?>
            <tr>
                <td>
                    <?php if (!empty($c['image_url'])): ?>
                    <img class="course-thumb" src="<?= h($c['image_url']) ?>" alt="">
                    <?php else: ?>
                    <div class="course-thumb-empty"></div>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600">
                    <?= h($c['title'] ?? '') ?>
                    <?php $sp_name = clp_course_speaker_name($c); if ($sp_name !== ''): ?>
                    <div style="font-size:12px;font-weight:400;color:var(--text-muted);margin-top:2px"><?= h($sp_name) ?></div>
                    <?php endif; ?>
                    <?php if ($has_disc): ?>
                        <span class="discount-tag <?= $disc_active_now ? 'discount-tag--active' : 'discount-tag--expired' ?>">
                            −<?= (int)$c['discount_percent'] ?>%<?= $disc_active_now ? '' : ' (expirată)' ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted)"><?= h($c['date_display'] ?? $c['date_raw'] ?? '') ?></td>
                <td>
                    <?php if (empty($c['livetickets_url'])): ?>
                    <span class="btn btn-sm status-inactive" style="cursor:default;opacity:.85" title="Adaugă link LiveTickets ca să apară pe site">Draft</span>
                    <?php else: ?>
                    <form method="post" action="/admin/?tab=cursuri" style="display:inline">
                        <input type="hidden" name="action" value="toggle_course">
                        <input type="hidden" name="id" value="<?= h($cid) ?>">
                        <button type="submit" class="btn btn-sm <?= !empty($c['active']) ? 'status-active' : 'status-inactive' ?>">
                            <?= !empty($c['active']) ? 'Activ' : 'Inactiv' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;font-variant-numeric:tabular-nums;<?= empty($clicks[$cid]) ? 'color:var(--text-muted)' : 'font-weight:600' ?>">
                    <?= (int) ($clicks[$cid] ?? 0) ?>
                </td>
                <td>
                    <div class="row-actions">
                        <a href="/admin/?tab=cursuri&edit=<?= h($cid) ?>" class="btn btn-sm btn-secondary">Editează</a>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleDiscountRow('<?= h($cid) ?>')">Reducere ▾</button>
                        <form method="post" action="/admin/?tab=cursuri" onsubmit="return confirm('Ștergi cursul?')" style="display:inline">
                            <input type="hidden" name="action" value="delete_course">
                            <input type="hidden" name="id" value="<?= h($cid) ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                        </form>
                        <?php if (!empty($c['livetickets_url'])): ?>
                        <a href="<?= h($c['livetickets_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">LT ↗</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr id="discount-row-<?= h($cid) ?>" class="discount-edit-row" style="display:none">
                <td colspan="6">
                    <form method="post" action="/admin/?tab=cursuri" class="discount-form">
                        <input type="hidden" name="action" value="save_discount">
                        <input type="hidden" name="id" value="<?= h($cid) ?>">
                        <label>Reducere (%):
                            <input type="number" name="discount_percent" min="1" max="100" value="<?= $has_disc ? (int)$c['discount_percent'] : '' ?>" style="width:90px">
                        </label>
                        <label>Expiră la (ora București):
                            <input type="datetime-local" name="discount_ends_at" value="<?= h($disc_local) ?>">
                        </label>
                        <button type="submit" class="btn btn-sm btn-primary">Salvează reducerea</button>
                        <?php if ($has_disc): ?>
                            <button type="submit" name="clear" value="1" class="btn btn-sm btn-danger" onclick="return confirm('Ștergi reducerea?')">Șterge reducerea</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * @return array<string, mixed>
 */
function clp_courses_stats_js_config(array $courses, int $year, int $month, string $ctab): array {
    return [
        'year'          => $year,
        'month'         => $month,
        'calYear'       => (int)date('Y'),
        'calMonth'      => (int)date('n'),
        'calCourses'    => array_map(fn($c) => ['date' => $c['date_raw'] ?? '', 'title' => $c['title'] ?? ''], $courses),
        'initCalendar'  => $ctab === 'calendar',
        'scrollToStats' => isset($_GET['saved']),
        'activeTab'     => $ctab,
    ];
}
