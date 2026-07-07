    <h1 class="wp-page-title">Cursuri</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success">Curs salvat.</div>
    <?php endif; ?>

    <div class="card" id="course-form-card">
        <div class="card-title"><?= $edit_course ? 'Editează curs' : 'Adaugă curs' ?></div>
        <?php if ($course_form_error): ?>
        <p style="color:var(--danger);font-size:13px;margin:0 0 12px"><?= h($course_form_error) ?></p>
        <?php endif; ?>
        <?php if (empty($course_speakers)): ?>
        <p style="color:var(--text-muted);margin:0">Adaugă mai întâi speakeri în tab-ul <a href="/admin/?tab=speakeri">Speakeri</a>.</p>
        <?php else: ?>
        <form method="post" action="/admin/?tab=cursuri" id="courseForm" class="course-add-form" onsubmit="return validateCourseForm()">
            <input type="hidden" name="action" value="save_course">
            <input type="hidden" name="course_id" id="f_course_id" value="<?= h($edit_course['id'] ?? '') ?>">
            <input type="hidden" name="image_url" id="f_image_url" value="<?= h($edit_course['image_url'] ?? '') ?>">
            <div class="course-add-fields">
                <div class="form-group">
                    <label for="f_title">Nume curs</label>
                    <input type="text" name="title" id="f_title" required oninput="updateCoursePreview()" value="<?= h($edit_course['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="f_date_raw">Dată</label>
                    <input type="date" name="date_raw" id="f_date_raw" required onchange="updateCoursePreview()" value="<?= h($edit_course['date_raw'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="f_time">Oră</label>
                    <select name="time" id="f_time" required onchange="updateCoursePreview()">
                        <option value=""></option>
                        <?php foreach ($course_times as $t): ?>
                        <option value="<?= h($t) ?>" <?= ($edit_course['time'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group speaker-combobox">
                    <label for="f_speaker_input">Speaker</label>
                    <input type="text" id="f_speaker_input" autocomplete="off" required value="<?= h($edit_course ? clp_course_speaker_name($edit_course) : '') ?>">
                    <input type="hidden" name="speaker_id" id="f_speaker_id" value="<?= h($edit_course['speaker_id'] ?? '') ?>">
                    <div id="f_speaker_suggestions" class="speaker-suggestions" hidden></div>
                </div>
                <div class="form-group location-combobox">
                    <label for="f_location_input">Locație</label>
                    <input type="text" name="location" id="f_location_input" autocomplete="off" oninput="updateCoursePreview()" value="<?= h($edit_course['location'] ?? '') ?>">
                    <div id="f_location_suggestions" class="location-suggestions" hidden></div>
                </div>
                <div class="form-group">
                    <label for="f_lt_url">Link LiveTickets</label>
                    <input type="url" name="livetickets_url" id="f_lt_url" onblur="fetchLTImage()" value="<?= h($edit_course['livetickets_url'] ?? '') ?>">
                </div>
            </div>
            <script>
            window.CLP_SPEAKERS_PICKER = <?= json_encode(array_map(fn($s) => [
                'id' => $s['id'] ?? '',
                'name' => $s['name'] ?? '',
                'status' => $s['status'] ?? '',
            ], $course_speakers), JSON_UNESCAPED_UNICODE) ?>;
            window.CLP_LOCATIONS_PICKER = <?= json_encode(array_map(fn($l) => [
                'id' => $l['id'] ?? '',
                'name' => $l['name'] ?? '',
            ], $course_locations), JSON_UNESCAPED_UNICODE) ?>;
            </script>

            <div id="importMsg"></div>

            <div class="course-preview" id="coursePreview" style="display:none">
                <img id="prev_img" src="" alt="" style="display:none">
                <div class="course-preview-body">
                    <div class="course-preview-title" id="prev_title"></div>
                    <div class="course-preview-meta" id="prev_meta"></div>
                </div>
            </div>

            <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary btn-sm"><?= $edit_course ? 'Salvează' : 'Adaugă cursul' ?></button>
                <?php if ($edit_course): ?>
                <a href="/admin/?tab=cursuri&year=<?= (int)$clp_year ?>&month=<?= (int)$clp_month ?>&ctab=cursuri" class="btn btn-secondary btn-sm">Anulează</a>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- Courses table (upcoming) -->
    <div class="card">
        <div class="card-title">Cursuri (<?= count($courses_upcoming) ?>)</div>
        <?php if (empty($courses_upcoming)): ?>
        <p style="color:var(--text-muted)">Nu există cursuri adăugate încă.</p>
        <?php else: clp_render_admin_courses_table($courses_upcoming); endif; ?>
    </div>

    <!-- ── Statistici cursuri (full merge) ─────────────────────────────── -->
    <div class="card" id="clp-stats-card">

        <div class="clp-tabs" style="margin-bottom:16px">
            <button class="clp-tab-btn <?= $clp_ctab === 'cursuri' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'cursuri')">Cursuri</button>
            <button class="clp-tab-btn <?= $clp_ctab === 'calendar' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'calendar')">Calendar</button>
            <button class="clp-tab-btn <?= $clp_ctab === 'participanti' ? 'active' : '' ?>" onclick="clpSwitchTab(event,'participanti')">Participanți</button>
            <span class="clp-tabs-sep" aria-hidden="true"></span>
            <span id="clpMonthNav" style="display:contents">
                <button type="button" onclick="clpNav(-1)" class="clp-tab-btn" style="padding:7px 12px!important;line-height:1" aria-label="Luna anterioară">&#8592;</button>
                <span id="clpMonthLabel" class="clp-tab-btn active" style="cursor:default;min-width:96px;text-align:center;pointer-events:none"><?= ucfirst($clp_ro_months[$clp_month ?? 1]) . ' ' . ($clp_year ?? date('Y')) ?></span>
                <button type="button" onclick="clpNav(+1)" class="clp-tab-btn" style="padding:7px 12px!important;line-height:1" aria-label="Luna următoare">&#8594;</button>
            </span>
        </div>

        <!-- Tab: Cursuri (statistici — încărcat via API) -->
        <div class="clp-tab-panel <?= $clp_ctab === 'cursuri' ? 'active' : '' ?>" id="clp-panel-cursuri">
            <p style="color:var(--text-muted)">Se încarcă…</p>
        </div>

        <!-- Tab: Participanți (încărcat via API) -->
        <div class="clp-tab-panel <?= $clp_ctab === 'participanti' ? 'active' : '' ?>" id="clp-panel-participanti">
            <p style="color:var(--text-muted)">Se încarcă…</p>
        </div>

        <!-- Tab: Calendar -->
        <div class="clp-tab-panel <?= $clp_ctab === 'calendar' ? 'active' : '' ?>" id="clp-panel-calendar">

            <div id="calGrid"></div>
            <div style="display:flex;gap:16px;margin-top:12px;font-size:12px;color:#6b7280;flex-wrap:wrap">
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#dbeafe;border:1px solid #bfdbfe;display:inline-block"></span> Curs viitor</span>
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#1d4ed8;display:inline-block"></span> Curs azi</span>
                <span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:3px;background:#f1f5f9;border:1px solid #e5e7eb;display:inline-block"></span> Curs trecut</span>
            </div>
        </div>
    </div>

    <script>
    window.CLP_STATS = <?= json_encode(clp_courses_stats_js_config($courses, (int)$clp_year, (int)$clp_month, $clp_ctab), JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="/admin/assets/js/admin-cursuri-stats.js?v=8"></script>
