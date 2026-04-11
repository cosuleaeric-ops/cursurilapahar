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
$_clp_s  = file_exists(dirname(__DIR__) . '/data/settings.json')
    ? (json_decode(file_get_contents(dirname(__DIR__) . '/data/settings.json'), true) ?: []) : [];
$_clp_fh = $_clp_s['font_heading'] ?? 'Nunito';
$_clp_fb = $_clp_s['font_body']    ?? 'Inter';
// Navbar settings
$_clp_nav = [
    'nav_bg'           => $_clp_s['nav_bg']           ?? '#000000',
    'nav_brand_color'  => $_clp_s['nav_brand_color']  ?? '#ffffff',
    'nav_brand_size'   => $_clp_s['nav_brand_size']   ?? '20',
    'nav_brand_weight' => $_clp_s['nav_brand_weight'] ?? '800',
    'nav_brand_font'   => $_clp_s['nav_brand_font']   ?? 'Poppins',
    'nav_link_color'   => $_clp_s['nav_link_color']   ?? '#ffffff',
    'nav_link_weight'  => $_clp_s['nav_link_weight']  ?? '700',
    'nav_logo_h'       => $_clp_s['nav_logo_h']       ?? '40',
];
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

/* Font panel */
#clp-font-panel {
    display: none; position: fixed; top: 32px; right: 0; z-index: 999998;
    background: #1d2327; border-radius: 0 0 0 10px;
    box-shadow: -4px 4px 24px rgba(0,0,0,.6);
    padding: 16px 18px; min-width: 320px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 12px; color: #a7aaad;
}
#clp-font-panel.visible { display: block; }
#clp-font-panel .fp-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
#clp-font-panel .fp-row label { width: 90px; font-size: 11px; color: #777; flex-shrink: 0; }
#clp-font-panel .fp-row select {
    flex: 1; background: #2c3338; color: #fff;
    border: 1px solid rgba(255,255,255,.15); border-radius: 5px;
    padding: 5px 8px; font-size: 12px; cursor: pointer;
}
#clp-font-panel .fp-preview {
    background: #2c3338; border-radius: 6px; padding: 12px 14px;
    margin-bottom: 12px; display: flex; flex-direction: column; gap: 6px;
}
#clp-font-panel .fp-preview-heading {
    font-size: 20px; font-weight: 700; color: #fff; line-height: 1.2;
}
#clp-font-panel .fp-preview-body {
    font-size: 13px; color: #9ca3af; line-height: 1.5;
}
#clp-font-panel .fp-actions { display: flex; align-items: center; gap: 10px; }
#clp-fp-save { background: #2271b1; color: #fff; border: none; border-radius: 5px;
    padding: 6px 16px; font-size: 12px; font-weight: 600; cursor: pointer; transition: .15s; }
#clp-fp-save:hover { background: #135e96; }
#clp-fp-ok { color: #00a32a; font-size: 14px; display: none; }
#clp-fonts-btn { color: #c0d0ff !important; }
#clp-fonts-btn.active { background: #2c3338 !important; color: #fff !important; }
#clp-navbar-btn { color: #a0f0c0 !important; }
#clp-navbar-btn.active { background: #2c3338 !important; color: #fff !important; }

/* Navbar panel */
#clp-navbar-panel {
    display: none; position: fixed; top: 32px; right: 0; z-index: 999997;
    background: #1d2327; border-radius: 0 0 0 10px;
    box-shadow: -4px 4px 24px rgba(0,0,0,.6);
    padding: 16px 18px; min-width: 340px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 12px; color: #a7aaad;
}
#clp-navbar-panel.visible { display: block; }
#clp-navbar-panel .np-section { font-size: 10px; color: #555; text-transform: uppercase;
    letter-spacing: .08em; margin: 12px 0 6px; border-top: 1px solid rgba(255,255,255,.07);
    padding-top: 10px; }
#clp-navbar-panel .np-section:first-child { margin-top: 0; border-top: none; padding-top: 0; }
#clp-navbar-panel .np-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
#clp-navbar-panel .np-row label { width: 110px; font-size: 11px; color: #777; flex-shrink: 0; }
#clp-navbar-panel .np-row input[type=color] {
    width: 36px; height: 26px; border: none; border-radius: 4px; cursor: pointer;
    background: none; padding: 1px; flex-shrink: 0;
}
#clp-navbar-panel .np-row input[type=number],
#clp-navbar-panel .np-row select {
    flex: 1; background: #2c3338; color: #fff;
    border: 1px solid rgba(255,255,255,.15); border-radius: 5px;
    padding: 4px 6px; font-size: 12px;
}
#clp-navbar-panel .np-row input[type=color] + input[type=number] { flex: none; width: 54px; }
#clp-navbar-panel .np-actions { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
#clp-np-save { background: #2271b1; color: #fff; border: none; border-radius: 5px;
    padding: 6px 16px; font-size: 12px; font-weight: 600; cursor: pointer; transition: .15s; }
#clp-np-save:hover { background: #135e96; }
#clp-np-ok { color: #00a32a; font-size: 14px; display: none; }
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
    <button class="bar-link" id="clp-fonts-btn" onclick="clpToggleFontPanel()">🔤 Fonturi</button>
    <button class="bar-link" id="clp-navbar-btn" onclick="clpToggleNavbarPanel()">🖊 Navbar</button>
    <button class="bar-link" id="clp-edit-btn" onclick="clpToggleEdit()">✏ Editează live</button>
    <?php if (str_starts_with($current, '/admin')): ?>
    <a href="/">🌐 Site</a>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="bar-logout">Ieșire</a>
</div>

<?php
$_clp_heading_fonts = ['Anton','Nunito','Poppins','Rubik','Inter','Playfair Display','Montserrat','Raleway','Oswald','Lora','DM Serif Display','Bebas Neue','Cormorant Garamond'];
$_clp_body_fonts    = ['Inter','Roboto','Open Sans','Lato','DM Sans','Nunito','Rubik','Source Sans 3','Mulish','Cabin','Karla','Poppins'];
$_clp_weight_opts   = [300,400,500,600,700,800,900];
?>

<!-- Navbar panel -->
<div id="clp-navbar-panel">
    <div class="np-section">Logo & Brand</div>
    <div class="np-row">
        <label>Fundal navbar</label>
        <input type="color" id="clp-np-bg" value="<?= htmlspecialchars($_clp_nav['nav_bg']) ?>" oninput="clpNavApply()">
    </div>
    <div class="np-row">
        <label>Înălțime logo</label>
        <input type="number" id="clp-np-logo-h" value="<?= htmlspecialchars($_clp_nav['nav_logo_h']) ?>" min="20" max="120" style="width:64px" oninput="clpNavApply()">
        <span style="color:#555;font-size:11px">px</span>
    </div>
    <div class="np-row">
        <label>Font brand</label>
        <select id="clp-np-brand-font" onchange="clpNavApply()">
            <?php foreach ($_clp_heading_fonts as $f): ?>
            <option value="<?= htmlspecialchars($f) ?>" <?= $_clp_nav['nav_brand_font'] === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="np-row">
        <label>Mărime brand</label>
        <input type="number" id="clp-np-brand-size" value="<?= htmlspecialchars($_clp_nav['nav_brand_size']) ?>" min="10" max="60" style="width:64px" oninput="clpNavApply()">
        <span style="color:#555;font-size:11px">px</span>
    </div>
    <div class="np-row">
        <label>Greutate brand</label>
        <select id="clp-np-brand-weight" onchange="clpNavApply()">
            <?php foreach ($_clp_weight_opts as $w): ?>
            <option value="<?= $w ?>" <?= $_clp_nav['nav_brand_weight'] == $w ? 'selected' : '' ?>><?= $w ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="np-row">
        <label>Culoare brand</label>
        <input type="color" id="clp-np-brand-color" value="<?= htmlspecialchars($_clp_nav['nav_brand_color']) ?>" oninput="clpNavApply()">
    </div>

    <div class="np-section">Link-uri meniu</div>
    <div class="np-row">
        <label>Culoare link-uri</label>
        <input type="color" id="clp-np-link-color" value="<?= htmlspecialchars($_clp_nav['nav_link_color']) ?>" oninput="clpNavApply()">
    </div>
    <div class="np-row">
        <label>Greutate link-uri</label>
        <select id="clp-np-link-weight" onchange="clpNavApply()">
            <?php foreach ($_clp_weight_opts as $w): ?>
            <option value="<?= $w ?>" <?= $_clp_nav['nav_link_weight'] == $w ? 'selected' : '' ?>><?= $w ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="np-actions">
        <button id="clp-np-save" onclick="clpSaveNavbar()">Salvează</button>
        <span id="clp-np-ok">✓ Salvat</span>
    </div>
</div>

<!-- Global fonts panel -->
<div id="clp-font-panel">
    <div class="fp-row">
        <label>Font titluri</label>
        <select id="clp-fp-heading" onchange="clpFontApply('heading')">
            <?php foreach ($_clp_heading_fonts as $f): ?>
            <option value="<?= htmlspecialchars($f) ?>" <?= $_clp_fh === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="fp-row">
        <label>Font text</label>
        <select id="clp-fp-body" onchange="clpFontApply('body')">
            <?php foreach ($_clp_body_fonts as $f): ?>
            <option value="<?= htmlspecialchars($f) ?>" <?= $_clp_fb === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="fp-preview">
        <span class="fp-preview-heading" id="clp-prev-h" style="font-family:'<?= htmlspecialchars($_clp_fh) ?>',sans-serif">Titlu exemplu — Cursuri la Pahar</span>
        <span class="fp-preview-body" id="clp-prev-b" style="font-family:'<?= htmlspecialchars($_clp_fb) ?>',system-ui,sans-serif">Text de paragraf — educație la un pahar în oraș, cu experți și oameni faini.</span>
    </div>
    <div class="fp-actions">
        <button id="clp-fp-save" onclick="clpSaveFonts()">Salvează</button>
        <span id="clp-fp-ok">✓ Salvat</span>
    </div>
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
        <option value="var(--font-heading)">↑ Font titluri</option>
        <option value="var(--font-sans)">↑ Font text</option>
        <option disabled>──────────</option>
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
        // (skip if a CSS variable is already selected)
        if (fw && !ff && !ff.startsWith('var(')) {
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

// ── Global font panel ────────────────────────────────────────────────────────
(function(){
    function clpLoadFont(family) {
        const id = 'clp-gf-' + family.replace(/\s+/g, '-').toLowerCase();
        if (document.getElementById(id)) return;
        const link = document.createElement('link');
        link.id   = id;
        link.rel  = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family='
            + encodeURIComponent(family)
            + ':ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap';
        document.head.appendChild(link);
    }

    window.clpToggleFontPanel = function() {
        const panel = document.getElementById('clp-font-panel');
        const btn   = document.getElementById('clp-fonts-btn');
        const open  = panel.classList.toggle('visible');
        btn.classList.toggle('active', open);
        // Close edit toolbar if open
        if (open) document.getElementById('clp-toolbar').classList.remove('visible');
    };

    window.clpFontApply = function(type) {
        if (type === 'heading') {
            const fam = document.getElementById('clp-fp-heading').value;
            clpLoadFont(fam);
            document.documentElement.style.setProperty('--font-heading', "'" + fam + "', sans-serif");
            document.getElementById('clp-prev-h').style.fontFamily = "'" + fam + "', sans-serif";
        } else {
            const fam = document.getElementById('clp-fp-body').value;
            clpLoadFont(fam);
            document.documentElement.style.setProperty('--font-sans', "'" + fam + "', system-ui, sans-serif");
            document.getElementById('clp-prev-b').style.fontFamily = "'" + fam + "', system-ui, sans-serif";
        }
    };

    window.clpSaveFonts = function() {
        const fh = document.getElementById('clp-fp-heading').value;
        const fb = document.getElementById('clp-fp-body').value;
        const btn = document.getElementById('clp-fp-save');
        btn.textContent = '…';
        const fd = new FormData();
        fd.append('action', 'save_global_fonts');
        fd.append('font_heading', fh);
        fd.append('font_body',    fb);
        fetch('/admin/', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
            .then(r => r.json())
            .then(d => {
                btn.textContent = 'Salvează';
                if (d.ok) {
                    const ok = document.getElementById('clp-fp-ok');
                    ok.style.display = 'inline';
                    setTimeout(() => ok.style.display = 'none', 2000);
                }
            });
    };

    // Close font panel when clicking outside
    document.addEventListener('click', function(e) {
        const panel = document.getElementById('clp-font-panel');
        const btn   = document.getElementById('clp-fonts-btn');
        if (!panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            panel.classList.remove('visible');
            btn.classList.remove('active');
        }
    });
})();

// ── Navbar panel ─────────────────────────────────────────────────────────────
(function(){
    const root = document.documentElement;

    window.clpToggleNavbarPanel = function() {
        const panel = document.getElementById('clp-navbar-panel');
        const btn   = document.getElementById('clp-navbar-btn');
        const open  = panel.classList.toggle('visible');
        btn.classList.toggle('active', open);
        // Close other panels
        if (open) {
            document.getElementById('clp-font-panel').classList.remove('visible');
            document.getElementById('clp-fonts-btn').classList.remove('active');
            document.getElementById('clp-toolbar').classList.remove('visible');
        }
    };

    window.clpNavApply = function() {
        const bg          = document.getElementById('clp-np-bg').value;
        const logoH       = document.getElementById('clp-np-logo-h').value;
        const brandFont   = document.getElementById('clp-np-brand-font').value;
        const brandSize   = document.getElementById('clp-np-brand-size').value;
        const brandWeight = document.getElementById('clp-np-brand-weight').value;
        const brandColor  = document.getElementById('clp-np-brand-color').value;
        const linkColor   = document.getElementById('clp-np-link-color').value;
        const linkWeight  = document.getElementById('clp-np-link-weight').value;

        root.style.setProperty('--nav-bg',           bg);
        root.style.setProperty('--nav-logo-h',       logoH + 'px');
        root.style.setProperty('--nav-brand-font',   "'" + brandFont + "', sans-serif");
        root.style.setProperty('--nav-brand-size',   brandSize + 'px');
        root.style.setProperty('--nav-brand-weight', brandWeight);
        root.style.setProperty('--nav-brand-color',  brandColor);
        root.style.setProperty('--nav-link-color',   linkColor);
        root.style.setProperty('--nav-link-weight',  linkWeight);

        // Load font if needed
        const id = 'clp-gf-' + brandFont.replace(/\s+/g, '-').toLowerCase();
        if (!document.getElementById(id)) {
            const l = document.createElement('link');
            l.id = id; l.rel = 'stylesheet';
            l.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(brandFont) + ':wght@300;400;500;600;700;800;900&display=swap';
            document.head.appendChild(l);
        }
    };

    window.clpSaveNavbar = function() {
        const btn = document.getElementById('clp-np-save');
        btn.textContent = '…';
        const fd = new FormData();
        fd.append('action', 'save_navbar_live');
        fd.append('nav_bg',           document.getElementById('clp-np-bg').value);
        fd.append('nav_logo_h',       document.getElementById('clp-np-logo-h').value);
        fd.append('nav_brand_font',   document.getElementById('clp-np-brand-font').value);
        fd.append('nav_brand_size',   document.getElementById('clp-np-brand-size').value);
        fd.append('nav_brand_weight', document.getElementById('clp-np-brand-weight').value);
        fd.append('nav_brand_color',  document.getElementById('clp-np-brand-color').value);
        fd.append('nav_link_color',   document.getElementById('clp-np-link-color').value);
        fd.append('nav_link_weight',  document.getElementById('clp-np-link-weight').value);
        fetch('/admin/', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
            .then(r => r.json())
            .then(d => {
                btn.textContent = 'Salvează';
                if (d.ok) {
                    const ok = document.getElementById('clp-np-ok');
                    ok.style.display = 'inline';
                    setTimeout(() => ok.style.display = 'none', 2000);
                }
            });
    };

    // Close navbar panel when clicking outside
    document.addEventListener('click', function(e) {
        const panel = document.getElementById('clp-navbar-panel');
        const btn   = document.getElementById('clp-navbar-btn');
        if (!panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            panel.classList.remove('visible');
            btn.classList.remove('active');
        }
    });
})();
</script>
