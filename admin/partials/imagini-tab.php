    <h1 class="wp-page-title">Imagini</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success">Setările imaginilor au fost salvate.</div>
    <?php endif; ?>

    <?php if (!empty($upload_ok ?? '')): ?>
    <div class="notice notice-success"><?= h($upload_ok) ?></div>
    <?php endif; ?>
    <?php if (!empty($upload_error ?? '')): ?>
    <div class="notice notice-error"><?= h($upload_error) ?></div>
    <?php endif; ?>

    <!-- Upload -->
    <div class="card">
        <div class="card-title">Încarcă imagine nouă</div>
        <form method="post" action="/admin/?tab=imagini" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_image">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="file" name="image_files[]" accept="image/*" multiple style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
                <button type="submit" class="btn btn-primary">Încarcă</button>
            </div>
            <p class="form-desc">Formate acceptate: JPG, PNG, WEBP, GIF. Poți selecta mai multe fișiere. Imaginile sunt convertite automat în WebP și redimensionate la max 1920px.</p>
        </form>
    </div>

    <!-- Images grid with hero selection -->
    <?php $all_images = get_all_images(); ?>
    <div class="card">
        <div class="card-title">Toate imaginile</div>
        <?php if (empty($all_images)): ?>
        <p style="color:var(--text-muted)">Nu există imagini.</p>
        <?php else: ?>
        <form method="post" action="/admin/?tab=imagini">
            <input type="hidden" name="action" value="save_hero_images">
            <div class="images-grid">
                <?php foreach ($all_images as $img):
                    $is_hero    = in_array($img['url'], $settings['hero_images'] ?? []);
                    $is_gallery = in_array($img['url'], $settings['gallery_featured'] ?? []);
                ?>
                <div class="image-item">
                    <img src="<?= h($img['url']) ?>" alt="<?= h($img['name']) ?>">
                    <div class="image-item-body">
                        <div class="image-item-name"><?= h($img['name']) ?></div>
                        <div class="image-item-actions">
                            <label class="hero-check">
                                <input type="checkbox" name="hero_images[]" value="<?= h($img['url']) ?>" <?= $is_hero ? 'checked' : '' ?>>
                                Hero
                            </label>
                            <label class="hero-check" style="color:#C9A84C">
                                <input type="checkbox" name="gallery_featured[]" value="<?= h($img['url']) ?>" <?= $is_gallery ? 'checked' : '' ?>>
                                Galerie
                            </label>
                            <?php if ($img['deletable']): ?>
                            <button type="button" class="btn btn-sm btn-danger" style="padding:1px 7px"
                                onclick="deleteImage(<?= json_encode($img['name']) ?>)">✕</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary">Salvează</button>
                <span style="font-size:12px;color:var(--text-muted);margin-left:10px">Hero = slideshow pagină principală &nbsp;·&nbsp; Galerie = slider secțiunea Galerie.</span>
            </div>
        </form>
        <?php endif; ?>
    </div>
