<?php
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');
if (file_exists(dirname(__DIR__) . '/private/secrets.php')) {
    require dirname(__DIR__) . '/private/secrets.php';
}
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', '');
define('COURSES_FILE',        dirname(__DIR__) . '/data/courses.json');
define('VOTE_COURSES_FILE',   dirname(__DIR__) . '/data/vote_courses.json');
define('SETTINGS_FILE',       dirname(__DIR__) . '/data/settings.json');
define('SPEAKERS_FILE',       dirname(__DIR__) . '/data/speakers.json');
define('LOCATIONS_FILE',      dirname(__DIR__) . '/data/locations.json');
define('COLLABORATIONS_FILE', dirname(__DIR__) . '/data/collaborations.json');
define('UPLOADS_DIR',         dirname(__DIR__) . '/assets/images/uploads');
define('UPLOADS_URL',         '/assets/images/uploads');
define('PUBLIC_HTML',         dirname(__DIR__));
define('USERS_FILE',          dirname(__DIR__) . '/data/users.json');
define('MESSAGE_META_FILE',   dirname(__DIR__) . '/data/message_meta.json');

// ── Message metadata (read/eval/comments) ────────────────────────────────────
function msg_id_from_block(string $block): string {
    return substr(md5($block), 0, 12);
}
function load_msg_meta(): array {
    if (!file_exists(MESSAGE_META_FILE)) return [];
    return json_decode(file_get_contents(MESSAGE_META_FILE), true) ?: [];
}
function save_msg_meta(array $meta): void {
    $dir = dirname(MESSAGE_META_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(MESSAGE_META_FILE, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// ── Auth secret — stored in settings.json, never in code ─────────────────────
function get_auth_secret(): string {
    static $s = null;
    if ($s === null) {
        $s = file_exists(SETTINGS_FILE)
            ? (json_decode(file_get_contents(SETTINGS_FILE), true) ?: [])
            : [];
    }
    return $s['auth_secret'] ?? '';
}

// ── Users ─────────────────────────────────────────────────────────────────────
function load_users(): array {
    if (!file_exists(USERS_FILE)) return [];
    return json_decode(file_get_contents(USERS_FILE), true) ?: [];
}
function save_users(array $users): void {
    file_put_contents(USERS_FILE, json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
function find_user(string $username): ?array {
    foreach (load_users() as $u) {
        if (($u['username'] ?? '') === $username) return $u;
    }
    return null;
}

// ── Cookie-based auth ─────────────────────────────────────────────────────────
function clp_real_user(): ?array {
    $secret = get_auth_secret();
    if (!$secret) return null;
    $cookie = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie || !str_contains($cookie, ':')) return null;
    [$uname, $token] = explode(':', $cookie, 2);
    $expected = hash_hmac('sha256', 'clp_user:' . $uname, $secret);
    if (!hash_equals($expected, $token)) return null;
    return find_user($uname);
}
function clp_current_user(): ?array { // renamed from get_current_user to avoid PHP builtin conflict
    $real = clp_real_user();
    if (!$real) return null;
    if (($real['role'] ?? '') === 'owner') {
        $view_as = $_COOKIE['clp_view_as'] ?? '';
        if ($view_as) {
            $impersonated = find_user($view_as);
            if ($impersonated) return $impersonated;
        }
    }
    return $real;
}
function is_impersonating(): bool {
    $real = clp_real_user();
    if (!$real || ($real['role'] ?? '') !== 'owner') return false;
    $view_as = $_COOKIE['clp_view_as'] ?? '';
    return !empty($view_as) && find_user($view_as) !== null;
}
function is_authenticated(): bool {
    return clp_current_user() !== null;
}
function is_owner(): bool {
    return (clp_current_user()['role'] ?? '') === 'owner';
}
function can_access_tab(string $tab): bool {
    $user = clp_current_user();
    if (!$user) return false;
    if (($user['role'] ?? '') === 'owner') return true;
    return in_array($tab, ['dashboard', 'mesaje', 'vot', 'competitori', 'speakeri', 'locatii', 'colaborari']);
}
function set_auth_cookie(string $username): void {
    $token = hash_hmac('sha256', 'clp_user:' . $username, get_auth_secret());
    setcookie('clp_auth', $username . ':' . $token, [
        'expires'  => time() + 86400 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
function clear_auth_cookie(): void {
    setcookie('clp_auth', '', ['expires' => time() - 3600, 'path' => '/']);
}

// Ensure secrets exist in settings (generate on first run)
function ensure_secrets(): void {
    $settings = file_exists(SETTINGS_FILE)
        ? (json_decode(file_get_contents(SETTINGS_FILE), true) ?: [])
        : [];
    $changed = false;
    if (empty($settings['auth_secret']))    { $settings['auth_secret']    = bin2hex(random_bytes(32)); $changed = true; }
    if (empty($settings['webhook_secret'])) { $settings['webhook_secret'] = bin2hex(random_bytes(32)); $changed = true; }
    if (empty($settings['sync_token']))     { $settings['sync_token']     = bin2hex(random_bytes(32)); $changed = true; }
    if ($changed) {
        $dir = dirname(SETTINGS_FILE);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
ensure_secrets();

// Auto-create users.json on server if missing
if (!file_exists(USERS_FILE)) {
    $dir = dirname(USERS_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(USERS_FILE, json_encode([
        ['username' => 'eric6', 'password_hash' => '$2y$12$2dWGrc.k7sizuCBC18huu.XgqNkCgfVZ0DCaDS1kZQOFIDzgfLRPC', 'role' => 'owner'],
        ['username' => 'andy',  'password_hash' => '$2y$12$uxs/.33puwE3AmeCbilyve6t33qF3JXeaiObwDSiADFATmxQYzBvq',  'role' => 'city_manager'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

if (isset($_POST['login_username'])) {
    $uname = trim($_POST['login_username'] ?? '');
    $pass  = $_POST['login_password'] ?? '';
    $user  = find_user($uname);
    $ok    = false;
    if ($user) {
        $stored = $user['password_hash'] ?? '';
        $ok = (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2b$'))
            ? password_verify($pass, $stored)
            : ($pass === $stored);
    }
    if ($ok) {
        set_auth_cookie($uname);
        header('Location: /admin/');
        exit;
    } else {
        $login_error = 'Utilizator sau parolă incorecte.';
    }
}
if (isset($_GET['logout'])) {
    clear_auth_cookie();
    header('Location: /admin/');
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function load_courses(): array {
    if (!file_exists(COURSES_FILE)) return [];
    $courses = json_decode(file_get_contents(COURSES_FILE), true) ?: [];
    // Auto-deactivate courses whose date has passed
    $today = date('Y-m-d');
    $changed = false;
    foreach ($courses as &$c) {
        if (!empty($c['date_raw']) && $c['date_raw'] < $today && !empty($c['active'])) {
            $c['active'] = false;
            $changed = true;
        }
    }
    unset($c);
    if ($changed) save_courses($courses);
    return $courses;
}
function save_courses(array $courses): void {
    $dir = dirname(COURSES_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(COURSES_FILE, json_encode(array_values($courses), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function load_vote_courses(): array {
    if (!file_exists(VOTE_COURSES_FILE)) return [];
    return json_decode(file_get_contents(VOTE_COURSES_FILE), true) ?: [];
}
function save_vote_courses(array $courses): void {
    $dir = dirname(VOTE_COURSES_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(VOTE_COURSES_FILE, json_encode(array_values($courses), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function load_speakers(): array {
    if (!file_exists(SPEAKERS_FILE)) return [];
    return json_decode(file_get_contents(SPEAKERS_FILE), true) ?: [];
}
function save_speakers(array $items): void {
    $dir = dirname(SPEAKERS_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(SPEAKERS_FILE, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function load_locations(): array {
    if (!file_exists(LOCATIONS_FILE)) return [];
    return json_decode(file_get_contents(LOCATIONS_FILE), true) ?: [];
}
function save_locations(array $items): void {
    $dir = dirname(LOCATIONS_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(LOCATIONS_FILE, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function load_collaborations(): array {
    if (!file_exists(COLLABORATIONS_FILE)) return [];
    return json_decode(file_get_contents(COLLABORATIONS_FILE), true) ?: [];
}
function save_collaborations(array $items): void {
    $dir = dirname(COLLABORATIONS_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(COLLABORATIONS_FILE, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function default_settings(): array {
    return [
        'announcement'      => '🎉 Peste 1.000 de participanți au descoperit că educația are un gust mai bun la un pahar. Tu ești următorul?',
        'hero_title'        => 'Cursuri ținute de experți<br><em>la un pahar în oraș.</em>',
        'hero_btn'          => 'Vezi următoarele cursuri',
        'courses_title'     => 'Următoarele cursuri',
        'gallery_title'     => 'Galerie',
        'newsletter_title'  => 'Fii primul care află când au loc evenimentele Cursuri la Pahar',
        'newsletter_desc'   => 'Vei primi în exclusivitate data și tema viitoarelor evenimente Cursuri la Pahar.',
        'collab_title'      => 'Colaborare',
        'collab_subtitle'   => 'Vrei să faci parte din comunitatea Cursuri la Pahar? Hai să construim ceva frumos împreună.',
        'contact_title'     => 'Contact',
        'contact_subtitle'  => 'Ai o întrebare sau o idee? Scrie-ne.',
        'hero_images'       => ['/assets/images/hero1.jpg', '/assets/images/hero2.jpg', '/assets/images/hero3.jpg', '/assets/images/hero4.jpg', '/assets/images/hero5.jpg'],
        'logo_path'         => '/assets/images/logo.webp',
        'favicon_path'      => '',
        'nav_brand_text'    => 'Cursuri la Pahar',
        'nav_links'         => [
            ['label' => 'Cursuri',            'url' => '/#cursuri'],
            ['label' => 'FAQ',                'url' => '/#faq'],
            ['label' => 'Colaborare',         'url' => '/#colaborare'],
            ['label' => 'Contact',            'url' => '/#contact'],
        ],
        'steps' => [
            ['title' => 'Verifici calendarul',  'text' => 'Răsfoiești cursurile disponibile și găsești tema care te stârnește curiozitatea.'],
            ['title' => 'Cumperi biletul',       'text' => 'Achiziționezi biletul online prin LiveTickets, simplu și rapid, de pe orice dispozitiv.'],
            ['title' => 'Vii la eveniment',      'text' => 'Te prezinți la locație, îți iei o băutură preferată și ocupi un loc confortabil.'],
            ['title' => 'Înveți & socializezi',  'text' => 'Asculți expertul, pui orice întrebare la Q&A și cunoști oameni faini cu aceleași interese.'],
        ],
        'faq_items' => [
            ['q' => 'Ce este Cursuri la Pahar?',           'a' => 'Cursuri la Pahar este un eveniment care scoate educația din amfiteatre și o aduce în baruri. Experți și profesori vin să discute teme complexe într-un cadru relaxat, la un pahar cu publicul.'],
            ['q' => 'Cât durează un eveniment?',            'a' => 'Rezervăm cam 2 ore pentru întreaga experiență. Primele 60–90 de minute sunt dedicate prezentării, iar restul timpului îl petrecem la un Q&A, unde poți pune orice fel de întrebări.'],
            ['q' => 'Cât costă un bilet?',                  'a' => 'Biletul standard costă 50 de lei, iar biletul pentru studenți costă 30 de lei.'],
            ['q' => 'Despre ce sunt cursurile?',            'a' => 'Alegem teme care stârnesc curiozitatea oricui: de la psihologie și misterele istoriei, până la univers și tehnologie.'],
            ['q' => 'Unde au loc evenimentele?',            'a' => 'Ne vedem în baruri, pub-uri și alte spații relaxate din București (momentan).'],
            ['q' => 'Cine poate participa?',                'a' => 'Oricine este curios și are peste 16 ani. Nu ai nevoie de pregătire specială sau studii în domeniu.'],
            ['q' => 'Când va avea loc următorul eveniment?', 'a' => 'Dacă vrei să te anunțăm direct pe email când punem biletele la vânzare, abonează-te la newsletter-ul nostru.'],
        ],
        'kit_api_key'       => '',
        'kit_form_id'       => '',
        'color_bg'          => '#0D0D0D',
        'color_accent'      => '#C9A84C',
        'color_text'        => '#E8E4DC',
        'color_text_muted'  => '#9CA3AF',
        'color_surface'     => '#161616',
        'color_btn_hover'   => '#b8922e',
        'color_banner'      => '#FFB000',
        'font_heading'      => 'Nunito',
        'font_body'         => 'Inter',
        'head_scripts'      => '',
        'pages'             => [
            'sustine' => [
                'title'       => 'Prezintă un curs',
                'subtitle'    => 'Împărtășește-ți expertiza cu comunitatea noastră.',
                'description' => 'Ești expert într-un domeniu care te pasionează? Vino să susții un curs în fața unei comunități curioase, într-un cadru relaxat, la un pahar.',
            ],
            'gazduieste' => [
                'title'       => 'Găzduiește un curs',
                'subtitle'    => 'Transformă-ți locația în spațiul unde se nasc conexiunile.',
                'description' => 'Ai o locație cu atmosferă? Bar, café, spațiu cultural sau altceva? Hai să aducem un curs la tine și să umpleam locul de oameni curioși.',
            ],
            'parteneriat' => [
                'title'       => 'Propune un parteneriat',
                'subtitle'    => 'Construim ceva frumos împreună.',
                'description' => 'Reprezinți un brand, o platformă media sau o organizație? Explorăm împreună oportunități de colaborare care aduc valoare comunității noastre.',
            ],
        ],
        'section_bgs' => [
            'cursuri'          => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
            'newsletter'       => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
            'faq'              => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
            'colaborare'       => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
            'contact'          => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
        ],
        'quick_links' => [
            ['label' => 'Drive',               'url' => 'https://drive.google.com/drive/u/2/folders/1eXWzwb1KiDPTH1nNjl0wu3B0w0zqZKNV', 'icon' => '📁'],
            ['label' => 'Foto-video',          'url' => 'https://drive.google.com/drive/u/3/folders/1ix1WBuvRAk7EfEJhdc_9qHU2D8MwjxNF', 'icon' => '📷'],
            ['label' => 'Centralizator',       'url' => 'https://docs.google.com/spreadsheets/d/11Ch00q2d10JlW16nByLJE9LKXww77dEsSFOVYeTkr-c/edit?gid=548786879#gid=548786879', 'icon' => '📊'],
            ['label' => 'Platforma ticketing', 'url' => 'https://admin.livetickets.ro/', 'icon' => '🎟️'],
            ['label' => 'Tutorial LiveTickets','url' => 'https://payvent.notion.site/Organizer-Help-Center-b79f9086bbc9451087c7accdf6c9818e', 'icon' => '📖'],
            ['label' => 'Newsletter',          'url' => 'https://app.kit.com/dashboard', 'icon' => '📧'],
            ['label' => 'Afis IG',             'url' => 'https://www.canva.com/design/DAHBqjH01CA/r5YqP_oEent4GsU7aL1wZw/edit', 'icon' => '🖼️'],
            ['label' => 'Afis 1:1',            'url' => 'https://www.canva.com/design/DAHCF25PYEg/LlXrH9lP-x4U-JYciu5ILw/edit', 'icon' => '📋'],
            ['label' => 'Badge',               'url' => 'https://www.canva.com/design/DAHCdELFiuE/ZGLv9HI6NnX_8VFYvPUdVg/edit', 'icon' => '🏷️'],
            ['label' => 'Invitatie',           'url' => 'https://www.canva.com/design/DAHAEaZYZHE/akhj1g2nUiwthNTGEyo0dQ/edit', 'icon' => '✉️'],
            ['label' => 'Logo',                'url' => 'https://www.canva.com/design/DAG_I_HdOsQ/eAuL52PZe88j8KLMVSAaQw/edit', 'icon' => '🎨'],
        ],
    ];
}
function load_settings(): array {
    if (!file_exists(SETTINGS_FILE)) return default_settings();
    $data = json_decode(file_get_contents(SETTINGS_FILE), true) ?: [];
    return array_merge(default_settings(), $data);
}
function save_settings(array $settings): bool {
    $dir = dirname(SETTINGS_FILE);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) return false;
    }
    if (file_exists(SETTINGS_FILE) && !is_writable(SETTINGS_FILE)) return false;
    $result = file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $result !== false;
}

// ── Actions (only when authenticated) ────────────────────────────────────────
if (is_authenticated() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Switch user (owner impersonation)
    if ($action === 'switch_user' && ($clp_real_user_check = clp_real_user()) && ($clp_real_user_check['role'] ?? '') === 'owner') {
        $target = trim($_POST['target_username'] ?? '');
        if ($target === ($clp_real_user_check['username'] ?? '') || $target === '') {
            setcookie('clp_view_as', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
        } elseif (find_user($target)) {
            setcookie('clp_view_as', $target, ['expires' => time() + 7200, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
        }
        header('Location: /admin/');
        exit;
    }

    // ── Clear sold out cache
    if ($action === 'clear_soldout_cache') {
        file_put_contents(__DIR__ . '/../data/soldout_cache.json', '{}');
        header('Location: /admin/?tab=cursuri');
        exit;
    }

    // ── Delete course
    if ($action === 'delete_course') {
        $id = $_POST['id'] ?? '';
        $courses = load_courses();
        $courses = array_filter($courses, fn($c) => ($c['id'] ?? '') !== $id);
        save_courses($courses);
        header('Location: /admin/?tab=cursuri');
        exit;
    }

    // ── Toggle course active
    if ($action === 'toggle_course') {
        $id = $_POST['id'] ?? '';
        $courses = load_courses();
        foreach ($courses as &$c) {
            if (($c['id'] ?? '') === $id) {
                $c['active'] = !($c['active'] ?? false);
                break;
            }
        }
        unset($c);
        save_courses($courses);
        header('Location: /admin/?tab=cursuri');
        exit;
    }

    // ── Save course
    if ($action === 'save_course') {
        $id = trim($_POST['course_id'] ?? '');
        $courses = load_courses();
        $entry = [
            'id'              => $id ?: uniqid('c', true),
            'title'           => trim($_POST['title'] ?? ''),
            'date_display'    => trim($_POST['date_display'] ?? ''),
            'date_raw'        => trim($_POST['date_raw'] ?? ''),
            'time'            => trim($_POST['time'] ?? ''),
            'location'        => trim($_POST['location'] ?? ''),
            'livetickets_url' => trim($_POST['livetickets_url'] ?? ''),
            'image_url'       => trim($_POST['image_url'] ?? ''),
            'active'          => !empty($_POST['active']),
        ];
        if ($id) {
            $found = false;
            foreach ($courses as &$c) {
                if (($c['id'] ?? '') === $id) {
                    foreach (['discount_percent', 'discount_ends_at'] as $k) {
                        if (isset($c[$k])) $entry[$k] = $c[$k];
                    }
                    $c = $entry;
                    $found = true;
                    break;
                }
            }
            unset($c);
            if (!$found) $courses[] = $entry;
        } else {
            $courses[] = $entry;
        }
        save_courses($courses);
        header('Location: /admin/?tab=cursuri');
        exit;
    }

    // ── Save / clear discount for a course
    if ($action === 'save_discount') {
        $id = trim($_POST['id'] ?? '');
        $clear = !empty($_POST['clear']);
        $courses = load_courses();
        foreach ($courses as &$c) {
            if (($c['id'] ?? '') !== $id) continue;
            if ($clear) {
                unset($c['discount_percent'], $c['discount_ends_at']);
                break;
            }
            $pct = (int)($_POST['discount_percent'] ?? 0);
            $local = trim($_POST['discount_ends_at'] ?? '');
            if ($pct > 0 && $pct <= 100 && $local !== '') {
                $tz = new DateTimeZone('Europe/Bucharest');
                $dt = DateTime::createFromFormat('Y-m-d\TH:i', $local, $tz);
                if ($dt) {
                    $c['discount_percent'] = $pct;
                    $c['discount_ends_at'] = $dt->format('c');
                }
            } else {
                unset($c['discount_percent'], $c['discount_ends_at']);
            }
            break;
        }
        unset($c);
        save_courses($courses);
        header('Location: /admin/?tab=cursuri');
        exit;
    }

    // ── Upload image(s)
    if ($action === 'upload_image') {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
        $upload_error = '';
        $upload_ok    = '';
        $files = $_FILES['image_files'] ?? null;
        if (!$files || !is_array($files['name']) || empty($files['name'][0])) {
            $upload_error = 'Niciun fișier selectat.';
        } else {
            $count_ok = 0;
            $count_err = 0;
            $allowed = ['jpg','jpeg','png','webp','gif','avif'];
            for ($fi = 0; $fi < count($files['name']); $fi++) {
                if ($files['error'][$fi] !== UPLOAD_ERR_OK) { $count_err++; continue; }
                $ext = strtolower(pathinfo($files['name'][$fi], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) { $count_err++; continue; }
                $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($files['name'][$fi], PATHINFO_FILENAME));
                $new_name = $base . '-' . time() . $fi . '.webp';
                $dest = UPLOADS_DIR . '/' . $new_name;
                $img = match($ext) {
                    'jpg','jpeg' => @imagecreatefromjpeg($files['tmp_name'][$fi]),
                    'png'        => @imagecreatefrompng($files['tmp_name'][$fi]),
                    'webp'       => @imagecreatefromwebp($files['tmp_name'][$fi]),
                    'gif'        => @imagecreatefromgif($files['tmp_name'][$fi]),
                    default      => false,
                };
                if ($img) {
                    $w = imagesx($img); $h = imagesy($img);
                    if ($w > 1920) {
                        $img2 = imagescale($img, 1920, -1);
                        if ($img2) { imagedestroy($img); $img = $img2; }
                    }
                    if (imagewebp($img, $dest, 82)) {
                        imagedestroy($img);
                        $count_ok++;
                    } else {
                        imagedestroy($img);
                        $count_err++;
                    }
                } else {
                    if (move_uploaded_file($files['tmp_name'][$fi], UPLOADS_DIR . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($files['name'][$fi])))) {
                        $count_ok++;
                    } else {
                        $count_err++;
                    }
                }
            }
            if ($count_ok > 0) $upload_ok = $count_ok . ' imagine' . ($count_ok > 1 ? 'i' : '') . ' încărcată' . ($count_ok > 1 ? 'e' : '') . ' cu succes.';
            if ($count_err > 0) $upload_error = $count_err . ' fișier' . ($count_err > 1 ? 'e' : '') . ' nu ' . ($count_err > 1 ? 'au' : 'a') . ' putut fi încărcate.';
        }
        // Fall through to display
    }

    // ── Delete uploaded image
    if ($action === 'delete_image') {
        $filename = basename($_POST['filename'] ?? '');
        if ($filename) {
            $path = UPLOADS_DIR . '/' . $filename;
            if (file_exists($path)) unlink($path);
        }
        header('Location: /admin/?tab=imagini');
        exit;
    }

    // ── Save hero images + gallery featured
    if ($action === 'save_hero_images') {
        $settings = load_settings();
        $settings['hero_images']      = array_values(array_filter(array_map('trim', $_POST['hero_images'] ?? [])));
        $settings['gallery_featured'] = array_values(array_filter(array_map('trim', $_POST['gallery_featured'] ?? [])));
        save_settings($settings);
        header('Location: /admin/?tab=imagini&saved=1');
        exit;
    }

    // ── Save quick links (Owner only)
    if ($action === 'save_quick_links' && is_owner()) {
        $settings = load_settings();
        $labels = $_POST['ql_label'] ?? [];
        $urls   = $_POST['ql_url']   ?? [];
        $icons  = $_POST['ql_icon']  ?? [];
        $links  = [];
        for ($i = 0; $i < count($labels); $i++) {
            $lbl = trim($labels[$i] ?? '');
            $url = trim($urls[$i]   ?? '');
            if ($lbl && $url) {
                $links[] = ['label' => $lbl, 'url' => $url, 'icon' => trim($icons[$i] ?? '🔗')];
            }
        }
        $settings['quick_links'] = $links;
        save_settings($settings);
        header('Location: /admin/?tab=config&saved=1');
        exit;
    }

    // ── Save head scripts (analytics/tracking)
    if ($action === 'save_head_scripts') {
        $settings = load_settings();
        $settings['head_scripts'] = $_POST['head_scripts'] ?? '';
        save_settings($settings);
        header('Location: /admin/?tab=config&saved=1');
        exit;
    }

    // ── Upload logo
    if ($action === 'upload_logo') {
        $file = $_FILES['logo_file'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','svg'])) {
                $uploads_dir = PUBLIC_HTML . '/assets/images/uploads';
                if (!is_dir($uploads_dir)) @mkdir($uploads_dir, 0755, true);
                $new_name = 'logo-' . time() . '.' . $ext;
                $dest = $uploads_dir . '/' . $new_name;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $settings = load_settings();
                    $settings['logo_path'] = '/assets/images/uploads/' . $new_name;
                    save_settings($settings);
                }
            }
        }
        header('Location: /admin/?tab=aspect&saved=1');
        exit;
    }

    // ── Upload favicon (circular crop via GD, saved as PNG)
    if ($action === 'upload_favicon') {
        $file = $_FILES['favicon_file'] ?? null;
        $favicon_error = '';
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $favicon_error = 'Nu ai selectat niciun fișier.';
        } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $favicon_error = 'Fișierul este prea mare (limită server).';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $favicon_error = 'Eroare upload (cod ' . $file['error'] . ').';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['ico','png','jpg','jpeg','webp'])) {
                $favicon_error = 'Format neacceptat: ' . h($ext) . '. Folosește PNG, JPG sau WEBP.';
            } elseif (!function_exists('imagecreatefrompng')) {
                $favicon_error = 'Extensia GD nu este disponibilă pe server.';
            } else {
                // Load source image
                $src = null;
                if ($ext === 'png')                  $src = @imagecreatefrompng($file['tmp_name']);
                elseif (in_array($ext, ['jpg','jpeg'])) $src = @imagecreatefromjpeg($file['tmp_name']);
                elseif ($ext === 'webp')             $src = @imagecreatefromwebp($file['tmp_name']);
                elseif ($ext === 'ico')              $src = @imagecreatefromstring(file_get_contents($file['tmp_name']));

                if (!$src) {
                    $favicon_error = 'Nu am putut citi imaginea. Încearcă alt fișier.';
                } else {
                    $size   = 128; // 128px is plenty for a favicon, avoids memory issues
                    $orig_w = imagesx($src);
                    $orig_h = imagesy($src);
                    // Center-crop to square
                    $sq = min($orig_w, $orig_h);
                    $cx = (int)(($orig_w - $sq) / 2);
                    $cy = (int)(($orig_h - $sq) / 2);
                    // Create output canvas with transparency
                    $out = imagecreatetruecolor($size, $size);
                    imagealphablending($out, false);
                    imagesavealpha($out, true);
                    $trans = imagecolorallocatealpha($out, 0, 0, 0, 127);
                    imagefill($out, 0, 0, $trans);
                    // Resize source onto canvas
                    imagecopyresampled($out, $src, 0, 0, $cx, $cy, $size, $size, $sq, $sq);
                    imagedestroy($src);
                    // Circular mask — 128×128 = 16 384 pixels (lightweight)
                    $r = $size / 2.0;
                    for ($py = 0; $py < $size; $py++) {
                        for ($px = 0; $px < $size; $px++) {
                            $dx = $px - $r + 0.5;
                            $dy = $py - $r + 0.5;
                            if (($dx * $dx + $dy * $dy) > ($r * $r)) {
                                imagesetpixel($out, $px, $py, $trans);
                            }
                        }
                    }
                    $dest = PUBLIC_HTML . '/favicon.png';
                    if (!imagepng($out, $dest)) {
                        $favicon_error = 'Eroare la salvare favicon. Verifică permisiunile directorului.';
                    } else {
                        $settings = load_settings();
                        $settings['favicon_path'] = '/favicon.png';
                        save_settings($settings);
                        imagedestroy($out);
                        header('Location: /admin/?tab=aspect&saved=1');
                        exit;
                    }
                    imagedestroy($out);
                }
            }
        }
        // Fall through to render page with $favicon_error set
    }

    // ── Save Kit settings
    if ($action === 'save_kit') {
        $settings = load_settings();
        $settings['kit_api_key'] = trim($_POST['kit_api_key'] ?? '');
        $settings['kit_form_id'] = trim($_POST['kit_form_id'] ?? '');
        save_settings($settings);
        header('Location: /admin/?tab=config&saved=1');
        exit;
    }

    // ── Regenerate auth secret (invalidates all sessions)
    if ($action === 'regenerate_secret') {
        $settings = load_settings();
        $settings['auth_secret'] = bin2hex(random_bytes(32));
        save_settings($settings);
        clear_auth_cookie();
        header('Location: /admin/');
        exit;
    }

    // ── Regenerate webhook secret
    if ($action === 'regenerate_webhook_secret') {
        $settings = load_settings();
        $settings['webhook_secret'] = bin2hex(random_bytes(32));
        save_settings($settings);
        header('Location: /admin/?tab=config&webhook_saved=1');
        exit;
    }

    // ── Regenerate sync token
    if ($action === 'regenerate_sync_token') {
        $settings = load_settings();
        $settings['sync_token'] = bin2hex(random_bytes(32));
        save_settings($settings);
        header('Location: /admin/?tab=config&saved=1');
        exit;
    }

    // ── Export all data as download
    if ($action === 'export_settings') {
        $data_dir = dirname(SETTINGS_FILE);
        $export_settings = file_exists(SETTINGS_FILE) ? json_decode(file_get_contents(SETTINGS_FILE), true) : [];
        // Strip secrets from export
        foreach (['admin_password','auth_secret','webhook_secret','sync_token'] as $k) {
            unset($export_settings[$k]);
        }
        $bundle = [
            'settings'     => $export_settings,
            'courses'      => file_exists(COURSES_FILE)      ? json_decode(file_get_contents(COURSES_FILE), true)      : [],
            'vote_courses' => file_exists(VOTE_COURSES_FILE) ? json_decode(file_get_contents(VOTE_COURSES_FILE), true) : [],
            'messages_log' => file_exists($data_dir . '/messages.log') ? file_get_contents($data_dir . '/messages.log') : '',
        ];
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="clp-backup.json"');
        echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Import all data from bundle file + download images
    if ($action === 'import_settings') {
        if (!empty($_FILES['settings_file']['tmp_name'])) {
            $json   = file_get_contents($_FILES['settings_file']['tmp_name']);
            $bundle = json_decode($json, true);
            if ($bundle) {
                $data_dir = dirname(SETTINGS_FILE);
                // Support both old format (plain settings) and new bundle format
                $imported = $bundle['settings'] ?? $bundle;

                // Preserve local secrets and password
                $local = load_settings();
                foreach (['admin_password','auth_secret','webhook_secret','sync_token'] as $k) {
                    if (!empty($local[$k])) $imported[$k] = $local[$k];
                }
                save_settings($imported);

                // Restore courses
                if (!empty($bundle['courses'])) {
                    file_put_contents(COURSES_FILE, json_encode(array_values($bundle['courses']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
                // Restore vote_courses
                if (!empty($bundle['vote_courses'])) {
                    file_put_contents(VOTE_COURSES_FILE, json_encode(array_values($bundle['vote_courses']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
                // Restore messages log
                if (!empty($bundle['messages_log'])) {
                    file_put_contents($data_dir . '/messages.log', $bundle['messages_log']);
                }

                // Download images from source domain (scan settings + courses + vote_courses)
                $source_domain = rtrim(trim($_POST['source_domain'] ?? 'https://robotache.ro'), '/');
                $image_paths = [];
                $scan_for_images = function($val) use (&$image_paths) {
                    if (is_string($val) && preg_match('#^(/assets/images/|/assets/uploads/|/wp-content/)#', $val)) {
                        $image_paths[] = $val;
                    }
                };
                array_walk_recursive($imported, $scan_for_images);
                if (!empty($bundle['courses']))      array_walk_recursive($bundle['courses'], $scan_for_images);
                if (!empty($bundle['vote_courses'])) array_walk_recursive($bundle['vote_courses'], $scan_for_images);
                $downloaded = 0;
                foreach (array_unique($image_paths) as $path) {
                    $local_path = dirname(__DIR__) . $path;
                    if (file_exists($local_path)) { $downloaded++; continue; }
                    $dir = dirname($local_path);
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $img = @file_get_contents($source_domain . $path);
                    if (!$img) {
                        $ch = curl_init($source_domain . $path);
                        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_TIMEOUT=>10]);
                        $img = curl_exec($ch);
                    }
                    if ($img) { file_put_contents($local_path, $img); $downloaded++; }
                }

                header('Location: /admin/?tab=config&imported=' . $downloaded);
                exit;
            }
        }
        header('Location: /admin/?tab=config&import_error=1');
        exit;
    }

    // ── Change password (current user)
    if ($action === 'change_password') {
        $new     = trim($_POST['new_password']     ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        $cu      = clp_current_user();
        if ($new && $new === $confirm && strlen($new) >= 6 && $cu) {
            $users = load_users();
            foreach ($users as &$u) {
                if ($u['username'] === $cu['username']) {
                    $u['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
                    break;
                }
            }
            unset($u);
            save_users($users);
            header('Location: /admin/?tab=config&saved=1');
        } else {
            header('Location: /admin/?tab=config&error=1');
        }
        exit;
    }

    // ── Save design (colors + fonts)
    if ($action === 'save_design') {
        $settings = load_settings();
        $color_fields = ['color_bg','color_accent','color_text','color_text_muted','color_surface','color_btn_hover','color_banner'];
        foreach ($color_fields as $f) {
            $val = trim($_POST[$f] ?? '');
            if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) $settings[$f] = $val;
        }
        $font_heading = trim($_POST['font_heading'] ?? '');
        $font_body    = trim($_POST['font_body'] ?? '');
        if ($font_heading) $settings['font_heading'] = $font_heading;
        if ($font_body)    $settings['font_body']    = $font_body;
        save_settings($settings);
        header('Location: /admin/?tab=aspect&saved=1');
        exit;
    }

    // ── Save vote course
    if ($action === 'save_vote_course') {
        $id      = trim($_POST['vote_course_id'] ?? '');
        $courses = load_vote_courses();
        $entry   = [
            'id'          => $id ?: uniqid('vc', true),
            'name'        => trim($_POST['vc_name']        ?? ''),
            'emoji'       => trim($_POST['vc_emoji']       ?? '📚'),
            'description' => trim($_POST['vc_description'] ?? ''),
            'likes'       => 0,
        ];
        if ($id) {
            $found = false;
            foreach ($courses as &$c) {
                if (($c['id'] ?? '') === $id) {
                    // Preserve existing likes and active state when editing
                    $entry['likes']  = $c['likes']  ?? 0;
                    $entry['active'] = $c['active']  ?? true;
                    $c = $entry;
                    $found = true;
                    break;
                }
            }
            unset($c);
            if (!$found) $courses[] = $entry;
        } else {
            $courses[] = $entry;
        }
        save_vote_courses($courses);
        header('Location: /admin/?tab=vot&saved=1');
        exit;
    }

    // ── Delete vote course
    if ($action === 'delete_vote_course') {
        $id      = $_POST['id'] ?? '';
        $courses = load_vote_courses();
        $courses = array_filter($courses, fn($c) => ($c['id'] ?? '') !== $id);
        save_vote_courses($courses);
        header('Location: /admin/?tab=vot');
        exit;
    }

    // ── Toggle vote course active/inactive
    if ($action === 'toggle_vote_course') {
        $id      = $_POST['id'] ?? '';
        $courses = load_vote_courses();
        foreach ($courses as &$c) {
            if (($c['id'] ?? '') === $id) {
                $c['active'] = !($c['active'] ?? true);
                break;
            }
        }
        unset($c);
        save_vote_courses($courses);
        header('Location: /admin/?tab=vot');
        exit;
    }

    // ── Save speaker
    if ($action === 'save_speaker') {
        $id    = trim($_POST['speaker_id'] ?? '');
        $items = load_speakers();
        $entry = [
            'id'      => $id ?: uniqid('sp', true),
            'name'    => trim($_POST['sp_name']    ?? ''),
            'email'   => trim($_POST['sp_email']   ?? ''),
            'phone'   => trim($_POST['sp_phone']   ?? ''),
            'courses' => array_values(array_filter(array_map('trim', $_POST['sp_courses'] ?? []))),
            'status'  => in_array($_POST['sp_status'] ?? '', ['RECURENT','MID','NOPE']) ? $_POST['sp_status'] : 'MID',
            'notes'   => trim($_POST['sp_notes']   ?? ''),
        ];
        if ($id) {
            $found = false;
            foreach ($items as &$it) {
                if (($it['id'] ?? '') === $id) { $it = $entry; $found = true; break; }
            }
            unset($it);
            if (!$found) $items[] = $entry;
        } else {
            $items[] = $entry;
        }
        save_speakers($items);
        header('Location: /admin/?tab=speakeri&saved=1');
        exit;
    }

    // ── Delete speaker
    if ($action === 'delete_speaker') {
        $id    = $_POST['id'] ?? '';
        $items = load_speakers();
        $items = array_filter($items, fn($it) => ($it['id'] ?? '') !== $id);
        save_speakers($items);
        header('Location: /admin/?tab=speakeri');
        exit;
    }

    // ── Save location
    if ($action === 'save_location') {
        $id    = trim($_POST['location_id'] ?? '');
        $items = load_locations();
        $entry = [
            'id'        => $id ?: uniqid('loc', true),
            'name'      => trim($_POST['loc_name']  ?? ''),
            'phone'     => trim($_POST['loc_phone'] ?? ''),
            'maps_link' => trim($_POST['loc_maps']  ?? ''),
            'days'      => trim($_POST['loc_days']  ?? ''),
            'notes'     => trim($_POST['loc_notes'] ?? ''),
        ];
        if ($id) {
            $found = false;
            foreach ($items as &$it) {
                if (($it['id'] ?? '') === $id) { $it = $entry; $found = true; break; }
            }
            unset($it);
            if (!$found) $items[] = $entry;
        } else {
            $items[] = $entry;
        }
        save_locations($items);
        header('Location: /admin/?tab=locatii&saved=1');
        exit;
    }

    // ── Delete location
    if ($action === 'delete_location') {
        $id    = $_POST['id'] ?? '';
        $items = load_locations();
        $items = array_filter($items, fn($it) => ($it['id'] ?? '') !== $id);
        save_locations($items);
        header('Location: /admin/?tab=locatii');
        exit;
    }

    // ── Save collaboration
    if ($action === 'save_collaboration') {
        $id    = trim($_POST['collab_id'] ?? '');
        $items = load_collaborations();
        $entry = [
            'id'      => $id ?: uniqid('col', true),
            'name'    => trim($_POST['col_name']    ?? ''),
            'contact' => trim($_POST['col_contact'] ?? ''),
            'contact_info' => trim($_POST['col_contact_info'] ?? ''),
            'status'  => trim($_POST['col_status']  ?? ''),
            'notes'   => trim($_POST['col_notes']   ?? ''),
        ];
        if ($id) {
            $found = false;
            foreach ($items as &$it) {
                if (($it['id'] ?? '') === $id) { $it = $entry; $found = true; break; }
            }
            unset($it);
            if (!$found) $items[] = $entry;
        } else {
            $items[] = $entry;
        }
        save_collaborations($items);
        header('Location: /admin/?tab=colaborari&saved=1');
        exit;
    }

    // ── Delete collaboration
    if ($action === 'delete_collaboration') {
        $id    = $_POST['id'] ?? '';
        $items = load_collaborations();
        $items = array_filter($items, fn($it) => ($it['id'] ?? '') !== $id);
        save_collaborations($items);
        header('Location: /admin/?tab=colaborari');
        exit;
    }

    // ── Delete message
    if ($action === 'delete_message') {
        $idx  = (int)($_POST['msg_index'] ?? -1);
        $type = preg_replace('/[^a-z]/', '', $_POST['msg_type'] ?? '');
        $log_file = dirname(SETTINGS_FILE) . '/messages.log';
        if ($idx >= 0 && $type && file_exists($log_file)) {
            $raw    = file_get_contents($log_file);
            $blocks = preg_split('/(?=^===)/m', $raw);
            $blocks = array_values(array_filter(array_map('trim', $blocks)));
            // Find blocks of this type and remove the one at index $idx
            $type_i = 0;
            $to_remove = -1;
            for ($b = count($blocks) - 1; $b >= 0; $b--) {
                preg_match('/^===\s*.*?\s*\|\s*(\S+)\s*===/m', $blocks[$b], $m);
                $block_type = trim($m[1] ?? 'contact');
                if (!in_array($block_type, ['contact','sustine','gazduieste','parteneriat'])) $block_type = 'contact';
                if ($block_type === $type) {
                    if ($type_i === $idx) { $to_remove = $b; break; }
                    $type_i++;
                }
            }
            if ($to_remove >= 0) {
                $removed_id = msg_id_from_block($blocks[$to_remove]);
                array_splice($blocks, $to_remove, 1);
                file_put_contents($log_file, implode("\n\n", $blocks) . "\n", LOCK_EX);
                $meta = load_msg_meta();
                if (isset($meta[$removed_id])) {
                    unset($meta[$removed_id]);
                    save_msg_meta($meta);
                }
            }
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
        header('Location: /admin/?tab=mesaje&deleted=1');
        exit;
    }

    // ── Toggle read state (Contact)
    if ($action === 'mark_read_message') {
        header('Content-Type: application/json');
        $id = preg_replace('/[^a-f0-9]/', '', $_POST['msg_id'] ?? '');
        if (!$id) { echo json_encode(['ok' => false]); exit; }
        $read = !empty($_POST['read']);
        $meta = load_msg_meta();
        if (!isset($meta[$id])) $meta[$id] = [];
        $meta[$id]['read'] = $read;
        save_msg_meta($meta);
        echo json_encode(['ok' => true, 'read' => $read]);
        exit;
    }

    // ── Toggle contacted state
    if ($action === 'mark_contacted_message') {
        header('Content-Type: application/json');
        $id = preg_replace('/[^a-f0-9]/', '', $_POST['msg_id'] ?? '');
        if (!$id) { echo json_encode(['ok' => false]); exit; }
        $contacted = !empty($_POST['contacted']);
        $meta = load_msg_meta();
        if (!isset($meta[$id])) $meta[$id] = [];
        $meta[$id]['contacted'] = $contacted;
        save_msg_meta($meta);
        echo json_encode(['ok' => true, 'contacted' => $contacted]);
        exit;
    }

    // ── Set evaluation (Speakeri)
    if ($action === 'eval_message') {
        header('Content-Type: application/json');
        $id   = preg_replace('/[^a-f0-9]/', '', $_POST['msg_id'] ?? '');
        $eval = $_POST['eval'] ?? '';
        if (!$id || !in_array($eval, ['nope','meh','top',''], true)) {
            echo json_encode(['ok' => false]); exit;
        }
        $meta = load_msg_meta();
        if (!isset($meta[$id])) $meta[$id] = [];
        if ($eval === '') unset($meta[$id]['evaluation']);
        else $meta[$id]['evaluation'] = $eval;
        save_msg_meta($meta);
        echo json_encode(['ok' => true, 'evaluation' => $eval]);
        exit;
    }

    // ── Delete comment (Speakeri, owner only)
    if ($action === 'delete_message_comment' && is_owner()) {
        header('Content-Type: application/json');
        $id  = preg_replace('/[^a-f0-9]/', '', $_POST['msg_id'] ?? '');
        $idx = (int)($_POST['idx'] ?? -1);
        if (!$id || $idx < 0) { echo json_encode(['ok' => false]); exit; }
        $meta = load_msg_meta();
        if (isset($meta[$id]['comments'][$idx])) {
            array_splice($meta[$id]['comments'], $idx, 1);
            save_msg_meta($meta);
            echo json_encode(['ok' => true]);
            exit;
        }
        echo json_encode(['ok' => false]);
        exit;
    }

    // ── Add comment (Speakeri)
    if ($action === 'add_message_comment') {
        header('Content-Type: application/json');
        $id   = preg_replace('/[^a-f0-9]/', '', $_POST['msg_id'] ?? '');
        $text = trim($_POST['text'] ?? '');
        if (!$id || $text === '') { echo json_encode(['ok' => false]); exit; }
        $meta = load_msg_meta();
        if (!isset($meta[$id])) $meta[$id] = [];
        if (!isset($meta[$id]['comments'])) $meta[$id]['comments'] = [];
        $entry = [
            'text' => mb_substr($text, 0, 2000),
            'at'   => date('Y-m-d H:i:s'),
            'by'   => clp_current_user()['username'] ?? '',
        ];
        $meta[$id]['comments'][] = $entry;
        save_msg_meta($meta);
        echo json_encode(['ok' => true, 'comment' => $entry]);
        exit;
    }
}

// ── Navbar live (from live site editor) ──────────────────────────────────────
if (is_authenticated() && ($action === 'save_navbar_live')) {
    header('Content-Type: application/json');
    $s = load_settings();
    $color_keys = ['nav_bg','nav_brand_color','nav_link_color'];
    $num_keys   = ['nav_brand_size','nav_brand_weight','nav_link_size','nav_link_weight','nav_logo_h'];
    $font_keys  = ['nav_brand_font'];
    $allowed_fonts = ['Anton','Nunito','Poppins','Rubik','Inter','Playfair Display','Montserrat','Raleway','Oswald','Lora','DM Serif Display','Bebas Neue','Cormorant Garamond'];
    foreach ($color_keys as $k) {
        $v = trim($_POST[$k] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $v)) $s[$k] = $v;
    }
    foreach ($num_keys as $k) {
        $v = (int)($_POST[$k] ?? 0);
        if ($v > 0) $s[$k] = (string)$v;
    }
    foreach ($font_keys as $k) {
        $v = trim($_POST[$k] ?? '');
        if ($v && in_array($v, $allowed_fonts)) $s[$k] = $v;
    }
    save_settings($s);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Global fonts (from live site editor) ─────────────────────────────────────
if (is_authenticated() && ($action === 'save_global_fonts')) {
    $allowed_h = ['Anton','Nunito','Poppins','Rubik','Inter','Playfair Display','Montserrat','Raleway','Oswald','Lora','DM Serif Display','Bebas Neue','Cormorant Garamond'];
    $allowed_b = ['Inter','Roboto','Open Sans','Lato','DM Sans','Nunito','Rubik','Source Sans 3','Mulish','Cabin','Karla','Poppins'];
    header('Content-Type: application/json');
    $s  = load_settings();
    $fh = trim($_POST['font_heading'] ?? '');
    $fb = trim($_POST['font_body']    ?? '');
    if ($fh && in_array($fh, $allowed_h)) $s['font_heading'] = $fh;
    if ($fb && in_array($fb, $allowed_b)) $s['font_body']    = $fb;
    // Weight / italic / sizes
    foreach (['fh_weight','fb_weight'] as $k) {
        $v = (int)($_POST[$k] ?? 0);
        $s[$k] = ($v >= 100 && $v <= 900) ? (string)$v : '';
    }
    $s['fh_italic'] = !empty($_POST['fh_italic']) ? '1' : '';
    foreach (['fh_size_lg','fh_size_md','fh_size_sm','fb_size_lg','fb_size_md','fb_size_sm'] as $k) {
        $v = (int)($_POST[$k] ?? 0);
        $s[$k] = $v > 0 ? (string)$v : '';
    }
    save_settings($s);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Load data for display ─────────────────────────────────────────────────────
$courses  = [];
$settings = load_settings();
$tab      = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, ['dashboard','cursuri','imagini','aspect','kit','mesaje','vot','competitori','speakeri','locatii','colaborari','securitate','config'])) $tab = 'dashboard';
if (is_authenticated() && !can_access_tab($tab)) $tab = 'dashboard';

if (is_authenticated()) {
    $courses = load_courses();
    usort($courses, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
}

// ── Unread messages badge ─────────────────────────────────────────────────────
$_msg_last_read_file = dirname(SETTINGS_FILE) . '/messages_last_read.txt';
$_msg_log_file       = dirname(SETTINGS_FILE) . '/messages.log';
$_msg_unread_count   = 0;

if ($tab === 'mesaje' && is_authenticated()) {
    // Mark all current messages as read
    file_put_contents($_msg_last_read_file, date('Y-m-d H:i:s'), LOCK_EX);
} elseif (is_authenticated() && file_exists($_msg_log_file)) {
    $last_read = file_exists($_msg_last_read_file) ? trim(file_get_contents($_msg_last_read_file)) : '1970-01-01 00:00:00';
    $raw_log   = file_get_contents($_msg_log_file);
    preg_match_all('/^=== (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \|/m', $raw_log, $ts_matches);
    foreach ($ts_matches[1] as $ts) {
        if ($ts > $last_read) $_msg_unread_count++;
    }
}

// Collect images for imagini tab
function get_all_images(): array {
    $imgs = [];
    // Helper: collect files from a dir, skipping .webp when a .jpg/.jpeg/.png exists
    $collect = function(string $dir, string $url_prefix, bool $deletable) use (&$imgs) {
        if (!is_dir($dir)) return;
        $files = scandir($dir);
        $names = array_map(fn($f) => strtolower($f), $files);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            if (!is_file($dir . '/' . $f)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif','avif'])) continue;
            // Skip .webp if a matching .jpg/.jpeg/.png exists (it's an auto-generated duplicate)
            if ($ext === 'webp') {
                $base = strtolower(pathinfo($f, PATHINFO_FILENAME));
                if (in_array($base . '.jpg', $names) || in_array($base . '.jpeg', $names) || in_array($base . '.png', $names)) continue;
            }
            $imgs[] = ['url' => $url_prefix . $f, 'name' => $f, 'deletable' => $deletable];
        }
    };
    $collect(PUBLIC_HTML . '/assets/images/', '/assets/images/', false);
    $collect(PUBLIC_HTML . '/assets/images/gallery/', '/assets/images/gallery/', true);
    $collect(UPLOADS_DIR, UPLOADS_URL . '/', true);
    return $imgs;
}
?>
<!DOCTYPE html>
<html lang="ro" data-theme="corporate">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Cursuri la Pahar</title>
<?php if (!empty($settings['favicon_path'])): ?><link rel="icon" href="<?= htmlspecialchars($settings['favicon_path']) ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">
<script>tailwind={config:{corePlugins:{preflight:false}}}</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/coloris.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --text-muted: #6b7280;
    --border: #e5e7eb;
    --danger: #dc2626;
    --success: #16a34a;
    --accent: #1d4ed8;
    --surface: #ffffff;
    --bg: #f1f5f9;
    --text: #1f2937;
}
body { background: #f1f5f9; color: #1f2937; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; line-height: 1.5; min-height: 100vh; }

/* ── Login ── */
.login-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.login-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 40px; width: 380px; box-shadow: 0 8px 32px rgba(0,0,0,.08); }
.login-box h1 { font-size: 20px; font-weight: 700; margin-bottom: 24px; text-align: center; color: #111827; }
.login-box input[type="password"], .login-box input[type="text"] { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; margin-bottom: 12px; background: #fff; color: #1f2937; transition: border-color .15s, box-shadow .15s; box-sizing: border-box; }
.login-box input[type="password"]:focus, .login-box input[type="text"]:focus { outline: none; border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,.12); }
.login-error { color: #dc2626; font-size: 13px; margin-bottom: 10px; }

/* ── Top bar ── */
.wp-header { background: #1d232a; color: #fff; height: 52px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 100; box-shadow: 0 1px 4px rgba(0,0,0,.25); }
.wp-header .brand { font-size: 14px; font-weight: 700; color: #fff; text-decoration: none; }
.wp-header .brand span { opacity: .5; font-weight: 400; }
.wp-header-site-link { color: rgba(255,255,255,.65); font-size: 12px; text-decoration: none; padding: 5px 12px; border: 1px solid rgba(255,255,255,.2); border-radius: 8px; transition: background .15s, color .15s; }
.wp-header-site-link:hover { background: rgba(255,255,255,.1); color: #fff; }

/* ── Layout ── */
.wp-layout { display: flex; min-height: calc(100vh - 52px); margin-top: 52px; }

/* ── Sidebar ── */
.wp-sidebar { width: 220px; background: #1d232a; flex-shrink: 0; padding-top: 8px; position: fixed; top: 52px; left: 0; height: calc(100vh - 52px); overflow-y: auto; z-index: 99; }
.wp-sidebar nav a { display: flex; align-items: center; gap: 10px; padding: 9px 16px; color: #a6adba; text-decoration: none; font-size: 13px; font-weight: 500; border-left: 3px solid transparent; transition: background .15s, color .15s; }
.wp-sidebar nav a:hover { color: #fff; background: rgba(255,255,255,.06); }
.wp-sidebar nav a.active { color: #fff; background: #1d4ed8; border-left-color: #93c5fd; }
.wp-sidebar nav a .nav-icon { font-size: 15px; width: 20px; text-align: center; flex-shrink: 0; }
.sidebar-section { padding: 18px 16px 4px; font-size: 9px; text-transform: uppercase; letter-spacing: .1em; color: #4a5568; font-weight: 700; }
.sidebar-section.collapsible { cursor: pointer; user-select: none; display: flex; justify-content: space-between; align-items: center; padding-right: 14px; }
.sidebar-section.collapsible::after { content: '▾'; font-size: 11px; transition: transform .2s; }
.sidebar-section.collapsible.collapsed::after { transform: rotate(-90deg); }
.sidebar-collapse-content { overflow: hidden; transition: max-height .25s ease; max-height: 400px; }
.sidebar-collapse-content.collapsed { max-height: 0; }
.nav-new-badge { margin-left: auto; background: #ef4444; color: #fff; font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 10px; white-space: nowrap; }

/* ── Main content ── */
.wp-main { flex: 1; padding: 24px 28px; min-width: 0; margin-left: 220px; }
.wp-page-title { font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 20px; }

/* ── Cards ── */
.card { background: #fff !important; border: 1px solid #e5e7eb !important; border-radius: 12px !important; padding: 20px !important; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.04); display: block !important; flex-direction: unset !important; }
.card-title { font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; text-transform: uppercase; letter-spacing: .06em; }

/* ── Buttons ── */
.btn { display: inline-flex !important; align-items: center !important; gap: 5px; padding: 7px 16px !important; border-radius: 8px !important; border: 1px solid transparent; cursor: pointer; font-size: 13px !important; font-weight: 600; text-decoration: none; line-height: 1.4 !important; height: auto !important; min-height: auto !important; transition: background .15s, opacity .15s; }
.btn-primary { background: #1d4ed8 !important; border-color: #1d4ed8 !important; color: #fff !important; }
.btn-primary:hover { background: #1e40af !important; }
.btn-secondary { background: #f8fafc; border: 1px solid #e5e7eb !important; color: #374151 !important; }
.btn-secondary:hover { background: #f1f5f9; }
.btn-danger { background: #dc2626 !important; border-color: #dc2626 !important; color: #fff !important; }
.btn-danger:hover { background: #b91c1c !important; }
.btn-sm { padding: 3px 10px !important; font-size: 12px !important; }
.btn-link { background: transparent !important; border: none !important; color: #1d4ed8; padding: 0 !important; height: auto !important; min-height: auto !important; font-weight: 500; }
.btn-link:hover { text-decoration: underline; }
.status-active { background: #dcfce7 !important; border-color: #16a34a !important; color: #15803d !important; }
.status-active:hover { background: #bbf7d0 !important; }
.status-inactive { background: #f8fafc !important; border-color: #e5e7eb !important; color: #9ca3af !important; }
.btn-logout { background: transparent; border: 1px solid rgba(255,255,255,.25); color: rgba(255,255,255,.75); padding: 5px 12px; font-size: 12px; border-radius: 8px; cursor: pointer; text-decoration: none; transition: background .15s; }
.btn-logout:hover { background: rgba(255,255,255,.1); color: #fff; }

/* ── Forms ── */
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }
.form-group input[type="text"],
.form-group input[type="url"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="number"],
.form-group textarea,
.form-group select { width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; font-family: inherit; color: #1f2937; background: #fff; transition: border-color .15s, box-shadow .15s; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,.1); }
.form-group textarea { resize: vertical; min-height: 80px; }
.form-desc { font-size: 11px; color: #9ca3af; margin-top: 4px; }
.import-row { display: flex; gap: 8px; }
.import-row input { flex: 1; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; color: #1f2937; background: #fff; }
.import-row input:focus { outline: none; border-color: #1d4ed8; }
#importMsg { margin-top: 8px; font-size: 13px; }

/* ── Course preview ── */
.course-preview { display: flex; gap: 14px; align-items: flex-start; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; margin: 14px 0; }
.course-preview img { width: 90px; height: 56px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.course-preview-body { flex: 1; min-width: 0; }
.course-preview-title { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
.course-preview-meta { font-size: 12px; color: #9ca3af; }

/* ── Table ── */
.wp-table { width: 100%; border-collapse: collapse; }
.wp-table th { text-align: left; padding: 9px 12px; font-size: 10px; font-weight: 700; color: #9ca3af; background: #f8fafc; border-bottom: 1px solid #f1f5f9; text-transform: uppercase; letter-spacing: .06em; }
.wp-table td { padding: 11px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #374151; }
.wp-table tbody tr:last-child td { border-bottom: none; }
.wp-table tbody tr:hover td { background: #f8fafc; }
.course-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 6px; display: block; }
.course-thumb-empty { width: 60px; height: 40px; background: #f1f5f9; border: 1px solid #e5e7eb; border-radius: 6px; }
.row-actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
.discount-tag { display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; vertical-align: middle; }
.discount-tag--active { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.discount-tag--expired { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.discount-edit-row > td { background: #fafafa !important; padding: 14px 16px !important; }
.discount-form { display: flex; flex-wrap: wrap; align-items: end; gap: 14px; }
.discount-form label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #475569; font-weight: 600; }
.discount-form input { padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }

/* ── Notices ── */
.notice { padding: 12px 16px; border-radius: 8px; border-left: 4px solid; margin-bottom: 16px; font-size: 13px; }
.notice-success { background: #f0fdf4; border-left-color: #16a34a; color: #15803d; }
.notice-error { background: #fef2f2; border-left-color: #dc2626; color: #991b1b; }

/* ── Images ── */
.images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
.image-item { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: #fff; }
.image-item img { width: 100%; height: 100px; object-fit: cover; display: block; }
.image-item-body { padding: 8px; }
.image-item-name { font-size: 11px; color: #9ca3af; word-break: break-all; margin-bottom: 6px; }
.image-item-actions { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.hero-check { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #374151; cursor: pointer; }
.hero-check input { accent-color: #1d4ed8; width: 14px; height: 14px; cursor: pointer; }

/* ── CRM ── */
.crm-status-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; color: #fff; }
.crm-table td { vertical-align: top; }

/* ── Messages tabs ── */
.msg-tab { padding: 6px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; font-size: 13px; cursor: pointer; color: #374151; transition: background .15s; }
.msg-tab:hover { background: #f1f5f9; }
.msg-tab.active { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }

/* ── Coloris ── */
.clr-field { width: 100%; }
.clr-field input { width: 100%; padding: 9px 48px 9px 12px !important; border: 1px solid #e5e7eb; border-radius: 8px; font-family: monospace; font-size: 13px; background: #fff; color: #1f2937; cursor: pointer; box-sizing: border-box; }
.clr-field button { width: 36px; height: calc(100% - 2px); border-radius: 0 7px 7px 0; right: 1px; left: auto; top: 1px; transform: none; }
.clr-picker { width: 320px !important; }
#clr-color-area { height: 240px !important; }
#clr-format { display: none !important; }
.clr-alpha { display: none !important; }
</style>
</head>
<body>

<?php if (!is_authenticated()): ?>
<!-- ── LOGIN ─────────────────────────────────────────────────────────────────── -->
<div class="login-wrap">
    <div class="login-box">
        <h1>Cursuri la Pahar<br><small style="font-size:13px;color:var(--text-muted);font-weight:400">Panou de administrare</small></h1>
        <?php if (!empty($login_error)): ?>
        <p class="login-error"><?= h($login_error) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="login_username" autocomplete="username" autofocus style="margin-bottom:8px">
            <input type="password" name="login_password" autocomplete="current-password">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Intră</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── ADMIN PANEL ─────────────────────────────────────────────────────────── -->

<header class="wp-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="/admin/" class="brand">Cursuri la Pahar <span>— Admin</span></a>
        <a href="/" class="wp-header-site-link">🌐 Vezi site</a>
    </div>
    <?php
    $real_user = clp_real_user();
    $is_imp    = is_impersonating();
    if ($real_user && ($real_user['role'] ?? '') === 'owner'):
        $all_users = load_users();
        $cur_view  = clp_current_user()['username'] ?? '';
    ?>
    <div style="display:flex;align-items:center;gap:8px">
        <?php if ($is_imp): ?>
        <span style="font-size:11px;background:#fef3c7;color:#92400e;padding:3px 8px;border-radius:12px;font-weight:600">
            Vizualizezi ca: <?= h(ucfirst($cur_view)) ?>
        </span>
        <form method="post" action="/admin/" style="margin:0">
            <input type="hidden" name="action" value="switch_user">
            <input type="hidden" name="target_username" value="<?= h($real_user['username']) ?>">
            <button type="submit" style="font-size:11px;padding:3px 8px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer;color:#374151">
                Înapoi la <?= h(ucfirst($real_user['username'])) ?>
            </button>
        </form>
        <?php else: ?>
        <span style="font-size:12px;color:#a0aec0"><?= h(ucfirst($real_user['username'])) ?></span>
        <div style="position:relative" id="user-switcher">
            <button id="user-switcher-btn"
                style="padding:2px 5px;border:none;background:none;cursor:pointer;color:#c0c8d4;font-size:10px;line-height:1" title="Schimbă cont">
                ▾
            </button>
            <div id="user-switcher-menu" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);min-width:140px;z-index:999">
                <?php foreach ($all_users as $u): if ($u['username'] === $real_user['username']) continue; ?>
                <form method="post" action="/admin/" style="margin:0">
                    <input type="hidden" name="action" value="switch_user">
                    <input type="hidden" name="target_username" value="<?= h($u['username']) ?>">
                    <button type="submit" style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;cursor:pointer;font-size:13px;color:#374151">
                        <?= h(ucfirst($u['username'])) ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        (function() {
            var btn = document.getElementById('user-switcher-btn');
            var menu = document.getElementById('user-switcher-menu');
            btn.addEventListener('click', function(e) { e.stopPropagation(); menu.style.display = menu.style.display === 'block' ? 'none' : 'block'; });
            document.addEventListener('click', function() { menu.style.display = 'none'; });
        })();
        </script>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <span style="font-size:12px;color:#a0aec0"><?= h(ucfirst(clp_current_user()['username'] ?? '')) ?></span>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="btn-logout">Deconectează-te</a>
</header>

<div class="wp-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="wp-sidebar">
        <nav>
            <a href="/admin/" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span> Dashboard
            </a>
            <?php if (is_owner()): ?>
            <div class="sidebar-section">Conținut</div>
            <a href="/admin/?tab=cursuri" class="<?= $tab === 'cursuri' ? 'active' : '' ?>">
                <span class="nav-icon">📋</span> Cursuri
            </a>
            <a href="/admin/?tab=imagini" class="<?= $tab === 'imagini' ? 'active' : '' ?>">
                <span class="nav-icon">🖼️</span> Imagini
            </a>
            <a href="/admin/?tab=aspect" class="<?= $tab === 'aspect' ? 'active' : '' ?>">
                <span class="nav-icon">🎨</span> Aspect
            </a>
            <?php endif; ?>
            <a href="/admin/?tab=vot" class="<?= $tab === 'vot' ? 'active' : '' ?>">
                <span class="nav-icon">❤️</span> Vot cursuri
            </a>
            <div class="sidebar-section">Comunitate</div>
            <a href="/admin/?tab=mesaje" class="<?= $tab === 'mesaje' ? 'active' : '' ?>">
                <span class="nav-icon">💬</span> Mesaje<?php if ($_msg_unread_count > 0): ?><span class="nav-new-badge"><?= $_msg_unread_count ?> <?= $_msg_unread_count === 1 ? 'nou' : 'noi' ?></span><?php endif; ?>
            </a>
            <a href="/admin/?tab=competitori" class="<?= $tab === 'competitori' ? 'active' : '' ?>">
                <span class="nav-icon">🔍</span> Competitori
            </a>
            <div class="sidebar-section">CRM</div>
            <a href="/admin/?tab=speakeri" class="<?= $tab === 'speakeri' ? 'active' : '' ?>">
                <span class="nav-icon">🎤</span> Speakeri
            </a>
            <a href="/admin/?tab=locatii" class="<?= $tab === 'locatii' ? 'active' : '' ?>">
                <span class="nav-icon">📍</span> Locații
            </a>
            <a href="/admin/?tab=colaborari" class="<?= $tab === 'colaborari' ? 'active' : '' ?>">
                <span class="nav-icon">🤝</span> Colaborări
            </a>
            <div class="sidebar-section">Statistici</div>
            <a href="/admin/statistici/cursuri/">
                <span class="nav-icon">📋</span> Cursuri
            </a>
            <a href="/admin/statistici/participanti/">
                <span class="nav-icon">👥</span> Participanti
            </a>
            <?php if (is_owner()): ?>
            <a href="/admin/statistici/pnl/">
                <span class="nav-icon">📈</span> P&amp;L Cursuri
            </a>
            <div class="sidebar-section">Sistem</div>
            <a href="/admin/?tab=config" class="<?= $tab === 'config' || $tab === 'securitate' || $tab === 'kit' ? 'active' : '' ?>">
                <span class="nav-icon">⚙️</span> Setări
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- ── MAIN ── -->
    <main class="wp-main">

<?php /* ======================================================= TAB: DASHBOARD */ ?>
<?php if ($tab === 'dashboard'): ?>

<?php
// ── Dashboard data ───────────────────────────────────────────────────────────
$_dash_courses = load_courses();
$_dash_active  = count(array_filter($_dash_courses, fn($c) => !empty($c['active'])));

// Upcoming courses (future, sorted by date)
$_dash_today = date('Y-m-d');
$_dash_upcoming = array_filter($_dash_courses, fn($c) => !empty($c['active']) && ($c['date_raw'] ?? '') >= $_dash_today);
usort($_dash_upcoming, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
$_dash_upcoming = array_slice($_dash_upcoming, 0, 5);

// P&L stats (current month)
$_dash_pnl_profit = 0;
$_dash_pnl_venituri = 0;
$_dash_pnl_cheltuieli = 0;
$_dash_pnl_year  = date('Y');
$_dash_pnl_month = str_pad(date('n'), 2, '0', STR_PAD_LEFT);
$_dash_pnl_db_path = __DIR__ . '/statistici/data/pnl.sqlite';
if (file_exists($_dash_pnl_db_path)) {
    try {
        $_pdb = new SQLite3($_dash_pnl_db_path);
        $_pdb->exec('PRAGMA journal_mode=WAL');
        $_dash_pnl_venituri = (float)$_pdb->querySingle("SELECT COALESCE(SUM(suma),0) FROM venituri WHERE strftime('%Y',data)='{$_dash_pnl_year}' AND strftime('%m',data)='{$_dash_pnl_month}'");
        $_dash_pnl_cheltuieli = (float)$_pdb->querySingle("SELECT COALESCE(SUM(suma),0) FROM cheltuieli WHERE strftime('%Y',data)='{$_dash_pnl_year}' AND strftime('%m',data)='{$_dash_pnl_month}'");
        $_dash_pnl_profit = $_dash_pnl_venituri - $_dash_pnl_cheltuieli;
        $_pdb->close();
    } catch (Exception $e) {}
}

// Participants stats
$_dash_participants = 0;
$_dash_total_tickets = 0;
$_dash_clp_db_path = __DIR__ . '/statistici/data/clp.sqlite';
if (file_exists($_dash_clp_db_path)) {
    try {
        $_cdb = new SQLite3($_dash_clp_db_path);
        $_cdb->exec('PRAGMA journal_mode=WAL');
        $_dash_participants = (int)$_cdb->querySingle("SELECT COUNT(DISTINCT LOWER(TRIM(participant_name))) FROM tickets");
        $_dash_total_tickets = (int)$_cdb->querySingle("SELECT COUNT(*) FROM tickets");
        $_cdb->close();
    } catch (Exception $e) {}
}

// Vote courses (top voted)
$_dash_votes = load_vote_courses();
usort($_dash_votes, fn($a, $b) => ($b['likes'] ?? 0) - ($a['likes'] ?? 0));
$_dash_votes = array_slice($_dash_votes, 0, 5);

// P&L monthly data for chart (current year)
$_dash_pnl_monthly = [];
if (file_exists($_dash_pnl_db_path)) {
    try {
        $_pdb2 = new SQLite3($_dash_pnl_db_path);
        $_pdb2->exec('PRAGMA journal_mode=WAL');
        $_mv = []; $_mc = [];
        $r = $_pdb2->query("SELECT strftime('%m',data) as m, COALESCE(SUM(suma),0) as s FROM venituri WHERE strftime('%Y',data)='{$_dash_pnl_year}' GROUP BY m ORDER BY m");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_mv[$row['m']] = (float)$row['s'];
        $r = $_pdb2->query("SELECT strftime('%m',data) as m, COALESCE(SUM(suma),0) as s FROM cheltuieli WHERE strftime('%Y',data)='{$_dash_pnl_year}' GROUP BY m ORDER BY m");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_mc[$row['m']] = (float)$row['s'];
        for ($i = 1; $i <= (int)date('n'); $i++) {
            $k = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $_dash_pnl_monthly[] = ['v' => $_mv[$k] ?? 0, 'c' => $_mc[$k] ?? 0];
        }
        $_pdb2->close();
    } catch (Exception $e) {}
}

// Participant evolution (last 3 months)
$_dash_participant_months = [];
if (file_exists($_dash_clp_db_path)) {
    try {
        $_cdb2 = new SQLite3($_dash_clp_db_path);
        $_cdb2->exec('PRAGMA journal_mode=WAL');
        $r = $_cdb2->query("SELECT strftime('%Y-%m', c.date) as m, COUNT(DISTINCT LOWER(TRIM(t.participant_name))) as unici, COUNT(*) as bilete
            FROM tickets t JOIN courses c ON c.id = t.course_id
            GROUP BY m ORDER BY m DESC LIMIT 6");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_dash_participant_months[] = $row;
        $_dash_participant_months = array_reverse($_dash_participant_months);
        $_cdb2->close();
    } catch (Exception $e) {}
}

// Top returning participants
$_dash_top_fideli = [];
if (file_exists($_dash_clp_db_path)) {
    try {
        $_cdb3 = new SQLite3($_dash_clp_db_path);
        $_cdb3->exec('PRAGMA journal_mode=WAL');
        $r = $_cdb3->query("SELECT participant_name, COUNT(DISTINCT course_id) as nr_cursuri, COUNT(*) as nr_bilete
            FROM tickets GROUP BY LOWER(TRIM(participant_name)) HAVING nr_cursuri > 1
            ORDER BY nr_cursuri DESC, nr_bilete DESC LIMIT 5");
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_dash_top_fideli[] = $row;
        $_cdb3->close();
    } catch (Exception $e) {}
}

// DITL current year
$_dash_ditl_year = 0;
if (file_exists($_dash_clp_db_path)) {
    try {
        $_cdb4 = new SQLite3($_dash_clp_db_path);
        $_cdb4->exec('PRAGMA journal_mode=WAL');
        $_dash_ditl_year = (float)$_cdb4->querySingle("SELECT COALESCE(SUM(total_incasari),0) FROM course_reports r JOIN courses c ON c.id=r.course_id WHERE strftime('%Y',c.date)='{$_dash_pnl_year}'") * 0.02;
        $_cdb4->close();
    } catch (Exception $e) {}
}

$_ro_months_dash = ['','ian','feb','mar','apr','mai','iun','iul','aug','sep','oct','nov','dec'];
$_ro_months_full = ['','ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'];
$_dash_month_label = $_ro_months_full[(int)date('n')] . ' ' . date('Y');
?>

<h1 class="wp-page-title">Dashboard</h1>

<?php
$_ql = $settings['quick_links'] ?? [];
$_ql_general = [];
$_ql_canva   = [];
foreach ($_ql as $_ql_item) {
    if (str_contains($_ql_item['url'] ?? '', 'canva.com')) $_ql_canva[] = $_ql_item;
    else $_ql_general[] = $_ql_item;
}
if (!empty($_ql)): ?>
<style>
.ql-btn { display:inline-flex;align-items:center;gap:7px;padding:9px 16px;background:#fff;border:1px solid var(--border);border-radius:6px;text-decoration:none;color:var(--text);font-size:13px;font-weight:500;transition:border-color .15s,background .15s,color .15s; }
.ql-btn:hover { border-color:var(--accent);background:var(--accent);color:#fff; }
.ql-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px; }
@media (max-width: 900px) { .ql-grid { grid-template-columns:1fr; } }
</style>
<div class="ql-grid">
    <?php if (!empty($_ql_general)): ?>
    <div class="dash-section" style="margin:0">
        <div class="dash-section-title"><span>Linkuri utile</span></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($_ql_general as $_ql_item): ?>
            <a href="<?= h($_ql_item['url'] ?? '#') ?>" target="_blank" rel="noopener" class="ql-btn">
                <span style="font-size:17px"><?= h($_ql_item['icon'] ?? '🔗') ?></span>
                <?= h($_ql_item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($_ql_canva)): ?>
    <div class="dash-section" style="margin:0">
        <div class="dash-section-title"><span>Canva</span></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($_ql_canva as $_ql_item): ?>
            <a href="<?= h($_ql_item['url'] ?? '#') ?>" target="_blank" rel="noopener" class="ql-btn">
                <span style="font-size:17px"><?= h($_ql_item['icon'] ?? '🔗') ?></span>
                <?= h($_ql_item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<style>
.dash-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; }
.dash-card { background: var(--surface); border: 1px solid var(--border); border-radius: 4px; padding: 18px 20px; }
.dash-card .dash-label { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
.dash-card .dash-value { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; line-height: 1.1; }
.dash-card .dash-sub { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
.dash-card.accent-green { border-top: 3px solid var(--success); }
.dash-card.accent-blue { border-top: 3px solid #2271b1; }
.dash-card.accent-gold { border-top: 3px solid #B8860B; }
.dash-card.accent-red { border-top: 3px solid var(--danger); }
.dash-value.positive { color: var(--success); }
.dash-value.negative { color: var(--danger); }
.dash-section { background: var(--surface); border: 1px solid var(--border); border-radius: 4px; padding: 20px; margin-bottom: 20px; }
.dash-section-title { font-size: 14px; font-weight: 600; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.dash-section-title a { color: var(--accent); text-decoration: none; font-size: 12px; font-weight: 400; }
.dash-section-title a:hover { text-decoration: underline; }
.dash-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.dash-table td { padding: 8px 0; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
.dash-table tr:last-child td { border-bottom: none; }
.dash-table .muted { color: var(--text-muted); }
.dash-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.dash-chart-wrap { position: relative; height: 200px; }
.fidel-badge { background: #e8f4fd; color: #2271b1; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr 1fr; } .dash-cols { grid-template-columns: 1fr; } }
@media (max-width: 600px) { .dash-grid { grid-template-columns: 1fr; } }
</style>

<!-- Stats cards -->
<div class="dash-grid">
    <div class="dash-card accent-blue">
        <div class="dash-label">Cursuri active</div>
        <div class="dash-value"><?= $_dash_active ?></div>
        <div class="dash-sub">din <?= count($_dash_courses) ?> total</div>
    </div>
    <div class="dash-card accent-green">
        <div class="dash-label">Participanti unici</div>
        <div class="dash-value"><?= number_format($_dash_participants, 0, ',', '.') ?></div>
        <div class="dash-sub"><?= number_format($_dash_total_tickets, 0, ',', '.') ?> bilete total</div>
    </div>
</div>

<div class="dash-cols">
    <!-- Left column -->
    <div>
        <!-- Upcoming courses -->
        <div class="dash-section">
            <div class="dash-section-title"><span>Urmatoarele cursuri</span><a href="?tab=cursuri" style="font-size:12px;font-weight:400;color:var(--primary);text-decoration:none;margin-left:10px">+ Adaugă</a></div>
            <?php if (empty($_dash_upcoming)): ?>
                <p style="color:var(--text-muted);font-size:13px">Niciun curs programat.</p>
            <?php else: ?>
                <table class="dash-table">
                <?php foreach ($_dash_upcoming as $_uc): ?>
                    <tr>
                        <td style="font-weight:600"><?= h($_uc['title'] ?? '') ?></td>
                        <td class="muted" style="white-space:nowrap;text-align:right"><?= h($_uc['date_display'] ?? $_uc['date_raw'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- Participant evolution -->
        <div class="dash-section">
            <div class="dash-section-title"><span>Evolutie participanti</span></div>
            <?php if (empty($_dash_participant_months)): ?>
                <p style="color:var(--text-muted);font-size:13px">Nicio data disponibila.</p>
            <?php else: ?>
                <table class="dash-table">
                    <tr style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">
                        <td>Luna</td><td style="text-align:right">Unici</td><td style="text-align:right">Bilete</td>
                    </tr>
                <?php foreach ($_dash_participant_months as $_pm):
                    $pmIdx = (int)substr($_pm['m'], 5, 2);
                ?>
                    <tr>
                        <td><?= ucfirst($_ro_months_full[$pmIdx]) ?> <?= substr($_pm['m'], 0, 4) ?></td>
                        <td style="text-align:right;font-weight:600"><?= $_pm['unici'] ?></td>
                        <td style="text-align:right" class="muted"><?= $_pm['bilete'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right column -->
    <div>
        <!-- Vote courses -->
        <div class="dash-section">
            <div class="dash-section-title"><span>Vot cursuri</span></div>
            <?php if (empty($_dash_votes)): ?>
                <p style="color:var(--text-muted);font-size:13px">Nicio propunere de curs.</p>
            <?php else: ?>
                <table class="dash-table">
                <?php foreach ($_dash_votes as $_vc): ?>
                    <tr>
                        <td><?= $_vc['emoji'] ?? '' ?> <?= h($_vc['name'] ?? '') ?></td>
                        <td class="muted" style="text-align:right;white-space:nowrap"><?= (int)($_vc['likes'] ?? 0) ?> voturi</td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- Top fideli -->
        <div class="dash-section">
            <div class="dash-section-title"><span>Participanti fideli</span></div>
            <?php if (empty($_dash_top_fideli)): ?>
                <p style="color:var(--text-muted);font-size:13px">Niciun participant recurent.</p>
            <?php else: ?>
                <table class="dash-table">
                <?php foreach ($_dash_top_fideli as $_tf): ?>
                    <tr>
                        <td style="font-weight:600"><?= h($_tf['participant_name']) ?></td>
                        <td style="text-align:right"><span class="fidel-badge"><?= $_tf['nr_cursuri'] ?> cursuri</span></td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php /* ======================================================= TAB: CURSURI */ ?>
<?php elseif ($tab === 'cursuri'): ?>

    <h1 class="wp-page-title">Cursuri</h1>

    <!-- Import section -->
    <div class="card">
        <div class="card-title">Importă curs din LiveTickets</div>
        <div class="import-row">
            <input type="url" id="ltUrl" placeholder="https://www.livetickets.ro/bilete/slug-eveniment">
            <button class="btn btn-primary" onclick="importLT()">Importă</button>
        </div>
        <div id="importMsg"></div>

        <!-- Hidden form — shown after import -->
        <form method="post" action="/admin/?tab=cursuri" id="courseForm" style="display:none;margin-top:14px;">
            <input type="hidden" name="action" value="save_course">
            <input type="hidden" name="course_id"        id="f_id">
            <input type="hidden" name="title"            id="f_title">
            <input type="hidden" name="date_display"     id="f_date_display">
            <input type="hidden" name="date_raw"         id="f_date_raw">
            <input type="hidden" name="time"             id="f_time">
            <input type="hidden" name="location"         id="f_location">
            <input type="hidden" name="livetickets_url"  id="f_lt_url">
            <input type="hidden" name="image_url"        id="f_image_url">
            <input type="hidden" name="active"           value="1">

            <div class="course-preview" id="coursePreview" style="display:none">
                <img id="prev_img" src="" alt="" style="display:none">
                <div class="course-preview-body">
                    <div class="course-preview-title" id="prev_title"></div>
                    <div class="course-preview-meta" id="prev_meta"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Adaugă cursul</button>
        </form>
    </div>

    <?php
    $today_ymd = date('Y-m-d');
    $courses_upcoming = [];
    $courses_past = [];
    foreach ($courses as $c) {
        if (!empty($c['date_raw']) && $c['date_raw'] < $today_ymd) {
            $courses_past[] = $c;
        } else {
            $courses_upcoming[] = $c;
        }
    }
    // Past: most recent first
    usort($courses_past, fn($a, $b) => strcmp($b['date_raw'] ?? '', $a['date_raw'] ?? ''));
    $render_courses_table = function(array $list) {
        ?>
        <table class="wp-table">
            <thead>
                <tr>
                    <th style="width:72px">Imagine</th>
                    <th>Titlu</th>
                    <th>Dată</th>
                    <th style="width:100px">Status</th>
                    <th style="width:180px">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $c):
                    $cid = $c['id'] ?? '';
                    $has_disc = !empty($c['discount_percent']) && !empty($c['discount_ends_at']);
                    $disc_local = '';
                    $disc_active_now = false;
                    if ($has_disc) {
                        try {
                            $dt = new DateTime($c['discount_ends_at']);
                            $dt->setTimezone(new DateTimeZone('Europe/Bucharest'));
                            $disc_local = $dt->format('Y-m-d\TH:i');
                            $disc_active_now = $dt->getTimestamp() > time();
                        } catch (Exception $e) {}
                    }
                ?>
                <tr>
                    <td>
                        <?php if (!empty($c['image_url'])): ?>
                        <img class="course-thumb" src="<?= h($c['image_url']) ?>" alt="">
                        <?php else: ?>
                        <div class="course-thumb-empty"></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:600">
                        <?= h($c['title'] ?? '') ?>
                        <?php if ($has_disc): ?>
                            <span class="discount-tag <?= $disc_active_now ? 'discount-tag--active' : 'discount-tag--expired' ?>">
                                −<?= (int)$c['discount_percent'] ?>%<?= $disc_active_now ? '' : ' (expirată)' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted)"><?= h($c['date_display'] ?? $c['date_raw'] ?? '') ?></td>
                    <td>
                        <form method="post" action="/admin/?tab=cursuri" style="display:inline">
                            <input type="hidden" name="action" value="toggle_course">
                            <input type="hidden" name="id" value="<?= h($cid) ?>">
                            <button type="submit" class="btn btn-sm <?= !empty($c['active']) ? 'status-active' : 'status-inactive' ?>">
                                <?= !empty($c['active']) ? 'Activ' : 'Inactiv' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="row-actions">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="toggleDiscountRow('<?= h($cid) ?>')">Reducere ▾</button>
                            <form method="post" action="/admin/?tab=cursuri" onsubmit="return confirm('Ștergi cursul?')" style="display:inline">
                                <input type="hidden" name="action" value="delete_course">
                                <input type="hidden" name="id" value="<?= h($cid) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                            </form>
                            <?php if (!empty($c['livetickets_url'])): ?>
                            <a href="<?= h($c['livetickets_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">LT ↗</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <tr id="discount-row-<?= h($cid) ?>" class="discount-edit-row" style="display:none">
                    <td colspan="5">
                        <form method="post" action="/admin/?tab=cursuri" class="discount-form">
                            <input type="hidden" name="action" value="save_discount">
                            <input type="hidden" name="id" value="<?= h($cid) ?>">
                            <label>Reducere (%):
                                <input type="number" name="discount_percent" min="1" max="100" value="<?= $has_disc ? (int)$c['discount_percent'] : '' ?>" style="width:90px">
                            </label>
                            <label>Expiră la (ora București):
                                <input type="datetime-local" name="discount_ends_at" value="<?= h($disc_local) ?>">
                            </label>
                            <button type="submit" class="btn btn-sm btn-primary">Salvează reducerea</button>
                            <?php if ($has_disc): ?>
                                <button type="submit" name="clear" value="1" class="btn btn-sm btn-danger" onclick="return confirm('Ștergi reducerea?')">Șterge reducerea</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    };
    ?>

    <!-- Courses table (upcoming) -->
    <div class="card">
        <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
            <span>Cursuri (<?= count($courses_upcoming) ?>)</span>
            <form method="post" action="/admin/?tab=cursuri" style="margin:0">
                <input type="hidden" name="action" value="clear_soldout_cache">
                <button class="btn btn-secondary" type="submit" title="Șterge cache-ul de sold out — util dacă s-au adăugat bilete înapoi">
                    &#8635; Resetează sold out cache
                </button>
            </form>
        </div>
        <?php if (empty($courses_upcoming)): ?>
        <p style="color:var(--text-muted)">Nu există cursuri adăugate încă.</p>
        <?php else: $render_courses_table($courses_upcoming); endif; ?>
    </div>

    <?php if (!empty($courses_past)): ?>
    <!-- Courses table (past / auto-deactivated) -->
    <div class="card">
        <div class="card-title">Cursuri trecute (<?= count($courses_past) ?>)</div>
        <p style="color:var(--text-muted);margin:-4px 0 12px">Aceste cursuri au fost dezactivate automat după ce a trecut data evenimentului.</p>
        <?php $render_courses_table($courses_past); ?>
    </div>
    <?php endif; ?>

<?php /* ======================================================= TAB: IMAGINI */ ?>
<?php elseif ($tab === 'imagini'): ?>

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
    <script>
    function deleteImage(filename) {
        if (!confirm('Ștergi imaginea?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_image');
        fd.append('filename', filename);
        fetch('/admin/?tab=imagini', { method: 'POST', body: fd })
            .then(() => location.reload());
    }
    </script>

<?php /* ======================================================= TAB: ASPECT */ ?>
<?php elseif ($tab === 'aspect'): ?>
<h1 class="wp-page-title">Aspect</h1>
<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Setările de aspect au fost salvate.</div>
<?php endif; ?>

<!-- Logo -->
<div class="card">
    <div class="card-title">Logo</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">Logo curent: <code><?= h($settings['logo_path'] ?? '') ?></code></p>
    <?php if (!empty($settings['logo_path'])): ?>
    <img src="<?= h($settings['logo_path']) ?>" alt="Logo" style="max-height:60px;margin-bottom:12px;display:block;background:#1d2327;padding:8px;border-radius:4px;">
    <?php endif; ?>
    <form method="post" action="/admin/?tab=aspect" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_logo">
        <div style="display:flex;gap:8px;align-items:center">
            <input type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.svg" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
            <button type="submit" class="btn btn-primary">Încarcă logo</button>
        </div>
        <p class="form-desc">Formate: JPG, PNG, WEBP, SVG.</p>
    </form>
</div>

<!-- Favicon -->
<div class="card">
    <div class="card-title">Favicon</div>
    <?php if (!empty($settings['favicon_path'])): ?>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">Favicon curent: <code><?= h($settings['favicon_path']) ?></code></p>
    <?php endif; ?>
    <?php if (!empty($favicon_error)): ?>
    <div style="background:#fcf0f1;border:1px solid #f5c6cb;color:#c0392b;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:12px"><?= $favicon_error ?></div>
    <?php endif; ?>
    <form method="post" action="/admin/?tab=aspect" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_favicon">
        <div style="display:flex;gap:8px;align-items:center">
            <input type="file" name="favicon_file" accept=".ico,.png,.jpg,.jpeg,.webp" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
            <button type="submit" class="btn btn-primary">Încarcă favicon</button>
        </div>
        <p class="form-desc">Formate: ICO, PNG, JPG, WEBP. Fișierul va fi salvat în rădăcina site-ului.</p>
    </form>
</div>

<!-- Culori & Fonturi -->
<form method="post" action="/admin/?tab=aspect">
    <input type="hidden" name="action" value="save_design">
    <div class="card" style="margin-top:20px">
        <div class="card-title">Culori &amp; Fonturi</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <?php
            $color_fields_ui = [
                'color_bg'         => ['label' => 'Fundal principal',      'default' => '#0D0D0D'],
                'color_accent'     => ['label' => 'Culoare accent',         'default' => '#C9A84C'],
                'color_text'       => ['label' => 'Culoare text',           'default' => '#E8E4DC'],
                'color_text_muted' => ['label' => 'Text secundar',          'default' => '#9CA3AF'],
                'color_surface'    => ['label' => 'Fundal carduri/secțiuni','default' => '#161616'],
                'color_btn_hover'  => ['label' => 'Hover butoane',          'default' => '#b8922e'],
                'color_banner'     => ['label' => 'Fundal banner anunț',    'default' => '#FFB000'],
            ];
            foreach ($color_fields_ui as $fname => $meta):
                $val = h($settings[$fname] ?? $meta['default']);
            ?>
            <div class="form-group" style="margin:0">
                <label><?= $meta['label'] ?></label>
                <input type="text" name="<?= $fname ?>" value="<?= $val ?>" data-coloris>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
            <div class="form-group" style="margin:0">
                <label>Font titluri</label>
                <select name="font_heading" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:4px;font-size:13px;background:#fff">
                    <?php foreach (['Nunito','Playfair Display','Montserrat','Raleway','Oswald','Lora','Poppins','Rubik','DM Serif Display','Bebas Neue','Cormorant Garamond'] as $f): ?>
                    <option value="<?= h($f) ?>" <?= ($settings['font_heading'] ?? 'Nunito') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-desc">Font folosit pentru titluri și headinguri.</p>
            </div>
            <div class="form-group" style="margin:0">
                <label>Font text</label>
                <select name="font_body" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:4px;font-size:13px;background:#fff">
                    <?php foreach (['Inter','Roboto','Open Sans','Lato','Source Sans 3','DM Sans','Nunito','Rubik','Mulish','Cabin','Karla'] as $f): ?>
                    <option value="<?= h($f) ?>" <?= ($settings['font_body'] ?? 'Inter') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-desc">Font folosit pentru textele din pagină.</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salvează design</button>
    </div>
</form>
<script src="/assets/js/coloris.min.js"></script>
<script>
Coloris({ el: '[data-coloris]', format: 'hex', forceAlpha: false, focusInput: false, selectInput: true, clearButton: false,
    swatches: ['#0D0D0D','#161616','#1A1A1A','#ffffff','#C9A84C','#b8922e','#FFB000','#E8E4DC','#9CA3AF'],
});
</script>

<?php /* KIT tab redirects to config */ ?>
<?php elseif ($tab === 'kit'): ?>
<?php header('Location: /admin/?tab=config'); exit; ?>

<?php /* ======================================================= TAB: MESAJE */ ?>
<?php elseif ($tab === 'mesaje'): ?>
<h1 class="wp-page-title">Mesaje</h1>
<?php if (isset($_GET['deleted'])): ?>
<div class="notice notice-success">Mesajul a fost șters.</div>
<?php endif; ?>
<style>
.msg-tabs { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.msg-tab .msg-count { display:inline-block; background:rgba(255,255,255,.2); border-radius:10px; padding:1px 7px; font-size:11px; margin-left:5px; }
.msg-panel { display:none; }
.msg-panel.active { display:block; }
.msg-cards { display:grid; grid-template-columns:1fr; gap:12px; }
.msg-card { background:var(--surface, #fff); border:1px solid var(--border); border-radius:10px; cursor:pointer; overflow:hidden; transition:background .15s, opacity .15s; }
.msg-card:hover { background:rgba(0,0,0,.02); }
.msg-card.is-read { background:#f3f4f6; opacity:.72; }
.msg-card.eval-nope { border-left:4px solid #e74c3c; }
.msg-card.eval-meh  { border-left:4px solid #f5a623; }
.msg-card.eval-top  { border-left:4px solid #16a34a; }
.msg-delete-btn { background:transparent; border:1px solid var(--danger, #e74c3c); color:var(--danger, #e74c3c); border-radius:6px; padding:4px 10px; font-size:11px; cursor:pointer; transition:.15s; }
.msg-delete-btn:hover { background:var(--danger, #e74c3c); color:#fff; }
.msg-card-head { padding:14px 16px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
.msg-card-name { font-size:14px; font-weight:600; color:var(--text); }
.msg-card-course { font-weight:400; color:var(--text-muted); }
.msg-card-date { font-size:11px; color:var(--text-muted); white-space:nowrap; }
.msg-card-preview { padding:0 16px 14px; font-size:12px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.msg-detail { display:none; padding:16px; border-top:1px solid var(--border); background:var(--bg-surface); }
.msg-detail.open { display:block; }
.msg-detail-row { display:flex; gap:10px; font-size:13px; line-height:1.6; }
.msg-detail-row + .msg-detail-row { margin-top:4px; }
.msg-detail-lbl { color:var(--text-muted); min-width:110px; flex-shrink:0; display:inline-flex; align-items:center; gap:4px; }
.msg-detail-val { color:var(--text); flex:1; min-width:0; overflow-wrap:break-word; }
.msg-detail-actions { margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.msg-empty { color:var(--text-muted); font-size:13px; padding:12px 0; }
.msg-copy-btn { margin-left:8px; background:transparent; border:1px solid var(--border); color:var(--text-muted); border-radius:5px; padding:2px 8px; font-size:11px; cursor:pointer; transition:.15s; vertical-align:middle; }
.msg-copy-btn:hover { border-color:var(--primary,#333); color:var(--primary,#333); }
.msg-copy-btn.copied { border-color:#27ae60; color:#27ae60; }
.msg-info { position:relative; display:inline-flex; align-items:center; justify-content:center; width:14px; height:14px; border-radius:50%; background:#e5e7eb; color:#6b7280; font-size:10px; font-weight:700; cursor:help; user-select:none; flex-shrink:0; }
.msg-info:hover { background:#d1d5db; color:#1f2937; }
.msg-info::after {
    content: attr(data-tooltip);
    position:absolute; left:0; bottom:calc(100% + 6px);
    background:#1f2937; color:#fff; padding:6px 10px; border-radius:6px;
    font-size:12px; font-weight:400; line-height:1.35; white-space:normal;
    width:max-content; max-width:280px; text-align:left;
    opacity:0; pointer-events:none; transition:opacity .12s; z-index:50;
    box-shadow:0 4px 12px rgba(0,0,0,.15);
}
.msg-info::before {
    content:''; position:absolute; left:50%; bottom:100%; transform:translateX(-50%);
    border:5px solid transparent; border-top-color:#1f2937;
    opacity:0; pointer-events:none; transition:opacity .12s; z-index:50;
}
.msg-info:hover::after, .msg-info:hover::before { opacity:1; }
.msg-read-btn, .msg-eval-btn, .msg-comment-btn {
    border:1px solid var(--border); background:#fff; color:var(--text);
    border-radius:6px; padding:5px 12px; font-size:12px; font-weight:500;
    cursor:pointer; transition:.15s;
}
.msg-read-btn:hover { border-color:#16a34a; color:#16a34a; }
.msg-read-btn.is-active { background:#16a34a; color:#fff; border-color:#16a34a; }
.msg-eval-btn { color:#fff; border:none; }
.msg-eval-btn[data-eval=nope]                  { background:#e74c3c; }
.msg-eval-btn[data-eval=nope]:hover            { background:#c0392b; }
.msg-eval-btn[data-eval=nope].is-active        { background:#c0392b; box-shadow:inset 0 0 0 2px rgba(0,0,0,.25); }
.msg-eval-btn[data-eval=meh]                   { background:#f5a623; }
.msg-eval-btn[data-eval=meh]:hover             { background:#d4901c; }
.msg-eval-btn[data-eval=meh].is-active         { background:#d4901c; box-shadow:inset 0 0 0 2px rgba(0,0,0,.25); }
.msg-eval-btn[data-eval=top]                   { background:#16a34a; }
.msg-eval-btn[data-eval=top]:hover             { background:#0f7a37; }
.msg-eval-btn[data-eval=top].is-active         { background:#0f7a37; box-shadow:inset 0 0 0 2px rgba(0,0,0,.25); }
.msg-comment-btn:hover { border-color:#2271b1; color:#2271b1; }
.msg-comments { margin-top:12px; padding-top:12px; border-top:1px dashed var(--border); }
.msg-comments-title { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:8px; }
.msg-comment-item { position:relative; background:#f9fafb; border-radius:6px; padding:8px 30px 8px 10px; margin-bottom:6px; font-size:13px; line-height:1.5; }
.msg-comment-item .msg-comment-when { display:block; font-size:11px; color:var(--text-muted); margin-bottom:2px; }
.msg-comment-del { position:absolute; top:6px; right:8px; background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:16px; line-height:1; padding:2px 4px; opacity:0; transition:opacity .15s, color .15s; }
.msg-comment-item:hover .msg-comment-del { opacity:1; }
.msg-comment-del:hover { color:var(--danger,#e74c3c); }
.msg-comment-form { display:flex; gap:6px; margin-top:8px; }
.msg-comment-form textarea { flex:1; padding:6px 8px; border:1px solid var(--border); border-radius:6px; font-size:13px; font-family:inherit; resize:vertical; min-height:34px; }
.msg-comment-form button { padding:6px 14px; border:none; background:#2271b1; color:#fff; border-radius:6px; font-size:12px; font-weight:500; cursor:pointer; }
.msg-comment-form button:hover { background:#135e96; }
.msg-section-title { font-size:13px; font-weight:600; color:var(--text); margin:0 0 10px; padding:6px 0; }
.msg-section + .msg-section { margin-top:24px; }
.msg-eval-filter { display:flex; gap:6px; margin-bottom:10px; flex-wrap:wrap; }
.msg-eval-filter-btn { border:1px solid var(--border); background:#fff; color:var(--text); border-radius:6px; padding:4px 12px; font-size:12px; font-weight:500; cursor:pointer; transition:.15s; }
.msg-eval-filter-btn[data-filter="all"].active { background:#6b7280; border-color:#6b7280; color:#fff; }
.msg-eval-filter-btn[data-filter="nope"].active { background:#e74c3c; border-color:#e74c3c; color:#fff; }
.msg-eval-filter-btn[data-filter="meh"].active  { background:#f5a623; border-color:#f5a623; color:#fff; }
.msg-eval-filter-btn[data-filter="top"].active  { background:#16a34a; border-color:#16a34a; color:#fff; }
.msg-card.is-contacted { border-left:4px solid #2271b1; }
.msg-contact-btn { border:1px solid var(--border); background:#fff; color:var(--text); border-radius:6px; padding:5px 12px; font-size:12px; font-weight:500; cursor:pointer; transition:.15s; }
.msg-contact-btn:hover { border-color:#2271b1; color:#2271b1; }
.msg-contact-btn.is-active { background:#2271b1; color:#fff; border-color:#2271b1; }
</style>

<?php
$log_file = dirname(SETTINGS_FILE) . '/messages.log';
$categories = [
    'sustine'     => ['label' => 'Speakeri',     'icon' => '🎤'],
    'contact'     => ['label' => 'Contact',      'icon' => '💬'],
    'gazduieste'  => ['label' => 'Locații',      'icon' => '📍'],
    'parteneriat' => ['label' => 'Parteneriate', 'icon' => '🤝'],
];
// Map auto-generated labels back to their original form questions (Speakeri tab tooltips)
$sustine_questions = [
    'Name'                   => 'Nume și prenume',
    'Email'                  => 'Email',
    'Phone'                  => 'Număr de telefon',
    'Social'                 => 'Link profil social media',
    'Course name'            => 'Nume curs susținut',
    'Course desc'            => 'Descrie cursul susținut',
    'Motivation'             => 'De ce îți dorești să susții acest curs?',
    'Experience'             => 'Ce experiențe sau competențe te califică?',
    'Previous presentations' => 'Ai mai susținut astfel de prezentări?',
    'City'                   => 'În ce oraș ai vrea să susții cursul?',
    'Other'                  => 'Mai e ceva ce vrei să ne transmiți?',
];
$grouped     = array_fill_keys(array_keys($categories), []);
$_msg_meta   = load_msg_meta();

if (file_exists($log_file) && filesize($log_file)) {
    $raw    = file_get_contents($log_file);
    $blocks = preg_split('/(?=^===)/m', $raw);
    $blocks = array_values(array_filter(array_map('trim', $blocks)));
    $blocks = array_reverse($blocks);
    foreach ($blocks as $block) {
        preg_match('/^===\s*(.*?)\s*\|\s*(\S+)\s*===/m', $block, $m);
        $type = trim($m[2] ?? 'contact');
        if (!isset($grouped[$type])) $type = 'contact';
        $date = trim($m[1] ?? '');
        $body = trim(preg_replace('/^===.*===\n?/m', '', $block));
        $lines = array_values(array_filter(array_map('trim', explode("\n", $body))));
        $fields = [];
        $last_key = null;
        foreach ($lines as $l) {
            if ($l === '---') break;
            $sep = strpos($l, ':');
            // Only treat as a new field if the key part is short (≤40 chars = real label, not sentence text)
            if ($sep !== false && $sep <= 40) {
                $key = trim(substr($l, 0, $sep));
                $fields[$key] = trim(substr($l, $sep + 1));
                $last_key = $key;
            } elseif ($last_key !== null && $l !== '') {
                $fields[$last_key] .= ' ' . $l;
            }
        }
        $mid = msg_id_from_block($block);
        $grouped[$type][] = [
            'date'   => $date,
            'fields' => $fields,
            'id'     => $mid,
            'meta'   => $_msg_meta[$mid] ?? [],
        ];
    }
}

// Per-tab smart counts
$tab_counts = [];
foreach ($grouped as $k => $list) {
    if ($k === 'sustine') {
        $tab_counts[$k] = count(array_filter($list, fn($m) => empty($m['meta']['evaluation'])));
    } else {
        $tab_counts[$k] = count(array_filter($list, fn($m) => empty($m['meta']['read'])));
    }
}

$render_card = function(string $key, int $i, array $msg) use ($sustine_questions) {
    $name = $msg['fields']['Nume'] ?? $msg['fields']['nume'] ?? $msg['fields']['Name']
         ?? $msg['fields']['Organizație'] ?? $msg['fields']['organizatie'] ?? '—';
    $uid  = $key . '_' . $i;
    $is_read = !empty($msg['meta']['read']);
    $eval    = $msg['meta']['evaluation'] ?? '';
    $comments = $msg['meta']['comments'] ?? [];
    $is_contacted = !empty($msg['meta']['contacted']);
    $card_classes = ['msg-card'];
    if ($key !== 'sustine' && $is_read) $card_classes[] = 'is-read';
    if ($key === 'sustine' && $eval)    $card_classes[] = 'eval-' . $eval;
    if ($is_contacted)                  $card_classes[] = 'is-contacted';
    ?>
    <?php
    $name_extra = '';
    if ($key === 'sustine' && !empty($msg['fields']['Course name'])) {
        $cn = trim($msg['fields']['Course name']);
        // Take just the first sentence/line so the head stays readable
        $cn_first = preg_split('/(?<=[.!?])\s+|\s*[\r\n]+\s*/u', $cn, 2)[0];
        $name_extra = ' — ' . $cn_first;
    }
    ?>
    <div class="<?= implode(' ', $card_classes) ?>" data-msg-id="<?= h($msg['id']) ?>" onclick="toggleMsg('<?= $uid ?>')">
        <div class="msg-card-head">
            <span class="msg-card-name"><?= h($name) ?><?php if ($name_extra): ?><span class="msg-card-course"><?= h($name_extra) ?></span><?php endif; ?></span>
            <span class="msg-card-date"><?= h($msg['date']) ?></span>
        </div>
        <div class="msg-detail" id="msg-<?= $uid ?>">
            <?php foreach ($msg['fields'] as $lbl => $val):
                $lbl_lc = strtolower($lbl);
                if ($lbl_lc === 'trimis de pe' || $lbl_lc === 'data') continue;
                $tooltip = ($key === 'sustine' && isset($sustine_questions[$lbl])) ? $sustine_questions[$lbl] : '';
            ?>
            <div class="msg-detail-row">
                <span class="msg-detail-lbl"><?= h($lbl) ?><?php if ($tooltip): ?><span class="msg-info" data-tooltip="<?= h($tooltip) ?>">i</span><?php endif; ?></span>
                <span class="msg-detail-val"><?= h($val) ?><?php if (in_array(strtolower($lbl), ['social', 'email', 'phone']) && $val): ?><button type="button" class="msg-copy-btn" onclick="event.stopPropagation();copyField(this,'<?= addslashes($val) ?>')" title="Copiază">Copiază</button><?php endif; ?></span>
            </div>
            <?php endforeach; ?>

            <div class="msg-detail-actions">
                <?php if ($key === 'sustine'): ?>
                    <button type="button" class="msg-eval-btn <?= $eval === 'nope' ? 'is-active' : '' ?>" data-eval="nope" onclick="event.stopPropagation();evalMsg(this,'nope')">Nope</button>
                    <button type="button" class="msg-eval-btn <?= $eval === 'meh' ? 'is-active' : '' ?>"  data-eval="meh"  onclick="event.stopPropagation();evalMsg(this,'meh')">Meh</button>
                    <button type="button" class="msg-eval-btn <?= $eval === 'top' ? 'is-active' : '' ?>"  data-eval="top"  onclick="event.stopPropagation();evalMsg(this,'top')">Top</button>
                    <button type="button" class="msg-comment-btn" onclick="event.stopPropagation();toggleCommentForm(this)">💬 Comentariu</button>
                    <button type="button" class="msg-contact-btn <?= $is_contacted ? 'is-active' : '' ?>" onclick="event.stopPropagation();markContacted(this)"><?= $is_contacted ? '✓ Contactat' : 'Contactat' ?></button>
                <?php else: ?>
                    <button type="button" class="msg-read-btn <?= $is_read ? 'is-active' : '' ?>" onclick="event.stopPropagation();markRead(this)">
                        <?= $is_read ? '✓ Citit' : 'Citit' ?>
                    </button>
                    <button type="button" class="msg-contact-btn <?= $is_contacted ? 'is-active' : '' ?>" onclick="event.stopPropagation();markContacted(this)"><?= $is_contacted ? '✓ Contactat' : 'Contactat' ?></button>
                    <button type="button" class="msg-delete-btn" onclick="event.stopPropagation();deleteMsg(this,'<?= h($key) ?>',<?= $i ?>)">Șterge</button>
                <?php endif; ?>
            </div>

            <?php if ($key === 'sustine'): ?>
            <div class="msg-comments">
                <div class="msg-comments-title">Comentarii</div>
                <div class="msg-comments-list">
                    <?php foreach ($comments as $cidx => $c): ?>
                    <div class="msg-comment-item" data-comment-idx="<?= $cidx ?>">
                        <span class="msg-comment-when"><?= h($c['at'] ?? '') ?><?php if (!empty($c['by'])): ?> · <?= h($c['by']) ?><?php endif; ?></span>
                        <?= h($c['text'] ?? '') ?>
                        <?php if (is_owner()): ?>
                        <button type="button" class="msg-comment-del" onclick="event.stopPropagation();deleteComment(this)" title="Șterge comentariu">×</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="msg-comment-form" style="display:none">
                    <textarea placeholder="Scrie un comentariu..." rows="2" onclick="event.stopPropagation()"></textarea>
                    <button type="button" onclick="event.stopPropagation();saveComment(this)">Adaugă</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
};
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
            <?php foreach ($pending as [$i, $msg]) $render_card($key, $i, $msg); ?>
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
            </div>
            <div class="msg-cards">
            <?php foreach ($evaluated as [$i, $msg]) $render_card($key, $i, $msg); ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="msg-cards">
    <?php foreach ($grouped[$key] as $i => $msg) $render_card($key, $i, $msg); ?>
    </div>
<?php endif; ?>
</div>
<?php endforeach; ?>

<script>
window.CLP_IS_OWNER = <?= is_owner() ? 'true' : 'false' ?>;
function filterEval(btn) {
    const filter = btn.dataset.filter;
    btn.closest('.msg-section').querySelectorAll('.msg-eval-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    btn.closest('.msg-section').querySelectorAll('.msg-card').forEach(card => {
        card.style.display = (filter === 'all' || card.classList.contains('eval-' + filter)) ? '' : 'none';
    });
}
function showMsgTab(key) {
    document.querySelectorAll('.msg-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.msg-panel').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('msg-panel-' + key).classList.add('active');
}
function deleteComment(btn) {
    if (!confirm('Ștergi comentariul?')) return;
    const item = btn.closest('.msg-comment-item');
    const card = btn.closest('.msg-card');
    const fd = new FormData();
    fd.append('action', 'delete_message_comment');
    fd.append('msg_id', card.dataset.msgId);
    fd.append('idx',    item.dataset.commentIdx);
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            if (!d.ok) return;
            const list = item.parentElement;
            item.remove();
            list.querySelectorAll('.msg-comment-item').forEach((el, i) => el.dataset.commentIdx = i);
        });
}
function toggleMsg(uid) {
    const el = document.getElementById('msg-' + uid);
    el.classList.toggle('open');
}
function copyField(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = 'Copiat!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Copiază'; btn.classList.remove('copied'); }, 2000);
    });
}
function deleteMsg(btn, type, idx) {
    if (!confirm('Sigur vrei să ștergi acest mesaj?')) return;
    const card = btn.closest('.msg-card');
    const fd = new FormData();
    fd.append('action', 'delete_message');
    fd.append('msg_type', type);
    fd.append('msg_index', idx);
    fetch('/admin/?tab=mesaje', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: fd })
        .then(r => r.json())
        .then(d => { if (d.ok) { updateBadgeAfterRemoval(card, type); card.remove(); } });
}
function updateBadge(tabKey, delta) {
    const tab = document.querySelector('.msg-tab[data-key="' + tabKey + '"]');
    if (!tab) return;
    const span = tab.querySelector('.msg-count');
    let n = parseInt(span.textContent, 10) || 0;
    n = Math.max(0, n + delta);
    span.textContent = n;
    span.style.display = n > 0 ? '' : 'none';
}
function updateBadgeAfterRemoval(card, type) {
    if (type === 'sustine') {
        if (!card.className.match(/eval-(nope|meh|top)/)) updateBadge('sustine', -1);
    } else {
        if (!card.classList.contains('is-read')) updateBadge(type, -1);
    }
}
function markRead(btn) {
    const card  = btn.closest('.msg-card');
    const panel = card.closest('.msg-panel');
    const type  = panel ? panel.id.replace('msg-panel-', '') : 'contact';
    const id = card.dataset.msgId;
    const wasRead = card.classList.contains('is-read');
    const now = !wasRead;
    const fd = new FormData();
    fd.append('action', 'mark_read_message');
    fd.append('msg_id', id);
    if (now) fd.append('read', '1');
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            if (!d.ok) return;
            card.classList.toggle('is-read', now);
            btn.classList.toggle('is-active', now);
            btn.textContent = now ? '✓ Citit' : 'Citit';
            updateBadge(type, now ? -1 : 1);
            if (now) card.querySelector('.msg-detail').classList.remove('open');
        });
}
function markContacted(btn) {
    const card = btn.closest('.msg-card');
    const id = card.dataset.msgId;
    const wasContacted = card.classList.contains('is-contacted');
    const now = !wasContacted;
    const fd = new FormData();
    fd.append('action', 'mark_contacted_message');
    fd.append('msg_id', id);
    if (now) fd.append('contacted', '1');
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            if (!d.ok) return;
            card.classList.toggle('is-contacted', now);
            btn.classList.toggle('is-active', now);
            btn.textContent = now ? '✓ Contactat' : 'Contactat';
        });
}
function evalMsg(btn, value) {
    const card = btn.closest('.msg-card');
    const id   = card.dataset.msgId;
    const cur  = (card.className.match(/eval-(nope|meh|top)/) || [,''])[1];
    const next = cur === value ? '' : value; // toggle off if same button pressed twice
    const fd = new FormData();
    fd.append('action', 'eval_message');
    fd.append('msg_id', id);
    fd.append('eval',   next);
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            if (!d.ok) return;
            card.classList.remove('eval-nope','eval-meh','eval-top');
            if (next) card.classList.add('eval-' + next);
            card.querySelectorAll('.msg-eval-btn').forEach(b =>
                b.classList.toggle('is-active', b.dataset.eval === next)
            );
            // badge: pending count = unevaluated; cur was unset → -1; cur was set & next='' → +1
            if (!cur && next) updateBadge('sustine', -1);
            if (cur && !next) updateBadge('sustine', +1);
        });
}
function toggleCommentForm(btn) {
    const form = btn.closest('.msg-detail-actions').nextElementSibling.querySelector('.msg-comment-form');
    const visible = form.style.display !== 'none';
    form.style.display = visible ? 'none' : 'flex';
    if (!visible) form.querySelector('textarea').focus();
}
function saveComment(btn) {
    const form = btn.closest('.msg-comment-form');
    const card = btn.closest('.msg-card');
    const ta   = form.querySelector('textarea');
    const text = ta.value.trim();
    if (!text) return;
    const id = card.dataset.msgId;
    const fd = new FormData();
    fd.append('action', 'add_message_comment');
    fd.append('msg_id', id);
    fd.append('text',   text);
    btn.disabled = true;
    fetch('/admin/?tab=mesaje', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(r => r.json()).then(d => {
            btn.disabled = false;
            if (!d.ok) return;
            const list = card.querySelector('.msg-comments-list');
            const item = document.createElement('div');
            item.className = 'msg-comment-item';
            item.dataset.commentIdx = list.querySelectorAll('.msg-comment-item').length;
            item.innerHTML = '<span class="msg-comment-when"></span>';
            item.querySelector('.msg-comment-when').textContent =
                d.comment.at + (d.comment.by ? ' · ' + d.comment.by : '');
            item.appendChild(document.createTextNode(d.comment.text));
            if (window.CLP_IS_OWNER) {
                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'msg-comment-del';
                del.title = 'Șterge comentariu';
                del.textContent = '×';
                del.onclick = function(e) { e.stopPropagation(); deleteComment(this); };
                item.appendChild(del);
            }
            list.appendChild(item);
            ta.value = '';
            form.style.display = 'none';
        });
}
</script>

<?php /* ======================================================= TAB: VOT CURSURI */ ?>
<?php elseif ($tab === 'vot'): ?>

<?php
$vote_courses   = load_vote_courses();
$edit_vc        = null;
$edit_vc_id     = $_GET['edit'] ?? '';
if ($edit_vc_id) {
    foreach ($vote_courses as $vc) {
        if (($vc['id'] ?? '') === $edit_vc_id) { $edit_vc = $vc; break; }
    }
}
// Sort by likes descending for admin view
usort($vote_courses, fn($a,$b) => ($b['likes'] ?? 0) <=> ($a['likes'] ?? 0));
?>

<style>
.vc-table .likes-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fce8e8; color: #8c1415;
    padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 700;
}
</style>

<h1 class="wp-page-title">Vot cursuri</h1>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Cursul a fost salvat.</div>
<?php endif; ?>

<!-- Add / Edit form -->
<div class="card">
    <div class="card-title"><?= $edit_vc ? 'Editează cursul' : 'Adaugă idee de curs' ?></div>
    <form method="post" action="/admin/?tab=vot">
        <input type="hidden" name="action" value="save_vote_course">
        <input type="hidden" name="vote_course_id" value="<?= h($edit_vc['id'] ?? '') ?>">

        <div style="display:grid;grid-template-columns:64px 1fr;gap:12px;align-items:start">
            <div class="form-group" style="margin-bottom:0">
                <label for="vc_emoji">Emoji</label>
                <input type="text" id="vc_emoji" name="vc_emoji" value="<?= h($edit_vc['emoji'] ?? '📚') ?>" maxlength="4" style="text-align:center;font-size:1.5rem;padding:6px 4px">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label for="vc_name">Nume curs <span style="color:var(--danger)">*</span></label>
                <input type="text" id="vc_name" name="vc_name" value="<?= h($edit_vc['name'] ?? '') ?>" required placeholder="ex: Educație montană">
            </div>
        </div>

        <div class="form-group" style="margin-top:12px">
            <label for="vc_description">Descriere</label>
            <textarea id="vc_description" name="vc_description" rows="4" placeholder="Descrierea cursului, vizibilă la toggle pe pagina publică."><?= h($edit_vc['description'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:8px;align-items:center">
            <button type="submit" class="btn btn-primary"><?= $edit_vc ? 'Salvează modificările' : 'Adaugă cursul' ?></button>
            <?php if ($edit_vc): ?>
            <a href="/admin/?tab=vot" class="btn btn-secondary">Anulează</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Courses table -->
<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Idei de cursuri (<?= count($vote_courses) ?>)</span>
        <a href="/voteaza-cursuri" target="_blank" class="btn btn-sm btn-secondary">Vezi pagina ↗</a>
    </div>
    <?php if (empty($vote_courses)): ?>
    <p style="color:var(--text-muted)">Nu există idei de cursuri adăugate încă.</p>
    <?php else: ?>
    <table class="wp-table vc-table">
        <thead>
            <tr>
                <th style="width:48px">Emoji</th>
                <th>Nume</th>
                <th style="width:90px">Voturi</th>
                <th style="width:210px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vote_courses as $vc): ?>
            <?php $is_active = $vc['active'] ?? true; ?>
            <tr style="<?= $is_active ? '' : 'opacity:0.45' ?>">
                <td style="font-size:1.4rem;text-align:center"><?= h($vc['emoji'] ?? '📚') ?></td>
                <td style="font-weight:600">
                    <?= h($vc['name'] ?? '') ?>
                    <?php if (!$is_active): ?>
                    <span style="font-size:11px;color:var(--text-muted);font-weight:400;margin-left:6px">(dezactivat)</span>
                    <?php endif; ?>
                    <?php if (!empty($vc['description'])): ?>
                    <div style="font-size:12px;color:var(--text-muted);font-weight:400;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:360px"><?= h(mb_substr($vc['description'], 0, 80)) ?>…</div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="likes-badge">❤️ <?= (int)($vc['likes'] ?? 0) ?></span>
                </td>
                <td>
                    <div class="row-actions">
                        <a href="/admin/?tab=vot&edit=<?= h($vc['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                        <form method="post" action="/admin/?tab=vot" style="display:inline">
                            <input type="hidden" name="action" value="toggle_vote_course">
                            <input type="hidden" name="id" value="<?= h($vc['id'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm <?= $is_active ? 'btn-secondary' : 'btn-primary' ?>"><?= $is_active ? 'Dezactivează' : 'Activează' ?></button>
                        </form>
                        <form method="post" action="/admin/?tab=vot" onsubmit="return confirm('Ștergi această idee de curs?')" style="display:inline">
                            <input type="hidden" name="action" value="delete_vote_course">
                            <input type="hidden" name="id" value="<?= h($vc['id'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php /* ======================================================= TAB: COMPETITORI */ ?>
<?php elseif ($tab === 'competitori'): ?>

<h1 class="wp-page-title">Competitori</h1>

<?php
$_competitors = [
    ['name' => 'Nota de Subsol',          'ig' => 'https://www.instagram.com/notadesubsol.live/', 'tt' => 'https://www.tiktok.com/@notadesubsol.live', 'web' => ''],
    ['name' => 'Lectures on Tap',         'ig' => 'https://www.instagram.com/lecturesontap/',     'tt' => 'https://www.tiktok.com/@lecturesontap',     'web' => 'https://lecturesontap.com/'],
    ['name' => 'Boozy Lectures',          'ig' => 'https://www.instagram.com/boozylectures/',     'tt' => 'https://www.tiktok.com/@boozylecturesyyc',  'web' => 'https://www.boozylectures.com/'],
    ['name' => 'Brewing Minds',           'ig' => 'https://www.instagram.com/brewingminds_lectures/', 'tt' => 'https://www.tiktok.com/@brewingminds',  'web' => 'https://www.brewing-minds.com/'],
    ['name' => 'Brains and Barstools',    'ig' => 'https://www.instagram.com/brainsandbarstools/', 'tt' => 'https://www.tiktok.com/@brainsandbarstools', 'web' => 'http://brainsandbarstools.com/'],
    ['name' => 'The Social Study',        'ig' => 'https://www.instagram.com/thesocial.study/',   'tt' => 'https://www.tiktok.com/@thesocial.study',   'web' => 'https://www.thesocial.study/'],
    ['name' => 'Sip and Learn Toronto',   'ig' => 'https://www.instagram.com/sip_and_learn_toronto/', 'tt' => 'https://www.tiktok.com/@sip_and_learn', 'web' => 'https://www.sipandlearn.ca'],
    ['name' => 'The Unlecture',           'ig' => 'https://www.instagram.com/theunlecture/',     'tt' => '',                                          'web' => ''],
    ['name' => 'Sip and Scholar',         'ig' => 'https://www.instagram.com/sipandscholar/',     'tt' => '',                                          'web' => 'https://www.sipandscholar.com/'],
    ['name' => 'Pint of View',            'ig' => 'https://www.instagram.com/pintofview.club/',   'tt' => '',                                          'web' => 'https://pintofview.club/'],
    ['name' => 'Big Brain SF',            'ig' => 'https://www.instagram.com/bigbrainsf/',         'tt' => 'https://www.tiktok.com/@bigbrainsf',        'web' => ''],
    ['name' => 'Society of Intellectuals','ig' => 'https://www.instagram.com/societyofintellectuals/', 'tt' => '',                                     'web' => 'https://societyofintellectuals.org/'],
    ['name' => 'Off-Campus',              'ig' => 'https://www.instagram.com/offcampus_fr/',      'tt' => '',                                          'web' => 'https://www.offcampus.fr/'],
    ['name' => 'Cursuri la Bar',          'ig' => 'https://www.instagram.com/cursurilabar',       'tt' => 'https://www.tiktok.com/@cursurilabar',      'web' => 'https://cursurilabar.ro/'],
];
?>
<style>
.comp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.comp-card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
.comp-card-header { padding: 18px 20px 14px; border-bottom: 1px solid var(--border); }
.comp-card-name { font-size: 15px; font-weight: 700; color: var(--text); }
.comp-card-links { display: flex; gap: 8px; padding: 14px 20px; flex-wrap: wrap; }
.comp-link { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; transition: opacity .15s; }
.comp-link:hover { opacity: .75; }
.comp-link-ig  { background: #fce7f3; color: #be185d; }
.comp-link-tt  { background: #f0fdf4; color: #166534; }
.comp-link-web { background: #eff6ff; color: #1d4ed8; }
</style>

<div class="comp-grid">
<?php foreach ($_competitors as $_c): ?>
<div class="comp-card">
    <div class="comp-card-header">
        <div class="comp-card-name"><?= h($_c['name']) ?></div>
    </div>
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

<?php /* ======================================================= TAB: SPEAKERI */ ?>
<?php elseif ($tab === 'speakeri'): ?>

<?php
$speakers    = load_speakers();
$edit_sp     = null;
$edit_sp_id  = $_GET['edit'] ?? '';
if ($edit_sp_id) {
    foreach ($speakers as $sp) {
        if (($sp['id'] ?? '') === $edit_sp_id) { $edit_sp = $sp; break; }
    }
}
$sp_status_colors = ['RECURENT' => '#16a34a', 'MID' => '#d97706', 'NOPE' => '#dc2626'];
?>

<style>
.crm-status-badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; color:#fff; }
.crm-table td { vertical-align:top; }
.crm-form { max-width:580px !important; }
.crm-form .form-group { margin-bottom:8px !important; }
.crm-form .form-group label { margin-bottom:3px !important; }
.crm-form input[type="text"],.crm-form input[type="email"],.crm-form input[type="url"],.crm-form select { padding:5px 9px !important; font-size:12px !important; }
.crm-form textarea { padding:5px 9px !important; font-size:12px !important; min-height:60px !important; }
</style>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Speakerul a fost salvat.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Speakeri (<?= count($speakers) ?>)</span>
        <button type="button" onclick="document.getElementById('sp-modal').style.display='flex'" class="btn btn-sm btn-primary">+ Adaugă speaker</button>
    </div>
    <?php if (empty($speakers)): ?>
    <p style="color:var(--text-muted)">Nu există speakeri adăugați încă.</p>
    <?php else: ?>
    <table class="wp-table crm-table">
        <thead>
            <tr>
                <th>Nume</th>
                <th>Contact</th>
                <th>Cursuri</th>
                <th style="width:90px">Status</th>
                <th style="width:150px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($speakers as $sp): ?>
        <tr>
            <td style="font-weight:600">
                <?= h($sp['name'] ?? '') ?>
                <?php if (!empty($sp['notes'])): ?>
                <div style="font-size:11px;color:var(--text-muted);font-weight:400;margin-top:2px"><?= h(mb_substr($sp['notes'], 0, 60)) ?><?= mb_strlen($sp['notes']) > 60 ? '…' : '' ?></div>
                <?php endif; ?>
            </td>
            <td style="font-size:13px">
                <?php if (!empty($sp['email'])): ?><div><?= h($sp['email']) ?></div><?php endif; ?>
                <?php if (!empty($sp['phone'])): ?><div><?= h($sp['phone']) ?></div><?php endif; ?>
            </td>
            <td>
                <?php
                $sp_c = $sp['courses'] ?? [];
                if (is_string($sp_c)) $sp_c = $sp_c ? [$sp_c] : [];
                foreach (array_filter($sp_c) as $sp_cv):
                ?>
                <span style="display:inline-block;background:#e5e7eb;color:#374151;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:500;margin:2px 2px 2px 0"><?= h($sp_cv) ?></span>
                <?php endforeach; ?>
            </td>
            <td>
                <?php $sc = $sp_status_colors[$sp['status'] ?? 'MID'] ?? '#6b7280'; ?>
                <span class="crm-status-badge" style="background:<?= $sc ?>"><?= h($sp['status'] ?? 'MID') ?></span>
            </td>
            <td>
                <div class="row-actions">
                    <a href="/admin/?tab=speakeri&edit=<?= h($sp['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                    <form method="post" action="/admin/?tab=speakeri" onsubmit="return confirm('Ștergi speakerul?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_speaker">
                        <input type="hidden" name="id" value="<?= h($sp['id'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div id="sp-modal" style="display:<?= $edit_sp ? 'flex' : 'none' ?>;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,.45)" onclick="if(event.target===this)this.style.display='none'">
<div class="card crm-form" style="width:min(640px,95vw);max-height:90vh;overflow-y:auto;margin:0;position:relative">
    <button type="button" onclick="document.getElementById('sp-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:none;border:none;font-size:20px;cursor:pointer;color:#6b7280;line-height:1">×</button>
    <div class="card-title"><?= $edit_sp ? 'Editează speaker' : 'Adaugă speaker' ?></div>
    <form method="post" action="/admin/?tab=speakeri">
        <input type="hidden" name="action" value="save_speaker">
        <input type="hidden" name="speaker_id" value="<?= h($edit_sp['id'] ?? '') ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
            <div class="form-group"><label>Nume *</label><input type="text" name="sp_name" value="<?= h($edit_sp['name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="sp_email" value="<?= h($edit_sp['email'] ?? '') ?>"></div>
            <div class="form-group"><label>Telefon</label><input type="text" name="sp_phone" value="<?= h($edit_sp['phone'] ?? '') ?>"></div>
        </div>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px">
            <div class="form-group">
                <label>Cursuri susținute</label>
                <?php
                $sp_courses_arr = $edit_sp['courses'] ?? [];
                if (is_string($sp_courses_arr)) $sp_courses_arr = $sp_courses_arr ? [$sp_courses_arr] : [];
                if (empty($sp_courses_arr)) $sp_courses_arr = [''];
                ?>
                <div id="sp-courses-list" style="display:flex;flex-direction:column;gap:4px">
                <?php foreach ($sp_courses_arr as $sc_val): ?>
                    <div style="display:flex;gap:4px;align-items:center">
                        <input type="text" name="sp_courses[]" value="<?= h($sc_val) ?>" style="flex:1;padding:5px 9px;font-size:12px">
                        <button type="button" onclick="this.closest('div').remove()" style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:0 7px;height:28px;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1">×</button>
                    </div>
                <?php endforeach; ?>
                </div>
                <button type="button" onclick="spAddCourse()" style="margin-top:4px;background:none;border:1px solid #d1d5db;border-radius:6px;padding:2px 8px;cursor:pointer;font-size:11px;color:#6b7280">+ curs</button>
                <script>
                function spAddCourse() {
                    var wrap = document.createElement('div');
                    wrap.style.cssText = 'display:flex;gap:4px;align-items:center';
                    wrap.innerHTML = '<input type="text" name="sp_courses[]" style="flex:1;padding:5px 9px;font-size:12px;border:1px solid #e5e7eb;border-radius:8px"><button type="button" onclick="this.closest(\'div\').remove()" style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:0 7px;height:28px;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1">×</button>';
                    document.getElementById('sp-courses-list').appendChild(wrap);
                    wrap.querySelector('input').focus();
                }
                </script>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="sp_status">
                    <?php foreach (['RECURENT','MID','NOPE'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($edit_sp['status'] ?? 'MID') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Note</label><textarea name="sp_notes" rows="2"><?= h($edit_sp['notes'] ?? '') ?></textarea></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $edit_sp ? 'Salvează' : 'Adaugă speakerul' ?></button>
            <a href="/admin/?tab=speakeri" class="btn btn-secondary btn-sm">Anulează</a>
        </div>
    </form>
</div>
</div>

<?php /* ======================================================= TAB: LOCATII */ ?>
<?php elseif ($tab === 'locatii'): ?>

<?php
$locations   = load_locations();
$edit_loc    = null;
$edit_loc_id = $_GET['edit'] ?? '';
if ($edit_loc_id) {
    foreach ($locations as $loc) {
        if (($loc['id'] ?? '') === $edit_loc_id) { $edit_loc = $loc; break; }
    }
}
?>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Locația a fost salvată.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Locații (<?= count($locations) ?>)</span>
        <button type="button" onclick="document.getElementById('loc-form').style.display=document.getElementById('loc-form').style.display==='none'?'block':'none'" class="btn btn-sm btn-primary">+ Adaugă locație</button>
    </div>
    <?php if (empty($locations)): ?>
    <p style="color:var(--text-muted)">Nu există locații adăugate încă.</p>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
    <?php foreach ($locations as $loc): ?>
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px 14px;background:#fafafa">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                <div>
                    <div style="font-weight:700;font-size:13px"><?= h($loc['name'] ?? '') ?></div>
                    <?php if (!empty($loc['phone'])): ?><div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= h($loc['phone']) ?></div><?php endif; ?>
                    <?php if (!empty($loc['days'])): ?><div style="font-size:12px;color:var(--text-muted)"><?= h($loc['days']) ?></div><?php endif; ?>
                    <?php if (!empty($loc['notes'])): ?><div style="font-size:11px;color:#9ca3af;margin-top:3px"><?= h(mb_substr($loc['notes'], 0, 80)) ?><?= mb_strlen($loc['notes']) > 80 ? '…' : '' ?></div><?php endif; ?>
                </div>
                <div style="display:flex;gap:5px;flex-shrink:0;align-items:center">
                    <?php if (!empty($loc['maps_link'])): ?>
                    <a href="<?= h($loc['maps_link']) ?>" target="_blank" class="btn btn-sm btn-secondary">Maps ↗</a>
                    <?php endif; ?>
                    <a href="/admin/?tab=locatii&edit=<?= h($loc['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                    <form method="post" action="/admin/?tab=locatii" onsubmit="return confirm('Ștergi locația?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_location">
                        <input type="hidden" name="id" value="<?= h($loc['id'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div id="loc-form" style="<?= $edit_loc ? '' : 'display:none' ?>">
<div class="card crm-form">
    <div class="card-title"><?= $edit_loc ? 'Editează locație' : 'Adaugă locație' ?></div>
    <form method="post" action="/admin/?tab=locatii">
        <input type="hidden" name="action" value="save_location">
        <input type="hidden" name="location_id" value="<?= h($edit_loc['id'] ?? '') ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px">
            <div class="form-group"><label>Nume *</label><input type="text" name="loc_name" value="<?= h($edit_loc['name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Telefon</label><input type="text" name="loc_phone" value="<?= h($edit_loc['phone'] ?? '') ?>"></div>
            <div class="form-group"><label>Link Google Maps</label><input type="url" name="loc_maps" value="<?= h($edit_loc['maps_link'] ?? '') ?>"></div>
            <div class="form-group"><label>Zile disponibile</label><input type="text" name="loc_days" value="<?= h($edit_loc['days'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Note</label><textarea name="loc_notes" rows="2"><?= h($edit_loc['notes'] ?? '') ?></textarea></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $edit_loc ? 'Salvează' : 'Adaugă locația' ?></button>
            <a href="/admin/?tab=locatii" class="btn btn-secondary btn-sm">Anulează</a>
        </div>
    </form>
</div>
</div>

<?php /* ======================================================= TAB: COLABORARI */ ?>
<?php elseif ($tab === 'colaborari'): ?>

<?php
$collabs      = load_collaborations();
$edit_col     = null;
$edit_col_id  = $_GET['edit'] ?? '';
if ($edit_col_id) {
    foreach ($collabs as $col) {
        if (($col['id'] ?? '') === $edit_col_id) { $edit_col = $col; break; }
    }
}
?>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Colaborarea a fost salvată.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title" style="display:flex;align-items:center;justify-content:space-between">
        <span>Colaborări (<?= count($collabs) ?>)</span>
        <button type="button" onclick="document.getElementById('col-form').style.display=document.getElementById('col-form').style.display==='none'?'block':'none'" class="btn btn-sm btn-primary">+ Adaugă colaborare</button>
    </div>
    <?php if (empty($collabs)): ?>
    <p style="color:var(--text-muted)">Nu există colaborări adăugate încă.</p>
    <?php else: ?>
    <table class="wp-table crm-table">
        <thead>
            <tr>
                <th>Brand / Organizație</th>
                <th>Persoana de contact</th>
                <th>Email / Telefon</th>
                <th>Status</th>
                <th style="width:150px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($collabs as $col): ?>
        <tr>
            <td style="font-weight:600">
                <?= h($col['name'] ?? '') ?>
                <?php if (!empty($col['notes'])): ?>
                <div style="font-size:11px;color:var(--text-muted);font-weight:400;margin-top:2px"><?= h(mb_substr($col['notes'], 0, 60)) ?><?= mb_strlen($col['notes']) > 60 ? '…' : '' ?></div>
                <?php endif; ?>
            </td>
            <td style="font-size:13px"><?= h($col['contact'] ?? '') ?></td>
            <td style="font-size:13px"><?= h($col['contact_info'] ?? '') ?></td>
            <td style="font-size:13px;color:var(--text-muted)"><?= h($col['status'] ?? '') ?></td>
            <td>
                <div class="row-actions">
                    <a href="/admin/?tab=colaborari&edit=<?= h($col['id'] ?? '') ?>" class="btn btn-sm btn-secondary">Editează</a>
                    <form method="post" action="/admin/?tab=colaborari" onsubmit="return confirm('Ștergi colaborarea?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_collaboration">
                        <input type="hidden" name="id" value="<?= h($col['id'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div id="col-form" style="<?= $edit_col ? '' : 'display:none' ?>">
<div class="card crm-form">
    <div class="card-title"><?= $edit_col ? 'Editează colaborare' : 'Adaugă colaborare' ?></div>
    <form method="post" action="/admin/?tab=colaborari">
        <input type="hidden" name="action" value="save_collaboration">
        <input type="hidden" name="collab_id" value="<?= h($edit_col['id'] ?? '') ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px">
            <div class="form-group"><label>Nume brand / org. *</label><input type="text" name="col_name" value="<?= h($edit_col['name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Persoana de contact</label><input type="text" name="col_contact" value="<?= h($edit_col['contact'] ?? '') ?>"></div>
            <div class="form-group"><label>Email / Telefon</label><input type="text" name="col_contact_info" value="<?= h($edit_col['contact_info'] ?? '') ?>"></div>
            <div class="form-group"><label>Status</label><input type="text" name="col_status" value="<?= h($edit_col['status'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Note</label><textarea name="col_notes" rows="2"><?= h($edit_col['notes'] ?? '') ?></textarea></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm"><?= $edit_col ? 'Salvează' : 'Adaugă colaborarea' ?></button>
            <a href="/admin/?tab=colaborari" class="btn btn-secondary btn-sm">Anulează</a>
        </div>
    </form>
</div>
</div>

<?php /* Securitate tab redirects to config */ ?>
<?php elseif ($tab === 'securitate'): ?>
<?php header('Location: /admin/?tab=config'); exit; ?>

<?php /* ======================================================= TAB: CONFIG (Setări) */ ?>
<?php elseif ($tab === 'config'): ?>
<h1 class="wp-page-title">Setări</h1>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Setările au fost salvate.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="notice notice-error">Parolele nu coincid sau sunt prea scurte (minim 6 caractere).</div>
<?php endif; ?>
<?php if (isset($_GET['imported'])): ?>
<div class="notice notice-success">Import reușit! <?= (int)$_GET['imported'] ?> imagini descărcate.</div>
<?php endif; ?>

<!-- Quick links editor (Owner only) -->
<div class="card">
    <div class="card-title">🔗 Linkuri rapide — Dashboard</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">Aceste linkuri apar ca butoane în partea de sus a dashboard-ului.</p>
    <form method="post" action="/admin/?tab=config" id="qlForm">
        <input type="hidden" name="action" value="save_quick_links">
        <div id="qlRows" style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
        <?php foreach ($settings['quick_links'] ?? [] as $idx => $_ql): ?>
            <div class="ql-row" style="display:grid;grid-template-columns:60px 1fr 3fr auto;gap:8px;align-items:center">
                <input type="text" name="ql_icon[]" value="<?= h($_ql['icon'] ?? '🔗') ?>" style="text-align:center;font-size:18px">
                <input type="text" name="ql_label[]" value="<?= h($_ql['label'] ?? '') ?>">
                <input type="text" name="ql_url[]" value="<?= h($_ql['url'] ?? '') ?>">
                <button type="button" onclick="this.closest('.ql-row').remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">✕</button>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" onclick="addQlRow()" class="btn btn-secondary btn-sm">+ Adaugă link</button>
            <button type="submit" class="btn btn-primary btn-sm">Salvează</button>
        </div>
    </form>
</div>

<script>
function addQlRow() {
    const row = document.createElement('div');
    row.className = 'ql-row';
    row.style.cssText = 'display:grid;grid-template-columns:60px 1fr 3fr auto;gap:8px;align-items:center';
    row.innerHTML = '<input type="text" name="ql_icon[]" value="🔗" style="text-align:center;font-size:18px">'
        + '<input type="text" name="ql_label[]" value="">'
        + '<input type="text" name="ql_url[]" value="">'
        + '<button type="button" onclick="this.closest(\'.ql-row\').remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">✕</button>';
    document.getElementById('qlRows').appendChild(row);
}
</script>

<!-- Kit (Email) -->
<form method="post" action="/admin/?tab=config">
    <input type="hidden" name="action" value="save_kit">
    <div class="card">
        <div class="card-title">📧 Kit (Email Marketing)</div>
        <div class="form-group">
            <label>API Key</label>
            <input type="text" name="kit_api_key" value="<?= h($settings['kit_api_key'] ?? '') ?>" placeholder="kit_...">
            <p class="form-desc">Găsești API Key-ul în <a href="https://app.kit.com/account_settings/developer_settings" target="_blank" style="color:var(--accent)">Kit → Settings → Developer</a>.</p>
        </div>
        <div class="form-group">
            <label>Form ID (opțional)</label>
            <input type="text" name="kit_form_id" value="<?= h($settings['kit_form_id'] ?? '') ?>" placeholder="ex: 1234567">
            <p class="form-desc">Dacă vrei să adaugi abonații la un form specific. Lasă gol pentru a adăuga direct ca subscriber.</p>
        </div>
        <button type="submit" class="btn btn-primary">Salvează</button>
    </div>
</form>

<!-- Analytics -->
<form method="post" action="/admin/?tab=config">
    <input type="hidden" name="action" value="save_head_scripts">
    <div class="card">
        <div class="card-title">📊 Analytics &amp; Tracking</div>
        <div class="form-group">
            <label>Cod <code>&lt;head&gt;</code></label>
            <textarea name="head_scripts" rows="10" style="font-family:monospace;font-size:12px;line-height:1.7"><?= htmlspecialchars($settings['head_scripts'] ?? '') ?></textarea>
            <p class="form-desc">
                Lipește aici codul de tracking pentru <strong>Umami</strong>, <strong>Google Analytics (GA4)</strong> sau orice alt script.
                Va fi inserat automat în <code>&lt;head&gt;</code> pe <strong>toate paginile</strong> site-ului.<br>
                <span style="color:#d63638">⚠ Codul este inserat fără filtrare — adaugă doar scripturi de încredere.</span>
            </p>
        </div>
        <button type="submit" class="btn btn-primary">Salvează</button>
    </div>
</form>

<!-- Schimba parola -->
<div class="card">
    <div class="card-title">🔒 Schimbă parola de admin</div>
    <form method="post" action="/admin/?tab=config" style="max-width:400px">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
            <label for="new_password">Parolă nouă</label>
            <input type="password" id="new_password" name="new_password" placeholder="Minim 6 caractere" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmă parola</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repetă parola" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Schimbă parola</button>
    </form>
    <p class="form-desc" style="margin-top:12px">Parola este salvată în <code>data/settings.json</code> și nu apare nicăieri în cod sau Git.</p>
</div>

<!-- Sync token (pentru sync.sh local) -->
<div class="card">
    <div class="card-title">🔄 Sync Token</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
        Folosit de scriptul <code>./sync.sh</code> pentru a sincroniza datele din producție în mediul local.
        Pune valoarea într-un fișier <code>.sync-token</code> în root-ul proiectului local.
    </p>
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
        <input type="text" id="sync_token_input" value="<?= h($settings['sync_token'] ?? '') ?>" readonly style="font-family:monospace;font-size:12px;flex:1">
        <button type="button" class="btn btn-secondary btn-sm" onclick="copySyncToken()">Copiază</button>
        <form method="post" action="/admin/?tab=config" style="margin:0" onsubmit="return confirm('Regenerezi tokenul? Va trebui să-l actualizezi local.')">
            <input type="hidden" name="action" value="regenerate_sync_token">
            <button type="submit" class="btn btn-secondary btn-sm">Regenerează</button>
        </form>
    </div>
    <p class="form-desc" style="margin:0">Conținut <code>.sync-token</code>:</p>
    <pre style="background:#f5f5f5;padding:10px;border-radius:4px;font-size:12px;margin:6px 0 0;user-select:all">SYNC_URL=https://cursurilapahar.ro/admin/sync-export.php
SYNC_TOKEN=<?= h($settings['sync_token'] ?? '') ?></pre>
</div>

<script>
function copySyncToken() {
    const inp = document.getElementById('sync_token_input');
    inp.select();
    navigator.clipboard.writeText(inp.value);
}
</script>

<?php endif; ?>

    </main>
</div><!-- /wp-layout -->

<script>
function toggleDiscountRow(id) {
    const row = document.getElementById('discount-row-' + id);
    if (!row) return;
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
async function importLT() {
    const url = document.getElementById('ltUrl').value.trim();
    const msg = document.getElementById('importMsg');
    if (!url) { msg.style.cssText = 'color:var(--danger);margin-top:8px;font-size:13px'; msg.textContent = 'Introdu un URL.'; return; }

    msg.style.cssText = 'color:var(--text-muted);margin-top:8px;font-size:13px';
    msg.textContent = 'Se importă…';

    try {
        const res  = await fetch('/api/livetickets.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url })
        });
        const data = await res.json();

        if (data.success && data.data) {
            const d = data.data;
            document.getElementById('f_id').value           = '';
            document.getElementById('f_title').value        = d.title || '';
            document.getElementById('f_date_display').value = d.date_display || '';
            document.getElementById('f_date_raw').value     = d.date_raw || '';
            document.getElementById('f_time').value         = d.time || '';
            document.getElementById('f_location').value     = d.location || '';
            document.getElementById('f_lt_url').value       = d.livetickets_url || '';
            document.getElementById('f_image_url').value    = d.image_url || '';

            // Update preview
            document.getElementById('prev_title').textContent = d.title || '';
            document.getElementById('prev_meta').textContent  =
                [d.date_display, d.time, d.location].filter(Boolean).join(' · ');
            const img = document.getElementById('prev_img');
            if (d.image_url) { img.src = d.image_url; img.style.display = 'block'; }

            document.getElementById('coursePreview').style.display = 'flex';
            document.getElementById('courseForm').style.display    = 'block';

            msg.style.color = 'var(--success)';
            msg.textContent = '✓ Import reușit! Verifică detaliile și apasă "Adaugă cursul".';
        } else {
            msg.style.color = 'var(--danger)';
            msg.textContent = data.message || 'Eroare la import.';
        }
    } catch (err) {
        msg.style.color = 'var(--danger)';
        msg.textContent = 'Eroare: ' + err.message;
    }
}
</script>

<?php endif; ?>
<script>
function clpToggleSidebarSection(header, id) {
    header.classList.toggle('collapsed');
    document.getElementById('sidebar-' + id).classList.toggle('collapsed');
}
</script>
</body>
</html>
