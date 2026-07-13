function toggleDiscountRow(id) {
    const row = document.getElementById('discount-row-' + id);
    if (!row) return;
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

function clpToggleSidebarSection(header, id) {
    header.classList.toggle('collapsed');
    document.getElementById('sidebar-' + id).classList.toggle('collapsed');
}

function copySyncToken() {
    const inp = document.getElementById('sync_token_input');
    if (!inp) return;
    inp.select();
    navigator.clipboard.writeText(inp.value);
}

function addQlRow() {
    const row = document.createElement('div');
    row.className = 'ql-row';
    row.style.cssText = 'display:grid;grid-template-columns:60px 1fr 3fr auto;gap:8px;align-items:center';
    row.innerHTML = '<input type="text" name="ql_icon[]" value="🔗" style="text-align:center;font-size:18px">'
        + '<input type="text" name="ql_label[]" value="">'
        + '<input type="text" name="ql_url[]" value="">'
        + '<button type="button" onclick="this.closest(\'.ql-row\').remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">✕</button>';
    document.getElementById('qlRows').appendChild(row);
}

function tplToggle(view) {
    const card = view.closest('.tpl-card');
    const edit = card.querySelector('.tpl-edit');
    edit.hidden = !edit.hidden;
    card.classList.toggle('open', !edit.hidden);
}

function tplSyncTitle(inp) {
    const t = inp.closest('.tpl-card').querySelector('.tpl-view-title');
    t.textContent = inp.value.trim() || 'Template fără titlu';
}

function tplSyncIcon(inp) {
    const el = inp.closest('.tpl-card').querySelector('.tpl-view-icon');
    el.textContent = inp.value.trim() || '📋';
}

function tplSyncPreview(ta) {
    const card = ta.closest('.tpl-card');
    card.querySelector('.tpl-view-preview').textContent = ta.value.trim() || 'gol';
    const cb = card.querySelector('.tpl-copy-btn');
    if (cb) cb.setAttribute('data-tpl-text', ta.value);
}

function addTemplateRow() {
    const card = document.createElement('div');
    card.className = 'tpl-card open';
    card.innerHTML =
        '<div class="tpl-view" onclick="tplToggle(this)">'
        + '<span class="tpl-chevron">▸</span>'
        + '<span class="tpl-view-icon">📋</span>'
        + '<div class="tpl-view-main">'
        + '<div class="tpl-view-title">Template fără titlu</div>'
        + '<div class="tpl-view-preview">gol</div>'
        + '</div>'
        + '<button type="button" class="tpl-copy-btn" data-tpl-text="" onclick="event.stopPropagation();clpCopyTemplate(this)">📋 Copiază</button>'
        + '</div>'
        + '<div class="tpl-edit">'
        + '<label class="tpl-lbl">Emoji &amp; titlu</label>'
        + '<div style="display:flex;gap:8px">'
        + '<input type="text" name="tpl_icon[]" value="📋" oninput="tplSyncIcon(this)" style="width:56px;text-align:center;font-size:18px">'
        + '<input type="text" name="tpl_label[]" value="" oninput="tplSyncTitle(this)" style="flex:1;font-weight:600">'
        + '</div>'
        + '<label class="tpl-lbl">Text mesaj</label>'
        + '<textarea name="tpl_text[]" rows="6" oninput="tplSyncPreview(this)"></textarea>'
        + '<div class="tpl-edit-actions">'
        + '<button type="button" class="btn btn-secondary btn-sm" onclick="tplToggle(this.closest(\'.tpl-card\').querySelector(\'.tpl-view\'))">Închide</button>'
        + '<button type="button" class="btn btn-danger btn-sm tpl-del" onclick="this.closest(\'.tpl-card\').remove()">Șterge</button>'
        + '</div></div>';
    document.getElementById('tplRows').appendChild(card);
    card.querySelector('input[name="tpl_label[]"]').focus();
}

function clpCopyTemplate(btn) {
    const text = btn.getAttribute('data-tpl-text') || '';
    navigator.clipboard.writeText(text).then(function () {
        const orig = btn.innerHTML;
        btn.innerHTML = '✅ Copiat!';
        btn.disabled = true;
        setTimeout(function () { btn.innerHTML = orig; btn.disabled = false; }, 1400);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('user-switcher-btn');
    var menu = document.getElementById('user-switcher-menu');
    if (!btn || !menu) return;
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function () { menu.style.display = 'none'; });
});
