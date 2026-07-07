<h1 class="wp-page-title">Templates</h1>

<style>
.tpl-intro { font-size:13px; color:var(--text-muted); margin-bottom:18px; }
.tpl-card { border:1px solid var(--border); border-radius:12px; background:var(--surface); margin-bottom:10px; overflow:hidden; transition:border-color .15s, box-shadow .15s; }
.tpl-card.open { border-color:var(--accent); box-shadow:0 2px 10px rgba(0,0,0,.05); }
.tpl-view { display:flex; align-items:center; gap:12px; padding:14px 16px; cursor:pointer; user-select:none; }
.tpl-view:hover { background:var(--accent-soft); }
.tpl-chevron { color:var(--text-muted); font-size:12px; transition:transform .15s; flex-shrink:0; }
.tpl-card.open .tpl-chevron { transform:rotate(90deg); }
.tpl-view-main { flex:1; min-width:0; }
.tpl-view-title { font-weight:600; font-size:14px; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tpl-view-preview { font-size:12.5px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:3px; }
.tpl-copy-btn { background:none; border:1px solid var(--border); border-radius:8px; padding:6px 11px; cursor:pointer; color:var(--text-muted); display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:500; flex-shrink:0; transition:color .15s, border-color .15s, background .15s; }
.tpl-copy-btn:hover { color:#fff; background:var(--accent); border-color:var(--accent); }
.tpl-edit { padding:4px 16px 18px; border-top:1px solid var(--border); }
.tpl-lbl { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); margin:16px 0 7px; }
.tpl-edit input, .tpl-edit textarea { width:100%; }
.tpl-edit textarea { font-family:inherit; resize:vertical; line-height:1.6; }
.tpl-edit-actions { display:flex; gap:8px; margin-top:16px; }
.tpl-edit-actions .tpl-del { margin-left:auto; }
</style>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Template-urile au fost salvate.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title">📋 Mesaje template</div>
    <p class="tpl-intro">Apar ca butoane pe dashboard — un click copiază textul în clipboard. Dă click pe un template ca să-l editezi. Le poți edita atât tu, cât și Andy.</p>
    <form method="post" action="/admin/?tab=templates" id="tplForm">
        <input type="hidden" name="action" value="save_templates">
        <div id="tplRows">
        <?php foreach ($settings['templates'] ?? [] as $_tpl): $_l = $_tpl['label'] ?? ''; $_t = $_tpl['text'] ?? ''; ?>
            <div class="tpl-card">
                <div class="tpl-view" onclick="tplToggle(this)">
                    <span class="tpl-chevron">▸</span>
                    <div class="tpl-view-main">
                        <div class="tpl-view-title"><?= h($_l !== '' ? $_l : 'Template fără titlu') ?></div>
                        <div class="tpl-view-preview"><?= h($_t !== '' ? $_t : 'gol') ?></div>
                    </div>
                    <button type="button" class="tpl-copy-btn" data-tpl-text="<?= h($_t) ?>" onclick="event.stopPropagation();clpCopyTemplate(this)">📋 Copiază</button>
                </div>
                <div class="tpl-edit" hidden>
                    <label class="tpl-lbl">Titlu template</label>
                    <input type="text" name="tpl_label[]" value="<?= h($_l) ?>" oninput="tplSyncTitle(this)" style="font-weight:600">
                    <label class="tpl-lbl">Text mesaj</label>
                    <textarea name="tpl_text[]" rows="6" oninput="tplSyncPreview(this)"><?= h($_t) ?></textarea>
                    <div class="tpl-edit-actions">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="tplToggle(this.closest('.tpl-card').querySelector('.tpl-view'))">Închide</button>
                        <button type="button" class="btn btn-danger btn-sm tpl-del" onclick="this.closest('.tpl-card').remove()">Șterge</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
            <button type="button" onclick="addTemplateRow()" class="btn btn-secondary btn-sm">+ Adaugă template</button>
            <button type="submit" class="btn btn-primary btn-sm">Salvează</button>
        </div>
    </form>
</div>
