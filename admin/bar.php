<?php
// Admin bar — shown on public pages when logged in as admin
function clp_is_admin(): bool {
    $cookie = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie || !str_contains($cookie, ':')) return false;
    $settings_file = dirname(__DIR__) . '/data/settings.json';
    $s = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
    $secret = $s['auth_secret'] ?? '';
    if (!$secret) return false;
    [$uname, $token] = explode(':', $cookie, 2);
    $expected = hash_hmac('sha256', 'clp_user:' . $uname, $secret);
    if (!hash_equals($expected, $token)) return false;
    $users_file = dirname(__DIR__) . '/data/users.json';
    if (!file_exists($users_file)) return false;
    $users = json_decode(file_get_contents($users_file), true) ?: [];
    foreach ($users as $u) {
        if (($u['username'] ?? '') === $uname) return ($u['role'] ?? '') === 'owner';
    }
    return false;
}
if (!clp_is_admin()) return;
$current = $_SERVER['REQUEST_URI'] ?? '/';
$_clp_s  = file_exists(dirname(__DIR__) . '/data/settings.json')
    ? (json_decode(file_get_contents(dirname(__DIR__) . '/data/settings.json'), true) ?: []) : [];
$_clp_fh = $_clp_s['font_heading'] ?? 'Nunito';
$_clp_fb = $_clp_s['font_body']    ?? 'Inter';
$_clp_nav = [
    'nav_bg'           => $_clp_s['nav_bg']           ?? '#000000',
    'nav_brand_color'  => $_clp_s['nav_brand_color']  ?? '#ffffff',
    'nav_brand_size'   => $_clp_s['nav_brand_size']   ?? '20',
    'nav_brand_weight' => $_clp_s['nav_brand_weight'] ?? '800',
    'nav_brand_font'   => $_clp_s['nav_brand_font']   ?? 'Poppins',
    'nav_link_color'   => $_clp_s['nav_link_color']   ?? '#ffffff',
    'nav_link_weight'  => $_clp_s['nav_link_weight']  ?? '700',
    'nav_link_size'    => $_clp_s['nav_link_size']    ?? '13',
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
body { padding-top: 120px !important; } /* 32px admin bar + 88px navbar */
.navbar { top: 32px !important; }

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
    <a href="/admin/?tab=aspect">🎨 Aspect</a>
    <a href="/admin/?tab=imagini">🖼 Imagini</a>
    <a href="/admin/?tab=mesaje">💬 Mesaje</a>
    <a href="/admin/?tab=vot">❤️ Vot</a>
    <span class="bar-sep"></span>
    <button class="bar-link" id="clp-fonts-btn" onclick="clpToggleFontPanel()">🔤 Fonturi</button>
    <button class="bar-link" id="clp-navbar-btn" onclick="clpToggleNavbarPanel()">🖊 Navbar</button>
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
        <label>Mărime link-uri</label>
        <input type="number" id="clp-np-link-size" value="<?= htmlspecialchars($_clp_nav['nav_link_size']) ?>" min="8" max="40" oninput="clpNavApply()"> <span class="np-unit">px</span>
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

<script>
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

        document.documentElement.style.setProperty('--font-heading', "'" + fhFam + "', sans-serif");
        document.documentElement.style.setProperty('--font-sans', "'" + fbFam + "', system-ui, sans-serif");

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
        if (open) {
            document.getElementById('clp-font-panel').classList.remove('visible');
            document.getElementById('clp-fonts-btn').classList.remove('active');
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
        const linkSize    = document.getElementById('clp-np-link-size').value;
        const linkWeight  = document.getElementById('clp-np-link-weight').value;

        root.style.setProperty('--nav-bg',           bg);
        root.style.setProperty('--nav-logo-h',       logoH + 'px');
        root.style.setProperty('--nav-brand-font',   "'" + brandFont + "', sans-serif");
        root.style.setProperty('--nav-brand-size',   brandSize + 'px');
        root.style.setProperty('--nav-brand-weight', brandWeight);
        root.style.setProperty('--nav-brand-color',  brandColor);
        root.style.setProperty('--nav-link-color',   linkColor);
        root.style.setProperty('--nav-link-size',    linkSize + 'px');
        root.style.setProperty('--nav-link-weight',  linkWeight);

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
        fd.append('nav_link_size',    document.getElementById('clp-np-link-size').value);
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
