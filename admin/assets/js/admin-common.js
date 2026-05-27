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
