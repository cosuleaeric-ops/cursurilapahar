<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Cursul a fost salvat.</div>
<?php endif; ?>

<!-- Add / Edit form -->
<div class="card">
    <div class="card-title"><?= $edit_vc ? 'Editează cursul' : 'Adaugă idee de curs' ?></div>
    <form method="post" action="/admin/?tab=vot">
        <input type="hidden" name="action" value="save_vote_course">
        <input type="hidden" name="vote_course_id" value="<?= h($edit_vc['id'] ?? '') ?>">

        <div style="display:grid;grid-template-columns:64px 1fr;gap:12px;align-items:start">
            <div class="form-group" style="margin-bottom:0">
                <label for="vc_emoji">Emoji</label>
                <input type="text" id="vc_emoji" name="vc_emoji" value="<?= h($edit_vc['emoji'] ?? '📚') ?>" maxlength="4" style="text-align:center;font-size:1.5rem;padding:6px 4px">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label for="vc_name">Nume curs <span style="color:var(--danger)">*</span></label>
                <input type="text" id="vc_name" name="vc_name" value="<?= h($edit_vc['name'] ?? '') ?>" required placeholder="ex: Educație montană">
            </div>
        </div>

        <div class="form-group" style="margin-top:12px">
            <label for="vc_description">Descriere</label>
            <textarea id="vc_description" name="vc_description" rows="4" placeholder="Descrierea cursului, vizibilă la toggle pe pagina publică."><?= h($edit_vc['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:8px;align-items:center">
            <button type="submit" class="btn btn-primary"><?= $edit_vc ? 'Salvează modificările' : 'Adaugă cursul' ?></button>
            <?php if ($edit_vc): ?>
            <a href="/admin/?tab=vot" class="btn btn-secondary">Anulează</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Courses table -->
<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Idei de cursuri (<?= count($vote_courses) ?>)</span>
        <a href="/voteaza-cursuri" target="_blank" class="btn btn-sm btn-secondary">Vezi pagina ↗</a>
    </div>
    <?php if (empty($vote_courses)): ?>
    <p style="color:var(--text-muted)">Nu există idei de cursuri adăugate încă.</p>
    <?php else: ?>
    <table class="wp-table vc-table">
        <thead>
            <tr>
                <th style="width:48px">Emoji</th>
                <th>Nume</th>
                <th style="width:90px">Voturi</th>
                <th style="width:210px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vote_courses as $vc): ?>
            <?php $is_active = $vc['active'] ?? true; ?>
            <tr style="<?= $is_active ? '' : 'opacity:0.45' ?>">
                <td style="font-size:1.4rem;text-align:center"><?= h($vc['emoji'] ?? '📚') ?></td>
                <td style="font-weight:600">
                    <?= h($vc['name'] ?? '') ?>
                    <?php if (!$is_active): ?>
                    <span style="font-size:11px;color:var(--text-muted);font-weight:400;margin-left:6px">(dezactivat)</span>
                    <?php endif; ?>
                    <?php if (!empty($vc['description'])): ?>
                    <div style="font-size:12px;color:var(--text-muted);font-weight:400;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:360px"><?= h(mb_substr($vc['description'], 0, 80)) ?>…</div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="likes-badge">❤️ <?= (int)($vc['likes'] ?? 0) ?></span>
                </td>
                <td>
                    <div class="row-actions">
                        <a href="/admin/?tab=vot&edit=<?= h($vc['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                        <form method="post" action="/admin/?tab=vot" style="display:inline">
                            <input type="hidden" name="action" value="toggle_vote_course">
                            <input type="hidden" name="id" value="<?= h($vc['id'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm <?= $is_active ? 'btn-secondary' : 'btn-primary' ?>"><?= $is_active ? 'Dezactivează' : 'Activează' ?></button>
                        </form>
                        <form method="post" action="/admin/?tab=vot" onsubmit="return confirm('Ștergi această idee de curs?')" style="display:inline">
                            <input type="hidden" name="action" value="delete_vote_course">
                            <input type="hidden" name="id" value="<?= h($vc['id'] ?? '') ?>">
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
