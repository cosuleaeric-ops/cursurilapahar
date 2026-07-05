<h1 class="wp-page-title">Aspect</h1>
<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Setările de aspect au fost salvate.</div>
<?php endif; ?>

<!-- Logo -->
<div class="card">
    <div class="card-title">Logo</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">Logo curent: <code><?= h($settings['logo_path'] ?? '') ?></code></p>
    <?php if (!empty($settings['logo_path'])): ?>
    <img src="<?= h($settings['logo_path']) ?>" alt="Logo" style="max-height:60px;margin-bottom:12px;display:block;background:#1d2327;padding:8px;border-radius:4px;">
    <?php endif; ?>
    <form method="post" action="/admin/?tab=aspect" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_logo">
        <div style="display:flex;gap:8px;align-items:center">
            <input type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.svg" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
            <button type="submit" class="btn btn-primary">Încarcă logo</button>
        </div>
        <p class="form-desc">Formate: JPG, PNG, WEBP, SVG.</p>
    </form>
</div>

<!-- Favicon -->
<div class="card">
    <div class="card-title">Favicon</div>
    <?php if (!empty($settings['favicon_path'])): ?>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">Favicon curent: <code><?= h($settings['favicon_path']) ?></code></p>
    <?php endif; ?>
    <?php if (!empty($favicon_error)): ?>
    <div style="background:#fcf0f1;border:1px solid #f5c6cb;color:#c0392b;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:12px"><?= $favicon_error ?></div>
    <?php endif; ?>
    <form method="post" action="/admin/?tab=aspect" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_favicon">
        <div style="display:flex;gap:8px;align-items:center">
            <input type="file" name="favicon_file" accept=".ico,.png,.jpg,.jpeg,.webp" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
            <button type="submit" class="btn btn-primary">Încarcă favicon</button>
        </div>
        <p class="form-desc">Formate: ICO, PNG, JPG, WEBP. Fișierul va fi salvat în rădăcina site-ului.</p>
    </form>
</div>

<!-- Culori -->
<form method="post" action="/admin/?tab=aspect">
    <input type="hidden" name="action" value="save_design">
    <div class="card" style="margin-top:20px">
        <div class="card-title">Culori</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
            <?php foreach (clp_design_color_fields() as $fname => $meta):
                $val = h($settings[$fname] ?? $meta['default']);
            ?>
            <div class="form-group" style="margin:0">
                <label><?= $meta['label'] ?></label>
                <input type="text" name="<?= $fname ?>" value="<?= $val ?>" data-coloris>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary">Salvează design</button>
    </div>
</form>
