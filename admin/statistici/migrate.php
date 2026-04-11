<?php
/**
 * Script de migrare one-time: descarca bazele de date si fisierele uploadate
 * de pe ericcosulea.ro pe cursurilapahar.ro.
 *
 * USAGE: Logheaza-te in admin, apoi acceseaza:
 *   https://cursurilapahar.ro/admin/statistici/migrate.php
 *
 * Se sterge singur dupa executie reusita.
 */
declare(strict_types=1);

require __DIR__ . '/../auth_check.php';
if (!is_authenticated()) { header('Location: /admin/'); exit; }

header('Content-Type: text/plain; charset=utf-8');

$dataDir    = __DIR__ . '/data';
$uploadsDir = __DIR__ . '/uploads';

if (!is_dir($dataDir))    mkdir($dataDir, 0755, true);
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

$results = [];
$allOk   = true;

// ── Config bridge pe ericcosulea.ro ──────────────────────────────────────────
$BRIDGE_BASE = 'https://ericcosulea.ro/clp/export_data.php';
$BRIDGE_TOKEN = 'migrate_clp_2026_xK9mP2qR';

function bridge_url(string $file): string {
    global $BRIDGE_BASE, $BRIDGE_TOKEN;
    return $BRIDGE_BASE . '?token=' . urlencode($BRIDGE_TOKEN) . '&file=' . urlencode($file);
}

function download_file(string $url, string $dest): bool|string {
    $ctx = stream_context_create(['http' => ['timeout' => 30]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) return 'Nu am putut descarca: ' . $url;
    if (strlen($data) < 10) return 'Raspuns prea mic (' . strlen($data) . ' bytes) - posibil eroare: ' . substr($data, 0, 200);
    if (file_put_contents($dest, $data, LOCK_EX) === false) return 'Nu am putut scrie: ' . $dest;
    return true;
}

// ── 1. Descarca bazele de date SQLite ────────────────────────────────────────
$dbFiles = [
    'clp.sqlite' => $dataDir . '/clp.sqlite',
    'pnl.sqlite' => $dataDir . '/pnl.sqlite',
];

foreach ($dbFiles as $name => $dest) {
    $url = bridge_url($name);
    $res = download_file($url, $dest);
    if ($res === true) {
        $results[] = "OK: {$name} descarcat (" . filesize($dest) . " bytes)";
    } else {
        $results[] = "EROARE [{$name}]: {$res}";
        $allOk = false;
    }
}

// ── 2. Descarca fisierele uploadate (PDF, XLSX, TXT) ────────────────────────
$listUrl = bridge_url('list_uploads');
$listJson = @file_get_contents($listUrl);
$uploadFiles = $listJson ? json_decode($listJson, true) : null;

if (is_array($uploadFiles) && !empty($uploadFiles)) {
    $copied = 0;
    foreach ($uploadFiles as $fname) {
        $url  = bridge_url('uploads/' . $fname);
        $dest = $uploadsDir . '/' . $fname;
        $res  = download_file($url, $dest);
        if ($res === true) {
            $copied++;
        } else {
            $results[] = "EROARE [uploads/{$fname}]: {$res}";
        }
    }
    $results[] = "OK: {$copied}/" . count($uploadFiles) . " fisiere uploadate descarcate";
} elseif (is_array($uploadFiles) && empty($uploadFiles)) {
    $results[] = "OK: Niciun fisier in uploads/ (director gol)";
} else {
    $results[] = "EROARE: Nu am putut obtine lista uploads de pe ericcosulea.ro";
    $allOk = false;
}

// ── Rezultat ─────────────────────────────────────────────────────────────────
echo "=== Migrare Statistici ===\n\n";
foreach ($results as $r) echo $r . "\n";

if ($allOk) {
    echo "\n--- SUCCES: Toate datele au fost migrate! ---\n";

    // Curatare: sterge bridge-ul de pe ericcosulea.ro
    $cleanupUrl = bridge_url('clp.sqlite') . '&cleanup=yes';
    @file_get_contents($cleanupUrl);
    $results[] = "(Am trimis comanda de cleanup catre ericcosulea.ro)";

    // Sterge-te singur
    echo "Sterg migrate.php...\n";
    @unlink(__FILE__);
    echo "Done! Acum poti folosi /admin/statistici/\n";
} else {
    echo "\n--- MIGRARE INCOMPLETA ---\n";
    echo "Verifica:\n";
    echo "1. Ca export_data.php exista pe ericcosulea.ro/clp/export_data.php\n";
    echo "2. Ca a fost deploiat pe server (push pe main in repo ericcosulea)\n";
    echo "3. Reacceseaza aceasta pagina pentru a reincerca\n";
}
