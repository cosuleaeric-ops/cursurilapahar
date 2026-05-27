<?php
/** @var array<string, array{label: string, icon: string}> $categories */
/** @var array<string, list<array>> $grouped */
/** @var array<string, int> $tab_counts */
?>
<div class="msg-tabs">
<?php foreach ($categories as $key => $cat): $cnt = $tab_counts[$key]; ?>
    <button class="msg-tab <?= $key === 'sustine' ? 'active' : '' ?>" data-key="<?= h($key) ?>" onclick="showMsgTab('<?= $key ?>')">
        <?= $cat['icon'] ?> <?= $cat['label'] ?><span class="msg-count"<?= $cnt ? '' : ' style="display:none"' ?>><?= $cnt ?></span>
    </button>
<?php endforeach; ?>
</div>

<?php foreach ($categories as $key => $cat): ?>
<div class="msg-panel <?= $key === 'sustine' ? 'active' : '' ?>" id="msg-panel-<?= $key ?>">
<?php if (empty($grouped[$key])): ?>
    <div class="card"><p class="msg-empty">Niciun mesaj în această categorie.</p></div>
<?php elseif ($key === 'sustine'):
    $pending   = [];
    $evaluated = [];
    foreach ($grouped[$key] as $i => $msg) {
        if (!empty($msg['meta']['evaluation'])) $evaluated[] = [$i, $msg];
        else                                    $pending[]   = [$i, $msg];
    }
?>
    <div class="msg-section">
        <h3 class="msg-section-title">🤔 De evaluat (<?= count($pending) ?>)</h3>
        <?php if (empty($pending)): ?>
            <p class="msg-empty">Nimic de evaluat.</p>
        <?php else: ?>
            <div class="msg-cards">
            <?php foreach ($pending as [$i, $msg]) clp_render_message_card($key, $i, $msg, is_owner(), 'h'); ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="msg-section">
        <h3 class="msg-section-title">✅ Evaluați (<?= count($evaluated) ?>)</h3>
        <?php if (empty($evaluated)): ?>
            <p class="msg-empty">Niciun candidat evaluat încă.</p>
        <?php else: ?>
            <div class="msg-eval-filter">
                <button type="button" class="msg-eval-filter-btn active" data-filter="all"  onclick="filterEval(this)">Toți</button>
                <button type="button" class="msg-eval-filter-btn" data-filter="nope" onclick="filterEval(this)">⛔ Nope</button>
                <button type="button" class="msg-eval-filter-btn" data-filter="meh"  onclick="filterEval(this)">🤔 Meh</button>
                <button type="button" class="msg-eval-filter-btn" data-filter="top"  onclick="filterEval(this)">✅ Top</button>
                <button type="button" class="msg-eval-filter-btn" data-filter="contactat" onclick="filterEval(this)">📋 Contactați</button>
            </div>
            <div class="msg-cards">
            <?php foreach ($evaluated as [$i, $msg]) clp_render_message_card($key, $i, $msg, is_owner(), 'h'); ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="msg-cards">
    <?php foreach ($grouped[$key] as $i => $msg) clp_render_message_card($key, $i, $msg, is_owner(), 'h'); ?>
    </div>
<?php endif; ?>
</div>
<?php endforeach; ?>
