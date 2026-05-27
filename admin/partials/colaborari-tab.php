<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Colaborarea a fost salvată.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Colaborări (<?= count($collabs) ?>)</span>
        <button type="button" onclick="document.getElementById('col-form').style.display=document.getElementById('col-form').style.display==='none'?'block':'none'" class="btn btn-sm btn-primary">+ Adaugă colaborare</button>
    </div>
    <?php if (empty($collabs)): ?>
    <p style="color:var(--text-muted)">Nu există colaborări adăugate încă.</p>
    <?php else: ?>
    <table class="wp-table crm-table">
        <thead>
            <tr>
                <th>Brand / Organizație</th>
                <th>Persoana de contact</th>
                <th>Email / Telefon</th>
                <th>Status</th>
                <th style="width:150px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($collabs as $col): ?>
        <tr>
            <td style="font-weight:600">
                <?= h($col['name'] ?? '') ?>
                <?php if (!empty($col['notes'])): ?>
                <div style="font-size:11px;color:var(--text-muted);font-weight:400;margin-top:2px"><?= h(mb_substr($col['notes'], 0, 60)) ?><?= mb_strlen($col['notes']) > 60 ? '…' : '' ?></div>
                <?php endif; ?>
            </td>
            <td style="font-size:13px"><?= h($col['contact'] ?? '') ?></td>
            <td style="font-size:13px"><?= h($col['contact_info'] ?? '') ?></td>
            <td style="font-size:13px;color:var(--text-muted)"><?= h($col['status'] ?? '') ?></td>
            <td>
                <div class="row-actions">
                    <a href="/admin/?tab=colaborari&edit=<?= h($col['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                    <form method="post" action="/admin/?tab=colaborari" onsubmit="return confirm('Ștergi colaborarea?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_collaboration">
                        <input type="hidden" name="id" value="<?= h($col['id'] ?? '') ?>">
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

<div id="col-form" style="<?= $edit_col ? '' : 'display:none' ?>">
<div class="card crm-form">
    <div class="card-title"><?= $edit_col ? 'Editează colaborare' : 'Adaugă colaborare' ?></div>
    <form method="post" action="/admin/?tab=colaborari">
        <input type="hidden" name="action" value="save_collaboration">
        <input type="hidden" name="collab_id" value="<?= h($edit_col['id'] ?? '') ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px">
            <div class="form-group"><label>Nume brand / org. *</label><input type="text" name="col_name" value="<?= h($edit_col['name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Persoana de contact</label><input type="text" name="col_contact" value="<?= h($edit_col['contact'] ?? '') ?>"></div>
            <div class="form-group"><label>Email / Telefon</label><input type="text" name="col_contact_info" value="<?= h($edit_col['contact_info'] ?? '') ?>"></div>
            <div class="form-group"><label>Status</label><input type="text" name="col_status" value="<?= h($edit_col['status'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Note</label><textarea name="col_notes" rows="2"><?= h($edit_col['notes'] ?? '') ?></textarea></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $edit_col ? 'Salvează' : 'Adaugă colaborarea' ?></button>
            <a href="/admin/?tab=colaborari" class="btn btn-secondary btn-sm">Anulează</a>
        </div>
    </form>
</div>
</div>
