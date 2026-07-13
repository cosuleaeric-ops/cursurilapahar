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
