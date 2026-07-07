<h1 class="wp-page-title">Templates</h1>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Template-urile au fost salvate.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title">📋 Mesaje template</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">Apar ca butoane pe dashboard. Un click copiază textul în clipboard. Le poți edita atât tu, cât și Andy.</p>
    <form method="post" action="/admin/?tab=templates" id="tplForm">
        <input type="hidden" name="action" value="save_templates">
        <div id="tplRows" style="display:flex;flex-direction:column;gap:12px;margin-bottom:14px">
        <?php foreach ($settings['templates'] ?? [] as $_tpl): ?>
            <div class="tpl-row" style="border:1px solid var(--border);border-radius:10px;padding:12px;display:flex;flex-direction:column;gap:8px">
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="text" name="tpl_label[]" value="<?= h($_tpl['label'] ?? '') ?>" style="flex:1;font-weight:600">
                    <button type="button" onclick="this.closest('.tpl-row').remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">✕</button>
                </div>
                <textarea name="tpl_text[]" rows="4" style="width:100%;font-family:inherit;resize:vertical"><?= h($_tpl['text'] ?? '') ?></textarea>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" onclick="addTemplateRow()" class="btn btn-secondary btn-sm">+ Adaugă template</button>
            <button type="submit" class="btn btn-primary btn-sm">Salvează</button>
        </div>
    </form>
</div>
