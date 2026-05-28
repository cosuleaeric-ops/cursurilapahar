function filterEval(btn) {
    const filter = btn.dataset.filter;
    btn.closest('.msg-section').querySelectorAll('.msg-eval-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    btn.closest('.msg-section').querySelectorAll('.msg-card').forEach(card => {
        let show;
        if (filter === 'all') show = true;
        else if (filter === 'contactat') show = card.classList.contains('is-contacted');
        else show = card.classList.contains('eval-' + filter);
        card.style.display = show ? '' : 'none';
    });
}
function showMsgTab(key) {
    document.querySelectorAll('.msg-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.msg-panel').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('msg-panel-' + key).classList.add('active');
}
function deleteComment(btn) {
    if (!confirm('Ștergi comentariul?')) return;
    const item = btn.closest('.msg-comment-item');
    const card = btn.closest('.msg-card');
    const fd = new FormData();
    fd.append('action', 'delete_message_comment');
    fd.append('msg_id', card.dataset.msgId);
    fd.append('idx',    item.dataset.commentIdx);
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            if (!d.ok) return;
            const list = item.parentElement;
            item.remove();
            list.querySelectorAll('.msg-comment-item').forEach((el, i) => el.dataset.commentIdx = i);
        });
}
function toggleMsg(uid) {
    const el = document.getElementById('msg-' + uid);
    el.classList.toggle('open');
}
const _COPY_SVG = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
const _CHECK_SVG = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
function copyField(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        btn.innerHTML = _CHECK_SVG;
        btn.classList.add('copied');
        setTimeout(() => { btn.innerHTML = _COPY_SVG; btn.classList.remove('copied'); }, 2000);
    });
}
function deleteMsg(btn, type, idx) {
    if (!confirm('Sigur vrei să ștergi acest mesaj?')) return;
    const card = btn.closest('.msg-card');
    const fd = new FormData();
    fd.append('action', 'delete_message');
    fd.append('msg_type', type);
    fd.append('msg_index', idx);
    fetch('/admin/?tab=mesaje', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: fd })
        .then(r => r.json())
        .then(d => { if (d.ok) { updateBadgeAfterRemoval(card, type); card.remove(); } });
}
function updateNavBadge(delta) {
    const link = document.querySelector('.wp-sidebar a[href*="tab=mesaje"]');
    if (!link) return;
    let badge = link.querySelector('.nav-new-badge');
    let n = badge ? (parseInt(badge.textContent, 10) || 0) : 0;
    n = Math.max(0, n + delta);
    if (n === 0) {
        if (badge) badge.remove();
        return;
    }
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'nav-new-badge';
        link.appendChild(badge);
    }
    badge.textContent = n;
}
function updateBadge(tabKey, delta) {
    const tab = document.querySelector('.msg-tab[data-key="' + tabKey + '"]');
    if (!tab) return;
    const span = tab.querySelector('.msg-count');
    let n = parseInt(span.textContent, 10) || 0;
    n = Math.max(0, n + delta);
    span.textContent = n;
    span.style.display = n > 0 ? '' : 'none';
    updateNavBadge(delta);
}
function updateBadgeAfterRemoval(card, type) {
    if (type === 'sustine') {
        if (!card.className.match(/eval-(nope|meh|top)/)) updateBadge('sustine', -1);
    } else {
        if (!card.classList.contains('is-read')) updateBadge(type, -1);
    }
}
function markRead(btn) {
    const card  = btn.closest('.msg-card');
    const panel = card.closest('.msg-panel');
    const type  = panel ? panel.id.replace('msg-panel-', '') : 'contact';
    const id = card.dataset.msgId;
    const wasRead = card.classList.contains('is-read');
    const now = !wasRead;
    const fd = new FormData();
    fd.append('action', 'mark_read_message');
    fd.append('msg_id', id);
    if (now) fd.append('read', '1');
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            if (!d.ok) return;
            card.classList.toggle('is-read', now);
            btn.classList.toggle('is-active', now);
            btn.textContent = now ? '✓ Citit' : 'Citit';
            updateBadge(type, now ? -1 : 1);
            if (now) card.querySelector('.msg-detail').classList.remove('open');
        });
}
function markContacted(btn) {
    const card = btn.closest('.msg-card');
    const id = card.dataset.msgId;
    const wasContacted = card.classList.contains('is-contacted');
    const now = !wasContacted;
    const fd = new FormData();
    fd.append('action', 'mark_contacted_message');
    fd.append('msg_id', id);
    if (now) fd.append('contacted', '1');
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            if (!d.ok) return;
            card.classList.toggle('is-contacted', now);
            btn.classList.toggle('is-active', now);
            btn.textContent = now ? '✓ Contactat' : 'Contactat';
        });
}
function evalMsg(btn, value) {
    const card = btn.closest('.msg-card');
    const id   = card.dataset.msgId;
    const cur  = (card.className.match(/eval-(nope|meh|top)/) || [,''])[1];
    const next = cur === value ? '' : value; // toggle off if same button pressed twice
    const fd = new FormData();
    fd.append('action', 'eval_message');
    fd.append('msg_id', id);
    fd.append('eval',   next);
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            if (!d.ok) return;
            card.classList.remove('eval-nope','eval-meh','eval-top');
            if (next) card.classList.add('eval-' + next);
            card.querySelectorAll('.msg-eval-btn').forEach(b =>
                b.classList.toggle('is-active', b.dataset.eval === next)
            );
            // badge: pending count = unevaluated; cur was unset → -1; cur was set & next='' → +1
            if (!cur && next) updateBadge('sustine', -1);
            if (cur && !next) updateBadge('sustine', +1);
        });
}
function toggleCommentForm(btn) {
    const form = btn.closest('.msg-detail-actions').nextElementSibling.querySelector('.msg-comment-form');
    const visible = form.style.display !== 'none';
    form.style.display = visible ? 'none' : 'flex';
    if (!visible) form.querySelector('textarea').focus();
}
function saveComment(btn) {
    const form = btn.closest('.msg-comment-form');
    const card = btn.closest('.msg-card');
    const ta   = form.querySelector('textarea');
    const text = ta.value.trim();
    if (!text) return;
    const id = card.dataset.msgId;
    const fd = new FormData();
    fd.append('action', 'add_message_comment');
    fd.append('msg_id', id);
    fd.append('text',   text);
    btn.disabled = true;
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            btn.disabled = false;
            if (!d.ok) return;
            const list = card.querySelector('.msg-comments-list');
            const item = document.createElement('div');
            item.className = 'msg-comment-item';
            item.dataset.commentIdx = list.querySelectorAll('.msg-comment-item').length;
            item.innerHTML = '<span class="msg-comment-when"></span>';
            item.querySelector('.msg-comment-when').textContent =
                d.comment.at + (d.comment.by ? ' · ' + d.comment.by : '');
            item.appendChild(document.createTextNode(d.comment.text));
            if (window.CLP_IS_OWNER) {
                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'msg-comment-del';
                del.title = 'Șterge comentariu';
                del.textContent = '×';
                del.onclick = function(e) { e.stopPropagation(); deleteComment(this); };
                item.appendChild(del);
            }
            list.appendChild(item);
            ta.value = '';
            form.style.display = 'none';
        });
}
