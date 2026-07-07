    <h1 class="wp-page-title">Imagini</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success">Setările imaginilor au fost salvate.</div>
    <?php endif; ?>
    <?php if (isset($_GET['err']) && $_GET['err'] === 'save'): ?>
    <div class="notice notice-error">Salvarea a eșuat — nu s-a putut scrie fișierul de setări pe server. Modificările NU au fost aplicate.</div>
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
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input type="file" name="image_files[]" accept="image/*" multiple style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
                <button type="submit" class="btn btn-primary">Încarcă</button>
            </div>
            <p class="form-desc">JPG, PNG, WEBP, GIF. Poți selecta mai multe. Convertite automat în WebP și redimensionate la max 1920px. După încărcare apar primele în Bibliotecă.</p>
        </form>
    </div>

    <?php
        $all_images  = $all_images ?? get_all_images();
        $hero_sel    = array_values($settings['hero_images'] ?? []);
        $gallery_sel = array_values($settings['gallery_featured'] ?? []);
        // Hărți url → nume / thumb pentru benzile de selecție (JS)
        $img_names  = [];
        $img_thumbs = [];
        foreach ($all_images as $im) {
            $img_names[$im['url']]  = $im['name'];
            $img_thumbs[$im['url']] = $im['thumb'] ?? $im['url'];
        }
    ?>

    <!-- Selecție Hero + Galerie -->
    <form method="post" action="/admin/?tab=imagini" id="img-form">
        <input type="hidden" name="action" value="save_hero_images">

        <div class="card img-select-card">
            <div class="card-title">Hero — slideshow pagina principală</div>
            <p class="img-hint">Trage pentru a reordona. <strong>Imaginea ① se încarcă instant</strong>; restul rulează în slideshow la fiecare 4.5s. Adaugă/scoate imagini din Bibliotecă (butonul <span class="img-hint-chip">Hero</span>).</p>
            <div class="img-strip" id="hero-strip" data-target="hero"></div>
        </div>

        <div class="card img-select-card">
            <div class="card-title">Galerie — sliderul din secțiunea „Galerie"</div>
            <p class="img-hint">Trage pentru a reordona. Adaugă/scoate imagini din Bibliotecă (butonul <span class="img-hint-chip img-hint-chip-gal">Galerie</span>).</p>
            <div class="img-strip" id="gallery-strip" data-target="gallery"></div>
        </div>

        <div class="img-save-bar">
            <button type="submit" class="btn btn-primary">Salvează selecția</button>
            <span class="img-dirty" id="img-dirty" hidden>● Modificări nesalvate</span>
        </div>
        <div id="img-hidden"></div>
    </form>

    <!-- Biblioteca -->
    <div class="card">
        <div class="card-title">Biblioteca — toate imaginile (cele mai noi primele)</div>
        <?php if (empty($all_images)): ?>
        <p style="color:var(--text-muted)">Nu există imagini.</p>
        <?php else: ?>
        <div class="img-lib">
            <?php foreach ($all_images as $img): ?>
            <div class="img-tile" data-url="<?= h($img['url']) ?>">
                <div class="img-tile-thumb">
                    <img loading="lazy" decoding="async" src="<?= h($img['thumb'] ?? $img['url']) ?>" alt="<?= h($img['name']) ?>">
                    <?php if ($img['deletable']): ?>
                    <button type="button" class="img-tile-del" title="Șterge imaginea"
                        data-del="<?= h($img['name']) ?>">✕</button>
                    <?php endif; ?>
                </div>
                <div class="img-tile-chips">
                    <button type="button" class="img-chip" data-role="hero" data-url="<?= h($img['url']) ?>">Hero</button>
                    <button type="button" class="img-chip img-chip-gal" data-role="gallery" data-url="<?= h($img['url']) ?>">Galerie</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        window.CLP_HERO    = <?= json_encode($hero_sel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.CLP_GALLERY = <?= json_encode($gallery_sel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.CLP_NAMES   = <?= json_encode($img_names, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.CLP_THUMBS  = <?= json_encode($img_thumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
