<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Lista a fost salvată.</div>
<?php endif; ?>

<form method="post" action="/admin/?tab=cursuri-posibile">
    <input type="hidden" name="action" value="save_course_ideas">

    <div class="card">
        <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
            <span>Cursuri posibile (<?= count($course_ideas['categories']) ?> categorii)</span>
            <div style="display:flex;gap:8px;align-items:center">
                <a href="/cursuri-posibile" target="_blank" class="btn btn-sm btn-secondary">Vezi pagina ↗</a>
                <button type="submit" class="btn btn-sm btn-primary">Salvează tot</button>
            </div>
        </div>
        <div class="form-group">
            <label>Text introductiv</label>
            <textarea name="ideas_intro" rows="3"><?= h($course_ideas['intro'] ?? '') ?></textarea>
        </div>
    </div>

    <div id="ci-blocks" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <?php foreach ($course_ideas['categories'] as $cat): ?>
        <div class="card ci-block" style="margin:0">
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                <input type="text" name="cat_emoji[]" value="<?= h($cat['emoji'] ?? '') ?>" style="width:52px;text-align:center" title="Emoji">
                <input type="text" name="cat_title[]" value="<?= h($cat['title'] ?? '') ?>" style="flex:1;font-weight:700" required>
                <button type="button" class="btn btn-sm btn-secondary" onclick="ciMove(this,-1)" title="Mută sus">↑</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="ciMove(this,1)" title="Mută jos">↓</button>
                <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Ștergi categoria?'))this.closest('.ci-block').remove()" title="Șterge">✕</button>
            </div>
            <textarea name="cat_topics[]" rows="7" style="width:100%" title="O temă pe linie"><?= h(implode("\n", $cat['topics'] ?? [])) ?></textarea>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:flex;gap:8px;margin-top:14px">
        <button type="button" class="btn btn-sm btn-secondary" onclick="ciAdd()">+ Adaugă categorie</button>
        <button type="submit" class="btn btn-sm btn-primary">Salvează tot</button>
    </div>
</form>

<p style="color:var(--text-muted);font-size:12px;margin-top:8px">În fiecare categorie: o temă pe linie. Ordinea de aici e ordinea de pe pagină.</p>

<template id="ci-block-tpl">
    <div class="card ci-block" style="margin:0">
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
            <input type="text" name="cat_emoji[]" value="" style="width:52px;text-align:center" title="Emoji">
            <input type="text" name="cat_title[]" value="" style="flex:1;font-weight:700" required>
            <button type="button" class="btn btn-sm btn-secondary" onclick="ciMove(this,-1)" title="Mută sus">↑</button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="ciMove(this,1)" title="Mută jos">↓</button>
            <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Ștergi categoria?'))this.closest('.ci-block').remove()" title="Șterge">✕</button>
        </div>
        <textarea name="cat_topics[]" rows="7" style="width:100%" title="O temă pe linie"></textarea>
    </div>
</template>

<script>
function ciAdd() {
    var tpl = document.getElementById('ci-block-tpl');
    var node = tpl.content.cloneNode(true);
    document.getElementById('ci-blocks').appendChild(node);
    var blocks = document.querySelectorAll('#ci-blocks .ci-block');
    blocks[blocks.length - 1].querySelector('input[name="cat_title[]"]').focus();
}
function ciMove(btn, dir) {
    var block = btn.closest('.ci-block');
    var sibling = dir === -1 ? block.previousElementSibling : block.nextElementSibling;
    if (!sibling) return;
    if (dir === -1) block.parentNode.insertBefore(block, sibling);
    else block.parentNode.insertBefore(sibling, block);
}
</script>
