<?php
if (file_exists(dirname(__DIR__) . '/private/secrets.php')) {
    require dirname(__DIR__) . '/private/secrets.php';
}
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', '');
define('COURSES_FILE',      dirname(__DIR__) . '/data/courses.json');
define('VOTE_COURSES_FILE', dirname(__DIR__) . '/data/vote_courses.json');
define('SETTINGS_FILE',     dirname(__DIR__) . '/data/settings.json');
define('UPLOADS_DIR',       dirname(__DIR__) . '/assets/images/uploads');
define('UPLOADS_URL',       '/assets/images/uploads');
define('PUBLIC_HTML',       dirname(__DIR__));

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

// ── Cookie-based auth ─────────────────────────────────────────────────────────
function is_authenticated(): bool {
    $secret = get_auth_secret();
    if (!$secret) return false;
    $cookie   = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie) return false;
    $expected = hash_hmac('sha256', 'clp_admin_ok', $secret);
    return hash_equals($expected, $cookie);
}
function set_auth_cookie(): void {
    $token = hash_hmac('sha256', 'clp_admin_ok', get_auth_secret());
    setcookie('clp_auth', $token, [
        'expires'  => time() + 86400 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
function clear_auth_cookie(): void {
    setcookie('clp_auth', '', ['expires' => time() - 3600, 'path' => '/']);
}

function get_active_password(): string {
    if (file_exists(SETTINGS_FILE)) {
        $s = json_decode(file_get_contents(SETTINGS_FILE), true) ?: [];
        if (!empty($s['admin_password'])) return $s['admin_password'];
    }
    return ADMIN_PASSWORD;
}

// Ensure secrets exist in settings (generate on first run)
function ensure_secrets(): void {
    $settings = file_exists(SETTINGS_FILE)
        ? (json_decode(file_get_contents(SETTINGS_FILE), true) ?: [])
        : [];
    $changed = false;
    if (empty($settings['auth_secret']))    { $settings['auth_secret']    = bin2hex(random_bytes(32)); $changed = true; }
    if (empty($settings['webhook_secret'])) { $settings['webhook_secret'] = bin2hex(random_bytes(32)); $changed = true; }
    if ($changed) {
        $dir = dirname(SETTINGS_FILE);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
ensure_secrets();

if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === get_active_password()) {
        set_auth_cookie();
        header('Location: /admin/');
        exit;
    } else {
        $login_error = 'Parolă incorectă.';
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
    return json_decode(file_get_contents(COURSES_FILE), true) ?: [];
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

function default_settings(): array {
    return [
        'announcement'      => '🎉 Peste 1.000 de participanți au descoperit că educația are un gust mai bun la un pahar. Tu ești următorul?',
        'hero_title'        => 'Cursuri ținute de experți<br><em>la un pahar în oraș.</em>',
        'hero_btn'          => 'Vezi următoarele cursuri',
        'courses_title'     => 'Următoarele cursuri',
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
            ['label' => 'Cum funcționează',   'url' => '/#cum-functioneaza'],
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
                'title'       => 'Susține un curs',
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
            'cum-functioneaza' => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
            'faq'              => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
            'colaborare'       => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
            'contact'          => ['image' => '', 'blur' => 6, 'overlay' => 0.72],
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
                if (($c['id'] ?? '') === $id) { $c = $entry; $found = true; break; }
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

    // ── Upload image
    if ($action === 'upload_image') {
        if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
        $file = $_FILES['image_file'] ?? null;
        $upload_error = '';
        $upload_ok    = '';
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','gif','avif','heic','heif'];
            if (in_array($ext, $allowed)) {
                $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                $new_name = $base . '-' . time() . '.webp';
                $dest = UPLOADS_DIR . '/' . $new_name;
                // HEIC/HEIF: requires Imagick (GD doesn't support it)
                if (in_array($ext, ['heic','heif'])) {
                    if (class_exists('Imagick')) {
                        try {
                            $imagick = new Imagick($file['tmp_name']);
                            $imagick->setImageFormat('webp');
                            $imagick->setImageCompressionQuality(82);
                            if ($imagick->getImageWidth() > 1920) {
                                $imagick->resizeImage(1920, 0, Imagick::FILTER_LANCZOS, 1);
                            }
                            $imagick->writeImage($dest);
                            $imagick->clear();
                            $upload_ok = 'Imaginea HEIC a fost convertită în WebP: ' . h($new_name);
                        } catch (Exception $e) {
                            $upload_error = 'Eroare la conversia HEIC: ' . h($e->getMessage());
                        }
                    } else {
                        $upload_error = 'Serverul nu suportă HEIC (lipsește extensia Imagick). Convertește în JPG/PNG înainte de upload.';
                    }
                } else {
                    $img = match($ext) {
                        'jpg','jpeg' => @imagecreatefromjpeg($file['tmp_name']),
                        'png'        => @imagecreatefrompng($file['tmp_name']),
                        'webp'       => @imagecreatefromwebp($file['tmp_name']),
                        'gif'        => @imagecreatefromgif($file['tmp_name']),
                        default      => false,
                    };
                    if ($img) {
                        // Resize to max 1920px wide
                        $w = imagesx($img); $h = imagesy($img);
                        if ($w > 1920) {
                            $img2 = imagescale($img, 1920, (int)($h * 1920 / $w), IMG_BICUBIC);
                            imagedestroy($img); $img = $img2;
                        }
                        if (imagewebp($img, $dest, 82)) {
                            imagedestroy($img);
                            $upload_ok = 'Imaginea a fost încărcată și convertită în WebP: ' . h($new_name);
                        } else {
                            imagedestroy($img);
                            $upload_error = 'Eroare la salvarea WebP.';
                        }
                    } else {
                        // GD can't read it (e.g. avif) — save as-is
                        if (move_uploaded_file($file['tmp_name'], UPLOADS_DIR . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name'])))) {
                            $upload_ok = 'Imaginea a fost încărcată: ' . h(basename($file['name']));
                        } else {
                            $upload_error = 'Eroare la salvarea fișierului.';
                        }
                    }
                }
            } else {
                $upload_error = 'Format neacceptat. Folosește JPG, PNG, WEBP, GIF sau HEIC.';
            }
        } else {
            $upload_error = 'Niciun fișier selectat sau eroare la upload.';
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

    // ── Save hero images
    if ($action === 'save_hero_images') {
        $settings = load_settings();
        $selected = $_POST['hero_images'] ?? [];
        $settings['hero_images'] = array_values(array_filter(array_map('trim', $selected)));
        save_settings($settings);
        header('Location: /admin/?tab=imagini&saved=1');
        exit;
    }

    // ── Save settings
    if ($action === 'save_settings') {
        $settings = load_settings();
        $fields = ['announcement','hero_title','hero_btn','courses_title','newsletter_title','newsletter_desc','collab_title','collab_subtitle','contact_title','contact_subtitle'];
        foreach ($fields as $f) {
            $settings[$f] = $_POST[$f] ?? $settings[$f];
        }
        // Steps
        $step_titles = $_POST['step_title'] ?? [];
        $step_texts  = $_POST['step_text']  ?? [];
        if (!empty($step_titles)) {
            $steps = [];
            foreach ($step_titles as $i => $title) {
                $steps[] = ['title' => trim($title), 'text' => trim($step_texts[$i] ?? '')];
            }
            $settings['steps'] = $steps;
        }
        // FAQ
        $faq_qs = $_POST['faq_q'] ?? [];
        $faq_as = $_POST['faq_a'] ?? [];
        $faq_items = [];
        foreach ($faq_qs as $i => $q) {
            $q = trim($q); $a = trim($faq_as[$i] ?? '');
            if ($q) $faq_items[] = ['q' => $q, 'a' => $a];
        }
        if (!empty($faq_items)) $settings['faq_items'] = $faq_items;
        save_settings($settings);
        header('Location: /admin/?tab=setari&saved=1');
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

    // ── Save navbar (mutat la tab Texte)
    if ($action === 'save_navbar') {
        $settings = load_settings();
        $settings['nav_brand_text'] = trim($_POST['nav_brand_text'] ?? 'Cursuri la Pahar');
        $nav_labels = $_POST['nav_label'] ?? [];
        $nav_urls   = $_POST['nav_url']   ?? [];
        $nav_links  = [];
        foreach ($nav_labels as $i => $label) {
            $label = trim($label);
            $url   = trim($nav_urls[$i] ?? '');
            if ($label && $url) $nav_links[] = ['label' => $label, 'url' => $url];
        }
        if ($nav_links) $settings['nav_links'] = $nav_links;
        save_settings($settings);
        header('Location: /admin/?tab=setari&saved=1');
        exit;
    }

    // ── Upload logo
    if ($action === 'upload_logo') {
        $file = $_FILES['logo_file'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','svg','heic','heif'])) {
                $uploads_dir = PUBLIC_HTML . '/assets/images/uploads';
                if (!is_dir($uploads_dir)) @mkdir($uploads_dir, 0755, true);
                $out_ext  = in_array($ext, ['heic','heif']) ? 'webp' : $ext;
                $new_name = 'logo-' . time() . '.' . $out_ext;
                $dest = $uploads_dir . '/' . $new_name;
                $saved = false;
                if (in_array($ext, ['heic','heif']) && class_exists('Imagick')) {
                    try {
                        $im = new Imagick($file['tmp_name']);
                        $im->setImageFormat('webp');
                        $im->setImageCompressionQuality(90);
                        $im->writeImage($dest);
                        $im->clear();
                        $saved = true;
                    } catch (Exception $e) { $saved = false; }
                } else {
                    $saved = move_uploaded_file($file['tmp_name'], $dest);
                }
                if ($saved) {
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

    // ── Save page content
    if ($action === 'save_page') {
        $page_key = preg_replace('/[^a-z]/', '', $_POST['page_key'] ?? '');
        $valid_pages = ['sustine','gazduieste','parteneriat'];
        if (in_array($page_key, $valid_pages)) {
            $settings = load_settings();
            if (!isset($settings['pages'])) $settings['pages'] = [];
            $settings['pages'][$page_key] = [
                'title'       => trim($_POST['title'] ?? ''),
                'subtitle'    => trim($_POST['subtitle'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
            ];
            save_settings($settings);
        }
        header('Location: /admin/?tab=pagini&saved=1&page=' . urlencode($page_key));
        exit;
    }

    // ── Save Kit settings
    if ($action === 'save_kit') {
        $settings = load_settings();
        $settings['kit_api_key'] = trim($_POST['kit_api_key'] ?? '');
        $settings['kit_form_id'] = trim($_POST['kit_form_id'] ?? '');
        save_settings($settings);
        header('Location: /admin/?tab=kit&saved=1');
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
        header('Location: /admin/?tab=securitate&webhook_saved=1');
        exit;
    }

    // ── Export all data as download
    if ($action === 'export_settings') {
        $data_dir = dirname(SETTINGS_FILE);
        $bundle = [
            'settings'     => file_exists(SETTINGS_FILE)     ? json_decode(file_get_contents(SETTINGS_FILE), true)     : [],
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
                foreach (['admin_password','auth_secret','webhook_secret'] as $k) {
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
                        $img = curl_exec($ch); curl_close($ch);
                    }
                    if ($img) { file_put_contents($local_path, $img); $downloaded++; }
                }

                header('Location: /admin/?tab=securitate&imported=' . $downloaded);
                exit;
            }
        }
        header('Location: /admin/?tab=securitate&import_error=1');
        exit;
    }

    // ── Change admin password
    if ($action === 'change_password') {
        $new     = trim($_POST['new_password']     ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        if ($new && $new === $confirm && strlen($new) >= 6) {
            $settings = load_settings();
            $settings['admin_password'] = $new;
            save_settings($settings);
            header('Location: /admin/?tab=securitate&saved=1');
        } else {
            header('Location: /admin/?tab=securitate&error=1');
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
                    // Preserve existing likes when editing
                    $entry['likes'] = $c['likes'] ?? 0;
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
                array_splice($blocks, $to_remove, 1);
                file_put_contents($log_file, implode("\n\n", $blocks) . "\n", LOCK_EX);
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
}

// ── Navbar live (from live site editor) ──────────────────────────────────────
if (is_authenticated() && ($action === 'save_navbar_live')) {
    header('Content-Type: application/json');
    $s = load_settings();
    $color_keys = ['nav_bg','nav_brand_color','nav_link_color'];
    $num_keys   = ['nav_brand_size','nav_brand_weight','nav_link_weight','nav_logo_h'];
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

// ── Inline edit (from live site editor) ──────────────────────────────────────
if (is_authenticated() && ($action === 'save_inline_edit')) {
    $key   = trim($_POST['key']   ?? '');
    $raw   = $_POST['value'] ?? '';
    // Fields that may contain HTML tags — allow only safe subset
    $html_keys = ['hero_title', 'announcement',
                  'sustine_intro_1', 'sustine_intro_2',
                  'gazduieste_intro_1', 'gazduieste_intro_2',
                  'parteneriat_intro_1', 'parteneriat_intro_2'];
    $value = in_array($key, $html_keys)
        ? trim(strip_tags($raw, '<br><em><strong>'))
        : trim(strip_tags($raw));
    $style = trim($_POST['style'] ?? '');
    $flat_allowed = ['hero_title','announcement','courses_title','newsletter_title',
                     'newsletter_desc','collab_title','collab_subtitle','contact_title','contact_subtitle',
                     'steps_title','faq_title','nav_brand_text',
                     'vote_title','vote_subtitle',
                     'sustine_title','sustine_intro_1','sustine_intro_2',
                     'gazduieste_title','gazduieste_intro_1','gazduieste_intro_2',
                     'parteneriat_title','parteneriat_intro_1','parteneriat_intro_2'];
    header('Content-Type: application/json');
    $ok = false;
    if ($key) {
        $s = load_settings();
        if (!isset($s['element_styles'])) $s['element_styles'] = [];

        // step_{i}_title or step_{i}_text
        if (preg_match('/^step_(\d+)_(title|text)$/', $key, $m)) {
            $idx  = (int)$m[1];
            $prop = $m[2];
            $defaults = [
                ['title' => 'Verifici calendarul',  'text' => 'Răsfoiești cursurile disponibile și găsești tema care te stârnește curiozitatea.'],
                ['title' => 'Cumperi biletul',       'text' => 'Achiziționezi biletul online prin LiveTickets, simplu și rapid, de pe orice dispozitiv.'],
                ['title' => 'Vii la eveniment',      'text' => 'Te prezinți la locație, îți iei o băutură preferată și ocupi un loc confortabil.'],
                ['title' => 'Înveți & socializezi',  'text' => 'Asculți expertul, pui orice întrebare la Q&A și cunoști oameni faini cu aceleași interese.'],
            ];
            if (!isset($s['steps'])) $s['steps'] = $defaults;
            if (isset($s['steps'][$idx])) {
                $s['steps'][$idx][$prop] = $value;
                if ($style) $s['element_styles'][$key] = $style;
                else unset($s['element_styles'][$key]);
                $ok = true;
            }
        // faq_{i}_q or faq_{i}_a
        } elseif (preg_match('/^faq_(\d+)_(q|a)$/', $key, $m)) {
            $idx  = (int)$m[1];
            $prop = $m[2];
            if (!isset($s['faq_items'])) $s['faq_items'] = [];
            if (isset($s['faq_items'][$idx])) {
                $s['faq_items'][$idx][$prop] = $value;
                if ($style) $s['element_styles'][$key] = $style;
                else unset($s['element_styles'][$key]);
                $ok = true;
            }
        // nav_link_{i}_label
        } elseif (preg_match('/^nav_link_(\d+)_label$/', $key, $m)) {
            $idx = (int)$m[1];
            if (!isset($s['nav_links'])) $s['nav_links'] = [];
            if (isset($s['nav_links'][$idx])) {
                $s['nav_links'][$idx]['label'] = $value;
                if ($style) $s['element_styles'][$key] = $style;
                else unset($s['element_styles'][$key]);
                $ok = true;
            }
        // flat keys
        } elseif (in_array($key, $flat_allowed)) {
            $s[$key] = $value;
            if ($style) $s['element_styles'][$key] = $style;
            else unset($s['element_styles'][$key]);
            $ok = true;
        }

        if ($ok) $ok = save_settings($s);
    }
    echo json_encode(['ok' => $ok, 'writable' => is_writable(dirname(SETTINGS_FILE))]);
    exit;
}

// ── Section background edit ───────────────────────────────────────────────────
if (is_authenticated() && ($action === 'save_section_bg')) {
    $allowed_sections = ['cursuri','newsletter','cum-functioneaza','faq','colaborare','contact'];
    $section = trim($_POST['section'] ?? '');
    header('Content-Type: application/json');
    if (in_array($section, $allowed_sections)) {
        $s = load_settings();
        if (!isset($s['section_bgs'])) $s['section_bgs'] = [];
        $s['section_bgs'][$section] = [
            'image'   => trim($_POST['image'] ?? ''),
            'blur'    => max(0, min(30, (int)($_POST['blur'] ?? 6))),
            'overlay' => max(0, min(1, round((float)($_POST['overlay'] ?? 0.72), 2))),
        ];
        $ok = save_settings($s);
        echo json_encode(['ok' => $ok]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'invalid section']);
    }
    exit;
}

// ── Load data for display ─────────────────────────────────────────────────────
$courses  = [];
$settings = load_settings();
$tab      = $_GET['tab'] ?? 'cursuri';
if (!in_array($tab, ['cursuri','imagini','setari','aspect','pagini','kit','mesaje','vot','securitate','config'])) $tab = 'cursuri';

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
    $collect(UPLOADS_DIR, UPLOADS_URL . '/', true);
    return $imgs;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Cursuri la Pahar</title>
<?php if (!empty($settings['favicon_path'])): ?><link rel="icon" href="<?= htmlspecialchars($settings['favicon_path']) ?>"><?php endif; ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #f0f0f1;
    --surface: #fff;
    --header-bg: #1d2327;
    --header-text: #fff;
    --sidebar-bg: #1d2327;
    --sidebar-text: #a7aaad;
    --sidebar-active: #fff;
    --sidebar-active-bg: #2271b1;
    --accent: #2271b1;
    --accent-hover: #135e96;
    --text: #1d2327;
    --text-muted: #646970;
    --border: #c3c4c7;
    --danger: #d63638;
    --success: #00a32a;
    --font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
body { background: var(--bg); color: var(--text); font-family: var(--font); font-size: 13px; line-height: 1.5; min-height: 100vh; }

/* ── Login ── */
.login-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.login-box { background: var(--surface); border: 1px solid var(--border); border-radius: 4px; padding: 40px; width: 320px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.login-box h1 { font-size: 20px; color: var(--text); margin-bottom: 24px; text-align: center; }
.login-box input[type="password"] { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; margin-bottom: 12px; background: #fff; color: var(--text); }
.login-box input[type="password"]:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.login-error { color: var(--danger); font-size: 13px; margin-bottom: 10px; }

/* ── Top bar ── */
.wp-header { background: var(--header-bg); color: var(--header-text); height: 46px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 100; }
.wp-header .brand { font-size: 14px; font-weight: 600; color: var(--header-text); text-decoration: none; }
.wp-header .brand span { opacity: .7; font-weight: 400; }

/* ── Layout ── */
.wp-layout { display: flex; min-height: calc(100vh - 46px); margin-top: 46px; }

/* ── Sidebar ── */
.wp-sidebar { width: 200px; background: var(--sidebar-bg); flex-shrink: 0; padding-top: 8px; position: fixed; top: 46px; left: 0; height: calc(100vh - 46px); overflow-y: hidden; z-index: 99; }
.wp-sidebar nav a {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px 8px 14px; color: var(--sidebar-text);
    text-decoration: none; font-size: 13px; font-weight: 500;
    border-left: 3px solid transparent; transition: background .1s, color .1s;
}
.wp-sidebar nav a:hover { color: var(--sidebar-active); background: rgba(255,255,255,.07); }
.wp-sidebar nav a.active { color: var(--sidebar-active); background: var(--sidebar-active-bg); border-left-color: rgba(255,255,255,.3); }
.wp-sidebar nav a .nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
.sidebar-section { padding: 14px 14px 4px; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #50575e; font-weight: 700; }
.nav-new-badge { margin-left: auto; background: #e74c3c; color: #fff; font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 10px; white-space: nowrap; }

/* ── Main content ── */
.wp-main { flex: 1; padding: 20px 24px; min-width: 0; margin-left: 200px; }
.wp-page-title { font-size: 22px; font-weight: 400; color: var(--text); margin-bottom: 20px; line-height: 1.3; }

/* ── Cards ── */
.card { background: var(--surface); border: 1px solid var(--border); border-radius: 4px; padding: 20px; margin-bottom: 20px; }
.card-title { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }

/* ── Buttons ── */
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 3px; border: 1px solid transparent; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; line-height: 2; transition: background .1s; }
.btn-primary { background: var(--accent); border-color: var(--accent); color: #fff; }
.btn-primary:hover { background: var(--accent-hover); border-color: var(--accent-hover); color: #fff; }
.btn-secondary { background: #f6f7f7; border-color: #c3c4c7; color: var(--text); }
.btn-secondary:hover { background: #f0f0f1; border-color: #a7aaad; }
.btn-danger { background: var(--danger); border-color: var(--danger); color: #fff; }
.btn-danger:hover { background: #b32d2e; border-color: #b32d2e; }
.btn-sm { padding: 2px 10px; font-size: 12px; line-height: 1.8; }
.btn-link { background: transparent; border-color: transparent; color: var(--accent); padding: 0; line-height: 1.5; }
.btn-link:hover { color: var(--accent-hover); text-decoration: underline; }

/* Toggle status buttons */
.status-active { background: #edfaef; border-color: #00a32a; color: #00a32a; }
.status-active:hover { background: #d8f5dc; }
.status-inactive { background: #f6f7f7; border-color: #c3c4c7; color: var(--text-muted); }
.status-inactive:hover { background: #f0f0f1; }

/* ── Logout button ── */
.btn-logout { background: transparent; border: 1px solid rgba(255,255,255,.25); color: rgba(255,255,255,.8); padding: 4px 10px; font-size: 12px; line-height: 1.8; border-radius: 3px; cursor: pointer; text-decoration: none; }
.btn-logout:hover { background: rgba(255,255,255,.1); color: #fff; }

/* ── Forms ── */
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 5px; }
.form-group input[type="text"],
.form-group input[type="url"],
.form-group textarea {
    width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 4px;
    font-size: 13px; font-family: inherit; color: var(--text); background: #fff;
    transition: border-color .1s;
}
.form-group input:focus,
.form-group textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.form-group textarea { resize: vertical; min-height: 80px; }
.form-desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

.import-row { display: flex; gap: 8px; }
.import-row input { flex: 1; padding: 8px 10px; border: 1px solid var(--border); border-radius: 4px; font-size: 13px; color: var(--text); }
.import-row input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
#importMsg { margin-top: 8px; font-size: 13px; }

/* Course preview card */
.course-preview { display: flex; gap: 14px; align-items: flex-start; background: #f6f7f7; border: 1px solid var(--border); border-radius: 4px; padding: 14px; margin: 14px 0; }
.course-preview img { width: 90px; height: 56px; object-fit: cover; border-radius: 3px; flex-shrink: 0; border: 1px solid var(--border); }
.course-preview-body { flex: 1; min-width: 0; }
.course-preview-title { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
.course-preview-meta { font-size: 12px; color: var(--text-muted); }

/* ── Table ── */
.wp-table { width: 100%; border-collapse: collapse; }
.wp-table th { text-align: left; padding: 8px 12px; font-size: 12px; font-weight: 700; color: var(--text-muted); background: #f6f7f7; border-bottom: 1px solid var(--border); }
.wp-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; font-size: 13px; }
.wp-table tbody tr:last-child td { border-bottom: none; }
.wp-table tbody tr:hover td { background: #f9f9f9; }
.course-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 3px; border: 1px solid var(--border); display: block; }
.course-thumb-empty { width: 60px; height: 40px; background: #f0f0f1; border: 1px solid var(--border); border-radius: 3px; }
.row-actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }

/* ── Notice ── */
.notice { padding: 10px 16px; border-radius: 3px; border-left: 4px solid; margin-bottom: 16px; font-size: 13px; }
.notice-success { background: #edfaef; border-left-color: var(--success); color: #00653a; }
.notice-error   { background: #fce8e8; border-left-color: var(--danger); color: #8c1415; }

/* ── Images grid ── */
.images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
.image-item { border: 1px solid var(--border); border-radius: 4px; overflow: hidden; background: #fff; }
.image-item img { width: 100%; height: 100px; object-fit: cover; display: block; }
.image-item-body { padding: 8px; }
.image-item-name { font-size: 11px; color: var(--text-muted); word-break: break-all; margin-bottom: 6px; }
.image-item-actions { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.hero-check { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text); cursor: pointer; }
.hero-check input { accent-color: var(--accent); width: 14px; height: 14px; cursor: pointer; }

/* ── Coloris overrides ── */
.clr-field { width: 100%; }
.clr-field input {
    width: 100%;
    padding: 9px 48px 9px 12px !important;
    border: 1px solid var(--border);
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    background: #fff;
    color: #1d2327;
    cursor: pointer;
    box-sizing: border-box;
}
.clr-field button {
    width: 36px; height: calc(100% - 2px);
    border-radius: 0 3px 3px 0;
    right: 1px; left: auto; top: 1px;
    transform: none;
}
/* Bigger popup */
.clr-picker { width: 320px !important; }
#clr-color-area { height: 240px !important; }
/* Hide format toggle (hex only) */
#clr-format { display: none !important; }
/* Hide alpha slider */
.clr-alpha { display: none !important; }
</style>
<link rel="stylesheet" href="/assets/css/coloris.min.css">
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
            <input type="password" name="login_password" placeholder="Parolă" autofocus>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Intră</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── ADMIN PANEL ─────────────────────────────────────────────────────────── -->

<header class="wp-header">
    <div style="display:flex;align-items:center;gap:10px">
        <a href="/admin/" class="brand">Cursuri la Pahar <span>— Admin</span></a>
        <a href="/" style="color:rgba(255,255,255,.7);font-size:12px;text-decoration:none;padding:4px 10px;border:1px solid rgba(255,255,255,.2);border-radius:3px;transition:background .1s" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background=''">🌐 Vezi site</a>
    </div>
    <a href="/admin/?logout=1" class="btn-logout">Deconectează-te</a>
</header>

<div class="wp-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="wp-sidebar">
        <nav>
            <a href="/admin/?tab=cursuri" class="<?= $tab === 'cursuri' ? 'active' : '' ?>">
                <span class="nav-icon">📋</span> Cursuri
            </a>
            <a href="/admin/?tab=imagini" class="<?= $tab === 'imagini' ? 'active' : '' ?>">
                <span class="nav-icon">🖼️</span> Imagini
            </a>
            <a href="/admin/?tab=setari" class="<?= $tab === 'setari' ? 'active' : '' ?>">
                <span class="nav-icon">⚙️</span> Texte
            </a>
            <a href="/admin/?tab=aspect" class="<?= $tab === 'aspect' ? 'active' : '' ?>">
                <span class="nav-icon">🎨</span> Aspect
            </a>
            <a href="/admin/?tab=pagini" class="<?= $tab === 'pagini' ? 'active' : '' ?>">
                <span class="nav-icon">📄</span> Pagini
            </a>
            <a href="/admin/?tab=kit" class="<?= $tab === 'kit' ? 'active' : '' ?>">
                <span class="nav-icon">📧</span> Kit (Email)
            </a>
            <a href="/admin/?tab=mesaje" class="<?= $tab === 'mesaje' ? 'active' : '' ?>">
                <span class="nav-icon">💬</span> Mesaje<?php if ($_msg_unread_count > 0): ?><span class="nav-new-badge"><?= $_msg_unread_count ?> <?= $_msg_unread_count === 1 ? 'nou' : 'noi' ?></span><?php endif; ?>
            </a>
            <a href="/admin/?tab=vot" class="<?= $tab === 'vot' ? 'active' : '' ?>">
                <span class="nav-icon">❤️</span> Vot cursuri
            </a>
            <a href="/admin/?tab=securitate" class="<?= $tab === 'securitate' ? 'active' : '' ?>">
                <span class="nav-icon">🔒</span> Securitate
            </a>
            <a href="/admin/?tab=config" class="<?= $tab === 'config' ? 'active' : '' ?>">
                <span class="nav-icon">⚙️</span> Setări
            </a>
        </nav>
    </aside>

    <!-- ── MAIN ── -->
    <main class="wp-main">

<?php /* ======================================================= TAB: CURSURI */ ?>
<?php if ($tab === 'cursuri'): ?>

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

    <!-- Courses table -->
    <div class="card">
        <div class="card-title">Cursuri (<?= count($courses) ?>)</div>
        <?php if (empty($courses)): ?>
        <p style="color:var(--text-muted)">Nu există cursuri adăugate încă.</p>
        <?php else: ?>
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
                <?php foreach ($courses as $c): ?>
                <tr>
                    <td>
                        <?php if (!empty($c['image_url'])): ?>
                        <img class="course-thumb" src="<?= h($c['image_url']) ?>" alt="">
                        <?php else: ?>
                        <div class="course-thumb-empty"></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:600"><?= h($c['title'] ?? '') ?></td>
                    <td style="color:var(--text-muted)"><?= h($c['date_display'] ?? $c['date_raw'] ?? '') ?></td>
                    <td>
                        <form method="post" action="/admin/?tab=cursuri" style="display:inline">
                            <input type="hidden" name="action" value="toggle_course">
                            <input type="hidden" name="id" value="<?= h($c['id'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm <?= !empty($c['active']) ? 'status-active' : 'status-inactive' ?>">
                                <?= !empty($c['active']) ? 'Activ' : 'Inactiv' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="row-actions">
                            <form method="post" action="/admin/?tab=cursuri" onsubmit="return confirm('Ștergi cursul?')" style="display:inline">
                                <input type="hidden" name="action" value="delete_course">
                                <input type="hidden" name="id" value="<?= h($c['id'] ?? '') ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Șterge</button>
                            </form>
                            <?php if (!empty($c['livetickets_url'])): ?>
                            <a href="<?= h($c['livetickets_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">LiveTickets ↗</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

<?php /* ======================================================= TAB: IMAGINI */ ?>
<?php elseif ($tab === 'imagini'): ?>

    <h1 class="wp-page-title">Imagini</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success">Setările imaginilor hero au fost salvate.</div>
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
        <form method="post" action="/admin/?tab=imagini" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="action" value="upload_image">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="file" name="image_file" id="imageFileInput" accept="image/*,.heic,.heif" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
                <button type="submit" class="btn btn-primary" id="uploadBtn">Încarcă</button>
            </div>
            <p class="form-desc">Formate acceptate: JPG, PNG, WEBP, GIF, HEIC. Imaginile sunt convertite automat în WebP și redimensionate la max 1920px.</p>
            <p id="heicStatus" style="display:none;color:#2271b1;font-size:13px;margin-top:6px;"></p>
        </form>
    </div>
    <script>
    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
        const fileInput = document.getElementById('imageFileInput');
        const file = fileInput.files[0];
        if (!file) return;
        const name = file.name.toLowerCase();
        if (!name.endsWith('.heic') && !name.endsWith('.heif')) return;

        e.preventDefault();
        const status = document.getElementById('heicStatus');
        const btn = document.getElementById('uploadBtn');
        status.style.display = 'block';
        status.textContent = '⏳ Se convertește HEIC → JPEG în browser...';
        btn.disabled = true;
        btn.textContent = 'Se convertește...';

        try {
            // Use native browser HEIC decoding (macOS Chrome/Safari support it)
            const url = URL.createObjectURL(file);
            const img = new Image();
            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = () => reject(new Error('Browserul nu poate decoda HEIC. Deschide poza în Preview și salvează ca JPG.'));
                img.src = url;
            });
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            canvas.getContext('2d').drawImage(img, 0, 0);
            URL.revokeObjectURL(url);

            const blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.92));
            const converted = new File([blob], file.name.replace(/\.heic$/i, '.jpg').replace(/\.heif$/i, '.jpg'), { type: 'image/jpeg' });

            status.textContent = '⏳ Se încarcă...';
            const fd = new FormData();
            fd.append('action', 'upload_image');
            fd.append('image_file', converted);

            const resp = await fetch('/admin/?tab=imagini', { method: 'POST', body: fd });
            if (resp.ok) {
                status.textContent = '✓ Convertit și încărcat!';
                setTimeout(() => window.location.reload(), 800);
            } else {
                status.textContent = '✗ Eroare la upload (HTTP ' + resp.status + ')';
                btn.disabled = false;
                btn.textContent = 'Încarcă';
            }
        } catch (err) {
            status.style.color = '#d63638';
            status.textContent = '✗ ' + err.message;
            btn.disabled = false;
            btn.textContent = 'Încarcă';
        }
    });
    </script>

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
                    $is_hero = in_array($img['url'], $settings['hero_images'] ?? []);
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
                            <?php if ($img['deletable']): ?>
                            <form method="post" action="/admin/?tab=imagini" onsubmit="return confirm('Ștergi imaginea?')" style="display:inline">
                                <input type="hidden" name="action" value="delete_image">
                                <input type="hidden" name="filename" value="<?= h($img['name']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger" style="padding:1px 7px">✕</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary">Salvează imaginile hero</button>
                <span style="font-size:12px;color:var(--text-muted);margin-left:10px">Bifează imaginile care apar în slideshow-ul hero.</span>
            </div>
        </form>
        <?php endif; ?>
    </div>

<?php /* ======================================================= TAB: SETARI */ ?>
<?php elseif ($tab === 'setari'): ?>

    <h1 class="wp-page-title">Setări</h1>

    <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success">Setările au fost salvate cu succes.</div>
    <?php endif; ?>

    <style>
    .tf-card { background: var(--surface, #fff); border: 1px solid var(--border); border-radius: 10px; margin-bottom: 16px; overflow: hidden; }
    .tf-card-title { padding: 14px 18px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); border-bottom: 1px solid var(--border); }
    .tf-row { border-bottom: 1px solid var(--border); }
    .tf-row:last-child { border-bottom: none; }
    .tf-header { display: flex; align-items: center; gap: 12px; padding: 11px 18px; cursor: pointer; user-select: none; transition: background .12s; }
    .tf-header:hover { background: rgba(255,255,255,.04); }
    .tf-label { font-size: 13px; font-weight: 600; color: var(--text); min-width: 180px; flex-shrink: 0; }
    .tf-preview { font-size: 12px; color: var(--text-muted); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tf-arrow { color: var(--text-muted); font-size: 11px; transition: transform .2s; flex-shrink: 0; }
    .tf-row.open .tf-arrow { transform: rotate(180deg); }
    .tf-body { display: none; padding: 4px 18px 16px; }
    .tf-row.open .tf-body { display: block; }
    .tf-body .form-desc { margin-top: 6px; }

    .tf-step { border: 1px solid var(--border); border-radius: 6px; margin-bottom: 8px; overflow: hidden; }
    .tf-step-header { display: flex; align-items: center; gap: 12px; padding: 10px 16px; cursor: pointer; user-select: none; background: var(--surface); transition: background .12s; }
    .tf-step-header:hover { background: #f8f8f8; }
    .tf-step-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); flex: 1; }
    .tf-step-preview { font-size: 12px; color: var(--text-muted); flex: 2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tf-step-body { display: none; padding: 14px 16px; background: var(--surface); border-top: 1px solid var(--border); }
    .tf-step.open .tf-step-body { display: block; }
    .tf-step.open .tf-arrow { transform: rotate(180deg); }

    .faq-edit-item .faq-item-header { display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; }
    .faq-edit-item .faq-item-preview { font-size: 12px; color: var(--text-muted); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .faq-edit-item .faq-item-body { display: none; margin-top: 12px; }
    .faq-edit-item.open .faq-item-body { display: block; }
    .faq-edit-item.open .tf-arrow { transform: rotate(180deg); }
    </style>

    <form method="post" action="/admin/?tab=setari">
        <input type="hidden" name="action" value="save_settings">

        <div class="tf-card">
            <div class="tf-card-title">Banner &amp; Hero</div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Anunț banner</span>
                    <span class="tf-preview"><?= h($settings['announcement']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <textarea id="s_announcement" name="announcement" rows="2" class="form-group" style="margin:0;width:100%"><?= h($settings['announcement']) ?></textarea>
                    <p class="form-desc">Textul afișat în bannerul de anunț de sub hero.</p>
                </div>
            </div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Titlu hero</span>
                    <span class="tf-preview"><?= h(strip_tags($settings['hero_title'])) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <textarea id="s_hero_title" name="hero_title" rows="3" style="width:100%"><?= h($settings['hero_title']) ?></textarea>
                    <p class="form-desc">Suportă HTML, ex: <code>&lt;em&gt;text italic&lt;/em&gt;</code>, <code>&lt;br&gt;</code>.</p>
                </div>
            </div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Text buton hero</span>
                    <span class="tf-preview"><?= h($settings['hero_btn']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <input type="text" id="s_hero_btn" name="hero_btn" value="<?= h($settings['hero_btn']) ?>" style="width:100%">
                </div>
            </div>
        </div>

        <div class="tf-card">
            <div class="tf-card-title">Secțiuni</div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Titlu Cursuri</span>
                    <span class="tf-preview"><?= h($settings['courses_title']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <input type="text" id="s_courses_title" name="courses_title" value="<?= h($settings['courses_title']) ?>" style="width:100%">
                </div>
            </div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Titlu Newsletter</span>
                    <span class="tf-preview"><?= h($settings['newsletter_title']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <input type="text" id="s_newsletter_title" name="newsletter_title" value="<?= h($settings['newsletter_title']) ?>" style="width:100%">
                </div>
            </div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Descriere Newsletter</span>
                    <span class="tf-preview"><?= h($settings['newsletter_desc']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <textarea id="s_newsletter_desc" name="newsletter_desc" rows="3" style="width:100%"><?= h($settings['newsletter_desc']) ?></textarea>
                </div>
            </div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Titlu Colaborare</span>
                    <span class="tf-preview"><?= h($settings['collab_title']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <input type="text" id="s_collab_title" name="collab_title" value="<?= h($settings['collab_title']) ?>" style="width:100%">
                </div>
            </div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Subtitlu Colaborare</span>
                    <span class="tf-preview"><?= h($settings['collab_subtitle']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <textarea id="s_collab_subtitle" name="collab_subtitle" rows="2" style="width:100%"><?= h($settings['collab_subtitle']) ?></textarea>
                </div>
            </div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Titlu Contact</span>
                    <span class="tf-preview"><?= h($settings['contact_title']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <input type="text" id="s_contact_title" name="contact_title" value="<?= h($settings['contact_title']) ?>" style="width:100%">
                </div>
            </div>

            <div class="tf-row">
                <div class="tf-header" onclick="toggleTf(this)">
                    <span class="tf-label">Subtitlu Contact</span>
                    <span class="tf-preview"><?= h($settings['contact_subtitle']) ?></span>
                    <span class="tf-arrow">▼</span>
                </div>
                <div class="tf-body">
                    <textarea id="s_contact_subtitle" name="contact_subtitle" rows="2" style="width:100%"><?= h($settings['contact_subtitle']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="tf-card">
            <div class="tf-card-title">Cum funcționează – Pași</div>
            <?php foreach ($settings['steps'] as $i => $step): $n = $i + 1; ?>
            <div class="tf-step <?= $n === 1 ? '' : '' ?>">
                <div class="tf-step-header" onclick="this.closest('.tf-step').classList.toggle('open')">
                    <span class="tf-step-label">Pasul <?= $n ?></span>
                    <span class="tf-step-preview"><?= h($step['title']) ?></span>
                    <span class="tf-arrow" style="color:var(--text-muted);font-size:11px;transition:transform .2s">▼</span>
                </div>
                <div class="tf-step-body">
                    <div class="form-group">
                        <label>Titlu</label>
                        <input type="text" name="step_title[]" value="<?= h($step['title']) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>Text</label>
                        <textarea name="step_text[]" rows="2"><?= h($step['text']) ?></textarea>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="tf-card">
            <div class="tf-card-title">FAQ – Întrebări frecvente</div>
            <div id="faq-editor" style="padding:8px 8px 4px">
                <?php foreach ($settings['faq_items'] as $i => $item): ?>
                <div class="faq-edit-item" style="background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:12px 14px;margin-bottom:8px;position:relative">
                    <div class="faq-item-header" onclick="this.closest('.faq-edit-item').classList.toggle('open')">
                        <span style="font-size:13px;font-weight:600;color:var(--text);flex-shrink:0">Q</span>
                        <span class="faq-item-preview"><?= h($item['q']) ?></span>
                        <span class="tf-arrow" style="color:var(--text-muted);font-size:11px;transition:transform .2s;margin-right:24px">▼</span>
                        <button type="button" onclick="event.stopPropagation();this.closest('.faq-edit-item').remove()" title="Șterge" style="position:absolute;top:8px;right:10px;background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:15px;line-height:1;padding:2px 4px">✕</button>
                    </div>
                    <div class="faq-item-body">
                        <div class="form-group" style="margin-top:10px">
                            <label>Întrebare</label>
                            <input type="text" name="faq_q[]" value="<?= h($item['q']) ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Răspuns</label>
                            <textarea name="faq_a[]" rows="3"><?= h($item['a']) ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="padding:0 8px 12px">
                <button type="button" onclick="addFaqItem()" class="btn btn-secondary" style="font-size:13px">+ Adaugă întrebare</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-bottom:24px">Salvează setările</button>
    </form>
    <script>
    function toggleTf(header) {
        header.closest('.tf-row').classList.toggle('open');
    }
    function addFaqItem() {
        const editor = document.getElementById('faq-editor');
        const div = document.createElement('div');
        div.className = 'faq-edit-item open';
        div.style.cssText = 'background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:12px 14px;margin-bottom:8px;position:relative';
        div.innerHTML = '<div class="faq-item-header" onclick="this.closest(\'.faq-edit-item\').classList.toggle(\'open\')">'
            + '<span style="font-size:13px;font-weight:600;color:var(--text);flex-shrink:0">Q</span>'
            + '<span class="faq-item-preview">Întrebare nouă</span>'
            + '<span class="tf-arrow" style="color:var(--text-muted);font-size:11px;transition:transform .2s;margin-right:24px">▼</span>'
            + '<button type="button" onclick="event.stopPropagation();this.closest(\'.faq-edit-item\').remove()" title="Șterge" style="position:absolute;top:8px;right:10px;background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:15px;line-height:1;padding:2px 4px">✕</button>'
            + '</div>'
            + '<div class="faq-item-body" style="display:block">'
            + '<div class="form-group" style="margin-top:10px"><label>Întrebare</label><input type="text" name="faq_q[]" value=""></div>'
            + '<div class="form-group" style="margin-bottom:0"><label>Răspuns</label><textarea name="faq_a[]" rows="3"></textarea></div>'
            + '</div>';
        editor.appendChild(div);
        div.querySelector('input').focus();
    }
    function removeFaqItem(btn) { btn.closest('.faq-edit-item').remove(); }
    </script>

<!-- Brand text + Nav links -->
<form method="post" action="/admin/?tab=setari">
    <input type="hidden" name="action" value="save_navbar">
    <div class="card">
        <div class="card-title">Navbar</div>
        <div class="form-group">
            <label>Text brand (lângă logo)</label>
            <input type="text" name="nav_brand_text" value="<?= h($settings['nav_brand_text'] ?? 'Cursuri la Pahar') ?>">
        </div>
        <div class="form-group">
            <label>Linkuri meniu <span style="font-weight:400;color:var(--text-muted)">(trage pentru reordonare)</span></label>
            <div id="navLinksList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px">
                <?php foreach ($settings['nav_links'] ?? [] as $i => $nl): ?>
                <div class="nav-link-row" draggable="true" style="display:flex;align-items:center;gap:8px;background:#f6f7f7;border:1px solid var(--border);border-radius:4px;padding:6px 10px;cursor:grab">
                    <span style="color:#aaa;font-size:16px;cursor:grab;flex-shrink:0">⠿</span>
                    <input type="text" name="nav_label[]" value="<?= h($nl['label'] ?? '') ?>" placeholder="Nume" style="flex:0 0 160px;padding:4px 8px;border:1px solid var(--border);border-radius:3px;font-size:13px;background:#fff">
                    <input type="text" name="nav_url[]"   value="<?= h($nl['url'] ?? '') ?>"   placeholder="/url" style="flex:1;padding:4px 8px;border:1px solid var(--border);border-radius:3px;font-size:13px;background:#fff;font-family:monospace">
                    <button type="button" onclick="this.closest('.nav-link-row').remove()" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:16px;line-height:1;flex-shrink:0" title="Șterge">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addNavLink()">+ Adaugă link</button>
        </div>
        <button type="submit" class="btn btn-primary">Salvează navbar</button>
<script>
(function(){
    const list = document.getElementById('navLinksList');
    if (!list) return;
    let dragged = null;
    list.addEventListener('dragstart', e => {
        dragged = e.target.closest('.nav-link-row');
        setTimeout(() => dragged && dragged.classList.add('dragging'), 0);
    });
    list.addEventListener('dragend', () => {
        if (dragged) dragged.classList.remove('dragging');
        dragged = null;
    });
    list.addEventListener('dragover', e => {
        e.preventDefault();
        const row = e.target.closest('.nav-link-row');
        if (row && row !== dragged) {
            const rect = row.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;
            list.insertBefore(dragged, after ? row.nextSibling : row);
        }
    });
    const style = document.createElement('style');
    style.textContent = '.nav-link-row.dragging { opacity:.4; }';
    document.head.appendChild(style);
})();
function addNavLink() {
    const list = document.getElementById('navLinksList');
    const row = document.createElement('div');
    row.className = 'nav-link-row';
    row.draggable = true;
    row.style.cssText = 'display:flex;align-items:center;gap:8px;background:#f6f7f7;border:1px solid #c3c4c7;border-radius:4px;padding:6px 10px;cursor:grab';
    row.innerHTML = '<span style="color:#aaa;font-size:16px;cursor:grab;flex-shrink:0">⠿</span>'
        + '<input type="text" name="nav_label[]" placeholder="Nume" style="flex:0 0 160px;padding:4px 8px;border:1px solid #c3c4c7;border-radius:3px;font-size:13px;background:#fff">'
        + '<input type="text" name="nav_url[]" placeholder="/url" style="flex:1;padding:4px 8px;border:1px solid #c3c4c7;border-radius:3px;font-size:13px;background:#fff;font-family:monospace">'
        + '<button type="button" onclick="this.closest(\'.nav-link-row\').remove()" style="background:none;border:none;color:#d63638;cursor:pointer;font-size:16px;line-height:1;flex-shrink:0" title="Șterge">✕</button>';
    list.appendChild(row);
    row.querySelector('input').focus();
}
</script>
    </div>
</form>

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
            <input type="file" name="logo_file" accept=".jpg,.jpeg,.png,.webp,.svg,.heic,.heif" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
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

<?php /* ======================================================= TAB: PAGINI */ ?>
<?php elseif ($tab === 'pagini'): ?>
<h1 class="wp-page-title">Pagini</h1>
<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Pagina a fost salvată.</div>
<?php endif; ?>

<?php
$page_meta = [
    'sustine'     => ['title' => 'Susține un curs',       'url' => '/sustine-un-curs'],
    'gazduieste'  => ['title' => 'Găzduiește un curs',    'url' => '/gazduieste-un-curs'],
    'parteneriat' => ['title' => 'Propune un parteneriat','url' => '/propune-un-parteneriat'],
];
$editing_page = $_GET['page'] ?? '';
if ($editing_page && isset($page_meta[$editing_page])):
    $pg = $settings['pages'][$editing_page] ?? [];
?>
<div class="card">
    <div class="card-title">
        Editează: <?= h($page_meta[$editing_page]['title']) ?>
        <a href="<?= h($page_meta[$editing_page]['url']) ?>" target="_blank" style="font-size:12px;font-weight:400;color:var(--accent);margin-left:10px">Vizualizează ↗</a>
    </div>
    <form method="post" action="/admin/?tab=pagini&page=<?= h($editing_page) ?>">
        <input type="hidden" name="action" value="save_page">
        <input type="hidden" name="page_key" value="<?= h($editing_page) ?>">
        <div class="form-group">
            <label>Titlu pagină</label>
            <input type="text" name="title" value="<?= h($pg['title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Subtitlu</label>
            <input type="text" name="subtitle" value="<?= h($pg['subtitle'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Descriere / Intro text</label>
            <textarea name="description" rows="4"><?= h($pg['description'] ?? '') ?></textarea>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Salvează</button>
            <a href="/admin/?tab=pagini" class="btn btn-secondary">Înapoi</a>
        </div>
    </form>
</div>

<?php else: ?>

<!-- Pages list -->
<div class="card">
    <div class="card-title">Pagini disponibile</div>
    <table class="wp-table">
        <thead><tr><th>Pagină</th><th>URL</th><th>Acțiuni</th></tr></thead>
        <tbody>
            <?php foreach ($page_meta as $key => $pm): ?>
            <tr>
                <td style="font-weight:600"><?= h($pm['title']) ?></td>
                <td><a href="<?= h($pm['url']) ?>" target="_blank" style="color:var(--accent)"><?= h($pm['url']) ?></a></td>
                <td>
                    <a href="/admin/?tab=pagini&page=<?= h($key) ?>" class="btn btn-sm btn-secondary">Editează</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php /* ======================================================= TAB: KIT */ ?>
<?php elseif ($tab === 'kit'): ?>
<h1 class="wp-page-title">Kit (Email Marketing)</h1>
<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Setările Kit au fost salvate.</div>
<?php endif; ?>

<form method="post" action="/admin/?tab=kit">
    <input type="hidden" name="action" value="save_kit">
    <div class="card">
        <div class="card-title">Conexiune Kit.com</div>
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

<?php /* ======================================================= TAB: MESAJE */ ?>
<?php elseif ($tab === 'mesaje'): ?>
<h1 class="wp-page-title">Mesaje</h1>
<?php if (isset($_GET['deleted'])): ?>
<div class="notice notice-success">Mesajul a fost șters.</div>
<?php endif; ?>
<style>
.msg-tabs { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.msg-tab { padding:8px 18px; border-radius:20px; border:1px solid var(--border); background:transparent; color:var(--text-muted); font-size:13px; font-weight:500; cursor:pointer; transition:.15s; }
.msg-tab:hover { background:rgba(255,255,255,.06); color:var(--text); }
.msg-tab.active { background:var(--sidebar-active-bg); border-color:transparent; color:#fff; }
.msg-tab .msg-count { display:inline-block; background:rgba(255,255,255,.18); border-radius:10px; padding:1px 7px; font-size:11px; margin-left:5px; }
.msg-panel { display:none; }
.msg-panel.active { display:block; }
.msg-cards { display:grid; grid-template-columns:1fr; gap:12px; }
.msg-card { background:var(--surface, #fff); border:1px solid var(--border); border-radius:10px; cursor:pointer; overflow:hidden; transition:background .15s; }
.msg-card:hover { background:rgba(0,0,0,.02); }
.msg-delete-btn { background:transparent; border:1px solid var(--danger, #e74c3c); color:var(--danger, #e74c3c); border-radius:6px; padding:4px 10px; font-size:11px; cursor:pointer; transition:.15s; }
.msg-delete-btn:hover { background:var(--danger, #e74c3c); color:#fff; }
.msg-card-head { padding:14px 16px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
.msg-card-name { font-size:14px; font-weight:600; color:var(--text); }
.msg-card-date { font-size:11px; color:var(--text-muted); white-space:nowrap; }
.msg-card-preview { padding:0 16px 14px; font-size:12px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.msg-detail { display:none; padding:16px; border-top:1px solid var(--border); background:var(--bg-surface); }
.msg-detail.open { display:block; }
.msg-detail-row { display:flex; gap:10px; font-size:13px; line-height:1.6; }
.msg-detail-row + .msg-detail-row { margin-top:4px; }
.msg-detail-lbl { color:var(--text-muted); min-width:110px; flex-shrink:0; }
.msg-detail-val { color:var(--text); }
.msg-detail-actions { margin-top:12px; }
.msg-empty { color:var(--text-muted); font-size:13px; padding:12px 0; }
</style>

<?php
$log_file = dirname(SETTINGS_FILE) . '/messages.log';
$categories = [
    'contact'     => ['label' => 'Contact',           'icon' => '💬'],
    'sustine'     => ['label' => 'Susține un curs',   'icon' => '🎤'],
    'gazduieste'  => ['label' => 'Locații',           'icon' => '📍'],
    'parteneriat' => ['label' => 'Parteneriate',      'icon' => '🤝'],
];
$grouped = array_fill_keys(array_keys($categories), []);

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
        foreach ($lines as $l) {
            $sep = strpos($l, ':');
            if ($sep !== false) $fields[trim(substr($l,0,$sep))] = trim(substr($l,$sep+1));
        }
        $grouped[$type][] = ['date' => $date, 'fields' => $fields];
    }
}
?>

<div class="msg-tabs">
<?php foreach ($categories as $key => $cat): $cnt = count($grouped[$key]); ?>
    <button class="msg-tab <?= $key === 'contact' ? 'active' : '' ?>" onclick="showMsgTab('<?= $key ?>')">
        <?= $cat['icon'] ?> <?= $cat['label'] ?><?php if ($cnt): ?><span class="msg-count"><?= $cnt ?></span><?php endif; ?>
    </button>
<?php endforeach; ?>
</div>

<?php foreach ($categories as $key => $cat): ?>
<div class="msg-panel <?= $key === 'contact' ? 'active' : '' ?>" id="msg-panel-<?= $key ?>">
<?php if (empty($grouped[$key])): ?>
    <div class="card"><p class="msg-empty">Niciun mesaj în această categorie.</p></div>
<?php else: ?>
    <div class="msg-cards">
    <?php foreach ($grouped[$key] as $i => $msg):
        $name    = $msg['fields']['Nume'] ?? $msg['fields']['nume'] ?? $msg['fields']['Organizație'] ?? $msg['fields']['organizatie'] ?? '—';
        $email   = $msg['fields']['Email'] ?? $msg['fields']['email'] ?? '';
        $preview = '';
        foreach ($msg['fields'] as $k => $v) { if (strtolower($k) !== 'email' && strtolower($k) !== 'nume') { $preview = $v; break; } }
        $uid = $key . '_' . $i;
    ?>
    <div class="msg-card" onclick="toggleMsg('<?= $uid ?>')">
        <div class="msg-card-head">
            <span class="msg-card-name"><?= h($name) ?></span>
            <span class="msg-card-date"><?= h($msg['date']) ?></span>
        </div>
        <?php if ($preview): ?><div class="msg-card-preview"><?= h($preview) ?></div><?php endif; ?>
        <div class="msg-detail" id="msg-<?= $uid ?>">
            <?php foreach ($msg['fields'] as $lbl => $val):
                $lbl_lc = strtolower($lbl);
                if ($lbl_lc === 'trimis de pe' || $lbl_lc === 'data') continue;
            ?>
            <div class="msg-detail-row">
                <span class="msg-detail-lbl"><?= h($lbl) ?></span>
                <span class="msg-detail-val"><?= h($val) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="msg-detail-actions">
                <button type="button" class="msg-delete-btn" onclick="deleteMsg(this,'<?= h($key) ?>',<?= $i ?>)">Șterge</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
<?php endforeach; ?>

<script>
function showMsgTab(key) {
    document.querySelectorAll('.msg-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.msg-panel').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('msg-panel-' + key).classList.add('active');
}
function toggleMsg(uid) {
    const el = document.getElementById('msg-' + uid);
    el.classList.toggle('open');
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
        .then(d => { if (d.ok) card.remove(); });
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
                <th style="width:160px">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vote_courses as $vc): ?>
            <tr>
                <td style="font-size:1.4rem;text-align:center"><?= h($vc['emoji'] ?? '📚') ?></td>
                <td style="font-weight:600">
                    <?= h($vc['name'] ?? '') ?>
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

<?php /* ======================================================= TAB: SECURITATE */ ?>
<?php elseif ($tab === 'securitate'): ?>

<h1 class="wp-page-title">Securitate</h1>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Parola a fost schimbată cu succes.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="notice notice-error">Parolele nu coincid sau sunt prea scurte (minim 6 caractere).</div>
<?php endif; ?>
<?php if (isset($_GET['imported'])): ?>
<div class="notice notice-success">Import reușit! <?= (int)$_GET['imported'] ?> imagini descărcate.</div>
<?php endif; ?>
<?php if (isset($_GET['import_error'])): ?>
<div class="notice notice-error">Eroare la import. Verifică fișierul.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title">Export / Import setări</div>
    <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px">Folosește Export pe serverul vechi și Import pe cel nou pentru a transfera toate setările și imaginile.</p>
    <form method="post" action="/admin/?tab=securitate" style="margin-bottom:16px">
        <input type="hidden" name="action" value="export_settings">
        <button type="submit" class="btn btn-primary">⬇ Export settings.json</button>
    </form>
    <form method="post" action="/admin/?tab=securitate" enctype="multipart/form-data">
        <input type="hidden" name="action" value="import_settings">
        <div class="form-group">
            <label>Fișier settings.json exportat</label>
            <input type="file" name="settings_file" accept=".json" required>
        </div>
        <div class="form-group">
            <label>Domeniu sursă (de unde se descarcă imaginile)</label>
            <input type="text" name="source_domain" value="https://robotache.ro" placeholder="https://robotache.ro">
        </div>
        <button type="submit" class="btn btn-primary">⬆ Importă setări + imagini</button>
    </form>
</div>

<div class="card">
    <div class="card-title">Schimbă parola de admin</div>
    <form method="post" action="/admin/?tab=securitate" style="max-width:400px">
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

<div class="card">
    <div class="card-title">Cheie de sesiune</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">Regenerarea cheii invalidează toate sesiunile active (vei fi deconectat și va trebui să te reloghezi).</p>
    <form method="post" action="/admin/?tab=securitate" onsubmit="return confirm('Ești sigur? Vei fi deconectat.')">
        <input type="hidden" name="action" value="regenerate_secret">
        <button type="submit" class="btn btn-danger">Regenerează cheia de sesiune</button>
    </form>
</div>

<div class="card">
    <div class="card-title">Webhook secret (GitHub)</div>
    <?php if (isset($_GET['webhook_saved'])): ?>
    <div class="notice notice-success" style="margin-bottom:12px">Secret regenerat. Actualizează-l și în setările webhook-ului de pe GitHub.</div>
    <?php endif; ?>
    <?php $wh_secret = load_settings()['webhook_secret'] ?? ''; ?>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:10px">
        Secretul curent (copiază-l în GitHub → repo → Settings → Webhooks):
    </p>
    <code style="display:block;background:#f6f7f7;border:1px solid var(--border);padding:10px 12px;border-radius:4px;font-size:13px;word-break:break-all;margin-bottom:14px;user-select:all"><?= h($wh_secret) ?></code>
    <form method="post" action="/admin/?tab=securitate" onsubmit="return confirm('Vei trebui să actualizezi și webhook-ul pe GitHub cu noul secret.')">
        <input type="hidden" name="action" value="regenerate_webhook_secret">
        <button type="submit" class="btn btn-danger">Regenerează webhook secret</button>
    </form>
</div>

<?php /* ======================================================= TAB: CONFIG */ ?>
<?php elseif ($tab === 'config'): ?>
<h1 class="wp-page-title">Setări</h1>

<?php if (isset($_GET['saved'])): ?>
<div class="notice notice-success">Setările au fost salvate.</div>
<?php endif; ?>

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

<?php endif; ?>

    </main>
</div><!-- /wp-layout -->

<script>
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
</body>
</html>
