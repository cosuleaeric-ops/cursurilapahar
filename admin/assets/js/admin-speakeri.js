function spCopy(btn) {
    const copySvg = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    const checkSvg = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
    navigator.clipboard.writeText(btn.dataset.copy).then(() => {
        btn.innerHTML = checkSvg;
        btn.style.color = '#27ae60'; btn.style.borderColor = '#27ae60';
        setTimeout(() => { btn.innerHTML = copySvg; btn.style.color = ''; btn.style.borderColor = ''; }, 2000);
    });
}
function spFilter(btn) {
    document.querySelectorAll('.sp-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const status = btn.dataset.status;
    document.querySelectorAll('#sp-main-table tbody tr').forEach(row => {
        if (status === 'all') { row.style.display = ''; return; }
        const badge = row.querySelector('.crm-status-badge');
        row.style.display = (badge && badge.textContent.trim() === status) ? '' : 'none';
    });
}
// .bc-doc are animation cu transform => e containing block pt position:fixed;
// mutăm modalul în <body> ca overlay-ul să acopere tot viewport-ul.
function spShowModal() {
    const modal = document.getElementById('sp-modal');
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.style.display = 'flex';
}
function spResetForm() {
    const modal = document.getElementById('sp-modal');
    modal.querySelectorAll('input[type=text],input[type=email],textarea').forEach(el => el.value = '');
    modal.querySelector('[name="speaker_id"]').value = '';
    modal.querySelector('[name="sp_status"]').value = 'MID';
    spModalTab('contact');
}
function spNew() {
    spResetForm();
    document.getElementById('sp-modal-title').textContent = 'Adaugă speaker';
    document.getElementById('sp-modal-submit').textContent = 'Adaugă speakerul';
    spShowModal();
}
function spEdit(data) {
    spResetForm();
    const modal = document.getElementById('sp-modal');
    modal.querySelector('[name="speaker_id"]').value = data.id || '';
    modal.querySelector('[name="sp_name"]').value = data.name || '';
    modal.querySelector('[name="sp_email"]').value = data.email || '';
    modal.querySelector('[name="sp_phone"]').value = data.phone || '';
    modal.querySelector('[name="sp_status"]').value = data.status || 'MID';
    modal.querySelector('[name="sp_notes"]').value = data.notes || '';
    modal.querySelectorAll('textarea[name^="meet_"]').forEach(el => {
        const key = el.name.slice(5);
        el.value = (data.meet && data.meet[key]) || '';
    });
    document.getElementById('sp-modal-title').textContent = 'Editează speaker';
    document.getElementById('sp-modal-submit').textContent = 'Salvează';
    spShowModal();
}
function spDetalii(data) {
    const modal = document.getElementById('sp-detalii-modal');
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    document.getElementById('sp-detalii-title').textContent = 'Detalii: ' + (data.name || '');
    document.getElementById('sp-detalii-id').value = data.id || '';
    // Tab Formular (read-only)
    document.getElementById('sp-dt-form-date').textContent = data.form_date ? 'Trimis pe ' + data.form_date : '';
    const wrap = document.getElementById('sp-dt-form-rows');
    wrap.innerHTML = '';
    const rows = data.form_rows || [];
    if (!rows.length) {
        wrap.innerHTML = '<div style="font-size:13px;color:#9ca3af">Fără formular trimis.</div>';
    } else {
        rows.forEach(r => {
            const row = document.createElement('div');
            row.style.cssText = 'margin-bottom:12px';
            const lbl = document.createElement('div');
            lbl.style.cssText = 'font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.02em;margin-bottom:2px';
            lbl.textContent = r.label;
            const val = document.createElement('div');
            val.style.cssText = 'font-size:13px;white-space:pre-wrap';
            val.textContent = r.value || '—';
            row.appendChild(lbl); row.appendChild(val);
            wrap.appendChild(row);
        });
    }
    // Tab Cursuri (editabil)
    spDtSetCourses(Array.isArray(data.courses) ? data.courses : (data.courses ? [data.courses] : []));
    spDetaliiTab('formular');
    modal.style.display = 'flex';
}
function spDetaliiTab(tab) {
    const active = 'padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#1f2937;box-shadow:0 1px 3px rgba(0,0,0,.1)';
    const idle   = 'padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:none;color:#6b7280';
    document.getElementById('sp-dt-formular').style.display = tab === 'formular' ? '' : 'none';
    document.getElementById('sp-dt-cursuri').style.display  = tab === 'cursuri'  ? '' : 'none';
    document.getElementById('sp-dt-btn-formular').style.cssText = tab === 'formular' ? active : idle;
    document.getElementById('sp-dt-btn-cursuri').style.cssText  = tab === 'cursuri'  ? active : idle;
}
function spDtSetCourses(courses) {
    const list = document.getElementById('sp-dt-courses-list');
    list.innerHTML = '';
    (courses && courses.length ? courses : ['']).forEach(c => {
        spDtAddCourse();
        list.lastElementChild.querySelector('input').value = c;
    });
}
function spDtAddCourse() {
    const wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;gap:4px;align-items:center';
    wrap.innerHTML = '<input type="text" style="flex:1;padding:5px 9px;font-size:12px;border:1px solid #e5e7eb;border-radius:8px"><button type="button" onclick="this.closest(\'div\').remove()" style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:0 7px;height:28px;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1">×</button>';
    document.getElementById('sp-dt-courses-list').appendChild(wrap);
    wrap.querySelector('input').focus();
}
function spDtSaveCourses() {
    const id = document.getElementById('sp-detalii-id').value;
    if (!id) return;
    const btn = document.getElementById('sp-dt-save');
    const fd = new FormData();
    fd.append('action', 'save_speaker_courses');
    fd.append('id', id);
    document.querySelectorAll('#sp-dt-courses-list input').forEach(i => {
        if (i.value.trim()) fd.append('sp_courses[]', i.value.trim());
    });
    btn.disabled = true; btn.textContent = 'Se salvează…';
    fetch('/admin/?tab=speakeri', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            btn.disabled = false; btn.textContent = 'Salvează cursurile';
            if (d.ok) document.getElementById('sp-detalii-modal').style.display = 'none';
        }).catch(() => { btn.disabled = false; btn.textContent = 'Salvează cursurile'; });
}
function spContactatEdit(data) {
    spResetForm();
    const modal = document.getElementById('sp-modal');
    modal.querySelector('[name="speaker_id"]').value = data.id || '';
    modal.querySelector('[name="sp_name"]').value = data.name || '';
    modal.querySelector('[name="sp_email"]').value = data.email || '';
    modal.querySelector('[name="sp_phone"]').value = data.phone || '';
    document.getElementById('sp-modal-title').textContent = 'Editează speaker';
    document.getElementById('sp-modal-submit').textContent = 'Salvează';
    spShowModal();
}
function spScoate(btn, id) {
    const fd = new FormData();
    fd.append('action', 'mark_contacted_message');
    fd.append('msg_id', id);
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => { if (d.ok) btn.closest('tr').remove(); });
}

function spModalTab(tab) {
    document.getElementById('sp-tab-contact').style.display = tab === 'contact' ? '' : 'none';
    document.getElementById('sp-tab-meet').style.display   = tab === 'meet'    ? '' : 'none';
    document.getElementById('sp-tab-btn-contact').style.cssText = tab==='contact' ? 'padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#1f2937;box-shadow:0 1px 3px rgba(0,0,0,.1)' : 'padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:none;color:#6b7280';
    document.getElementById('sp-tab-btn-meet').style.cssText    = tab==='meet'    ? 'padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#1f2937;box-shadow:0 1px 3px rgba(0,0,0,.1)' : 'padding:5px 16px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:none;color:#6b7280';
}

let _spPopId = null, _spPopBadge = null;
function spStatusPop(badge, id) {
    const pop = document.getElementById('sp-status-pop');
    if (_spPopBadge === badge && pop.style.display !== 'none') { pop.style.display='none'; _spPopBadge=null; return; }
    _spPopId = id; _spPopBadge = badge;
    // .bc-doc are animation cu transform => e containing block pt position:fixed;
    // mutăm popover-ul în <body> ca să se poziționeze relativ la viewport.
    if (pop.parentElement !== document.body) document.body.appendChild(pop);
    const r = badge.getBoundingClientRect();
    pop.style.position = 'fixed';
    pop.style.display = 'flex';
    const popW = pop.offsetWidth || 120;
    const popH = pop.offsetHeight || 160;
    let left = r.left;
    if (left + popW > window.innerWidth - 8) left = window.innerWidth - popW - 8;
    pop.style.left = Math.max(8, left) + 'px';
    // sub badge dacă e loc, altfel deasupra
    pop.style.top = (r.bottom + 4 + popH > window.innerHeight - 8)
        ? Math.max(8, r.top - popH - 4) + 'px'
        : (r.bottom + 4) + 'px';
}
function spSetStatus(status) {
    const pop = document.getElementById('sp-status-pop');
    pop.style.display = 'none';
    const fd = new FormData();
    fd.append('action', 'save_speaker_status');
    fd.append('id', _spPopId);
    fd.append('status', status);
    fetch('/admin/?tab=speakeri', {method:'POST', body:fd}).then(() => location.reload());
}
document.addEventListener('click', e => {
    const pop = document.getElementById('sp-status-pop');
    if (pop && !pop.contains(e.target) && !e.target.classList.contains('crm-status-badge')) pop.style.display = 'none';
});

// dacă modalul e deschis din server (?edit=...), mută-l în <body> ca să acopere tot
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('sp-modal');
    if (modal && modal.parentElement !== document.body) document.body.appendChild(modal);
    const dt = document.getElementById('sp-detalii-modal');
    if (dt && dt.parentElement !== document.body) document.body.appendChild(dt);
});

