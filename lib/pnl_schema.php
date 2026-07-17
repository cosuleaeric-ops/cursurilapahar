<?php

/**
 * Aduce baza pnl.sqlite la zi. Se apelează din orice punct care deschide baza,
 * ca export-urile să nu depindă de ordinea în care e atins P&L-ul.
 */
function clp_pnl_migrate(SQLite3 $db): void
{
    $res = $db->query("PRAGMA table_info(cheltuieli)");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === 'detalii') {
            return;
        }
    }

    $db->exec("ALTER TABLE cheltuieli ADD COLUMN detalii TEXT NOT NULL DEFAULT ''");
}
