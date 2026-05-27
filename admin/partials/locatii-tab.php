<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Locația a fost salvată.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Locații (<?= count($locations) ?>)</span>
        <button type="button" onclick="document.getElementById('loc-form').style.display=document.getElementById('loc-form').style.display==='none'?'block':'none'" class="btn btn-sm btn-primary">+ Adaugă locație</button>
    </div>
    <?php if (empty($locations)): ?>
    <p style="color:var(--text-muted)">Nu există locații adăugate încă.</p>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
    <?php foreach ($locations as $loc): ?>
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px 14px;background:#fafafa">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                <div>
                    <div style="font-weight:700;font-size:13px"><?= h($loc['name'] ?? '') ?></div>
                    <?php if (!empty($loc['phone'])): ?><div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= h($loc['phone']) ?></div><?php endif; ?>
                    <?php if (!empty($loc['days'])): ?><div style="font-size:12px;color:var(--text-muted)"><?= h($loc['days']) ?></div><?php endif; ?>
                    <?php if (!empty($loc['notes'])): ?><div style="font-size:11px;color:#9ca3af;margin-top:3px"><?= h(mb_substr($loc['notes'], 0, 80)) ?><?= mb_strlen($loc['notes']) > 80 ? '…' : '' ?></div><?php endif; ?>
                </div>
                <div style="display:flex;gap:5px;flex-shrink:0;align-items:center">
                    <?php if (!empty($loc['maps_link'])): ?>
                    <a href="<?= h($loc['maps_link']) ?>" target="_blank" class="btn btn-sm btn-secondary">Maps ↗</a>
                    <?php endif; ?>
                    <a href="/admin/?tab=locatii&edit=<?= h($loc['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                    <form method="post" action="/admin/?tab=locatii" onsubmit="return confirm('Ștergi locația?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_location">
                        <input type="hidden" name="id" value="<?= h($loc['id'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div id="loc-form" style="<?= $edit_loc ? '' : 'display:none' ?>">
<div class="card crm-form">
    <div class="card-title"><?= $edit_loc ? 'Editează locație' : 'Adaugă locație' ?></div>
    <form method="post" action="/admin/?tab=locatii">
        <input type="hidden" name="action" value="save_location">
        <input type="hidden" name="location_id" value="<?= h($edit_loc['id'] ?? '') ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px">
            <div class="form-group"><label>Nume *</label><input type="text" name="loc_name" value="<?= h($edit_loc['name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Telefon</label><input type="text" name="loc_phone" value="<?= h($edit_loc['phone'] ?? '') ?>"></div>
            <div class="form-group"><label>Link Google Maps</label><input type="url" name="loc_maps" value="<?= h($edit_loc['maps_link'] ?? '') ?>"></div>
            <div class="form-group"><label>Zile disponibile</label><input type="text" name="loc_days" value="<?= h($edit_loc['days'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Note</label><textarea name="loc_notes" rows="2"><?= h($edit_loc['notes'] ?? '') ?></textarea></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $edit_loc ? 'Salvează' : 'Adaugă locația' ?></button>
            <a href="/admin/?tab=locatii" class="btn btn-secondary btn-sm">Anulează</a>
        </div>
    </form>
</div>
</div>
