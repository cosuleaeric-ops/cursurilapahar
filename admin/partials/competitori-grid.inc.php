<div class="comp-grid">
<?php foreach ($_competitors as $_c): ?>
<div class="comp-card">
    <div class="comp-card-name"><?= h($_c['name']) ?></div>
    <div class="comp-card-links">
        <?php if ($_c['ig']): ?>
        <a href="<?= h($_c['ig']) ?>" target="_blank" rel="noopener" class="comp-link comp-link-ig">📸 Instagram</a>
        <?php endif; ?>
        <?php if ($_c['tt']): ?>
        <a href="<?= h($_c['tt']) ?>" target="_blank" rel="noopener" class="comp-link comp-link-tt">🎵 TikTok</a>
        <?php endif; ?>
        <?php if ($_c['web']): ?>
        <a href="<?= h($_c['web']) ?>" target="_blank" rel="noopener" class="comp-link comp-link-web">🌐 Website</a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
