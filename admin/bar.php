<?php
// Admin bar — shown on public pages when logged in as admin
function clp_is_admin(): bool {
    $cookie = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie) return false;
    $settings_file = dirname(__DIR__) . '/data/settings.json';
    $s = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
    $secret = $s['auth_secret'] ?? '';
    if (!$secret) return false;
    $expected = hash_hmac('sha256', 'clp_admin_ok', $secret);
    return hash_equals($expected, $cookie);
}
if (!clp_is_admin()) return;
$current = $_SERVER['REQUEST_URI'] ?? '/';
?>
<style>
#clp-adminbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 99999;
    height: 32px; background: #1d2327; color: #a7aaad;
    display: flex; align-items: center; gap: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.4);
}
#clp-adminbar a, #clp-adminbar button.bar-link {
    color: #a7aaad; text-decoration: none; padding: 0 12px;
    height: 100%; display: flex; align-items: center; gap: 5px;
    border-right: 1px solid rgba(255,255,255,.07); transition: background .15s, color .15s;
    white-space: nowrap; background: none; border-top: none; border-bottom: none; cursor: pointer;
    font-size: 12px; font-family: inherit;
}
#clp-adminbar a:hover, #clp-adminbar button.bar-link:hover { background: #2c3338; color: #fff; }
#clp-adminbar .bar-brand { font-weight: 600; color: #fff; }
#clp-adminbar .bar-sep { flex: 1; }
#clp-adminbar .bar-logout { border-right: none; border-left: 1px solid rgba(255,255,255,.07); }
#clp-edit-btn { color: #f0c040 !important; font-weight: 600 !important; }
#clp-edit-btn.active { background: #2271b1 !important; color: #fff !important; }
body { padding-top: 32px !important; }
.navbar { top: 32px !important; }

/* Editable elements */
[data-edit-key] { cursor: default; }
body.clp-edit-mode [data-edit-key] {
    outline: 2px dashed rgba(255,193,7,.5); outline-offset: 3px;
    cursor: text !important; border-radius: 2px;
    transition: outline-color .15s;
}
body.clp-edit-mode [data-edit-key]:hover { outline-color: #ffc107; }
body.clp-edit-mode [data-edit-key]:focus { outline: 2px solid #2271b1; outline-offset: 3px; }
body.clp-edit-mode [data-edit-key]:empty:before { content: '(gol)'; color: #999; font-style: italic; }

/* Toolbar */
#clp-toolbar {
    display: none;
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    z-index: 999999;
    background: #1d2327; border-radius: 10px;
    box-shadow: 0 4px 24px rgba(0,0,0,.5);
    padding: 10px 14px; gap: 10px; align-items: center;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 12px; color: #a7aaad;
    flex-wrap: wrap; max-width: 90vw;
}
#clp-toolbar.visible { display: flex; }
#clp-toolbar label { font-size: 11px; color: #777; margin-right: 2px; }
#clp-toolbar select, #clp-toolbar input[type=number] {
    background: #2c3338; color: #fff; border: 1px solid rgba(255,255,255,.15);
    border-radius: 5px; padding: 4px 6px; font-size: 12px; cursor: pointer;
}
#clp-toolbar .tb-sep { width: 1px; height: 20px; background: rgba(255,255,255,.1); }
#clp-toolbar button {
    padding: 5px 12px; border-radius: 5px; border: none; cursor: pointer;
    font-size: 12px; font-weight: 600; transition: .15s;
}
#clp-tb-italic { background: rgba(255,255,255,.08); color: #fff; font-style: italic; width: 32px; padding: 5px; }
#clp-tb-italic.on { background: #2271b1; }
#clp-tb-save { background: #2271b1; color: #fff; }
#clp-tb-save:hover { background: #135e96; }
#clp-tb-ok { color: #00a32a; display: none; font-size: 16px; }
#clp-tb-el { color: #fff; font-weight: 600; font-size: 11px;
    background: rgba(255,255,255,.08); padding: 3px 8px; border-radius: 4px; }
</style>

<div id="clp-adminbar">
    <a href="/admin/" class="bar-brand">⚙ Admin</a>
    <a href="/admin/?tab=cursuri">📋 Cursuri</a>
    <a href="/admin/?tab=setari">✏️ Texte</a>
    <a href="/admin/?tab=aspect">🎨 Aspect</a>
    <a href="/admin/?tab=imagini">🖼 Imagini</a>
    <a href="/admin/?tab=pagini">📄 Pagini</a>
    <a href="/admin/?tab=mesaje">💬 Mesaje</a>
    <a href="/admin/?tab=vot">❤️ Vot</a>
    <span class="bar-sep"></span>
    <button class="bar-link" id="clp-edit-btn" onclick="clpToggleEdit()">✏ Editează live</button>
    <?php if (str_starts_with($current, '/admin')): ?>
    <a href="/">🌐 Site</a>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="bar-logout">Ieșire</a>
</div>

<!-- Floating edit toolbar -->
<div id="clp-toolbar">
    <span id="clp-tb-el">—</span>
    <div class="tb-sep"></div>
    <label>Greutate</label>
    <select id="clp-tb-fw" onchange="clpApply()">
        <option value="">—</option>
        <?php foreach ([300,400,500,600,700,800,900] as $w): ?>
        <option value="<?= $w ?>"><?= $w ?></option>
        <?php endforeach; ?>
    </select>
    <button id="clp-tb-italic" onclick="clpToggleItalic()" title="Italic">I</button>
    <div class="tb-sep"></div>
    <label>Font</label>
    <select id="clp-tb-ff" onchange="clpApply()">
        <option value="">— moștenit —</option>
        <option value="Poppins, sans-serif">Poppins</option>
        <option value="Inter, sans-serif">Inter</option>
        <option value="Nunito, sans-serif">Nunito</option>
        <option value="Rubik, sans-serif">Rubik</option>
        <option value="Anton, sans-serif">Anton</option>
        <option value="Georgia, serif">Georgia</option>
        <option value="system-ui, sans-serif">System</option>
    </select>
    <div class="tb-sep"></div>
    <label>Mărime</label>
    <input type="number" id="clp-tb-fs" min="10" max="120" step="1" style="width:60px" oninput="clpApply()" placeholder="px">
    <div class="tb-sep"></div>
    <button id="clp-tb-save" onclick="clpSave()">Salvează</button>
    <span id="clp-tb-ok">✓</span>
</div>

<script>
(function(){
    let editMode = false;
    let selEl = null, selKey = null;

    window.clpToggleEdit = function() {
        editMode = !editMode;
        const btn = document.getElementById('clp-edit-btn');
        const tb  = document.getElementById('clp-toolbar');
        document.body.classList.toggle('clp-edit-mode', editMode);
        btn.classList.toggle('active', editMode);
        btn.textContent = editMode ? '✓ Ieși editare' : '✏ Editează live';

        document.querySelectorAll('[data-edit-key]').forEach(el => {
            if (editMode) {
                el.contentEditable = 'true';
                el.addEventListener('focus', clpOnFocus);
                el.addEventListener('keydown', clpOnKey);
            } else {
                el.contentEditable = 'false';
                el.removeEventListener('focus', clpOnFocus);
                el.removeEventListener('keydown', clpOnKey);
                el.classList.remove('clp-sel');
            }
        });

        if (!editMode) {
            tb.classList.remove('visible');
            selEl = null; selKey = null;
        }
    };

    function clpOnFocus(e) {
        selEl  = e.currentTarget;
        selKey = selEl.dataset.editKey;
        document.querySelectorAll('[data-edit-key]').forEach(x => x.classList.remove('clp-sel'));
        selEl.classList.add('clp-sel');

        // Read current computed styles
        const cs = window.getComputedStyle(selEl);
        const fw = Math.round(parseInt(cs.fontWeight)/100)*100;
        const fwSel = document.getElementById('clp-tb-fw');
        fwSel.value = fw || '';

        document.getElementById('clp-tb-italic').classList.toggle('on', cs.fontStyle === 'italic');
        document.getElementById('clp-tb-fs').value = Math.round(parseFloat(cs.fontSize)) || '';
        document.getElementById('clp-tb-ff').value = '';
        document.getElementById('clp-tb-el').textContent = selKey;
        document.getElementById('clp-toolbar').classList.add('visible');
        document.getElementById('clp-tb-ok').style.display = 'none';
    }

    function clpOnKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); }
    }

    window.clpApply = function() {
        if (!selEl) return;
        const fw = document.getElementById('clp-tb-fw').value;
        const it = document.getElementById('clp-tb-italic').classList.contains('on');
        let   ff = document.getElementById('clp-tb-ff').value;
        const fs = document.getElementById('clp-tb-fs').value;

        // Anton only has one weight — auto-switch to Poppins when weight is changed
        if (fw && !ff) {
            const currentFont = window.getComputedStyle(selEl).fontFamily.toLowerCase();
            if (currentFont.includes('anton')) {
                ff = 'Poppins, sans-serif';
                document.getElementById('clp-tb-ff').value = 'Poppins, sans-serif';
            }
        }

        selEl.style.fontWeight  = fw  || '';
        selEl.style.fontStyle   = it  ? 'italic' : '';
        selEl.style.fontFamily  = ff  || '';
        selEl.style.fontSize    = fs  ? fs + 'px' : '';
    };

    window.clpToggleItalic = function() {
        document.getElementById('clp-tb-italic').classList.toggle('on');
        clpApply();
    };

    window.clpSave = function() {
        if (!selEl || !selKey) return;
        const fw = document.getElementById('clp-tb-fw').value;
        const it = document.getElementById('clp-tb-italic').classList.contains('on');
        const ff = document.getElementById('clp-tb-ff').value;
        const fs = document.getElementById('clp-tb-fs').value;

        const parts = [];
        if (fw)  parts.push('font-weight:' + fw);
        if (it)  parts.push('font-style:italic');
        if (ff)  parts.push('font-family:' + ff);
        if (fs)  parts.push('font-size:' + fs + 'px');

        const fd = new FormData();
        fd.append('action', 'save_inline_edit');
        fd.append('key',    selKey);
        fd.append('value',  selEl.innerText.trim());
        fd.append('style',  parts.join(';'));

        const btn = document.getElementById('clp-tb-save');
        btn.textContent = '…';
        fetch('/admin/', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
            .then(r => r.json())
            .then(d => {
                btn.textContent = 'Salvează';
                if (d.ok) {
                    const ok = document.getElementById('clp-tb-ok');
                    ok.style.display = 'inline';
                    setTimeout(() => ok.style.display = 'none', 2000);
                }
            });
    };
})();
</script>
