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
body { padding-top: 120px !important; } /* 32px admin bar + 88px navbar */
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
#clp-tb-ok  { color: #00a32a; display: none; font-size: 16px; }
#clp-tb-err { color: #d63638; display: none; font-size: 12px; max-width: 260px; }
#clp-tb-el { color: #fff; font-weight: 600; font-size: 11px;
    background: rgba(255,255,255,.08); padding: 3px 8px; border-radius: 4px; }

/* Font panel */
#clp-font-panel {
    display: none; position: fixed; top: 32px; right: 0; z-index: 999998;
    background: #1d2327; border-radius: 0 0 0 10px;
    box-shadow: -4px 4px 24px rgba(0,0,0,.6);
    padding: 16px 18px; width: 420px; max-height: calc(100vh - 40px); overflow-y: auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 12px; color: #a7aaad;
}
#clp-font-panel.visible { display: block; }
#clp-font-panel .fp-section {
    font-size: 10px; color: #555; text-transform: uppercase; letter-spacing: .08em;
    margin: 14px 0 8px; border-top: 1px solid rgba(255,255,255,.07); padding-top: 12px;
}
#clp-font-panel .fp-section:first-child { margin-top: 0; border-top: none; padding-top: 0; }
#clp-font-panel .fp-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
#clp-font-panel .fp-row label { width: 80px; font-size: 11px; color: #777; flex-shrink: 0; }
#clp-font-panel .fp-row select,
#clp-font-panel .fp-row input[type=number] {
    flex: 1; background: #2c3338; color: #fff;
    border: 1px solid rgba(255,255,255,.15); border-radius: 5px;
    padding: 4px 6px; font-size: 12px; cursor: pointer;
}
#clp-font-panel .fp-row input[type=number] { max-width: 64px; flex: none; }
#clp-font-panel .fp-row .fp-unit { color: #555; font-size: 11px; }
#clp-font-panel .fp-row-sizes { display: flex; align-items: center; gap: 6px; flex: 1; }
#clp-font-panel .fp-row-sizes input { width: 46px; text-align: center; }
#clp-font-panel .fp-row-sizes span { color: #555; font-size: 10px; white-space: nowrap; }
#clp-font-panel .fp-italic-btn {
    background: rgba(255,255,255,.08); color: #fff; border: 1px solid rgba(255,255,255,.15);
    border-radius: 5px; width: 32px; height: 28px; cursor: pointer; font-style: italic;
    font-size: 13px; font-weight: 700; transition: .15s; flex-shrink: 0;
}
#clp-font-panel .fp-italic-btn.on { background: #2271b1; border-color: #2271b1; }
#clp-font-panel .fp-preview {
    background: #2c3338; border-radius: 6px; padding: 12px 14px;
    margin: 12px 0; display: flex; flex-direction: column; gap: 6px;
}
#clp-font-panel .fp-preview-heading { font-size: 20px; font-weight: 700; color: #fff; line-height: 1.2; }
#clp-font-panel .fp-preview-body { font-size: 13px; color: #9ca3af; line-height: 1.5; }
#clp-font-panel .fp-actions { display: flex; align-items: center; gap: 10px; margin-top: 4px; }
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
    <button class="bar-link" id="clp-tb-device" onclick="clpToggleDevice()" style="color:#f0c040 !important;font-weight:600 !important;">🖥️ Desktop</button>
    <?php if (str_starts_with($current, '/admin')): ?>
    <a href="/">🌐 Site</a>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="bar-logout">Logout</a>
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

<!-- Section background panel -->
<div id="clp-sbg-panel">
    <button id="clp-sbg-close" onclick="document.getElementById('clp-sbg-panel').classList.remove('visible')">✕</button>
    <div class="sbg-section-title">🖼 Fundal secțiune: <span id="clp-sbg-section-name"></span></div>

    <label class="sbg-label">URL imagine</label>
    <input type="text" id="clp-sbg-img" placeholder="https://... sau /assets/images/..." oninput="clpSectionBgApply()">

    <label class="sbg-label">Alege din imagini existente</label>
    <div class="sbg-gallery" id="clp-sbg-gallery"></div>
    <button class="sbg-clear" onclick="document.getElementById('clp-sbg-img').value='';clpSectionBgApply()">✕ Elimină fundalul</button>

    <label class="sbg-label">Blur</label>
    <div class="sbg-row">
        <input type="range" id="clp-sbg-blur" min="0" max="20" value="6" oninput="document.getElementById('clp-sbg-blur-val').textContent=this.value+'px';clpSectionBgApply()">
        <span class="sbg-val" id="clp-sbg-blur-val">6px</span>
    </div>

    <label class="sbg-label">Opacitate overlay întunecat</label>
    <div class="sbg-row">
        <input type="range" id="clp-sbg-overlay" min="0" max="95" value="72" oninput="document.getElementById('clp-sbg-overlay-val').textContent=this.value+'%';clpSectionBgApply()">
        <span class="sbg-val" id="clp-sbg-overlay-val">72%</span>
    </div>

    <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
        <button id="clp-sbg-save" onclick="clpSaveSectionBg()">Salvează</button>
        <span id="clp-sbg-ok">✓ Salvat</span>
    </div>
</div>

<?php
$_clp_fh_w     = $_clp_s['fh_weight']   ?? '';
$_clp_fh_i     = !empty($_clp_s['fh_italic']);
$_clp_fh_lg    = $_clp_s['fh_size_lg']  ?? '';
$_clp_fh_md    = $_clp_s['fh_size_md']  ?? '';
$_clp_fh_sm    = $_clp_s['fh_size_sm']  ?? '';
$_clp_fb_w     = $_clp_s['fb_weight']   ?? '';
$_clp_fb_lg    = $_clp_s['fb_size_lg']  ?? '';
$_clp_fb_md    = $_clp_s['fb_size_md']  ?? '';
$_clp_fb_sm    = $_clp_s['fb_size_sm']  ?? '';
?>
<!-- Global fonts panel -->
<div id="clp-font-panel">
    <div class="fp-section">Font titluri</div>
    <div class="fp-row">
        <label>Familie</label>
        <select id="clp-fp-heading" onchange="clpFontApply()">
            <?php foreach ($_clp_heading_fonts as $f): ?>
            <option value="<?= htmlspecialchars($f) ?>" <?= $_clp_fh === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="fp-row">
        <label>Greutate</label>
        <select id="clp-fp-fh-weight" onchange="clpFontApply()">
            <option value="">—</option>
            <?php foreach ([300,400,500,600,700,800,900] as $w): ?>
            <option value="<?= $w ?>" <?= $_clp_fh_w == $w ? 'selected' : '' ?>><?= $w ?></option>
            <?php endforeach; ?>
        </select>
        <button class="fp-italic-btn <?= $_clp_fh_i ? 'on' : '' ?>" id="clp-fp-fh-italic" onclick="this.classList.toggle('on');clpFontApply()" title="Italic">I</button>
    </div>
    <div class="fp-row">
        <label>Mărime</label>
        <div class="fp-row-sizes">
            <input type="number" id="clp-fp-fh-lg" value="<?= htmlspecialchars($_clp_fh_lg) ?>" min="10" max="200" placeholder="—" oninput="clpFontApply()">
            <span>desktop</span>
            <input type="number" id="clp-fp-fh-md" value="<?= htmlspecialchars($_clp_fh_md) ?>" min="10" max="200" placeholder="—" oninput="clpFontApply()">
            <span>tabletă</span>
            <input type="number" id="clp-fp-fh-sm" value="<?= htmlspecialchars($_clp_fh_sm) ?>" min="10" max="200" placeholder="—" oninput="clpFontApply()">
            <span>telefon</span>
        </div>
    </div>

    <div class="fp-section">Font text corp</div>
    <div class="fp-row">
        <label>Familie</label>
        <select id="clp-fp-body" onchange="clpFontApply()">
            <?php foreach ($_clp_body_fonts as $f): ?>
            <option value="<?= htmlspecialchars($f) ?>" <?= $_clp_fb === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="fp-row">
        <label>Greutate</label>
        <select id="clp-fp-fb-weight" onchange="clpFontApply()">
            <option value="">—</option>
            <?php foreach ([300,400,500,600,700,800,900] as $w): ?>
            <option value="<?= $w ?>" <?= $_clp_fb_w == $w ? 'selected' : '' ?>><?= $w ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="fp-row">
        <label>Mărime</label>
        <div class="fp-row-sizes">
            <input type="number" id="clp-fp-fb-lg" value="<?= htmlspecialchars($_clp_fb_lg) ?>" min="10" max="60" placeholder="—" oninput="clpFontApply()">
            <span>desktop</span>
            <input type="number" id="clp-fp-fb-md" value="<?= htmlspecialchars($_clp_fb_md) ?>" min="10" max="60" placeholder="—" oninput="clpFontApply()">
            <span>tabletă</span>
            <input type="number" id="clp-fp-fb-sm" value="<?= htmlspecialchars($_clp_fb_sm) ?>" min="10" max="60" placeholder="—" oninput="clpFontApply()">
            <span>telefon</span>
        </div>
    </div>

    <div class="fp-preview">
        <span class="fp-preview-heading" id="clp-prev-h" style="font-family:'<?= htmlspecialchars($_clp_fh) ?>',sans-serif;<?= $_clp_fh_w ? 'font-weight:'.$_clp_fh_w.';' : '' ?><?= $_clp_fh_i ? 'font-style:italic;' : '' ?><?= $_clp_fh_lg ? 'font-size:'.$_clp_fh_lg.'px;' : '' ?>">Titlu exemplu — Cursuri la Pahar</span>
        <span class="fp-preview-body" id="clp-prev-b" style="font-family:'<?= htmlspecialchars($_clp_fb) ?>',system-ui,sans-serif;<?= $_clp_fb_w ? 'font-weight:'.$_clp_fb_w.';' : '' ?><?= $_clp_fb_lg ? 'font-size:'.$_clp_fb_lg.'px;' : '' ?>">Text de paragraf — educație la un pahar în oraș, cu experți și oameni faini.</span>
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
    <span id="clp-tb-err"></span>
</div>

<script>
(function(){
    let editMode = false;
    let selEl = null, selKey = null;
    let editDevice = 'desktop';

    // Keys that store/return HTML (not plain text)
    const htmlKeys = ['hero_title', 'announcement',
        'sustine_intro_1', 'sustine_intro_2',
        'gazduieste_intro_1', 'gazduieste_intro_2',
        'parteneriat_intro_1', 'parteneriat_intro_2'];

    function clpGetValue(el, key) {
        return htmlKeys.includes(key) ? el.innerHTML : el.innerText.trim();
    }

    // Silent background save (no UI feedback) — used for auto-save on blur
    function clpAutoSave(el, key, value, style) {
        const fd = new FormData();
        fd.append('action', 'save_inline_edit');
        fd.append('key',    key);
        fd.append('value',  value);
        fd.append('style',  style);
        fd.append('device', editDevice);
        fetch('/admin/', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
            .then(r => r.json())
            .then(d => { if (d.ok) el._clpOrig = value; });
    }

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
                el.addEventListener('blur', clpOnBlur);
            } else {
                el.contentEditable = 'false';
                el.removeEventListener('focus', clpOnFocus);
                el.removeEventListener('keydown', clpOnKey);
                el.removeEventListener('blur', clpOnBlur);
                el.classList.remove('clp-sel');
            }
        });

        clpToggleSectionBgButtons(editMode);

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

        // Store original content for change detection
        selEl._clpOrig = clpGetValue(selEl, selKey);

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

    // Auto-save when focus leaves an element and content changed
    function clpOnBlur(e) {
        const el  = e.currentTarget;
        const key = el.dataset.editKey;
        const cur = clpGetValue(el, key);
        if (cur === el._clpOrig) return; // nothing changed

        // Read current toolbar styles (still reflect this element since blur fires before next focus)
        const fw = document.getElementById('clp-tb-fw').value;
        const it = document.getElementById('clp-tb-italic').classList.contains('on');
        const ff = document.getElementById('clp-tb-ff').value;
        const fs = document.getElementById('clp-tb-fs').value;
        const parts = [];
        if (fw) parts.push('font-weight:' + fw);
        if (it) parts.push('font-style:italic');
        if (ff) parts.push('font-family:' + ff);
        if (fs) parts.push('font-size:' + fs + 'px');

        clpAutoSave(el, key, cur, parts.join(';'));
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

    // Device styles from PHP for preview injection
    const _tabletStyles = <?= json_encode($_clp_s['element_styles_tablet'] ?? (object)[], JSON_FORCE_OBJECT) ?>;
    const _mobileStyles = <?= json_encode($_clp_s['element_styles_mobile'] ?? (object)[], JSON_FORCE_OBJECT) ?>;
    const _devices = ['desktop', 'tablet', 'mobile'];
    const _deviceLabels = { desktop: '\u{1F5A5}\uFE0F Desktop', tablet: '\u{1F4BB} Tabletă', mobile: '\u{1F4F1} Telefon' };
    const _deviceTitles = { desktop: 'Editezi: Desktop', tablet: 'Editezi: Tabletă', mobile: 'Editezi: Telefon' };
    const _deviceWidths = { desktop: null, tablet: '768px', mobile: '390px' };

    window.clpToggleDevice = function() {
        // Clear inline styles from all editable elements
        document.querySelectorAll('[data-edit-key]').forEach(el => {
            el.style.fontWeight = '';
            el.style.fontStyle = '';
            el.style.fontFamily = '';
            el.style.fontSize = '';
        });

        const idx = (_devices.indexOf(editDevice) + 1) % 3;
        editDevice = _devices[idx];
        const btn = document.getElementById('clp-tb-device');
        btn.textContent = _deviceLabels[editDevice];
        btn.title = _deviceTitles[editDevice];

        if (selEl) clpOnFocus({ currentTarget: selEl });

        // Remove existing preview
        let overlay = document.getElementById('clp-preview-overlay');
        let styleEl = document.getElementById('clp-device-preview');
        if (overlay) overlay.remove();
        if (styleEl) styleEl.remove();

        if (editDevice !== 'desktop') {
            const width = editDevice === 'tablet' ? 768 : 390;
            const height = editDevice === 'tablet' ? 1024 : 844;

            // Create overlay with iframe
            overlay = document.createElement('div');
            overlay.id = 'clp-preview-overlay';
            overlay.style.cssText = 'position:fixed;inset:0;z-index:99998;background:rgba(0,0,0,.85);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;';

            const label = document.createElement('div');
            label.style.cssText = 'color:#fff;font-size:13px;font-family:-apple-system,sans-serif;opacity:.7;';
            label.textContent = _deviceTitles[editDevice] + ' (' + width + '×' + height + ') — Click pe ' + _deviceLabels[editDevice] + ' pentru a ieși';

            const frame = document.createElement('iframe');
            frame.src = location.href;
            frame.style.cssText = 'width:' + width + 'px;height:' + height + 'px;border:2px solid rgba(255,255,255,.2);border-radius:16px;background:#000;';

            overlay.appendChild(label);
            overlay.appendChild(frame);
            document.body.appendChild(overlay);
        }
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

        const value = clpGetValue(selEl, selKey);

        const fd = new FormData();
        fd.append('action', 'save_inline_edit');
        fd.append('key',    selKey);
        fd.append('value',  value);
        fd.append('style',  parts.join(';'));
        fd.append('device', editDevice);

        const btn = document.getElementById('clp-tb-save');
        const errEl = document.getElementById('clp-tb-err');
        btn.textContent = '…';
        errEl.style.display = 'none';
        fetch('/admin/', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(text => {
                btn.textContent = 'Salvează';
                let d;
                try { d = JSON.parse(text); } catch(e) {
                    errEl.textContent = 'Răspuns invalid: ' + text.substring(0, 120);
                    errEl.style.display = 'inline';
                    return;
                }
                if (d.ok) {
                    if (selEl) selEl._clpOrig = value;
                    // Update local style cache so device preview stays correct
                    const styleStr = parts.join(';');
                    if (editDevice === 'tablet') _tabletStyles[selKey] = styleStr;
                    else if (editDevice === 'mobile') _mobileStyles[selKey] = styleStr;
                    const ok = document.getElementById('clp-tb-ok');
                    ok.style.display = 'inline';
                    setTimeout(() => ok.style.display = 'none', 2000);
                } else {
                    errEl.textContent = '✗ Eroare: ok=false, key=' + selKey;
                    errEl.style.display = 'inline';
                }
            })
            .catch(err => {
                btn.textContent = 'Salvează';
                errEl.textContent = '✗ ' + err.message;
                errEl.style.display = 'inline';
            });
    };
})();

// ── Section Background Editor ─────────────────────────────────────────────────
(function(){
    let selSection = null;
    // All images from assets/images + uploads
    const galleryImgs = <?php
        $_sbg_imgs = [];
        $_sbg_dirs = [
            [dirname(__DIR__) . '/assets/images', '/assets/images/'],
            [dirname(__DIR__) . '/assets/images/uploads', '/assets/images/uploads/'],
        ];
        foreach ($_sbg_dirs as [$dir, $prefix]) {
            if (!is_dir($dir)) continue;
            $files = scandir($dir);
            $names = array_map('strtolower', $files);
            foreach ($files as $f) {
                if ($f[0] === '.' || !is_file("$dir/$f")) continue;
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;
                if ($ext === 'webp') {
                    $base = strtolower(pathinfo($f, PATHINFO_FILENAME));
                    if (in_array($base.'.jpg',$names)||in_array($base.'.jpeg',$names)||in_array($base.'.png',$names)) continue;
                }
                $_sbg_imgs[] = $prefix . $f;
            }
        }
        echo json_encode(array_values(array_unique($_sbg_imgs)));
    ?>;

    // Build gallery on init
    (function buildGallery() {
        const gallery = document.getElementById('clp-sbg-gallery');
        if (!gallery || !galleryImgs.length) return;
        galleryImgs.forEach(url => {
            const img = document.createElement('img');
            img.src = url;
            img.alt = '';
            img.onclick = () => {
                document.getElementById('clp-sbg-img').value = url;
                gallery.querySelectorAll('img').forEach(i => i.classList.remove('selected'));
                img.classList.add('selected');
                clpSectionBgApply();
            };
            gallery.appendChild(img);
        });
    })();

    window.clpOpenSectionBg = function(sectionId) {
        selSection = sectionId;
        const section = document.querySelector('[data-section-bg="' + sectionId + '"]');
        document.getElementById('clp-sbg-section-name').textContent = sectionId;

        // Read current values from inline style
        const curImg  = (section && section.style.getPropertyValue('--section-bg-img') || '').replace(/^url\(['"]?|['"]?\)$/g,'');
        const curBlur = parseInt(section && section.style.getPropertyValue('--section-blur')) || 6;
        const curOvRaw = parseFloat(section && section.style.getPropertyValue('--section-overlay'));
        const curOv   = isNaN(curOvRaw) ? 72 : Math.round(curOvRaw * 100);

        document.getElementById('clp-sbg-img').value = curImg;
        document.getElementById('clp-sbg-blur').value = curBlur;
        document.getElementById('clp-sbg-blur-val').textContent = curBlur + 'px';
        document.getElementById('clp-sbg-overlay').value = curOv;
        document.getElementById('clp-sbg-overlay-val').textContent = curOv + '%';

        // Mark selected gallery image
        document.querySelectorAll('#clp-sbg-gallery img').forEach(img => {
            img.classList.toggle('selected', img.src === curImg || img.getAttribute('src') === curImg);
        });

        document.getElementById('clp-sbg-panel').classList.add('visible');
    };

    window.clpSectionBgApply = function() {
        if (!selSection) return;
        const section = document.querySelector('[data-section-bg="' + selSection + '"]');
        if (!section) return;
        const img     = document.getElementById('clp-sbg-img').value.trim();
        const blur    = document.getElementById('clp-sbg-blur').value;
        const overlay = (document.getElementById('clp-sbg-overlay').value / 100).toFixed(2);

        if (img) {
            section.style.setProperty('--section-bg-img', "url('" + img + "')");
            section.style.setProperty('--section-blur', blur + 'px');
            section.style.setProperty('--section-overlay', overlay);
            section.classList.add('section-bg-blur', 'section-dark');
        } else {
            section.style.removeProperty('--section-bg-img');
            section.style.removeProperty('--section-blur');
            section.style.removeProperty('--section-overlay');
            // Only remove section-dark if the section didn't have it originally
            const origDark = section.dataset.origDark === '1';
            if (!origDark) section.classList.remove('section-dark');
            section.classList.remove('section-bg-blur');
        }
    };

    window.clpSaveSectionBg = function() {
        if (!selSection) return;
        const img     = document.getElementById('clp-sbg-img').value.trim();
        const blur    = document.getElementById('clp-sbg-blur').value;
        const overlay = (document.getElementById('clp-sbg-overlay').value / 100).toFixed(2);
        const btn     = document.getElementById('clp-sbg-save');
        const okEl    = document.getElementById('clp-sbg-ok');

        const fd = new FormData();
        fd.append('action',  'save_section_bg');
        fd.append('section', selSection);
        fd.append('image',   img);
        fd.append('blur',    blur);
        fd.append('overlay', overlay);

        btn.textContent = '…';
        fetch('/admin/', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: fd })
            .then(r => r.json())
            .then(d => {
                btn.textContent = 'Salvează';
                if (d.ok) { okEl.style.display = 'inline'; setTimeout(() => okEl.style.display = 'none', 2000); }
            })
            .catch(() => { btn.textContent = 'Salvează'; });
    };

    // Add/remove "🖼 Fundal" buttons when edit mode changes
    // Called from clpToggleEdit
    window.clpToggleSectionBgButtons = function(enable) {
        document.querySelectorAll('[data-section-bg]').forEach(section => {
            // Store original dark state
            if (section.dataset.origDark === undefined) {
                section.dataset.origDark = section.classList.contains('section-dark') ? '1' : '0';
            }
            let btn = section.querySelector('.clp-sbg-btn');
            if (!btn) {
                btn = document.createElement('button');
                btn.className = 'clp-sbg-btn';
                btn.innerHTML = '🖼 Fundal';
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    clpOpenSectionBg(section.dataset.sectionBg);
                });
                // Make section relative if not already
                const pos = window.getComputedStyle(section).position;
                if (pos === 'static') section.style.position = 'relative';
                section.appendChild(btn);
            }
        });
        // CSS handles show/hide via body.clp-edit-mode .clp-sbg-btn
        if (!enable) {
            document.getElementById('clp-sbg-panel').classList.remove('visible');
        }
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

    window.clpFontApply = function() {
        const fhFam = document.getElementById('clp-fp-heading').value;
        const fbFam = document.getElementById('clp-fp-body').value;
        const fhW   = document.getElementById('clp-fp-fh-weight').value;
        const fhI   = document.getElementById('clp-fp-fh-italic').classList.contains('on');
        const fhLg  = document.getElementById('clp-fp-fh-lg').value;
        const fhMd  = document.getElementById('clp-fp-fh-md').value;
        const fhSm  = document.getElementById('clp-fp-fh-sm').value;
        const fbW   = document.getElementById('clp-fp-fb-weight').value;
        const fbLg  = document.getElementById('clp-fp-fb-lg').value;
        const fbMd  = document.getElementById('clp-fp-fb-md').value;
        const fbSm  = document.getElementById('clp-fp-fb-sm').value;

        clpLoadFont(fhFam);
        clpLoadFont(fbFam);

        // Apply CSS variables
        document.documentElement.style.setProperty('--font-heading', "'" + fhFam + "', sans-serif");
        document.documentElement.style.setProperty('--font-sans', "'" + fbFam + "', system-ui, sans-serif");

        // Inject responsive overrides via a dynamic style tag
        let styleEl = document.getElementById('clp-typo-override');
        if (!styleEl) { styleEl = document.createElement('style'); styleEl.id = 'clp-typo-override'; document.head.appendChild(styleEl); }
        let css = '';
        const hSel = '.section-title, h1, h2, h3';
        if (fhW || fhI || fhLg) {
            css += hSel + '{';
            if (fhW)  css += 'font-weight:' + fhW + '!important;';
            if (fhI)  css += 'font-style:italic!important;';
            else      css += 'font-style:normal!important;';
            if (fhLg) css += 'font-size:' + fhLg + 'px!important;';
            css += '}';
        }
        if (fhMd) css += '@media(max-width:1024px){' + hSel + '{font-size:' + fhMd + 'px!important;}}';
        if (fhSm) css += '@media(max-width:768px){'  + hSel + '{font-size:' + fhSm + 'px!important;}}';
        if (fbW || fbLg) {
            css += 'body,p{';
            if (fbW)  css += 'font-weight:' + fbW + '!important;';
            if (fbLg) css += 'font-size:' + fbLg + 'px!important;';
            css += '}';
        }
        if (fbMd) css += '@media(max-width:1024px){body{font-size:' + fbMd + 'px!important;}}';
        if (fbSm) css += '@media(max-width:768px){body{font-size:'  + fbSm + 'px!important;}}';
        styleEl.textContent = css;

        // Update preview
        const ph = document.getElementById('clp-prev-h');
        ph.style.fontFamily  = "'" + fhFam + "', sans-serif";
        ph.style.fontWeight  = fhW  || '';
        ph.style.fontStyle   = fhI  ? 'italic' : 'normal';
        ph.style.fontSize    = fhLg ? fhLg + 'px' : '';
        const pb = document.getElementById('clp-prev-b');
        pb.style.fontFamily  = "'" + fbFam + "', system-ui, sans-serif";
        pb.style.fontWeight  = fbW  || '';
        pb.style.fontSize    = fbLg ? fbLg + 'px' : '';
    };

    window.clpSaveFonts = function() {
        const btn = document.getElementById('clp-fp-save');
        btn.textContent = '…';
        const fd = new FormData();
        fd.append('action',       'save_global_fonts');
        fd.append('font_heading', document.getElementById('clp-fp-heading').value);
        fd.append('font_body',    document.getElementById('clp-fp-body').value);
        fd.append('fh_weight',    document.getElementById('clp-fp-fh-weight').value);
        fd.append('fh_italic',    document.getElementById('clp-fp-fh-italic').classList.contains('on') ? '1' : '');
        fd.append('fh_size_lg',   document.getElementById('clp-fp-fh-lg').value);
        fd.append('fh_size_md',   document.getElementById('clp-fp-fh-md').value);
        fd.append('fh_size_sm',   document.getElementById('clp-fp-fh-sm').value);
        fd.append('fb_weight',    document.getElementById('clp-fp-fb-weight').value);
        fd.append('fb_size_lg',   document.getElementById('clp-fp-fb-lg').value);
        fd.append('fb_size_md',   document.getElementById('clp-fp-fb-md').value);
        fd.append('fb_size_sm',   document.getElementById('clp-fp-fb-sm').value);
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
