<?php
require_once __DIR__ . '/../config.php';
session_start();

// ── Auth ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['clp_admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Parolă incorectă.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
$authed = !empty($_SESSION['clp_admin']);

// ── Load events ───────────────────────────
function loadEvents(): array {
    if (!file_exists(EVENTS_FILE)) return [];
    $data = json_decode(file_get_contents(EVENTS_FILE), true);
    return is_array($data) ? $data : [];
}
function saveEvents(array $events): void {
    file_put_contents(EVENTS_FILE, json_encode(array_values($events), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ── Actions ───────────────────────────────
$message = '';
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {

        if ($_POST['action'] === 'add') {
            $events = loadEvents();
            $events[] = [
                'id'       => uniqid(),
                'title'    => trim($_POST['title'] ?? ''),
                'date_raw' => trim($_POST['date_raw'] ?? ''),
                'date_display' => trim($_POST['date_display'] ?? ''),
                'time'     => trim($_POST['time'] ?? ''),
                'location' => trim($_POST['location'] ?? ''),
                'image'    => trim($_POST['image'] ?? ''),
                'url'      => trim($_POST['url'] ?? ''),
                'active'   => !empty($_POST['active']),
            ];
            saveEvents($events);
            $message = 'Eveniment adăugat!';
        }

        if ($_POST['action'] === 'toggle') {
            $id = $_POST['id'] ?? '';
            $events = loadEvents();
            foreach ($events as &$ev) {
                if ($ev['id'] === $id) $ev['active'] = !($ev['active'] ?? false);
            }
            saveEvents($events);
            $message = 'Status actualizat.';
        }

        if ($_POST['action'] === 'delete') {
            $id = $_POST['id'] ?? '';
            $events = array_filter(loadEvents(), fn($e) => $e['id'] !== $id);
            saveEvents($events);
            $message = 'Eveniment șters.';
        }
    }
}

$events = $authed ? loadEvents() : [];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Cursuri la Pahar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,600;0,700;0,800;1,400;1,700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Extra admin styles */
        .admin-login {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .admin-login-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 40px 36px;
            width: 100%;
            max-width: 380px;
        }
        .admin-login-box h1 {
            font-family: var(--font-serif);
            font-size: 1.5rem;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .admin-login-box p { color: var(--text-muted); margin-bottom: 28px; font-size:.9rem; }
        .error-msg { color: #f87171; font-size: .88rem; margin-top: 10px; }

        .tabs { display: flex; gap: 4px; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 0; }
        .tab-btn {
            padding: 10px 20px;
            border-radius: 8px 8px 0 0;
            font-size: .9rem;
            font-weight: 500;
            color: var(--text-muted);
            background: transparent;
            border: 1px solid transparent;
            border-bottom: none;
            cursor: pointer;
            transition: all .2s;
        }
        .tab-btn.active { background: var(--bg-card); border-color: var(--border); color: var(--accent); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .badge-active   { background: rgba(74,222,128,.15); color: #4ade80; border: 1px solid rgba(74,222,128,.3); padding: 2px 8px; border-radius: 20px; font-size: .75rem; }
        .badge-inactive { background: rgba(255,255,255,.05); color: var(--text-faint); border: 1px solid var(--border); padding: 2px 8px; border-radius: 20px; font-size: .75rem; }

        .success-msg { background: rgba(74,222,128,.1); border: 1px solid rgba(74,222,128,.2); color: #4ade80; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: .9rem; }
    </style>
</head>
<body class="admin-body">

<?php if (!$authed): ?>
<!-- ── Login ─── -->
<div class="admin-login">
    <div class="admin-login-box">
        <h1>Admin Panel</h1>
        <p>Cursuri la Pahar</p>
        <form method="POST">
            <div class="form-group">
                <label for="pw">Parolă</label>
                <input type="password" id="pw" name="password" autofocus required style="width:100%">
            </div>
            <?php if (!empty($loginError)): ?>
            <p class="error-msg"><?= htmlspecialchars($loginError) ?></p>
            <?php endif; ?>
            <button type="submit" class="btn btn-accent" style="width:100%;margin-top:16px;">Intră</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── Admin panel ─── -->
<header class="admin-header">
    <h1>🍷 Cursuri la Pahar — Admin</h1>
    <a href="?logout=1" style="color:var(--text-muted);font-size:.88rem;">Ieșire →</a>
</header>

<div class="admin-content">

    <?php if ($message): ?>
    <div class="success-msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('events')">Cursuri programate</button>
        <button class="tab-btn" onclick="switchTab('add')">Adaugă curs</button>
    </div>

    <!-- Events list -->
    <div class="tab-panel active" id="tab-events">
        <div class="admin-card">
            <h2>Cursuri (<?= count($events) ?>)</h2>
            <?php if (empty($events)): ?>
            <p style="color:var(--text-muted)">Nu există cursuri. Adaugă primul curs din tab-ul „Adaugă curs".</p>
            <?php else: ?>
            <div class="admin-events-list">
                <?php foreach (array_reverse($events) as $ev): ?>
                <div class="admin-event-item">
                    <div class="admin-event-info">
                        <strong><?= htmlspecialchars($ev['title']) ?></strong>
                        <small>
                            <?= htmlspecialchars($ev['date_display'] ?: $ev['date_raw']) ?> •
                            <?= htmlspecialchars($ev['time']) ?> •
                            <?= htmlspecialchars($ev['location']) ?>
                        </small>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span class="<?= ($ev['active'] ?? false) ? 'badge-active' : 'badge-inactive' ?>">
                            <?= ($ev['active'] ?? false) ? 'Activ' : 'Ascuns' ?>
                        </span>
                        <div class="admin-event-actions">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($ev['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-secondary">
                                    <?= ($ev['active'] ?? false) ? 'Ascunde' : 'Activează' ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Ștergi evenimentul?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($ev['id']) ?>">
                                <button type="submit" class="btn-danger">Șterge</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add event -->
    <div class="tab-panel" id="tab-add">
        <div class="admin-card">
            <h2>Adaugă curs nou</h2>

            <div style="background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.2);border-radius:8px;padding:14px 16px;margin-bottom:24px;font-size:.88rem;color:var(--text-muted);">
                <strong style="color:var(--accent);">Notă:</strong>
                LiveTickets.ro nu oferă date publice printr-un API accesibil server-side.
                Completează manual câmpurile de mai jos după ce deschizi pagina evenimentului pe livetickets.ro.
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Titlul cursului *</label>
                    <input type="text" name="title" placeholder="Ex: Metacogniția Aplicată" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Data (pentru sortare) *<br><small style="color:var(--text-faint)">Format: YYYY-MM-DD</small></label>
                        <input type="date" name="date_raw" required>
                    </div>
                    <div class="form-group">
                        <label>Data afișată pe card</label>
                        <input type="text" name="date_display" placeholder="Ex: 21 Aprilie 2025">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ora</label>
                        <input type="text" name="time" placeholder="Ex: 19:00">
                    </div>
                    <div class="form-group">
                        <label>Locație / Venue</label>
                        <input type="text" name="location" placeholder="Ex: Twisted Olives, București">
                    </div>
                </div>
                <div class="form-group">
                    <label>URL imagine (de pe livetickets sau alt hosting) *</label>
                    <input type="url" name="image" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label>Link livetickets *</label>
                    <input type="url" name="url" placeholder="https://www.livetickets.ro/bilete/..." required>
                </div>
                <div class="form-group">
                    <label class="checkbox-label" style="flex-direction:row;gap:10px;">
                        <input type="checkbox" name="active" value="1" checked> Activează imediat pe site
                    </label>
                </div>
                <button type="submit" class="btn btn-accent">Adaugă cursul</button>
            </form>
        </div>
    </div>

</div>

<script>
function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php endif; ?>
</body>
</html>
