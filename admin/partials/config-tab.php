<?php require_once dirname(__DIR__, 2) . '/lib/recurring.php'; ?>
<h1 class="wp-page-title">Setări</h1>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Setările au fost salvate.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="notice notice-error">Parolele nu coincid sau sunt prea scurte (minim 6 caractere).</div>
<?php endif; ?>
<?php if (isset($_GET['imported'])): ?>
<div class="notice notice-success">Import reușit! <?= (int)$_GET['imported'] ?> imagini descărcate.</div>
<?php endif; ?>

<!-- Quick links editor (Owner only) -->
<div class="card">
    <div class="card-title">🔗 Linkuri rapide — Dashboard</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">Aceste linkuri apar ca butoane în partea de sus a dashboard-ului.</p>
    <form method="post" action="/admin/?tab=config" id="qlForm">
        <input type="hidden" name="action" value="save_quick_links">
        <div id="qlRows" style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
        <?php foreach ($settings['quick_links'] ?? [] as $idx => $_ql): ?>
            <div class="ql-row" style="display:grid;grid-template-columns:60px 1fr 3fr auto;gap:8px;align-items:center">
                <input type="text" name="ql_icon[]" value="<?= h($_ql['icon'] ?? '🔗') ?>" style="text-align:center;font-size:18px">
                <input type="text" name="ql_label[]" value="<?= h($_ql['label'] ?? '') ?>">
                <input type="text" name="ql_url[]" value="<?= h($_ql['url'] ?? '') ?>">
                <button type="button" onclick="this.closest('.ql-row').remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">✕</button>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" onclick="addQlRow()" class="btn btn-secondary btn-sm">+ Adaugă link</button>
            <button type="submit" class="btn btn-primary btn-sm">Salvează</button>
        </div>
    </form>
</div>

<!-- Recurring tasks (Owner only) -->
<style>
.rec-card { position:relative; border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:14px; background:var(--bg-warm); }
.rec-top { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; padding-right:80px; }
.rec-top .rec-title { flex:1; min-width:220px; }
.rec-assignee { font-weight:600; border:none; border-radius:999px; padding:6px 14px; cursor:pointer; font-size:13px; }
.rec-assignee.a-eric6 { background:#eff6ff; color:#2563eb; }
.rec-assignee.a-andy  { background:#f0fdf4; color:#16a34a; }
.rec-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); margin-bottom:8px; }
.rec-days { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:14px; }
.rec-day-sel { padding:7px 10px; }
.rec-add-day { background:none; border:1px dashed var(--border-strong); border-radius:8px; padding:7px 12px; font-size:13px; font-weight:600; color:var(--accent); cursor:pointer; }
.rec-del { position:absolute; top:14px; right:14px; margin:0; }
.rec-auto { position:absolute; top:14px; right:16px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); background:#fff; border:1px solid var(--border); border-radius:6px; padding:3px 8px; }
.rec-pill { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 13px; font-size:13px; font-weight:600; white-space:nowrap; flex-shrink:0; }
.rec-pill.a-eric6 { background:#eff6ff; color:#2563eb; }
.rec-pill.a-andy  { background:#f0fdf4; color:#16a34a; }
.rec-pill .dot { width:8px; height:8px; border-radius:50%; background:currentColor; }
.rec-sys { display:flex; gap:14px; align-items:flex-start; padding:14px; border:1px solid var(--border); border-radius:10px; margin-bottom:10px; background:#fff; }
.rec-sys-body { flex:1; min-width:0; }
.rec-sys-meta { display:flex; align-items:center; gap:8px; margin-top:7px; flex-wrap:wrap; }
.rec-sys-badge { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#92400e; background:#fef3c7; border-radius:6px; padding:3px 8px; white-space:nowrap; }
.rec-sys-desc { font-size:12px; color:var(--text-muted); }
.rec-view-top { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px; padding-right:90px; }
.rec-view-title { font-size:15px; font-weight:600; color:var(--text); }
.rec-view-meta { font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.rec-view-days { font-weight:600; color:var(--text); }
.rec-edit-actions { display:flex; gap:8px; }
</style>

<div class="card" id="rec">
    <div class="card-title">🔁 Taskuri recurente</div>
    <?php if (isset($_GET['rec'])): ?>
        <?php if ($_GET['rec'] === 'ok'): ?>
            <div class="notice notice-success" style="margin-bottom:14px">Salvat ✓ (<?= count(clp_recurring_monthly()) ?> taskuri lunare)</div>
        <?php elseif ($_GET['rec'] === 'perm'): ?>
            <div class="notice notice-error" style="margin-bottom:14px">Folderul <code>data/</code> nu e scriibil pe server (permisiuni). Trebuie 755/775 pe <code>data/</code>.</div>
        <?php else: ?>
            <div class="notice notice-error" style="margin-bottom:14px">Nu am putut scrie <code>data/recurring_tasks.json</code>.</div>
        <?php endif; ?>
    <?php endif; ?>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:18px">Apar automat în To-dos la persoana aleasă. Cele lunare au zilele alese de tine; cele marcate „automat" au programare fixă (poți schimba doar numele).</p>

    <?php foreach (clp_load_recurring() as $_rt):
        $_type  = $_rt['type'] ?? 'monthly';
        $_asg   = $_rt['assigned_to'] ?? 'eric6';
        $_aname = $_asg === 'eric6' ? 'Eric' : ucfirst($_asg);
        $_pill  = '<span class="rec-pill a-' . h($_asg) . '"><span class="dot"></span>' . h($_aname) . '</span>'; ?>

        <?php if ($_type === 'monthly'):
            $_realdays = array_values(array_filter(array_map('intval', $_rt['days'] ?? [])));
            sort($_realdays);
            $_editdays = $_realdays ?: [0]; ?>
        <div class="rec-card">
            <!-- read-only view -->
            <div class="rec-view">
                <div class="rec-view-top">
                    <span class="rec-view-title"><?= h($_rt['title'] ?? '') ?></span>
                    <?= $_pill ?>
                </div>
                <div class="rec-view-meta">Lunar · <?= $_realdays ? 'zilele <span class="rec-view-days">' . implode(', ', $_realdays) . '</span>' : '<em>nicio zi aleasă</em>' ?></div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="recEdit(this)">Editează</button>
            </div>
            <!-- edit form -->
            <div class="rec-edit" hidden>
                <form method="post" action="/admin/?tab=config">
                    <input type="hidden" name="action" value="save_recurring">
                    <input type="hidden" name="id" value="<?= h($_rt['id'] ?? '') ?>">
                    <div class="rec-top">
                        <input type="text" name="title" value="<?= h($_rt['title'] ?? '') ?>" class="rec-title" required>
                        <select name="assigned_to" class="rec-assignee a-<?= h($_asg) ?>" onchange="this.className='rec-assignee a-'+this.value">
                            <?php foreach (($all_users ?? load_users()) as $_u): $un = $_u['username']; ?>
                            <option value="<?= h($un) ?>" <?= $_asg === $un ? 'selected' : '' ?>><?= h($un === 'eric6' ? 'Eric' : ucfirst($un)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rec-label">Zile din lună</div>
                    <div class="rec-days">
                        <?php foreach ($_editdays as $_sel): ?>
                        <select name="days[]" class="rec-day-sel">
                            <option value="">— zi —</option>
                            <?php for ($d = 1; $d <= 31; $d++): ?><option value="<?= $d ?>" <?= (int)$_sel === $d ? 'selected' : '' ?>><?= $d ?></option><?php endfor; ?>
                        </select>
                        <?php endforeach; ?>
                        <button type="button" class="rec-add-day" onclick="recAddDay(this)">+ zi</button>
                    </div>
                    <div class="rec-edit-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Salvează</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="recCancel(this)">Anulează</button>
                    </div>
                </form>
                <form method="post" action="/admin/?tab=config" style="margin-top:10px" onsubmit="return confirm('Ștergi taskul recurent?')">
                    <input type="hidden" name="action" value="delete_recurring">
                    <input type="hidden" name="id" value="<?= h($_rt['id'] ?? '') ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Șterge taskul</button>
                </form>
            </div>
        </div>

        <?php else: // system / automatic ?>
        <div class="rec-card">
            <span class="rec-auto">⚙︎ automat</span>
            <div class="rec-view">
                <div class="rec-view-top">
                    <span class="rec-view-title"><?= h($_rt['title'] ?? '') ?></span>
                    <?= $_pill ?>
                </div>
                <div class="rec-view-meta">
                    <span class="rec-sys-badge"><?= h($_rt['schedule'] ?? 'auto') ?></span>
                    <span class="rec-sys-desc"><?= h($_rt['description'] ?? '') ?></span>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="recEdit(this)">Editează numele</button>
            </div>
            <div class="rec-edit" hidden>
                <form method="post" action="/admin/?tab=config">
                    <input type="hidden" name="action" value="save_recurring_system">
                    <div class="rec-label">Nume task</div>
                    <input type="text" name="sys_title[<?= h($_rt['id'] ?? '') ?>]" value="<?= h($_rt['title'] ?? '') ?>" style="width:100%;margin-bottom:14px">
                    <div class="rec-edit-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Salvează</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="recCancel(this)">Anulează</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <form method="post" action="/admin/?tab=config" style="margin-top:4px">
        <input type="hidden" name="action" value="add_recurring">
        <button type="submit" class="btn btn-secondary btn-sm">+ Adaugă task recurent</button>
    </form>
</div>

<script>
function recAddDay(btn) {
    var sel = document.createElement('select');
    sel.name = 'days[]'; sel.className = 'rec-day-sel';
    var html = '<option value="">— zi —</option>';
    for (var d = 1; d <= 31; d++) html += '<option value="' + d + '">' + d + '</option>';
    sel.innerHTML = html;
    btn.parentNode.insertBefore(sel, btn);
}
function recEdit(btn) {
    var card = btn.closest('.rec-card');
    card.querySelector('.rec-view').hidden = true;
    card.querySelector('.rec-edit').hidden = false;
    var inp = card.querySelector('.rec-edit input[type=text]');
    if (inp) inp.focus();
}
function recCancel(btn) {
    var card = btn.closest('.rec-card');
    card.querySelector('.rec-edit').hidden = true;
    card.querySelector('.rec-view').hidden = false;
}
</script>


<form method="post" action="/admin/?tab=config">
    <input type="hidden" name="action" value="save_kit">
    <div class="card">
        <div class="card-title">📧 Kit (Email Marketing)</div>
        <div class="form-group">
            <label>API Key</label>
            <input type="text" name="kit_api_key" value="<?= h($settings['kit_api_key'] ?? '') ?>" placeholder="kit_...">
            <p class="form-desc">Găsești API Key-ul în <a href="https://app.kit.com/account_settings/developer_settings" target="_blank" style="color:var(--accent)">Kit → Settings → Developer</a>.</p>
        </div>
        <div class="form-group">
            <label>Form ID (opțional)</label>
            <input type="text" name="kit_form_id" value="<?= h($settings['kit_form_id'] ?? '') ?>" placeholder="ex: 1234567">
            <p class="form-desc">Dacă vrei să adaugi abonații la un form specific. Lasă gol pentru a adăuga direct ca subscriber.</p>
        </div>
        <button type="submit" class="btn btn-primary">Salvează</button>
    </div>
</form>

<!-- Analytics -->
<form method="post" action="/admin/?tab=config">
    <input type="hidden" name="action" value="save_head_scripts">
    <div class="card">
        <div class="card-title">📊 Analytics &amp; Tracking</div>
        <div class="form-group">
            <label>Cod <code>&lt;head&gt;</code></label>
            <textarea name="head_scripts" rows="10" style="font-family:monospace;font-size:12px;line-height:1.7"><?= htmlspecialchars($settings['head_scripts'] ?? '') ?></textarea>
            <p class="form-desc">
                Lipește aici codul de tracking pentru <strong>Umami</strong>, <strong>Google Analytics (GA4)</strong> sau orice alt script.
                Va fi inserat automat în <code>&lt;head&gt;</code> pe <strong>toate paginile</strong> site-ului.<br>
                <span style="color:#d63638">⚠ Codul este inserat fără filtrare — adaugă doar scripturi de încredere.</span>
            </p>
        </div>
        <button type="submit" class="btn btn-primary">Salvează</button>
    </div>
</form>

<!-- Schimba parola -->
<div class="card">
    <div class="card-title">🔒 Schimbă parola de admin</div>
    <form method="post" action="/admin/?tab=config" style="max-width:400px">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
            <label for="new_password">Parolă nouă</label>
            <input type="password" id="new_password" name="new_password" placeholder="Minim 6 caractere" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmă parola</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repetă parola" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Schimbă parola</button>
    </form>
    <p class="form-desc" style="margin-top:12px">Parola este salvată în <code>data/settings.json</code> și nu apare nicăieri în cod sau Git.</p>
</div>

<!-- Sync token (pentru sync.sh local) -->
<div class="card">
    <div class="card-title">🔄 Sync Token</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
        Folosit de scriptul <code>./sync.sh</code> pentru a sincroniza datele din producție în mediul local.
        Pune valoarea într-un fișier <code>.sync-token</code> în root-ul proiectului local.
    </p>
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
        <input type="text" id="sync_token_input" value="<?= h($settings['sync_token'] ?? '') ?>" readonly style="font-family:monospace;font-size:12px;flex:1">
        <button type="button" class="btn btn-secondary btn-sm" onclick="copySyncToken()">Copiază</button>
        <form method="post" action="/admin/?tab=config" style="margin:0" onsubmit="return confirm('Regenerezi tokenul? Va trebui să-l actualizezi local.')">
            <input type="hidden" name="action" value="regenerate_sync_token">
            <button type="submit" class="btn btn-secondary btn-sm">Regenerează</button>
        </form>
    </div>
    <p class="form-desc" style="margin:0">Conținut <code>.sync-token</code>:</p>
    <pre style="background:#f5f5f5;padding:10px;border-radius:4px;font-size:12px;margin:6px 0 0;user-select:all">SYNC_URL=https://cursurilapahar.ro/admin/sync-export.php
SYNC_TOKEN=<?= h($settings['sync_token'] ?? '') ?></pre>
</div>
