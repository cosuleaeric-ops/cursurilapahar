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
