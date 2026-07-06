<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Speakerul a fost salvat.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Speakeri (<?= count($speakers) ?>)</span>
        <button type="button" onclick="document.getElementById('sp-modal').style.display='flex'" class="btn btn-sm btn-primary">+ Adaugă speaker</button>
    </div>
    <?php if (empty($speakers) && empty($_sp_contacted)): ?>
    <p style="color:var(--text-muted)">Nu există speakeri adăugați încă.</p>
    <?php else: ?>
    <div class="sp-filter-bar">
        <button class="sp-filter-btn active" data-status="all" onclick="spFilter(this)">Toți</button>
        <button class="sp-filter-btn" data-status="URMEAZĂ" onclick="spFilter(this)">URMEAZĂ</button>
        <button class="sp-filter-btn" data-status="RECURENT" onclick="spFilter(this)">RECURENT</button>
        <button class="sp-filter-btn" data-status="MID" onclick="spFilter(this)">MID</button>
        <button class="sp-filter-btn" data-status="NOPE" onclick="spFilter(this)">NOPE</button>
        <button class="sp-filter-btn" data-status="CONTACTAT" onclick="spFilter(this)">CONTACTAT</button>
    </div>
    <table class="wp-table crm-table" id="sp-main-table">
        <thead>
            <tr>
                <th>Nume</th>
                <th>Contact</th>
                <th>Cursuri</th>
                <th style="width:90px">Status</th>
                <th style="width:150px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($_sp_contacted as $c):
            $sp_match_idx = clp_find_speaker_index_by_contact($speakers, $c['email'] ?? '', $c['phone'] ?? '');
            $sp_match_id = $sp_match_idx >= 0 ? ($speakers[$sp_match_idx]['id'] ?? '') : '';
            $contact_payload = ['id' => $sp_match_id, 'name' => $c['name'], 'email' => $c['email'], 'phone' => $c['phone']];
        ?>
        <tr data-msg-id="<?= h($c['id']) ?>">
            <td style="font-weight:600"><?= h($c['name']) ?></td>
            <td style="font-size:13px">
                <?php if ($c['email']): ?><div><?= h($c['email']) ?> <button type="button" class="sp-copy-btn" data-copy="<?= h($c['email']) ?>" onclick="spCopy(this)" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><?php endif; ?>
                <?php if ($c['phone']): ?><div><?= h($c['phone']) ?> <button type="button" class="sp-copy-btn" data-copy="<?= h($c['phone']) ?>" onclick="spCopy(this)" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><?php endif; ?>
            </td>
            <td></td>
            <td><span class="crm-status-badge" style="background:#2271b1">CONTACTAT</span></td>
            <td>
                <div class="row-actions">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="spContactatEdit(<?= h(json_encode($contact_payload, JSON_UNESCAPED_UNICODE)) ?>)">Editează</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="spScoate(this,'<?= h($c['id']) ?>')">Scoate</button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php foreach ($speakers as $sp): ?>
        <tr>
            <td style="font-weight:600">
                <?= h($sp['name'] ?? '') ?>
                <?php if (!empty($sp['notes'])): ?>
                <div style="font-size:11px;color:var(--text-muted);font-weight:400;margin-top:2px"><?= h(mb_substr($sp['notes'], 0, 60)) ?><?= mb_strlen($sp['notes']) > 60 ? '…' : '' ?></div>
                <?php endif; ?>
            </td>
            <td style="font-size:13px">
                <?php if (!empty($sp['email'])): ?><div><?= h($sp['email']) ?> <button type="button" class="sp-copy-btn" data-copy="<?= h($sp['email']) ?>" onclick="spCopy(this)" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><?php endif; ?>
                <?php if (!empty($sp['phone'])): ?><div><?= h($sp['phone']) ?> <button type="button" class="sp-copy-btn" data-copy="<?= h($sp['phone']) ?>" onclick="spCopy(this)" title="Copiază"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><?php endif; ?>
            </td>
            <td>
                <?php
                $sp_c = $sp['courses'] ?? [];
                if (is_string($sp_c)) $sp_c = $sp_c ? [$sp_c] : [];
                foreach (array_filter($sp_c) as $sp_cv):
                ?>
                <span style="display:inline-block;background:#e5e7eb;color:#374151;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:500;margin:2px 2px 2px 0"><?= h($sp_cv) ?></span>
                <?php endforeach; ?>
            </td>
            <td>
                <?php $sc = $sp_status_colors[$sp['status'] ?? 'MID'] ?? '#6b7280'; ?>
                <span class="crm-status-badge" style="background:<?= $sc ?>;cursor:pointer;user-select:none;position:relative" onclick="spStatusPop(this,'<?= h($sp['id'] ?? '') ?>')"><?= h($sp['status'] ?? 'MID') ?></span>
            </td>
            <td>
                <div class="row-actions">
                    <a href="/admin/?tab=speakeri&edit=<?= h($sp['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                    <form method="post" action="/admin/?tab=speakeri" onsubmit="return confirm('Ștergi speakerul?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_speaker">
                        <input type="hidden" name="id" value="<?= h($sp['id'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>


<div id="sp-modal" style="display:<?= $edit_sp ? 'flex' : 'none' ?>;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,.45)" onclick="if(event.target===this)this.style.display='none'">
<div class="card crm-form" style="width:min(640px,95vw);max-height:90vh;overflow-y:auto;margin:0;position:relative">
    <div class="card-title"><?= $edit_sp ? 'Editează speaker' : 'Adaugă speaker' ?></div>
    <form method="post" action="/admin/?tab=speakeri">
        <input type="hidden" name="action" value="save_speaker">
        <input type="hidden" name="speaker_id" value="<?= h($edit_sp['id'] ?? '') ?>">
        <!-- Modal tabs -->
        <div style="display:flex;gap:4px;background:#f1f5f9;border-radius:8px;padding:3px;margin-bottom:20px;width:fit-content">
            <button type="button" id="sp-tab-btn-contact" onclick="spModalTab('contact')" style="padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#1f2937;box-shadow:0 1px 3px rgba(0,0,0,.1)">Contact</button>
            <button type="button" id="sp-tab-btn-meet" onclick="spModalTab('meet')" style="padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:none;color:#6b7280">Meet</button>
        </div>
        <!-- Tab: Contact -->
        <div id="sp-tab-contact">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
            <div class="form-group"><label>Nume *</label><input type="text" name="sp_name" value="<?= h($edit_sp['name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="sp_email" value="<?= h($edit_sp['email'] ?? '') ?>"></div>
            <div class="form-group"><label>Telefon</label><input type="text" name="sp_phone" value="<?= h($edit_sp['phone'] ?? '') ?>"></div>
        </div>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px">
            <div class="form-group">
                <label>Cursuri susținute</label>
                <?php
                $sp_courses_arr = $edit_sp['courses'] ?? [];
                if (is_string($sp_courses_arr)) $sp_courses_arr = $sp_courses_arr ? [$sp_courses_arr] : [];
                if (empty($sp_courses_arr)) $sp_courses_arr = [''];
                ?>
                <div id="sp-courses-list" style="display:flex;flex-direction:column;gap:4px">
                <?php foreach ($sp_courses_arr as $sc_val): ?>
                    <div style="display:flex;gap:4px;align-items:center">
                        <input type="text" name="sp_courses[]" value="<?= h($sc_val) ?>" style="flex:1;padding:5px 9px;font-size:12px">
                        <button type="button" onclick="this.closest('div').remove()" style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:0 7px;height:28px;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1">×</button>
                    </div>
                <?php endforeach; ?>
                </div>
                <button type="button" onclick="spAddCourse()" style="margin-top:4px;background:none;border:1px solid #d1d5db;border-radius:6px;padding:2px 8px;cursor:pointer;font-size:11px;color:#6b7280">+ curs</button>
                            </div>
            <div class="form-group"><label>Status</label>
                <select name="sp_status">
                    <?php foreach (['CONTACTAT','URMEAZĂ','RECURENT','MID','NOPE'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($edit_sp['status'] ?? 'MID') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Note</label><textarea name="sp_notes" rows="2"><?= h($edit_sp['notes'] ?? '') ?></textarea></div>
        </div>
        <!-- Tab: Meet -->
        <div id="sp-tab-meet" style="display:none">
        <?php
        $mf = ['auzit'=>'Cum ai auzit de Cursuri la Pahar?','ocupatie'=>'Cu ce te ocupi?','pasiune'=>'Ce te pasionează cel mai mult la subiectul ăsta și crezi că ar fi valoros pentru oameni?','teme'=>'Ai mai avea alte idei de teme?','dinamica'=>'Cum vezi tu dinamica cu publicul? Cum ți-ar plăcea să arate?','experienta'=>'Unde ai mai ținut cursuri și cum s-au desfășurat? Ai vreo prezentare pe care ai folosit-o?','contract'=>'Contract (prezentare, durata, onorariu)','curiozitati'=>'Curiozități?','program'=>'Program pe perioada următoare'];
        ?>
        <?php foreach ($mf as $k => $lbl): ?>
        <div class="form-group">
            <label><?= h($lbl) ?></label>
            <textarea name="meet_<?= $k ?>" rows="2"><?= h($edit_sp['meet'][$k] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;margin-top:16px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $edit_sp ? 'Salvează' : 'Adaugă speakerul' ?></button>
            <a href="/admin/?tab=speakeri" class="btn btn-secondary btn-sm">Anulează</a>
        </div>
    </form>
</div>
</div>

<!-- Status quick-change popover -->
<div id="sp-status-pop" class="sp-status-popover" style="display:none">
<?php foreach (['CONTACTAT'=>'#2271b1','URMEAZĂ'=>'#7c3aed','RECURENT'=>'#16a34a','MID'=>'#d97706','NOPE'=>'#dc2626'] as $_ss=>$_sc): ?>
<button onclick="spSetStatus('<?= $_ss ?>')" style="color:<?= $_sc ?>"><?= $_ss ?></button>
<?php endforeach; ?>
</div>
