<?php
// One-time cleanup: delete auto-synced duplicates, keep only Public Speaking (2026-05-26)
require __DIR__ . '/../../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }

$db_path = __DIR__ . '/../data/clp.sqlite';
$db = new SQLite3($db_path);
$db->exec('PRAGMA foreign_keys = ON;');

// Delete all entries with external_id that are NOT the Public Speaking course
// (those are the bulk-sync duplicates of manually-added courses)
$deleted = $db->exec("DELETE FROM courses WHERE external_id IS NOT NULL AND date != '2026-05-26'");

$remaining = $db->querySingle("SELECT COUNT(*) FROM courses WHERE external_id IS NOT NULL");
$db->close();

// Self-delete this file after running
@unlink(__FILE__);

header('Location: /admin/?tab=cursuri');
exit;
