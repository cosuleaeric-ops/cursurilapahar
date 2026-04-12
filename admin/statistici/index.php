<?php
declare(strict_types=1);
require __DIR__ . '/../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }

$__page_title = 'Statistici';
include __DIR__ . '/layout_header.php';
?>
<style>
.tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; max-width: 860px; }
.tool-card {
    display: flex; flex-direction: column; align-items: flex-start; gap: 6px;
    background: var(--surface); border: 1px solid var(--border); border-radius: 4px;
    padding: 22px 20px; text-decoration: none; color: var(--text);
    transition: border-color .15s, box-shadow .15s;
}
.tool-card:hover { border-color: var(--accent); box-shadow: 0 1px 6px rgba(0,0,0,.08); }
.tool-icon { font-size: 24px; line-height: 1; margin-bottom: 2px; }
.tool-name { font-size: 15px; font-weight: 600; }
.tool-desc { font-size: 12px; color: var(--text-muted); line-height: 1.4; }
</style>
<?php include __DIR__ . '/layout_nav.php'; ?>

        <h1 class="wp-page-title">Statistici</h1>

        <div class="tools-grid">
            <a class="tool-card" href="/admin/statistici/cursuri/">
                <div class="tool-icon">📋</div>
                <div class="tool-name">Cursuri</div>
                <div class="tool-desc">toate cursurile, participanti, distributie bilete si viza</div>
            </a>
            <a class="tool-card" href="/admin/statistici/participanti/">
                <div class="tool-icon">👥</div>
                <div class="tool-name">Participanti</div>
                <div class="tool-desc">agregat complet — cine a venit si de cate ori revine</div>
            </a>
            <a class="tool-card" href="/admin/statistici/pnl/">
                <div class="tool-icon">📈</div>
                <div class="tool-name">P&amp;L Cursuri</div>
                <div class="tool-desc">venituri, cheltuieli si profit net pe luni si categorii</div>
            </a>
        </div>

    </main>
</div>
</body>
</html>
