<?php
declare(strict_types=1);
require __DIR__ . '/../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }

$__page_title = 'Statistici';
include __DIR__ . '/layout_header.php';
?>
<style>
.btn-export {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: var(--accent, #2A7D4F); color: #fff;
    border-radius: 6px; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: opacity .15s;
}
.btn-export:hover { opacity: .85; }
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

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <h1 class="wp-page-title" style="margin-bottom:0">Statistici</h1>
            <a href="/admin/statistici/export.php" class="btn-export" download>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export pentru Claude
            </a>
        </div>

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
