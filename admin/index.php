<?php
define('ADMIN_PASSWORD', 'clp2026admin');
define('AUTH_SECRET',    'clp-auth-xk9p-2026-secret');
define('COURSES_FILE',   dirname(__DIR__) . '/data/courses.json');
define('SETTINGS_FILE',  dirname(__DIR__) . '/data/settings.json');
define('UPLOADS_DIR',    dirname(__DIR__) . '/assets/images/uploads');
define('UPLOADS_URL',    '/assets/images/uploads');
define('PUBLIC_HTML',    dirname(__DIR__));

// ── Cookie-based auth ─────────────────────────────────────────────────────────
function is_authenticated(): bool {
    $cookie = $_COOKIE['clp_auth'] ?? '';
    if (!$cookie) return false;
    $expected = hash_hmac('sha256', 'clp_admin_ok', AUTH_SECRET);
    return hash_equals($expected, $cookie);
}
function set_auth_cookie(): void {
    $token = hash_hmac('sha256', 'clp_admin_ok', AUTH_SECRET);
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

if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === ADMIN_PASSWORD) {
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

function default_settings(): array {
    return [
        'announcement'      => '🎉 Peste 1.000 de participanți au descoperit că educația are un gust mai bun la un pahar. Tu ești următorul?',
        'hero_title'        => 'Cursuri ținute de experți<br><em>la un pahar în oraș.</em>',
        'hero_btn'          => 'Vezi următoarele cursuri',
        'courses_title'     => 'Următoarele cursuri',
        'newsletter_title'  => 'Fii primul care află când au loc evenimentele Cursuri la Pahar',
        'newsletter_desc'   => 'Vei primi în exclusivitate data și tema viitoarelor evenimente Cursuri la Pahar, cu 2 săptămâni înainte ca acestea să aibă loc.',
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
        'kit_api_key'       => 'kit_3ad1bb636169002be3359bd1048e0204',
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
    ];
}
function load_settings(): array {
    if (!file_exists(SETTINGS_FILE)) return default_settings();
    $data = json_decode(file_get_contents(SETTINGS_FILE), true) ?: [];
    return array_merge(default_settings(), $data);
}
function save_settings(array $settings): void {
    $dir = dirname(SETTINGS_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
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
            $allowed = ['jpg','jpeg','png','webp','gif','avif'];
            if (in_array($ext, $allowed)) {
                $new_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
                $dest = UPLOADS_DIR . '/' . $new_name;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $upload_ok = 'Imaginea a fost încărcată: ' . h($new_name);
                } else {
                    $upload_error = 'Eroare la salvarea fișierului.';
                }
            } else {
                $upload_error = 'Format neacceptat. Folosește JPG, PNG, WEBP sau GIF.';
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

    // ── Save aspect (navbar brand + links)
    if ($action === 'save_aspect') {
        $settings = load_settings();
        $settings['nav_brand_text'] = trim($_POST['nav_brand_text'] ?? 'Cursuri la Pahar');
        $raw_links = explode("\n", $_POST['nav_links_raw'] ?? '');
        $nav_links = [];
        foreach ($raw_links as $line) {
            $line = trim($line);
            if (!$line) continue;
            $parts = explode('|', $line, 2);
            if (count($parts) === 2) {
                $nav_links[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
            }
        }
        if ($nav_links) $settings['nav_links'] = $nav_links;
        save_settings($settings);
        header('Location: /admin/?tab=aspect&saved=1');
        exit;
    }

    // ── Upload logo
    if ($action === 'upload_logo') {
        $file = $_FILES['logo_file'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','svg'])) {
                $new_name = 'logo.' . $ext;
                $dest = PUBLIC_HTML . '/assets/images/' . $new_name;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $settings = load_settings();
                    $settings['logo_path'] = '/assets/images/' . $new_name;
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
}

// ── Load data for display ─────────────────────────────────────────────────────
$courses  = [];
$settings = load_settings();
$tab      = $_GET['tab'] ?? 'cursuri';
if (!in_array($tab, ['cursuri','imagini','setari','aspect','pagini','kit','mesaje'])) $tab = 'cursuri';

if (is_authenticated()) {
    $courses = load_courses();
    usort($courses, fn($a, $b) => strcmp($a['date_raw'] ?? '', $b['date_raw'] ?? ''));
}

// Collect images for imagini tab
function get_all_images(): array {
    $imgs = [];
    // Base images dir
    $base = PUBLIC_HTML . '/assets/images/';
    if (is_dir($base)) {
        foreach (scandir($base) as $f) {
            if ($f === '.' || $f === '..') continue;
            if (!is_file($base . $f)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif','avif'])) {
                $imgs[] = ['url' => '/assets/images/' . $f, 'name' => $f, 'deletable' => false];
            }
        }
    }
    // Uploads dir
    if (is_dir(UPLOADS_DIR)) {
        foreach (scandir(UPLOADS_DIR) as $f) {
            if ($f === '.' || $f === '..') continue;
            if (!is_file(UPLOADS_DIR . '/' . $f)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif','avif'])) {
                $imgs[] = ['url' => UPLOADS_URL . '/' . $f, 'name' => $f, 'deletable' => true];
            }
        }
    }
    return $imgs;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Cursuri la Pahar</title>
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
.wp-sidebar { width: 200px; background: var(--sidebar-bg); flex-shrink: 0; padding-top: 8px; }
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

/* ── Main content ── */
.wp-main { flex: 1; padding: 20px 24px; min-width: 0; }
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
    padding: 9px 12px 9px 44px;
    border: 1px solid var(--border);
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    background: #fff;
    color: #1d2327;
    cursor: pointer;
}
.clr-field button {
    width: 32px;
    height: 32px;
    border-radius: 3px;
    left: 5px;
    top: 50%;
    transform: translateY(-50%);
}
/* Make the popup bigger */
.clr-picker { width: 280px !important; }
.clr-gradient { height: 200px !important; }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdbassit/coloris@0.23.0/dist/coloris.min.css">
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
    <a href="/admin/" class="brand">Cursuri la Pahar <span>— Admin</span></a>
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
                <span class="nav-icon">⚙️</span> Setări
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
                <span class="nav-icon">💬</span> Mesaje
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
        <form method="post" action="/admin/?tab=imagini" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_image">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="file" name="image_file" accept="image/*" style="border:1px solid var(--border);padding:6px 10px;border-radius:4px;font-size:13px;background:#fff">
                <button type="submit" class="btn btn-primary">Încarcă</button>
            </div>
            <p class="form-desc">Formate acceptate: JPG, PNG, WEBP, GIF. Imaginile uploadate sunt salvate în <code>/assets/images/uploads/</code>.</p>
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

    <form method="post" action="/admin/?tab=setari">
        <input type="hidden" name="action" value="save_settings">

        <div class="card">
            <div class="card-title">Banner &amp; Hero</div>

            <div class="form-group">
                <label for="s_announcement">Anunț banner</label>
                <textarea id="s_announcement" name="announcement" rows="2"><?= h($settings['announcement']) ?></textarea>
                <p class="form-desc">Textul afișat în bannerul de anunț de sub hero.</p>
            </div>

            <div class="form-group">
                <label for="s_hero_title">Titlu hero</label>
                <textarea id="s_hero_title" name="hero_title" rows="3"><?= h($settings['hero_title']) ?></textarea>
                <p class="form-desc">Suportă HTML, ex: <code>&lt;em&gt;text italic&lt;/em&gt;</code>, <code>&lt;br&gt;</code>.</p>
            </div>

            <div class="form-group">
                <label for="s_hero_btn">Text buton hero</label>
                <input type="text" id="s_hero_btn" name="hero_btn" value="<?= h($settings['hero_btn']) ?>">
            </div>
        </div>

        <div class="card">
            <div class="card-title">Secțiuni</div>

            <div class="form-group">
                <label for="s_courses_title">Titlu secțiune Cursuri</label>
                <input type="text" id="s_courses_title" name="courses_title" value="<?= h($settings['courses_title']) ?>">
            </div>

            <div class="form-group">
                <label for="s_newsletter_title">Titlu secțiune Newsletter</label>
                <input type="text" id="s_newsletter_title" name="newsletter_title" value="<?= h($settings['newsletter_title']) ?>">
            </div>

            <div class="form-group">
                <label for="s_newsletter_desc">Descriere Newsletter</label>
                <textarea id="s_newsletter_desc" name="newsletter_desc" rows="3"><?= h($settings['newsletter_desc']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="s_collab_title">Titlu secțiune Colaborare</label>
                <input type="text" id="s_collab_title" name="collab_title" value="<?= h($settings['collab_title']) ?>">
            </div>

            <div class="form-group">
                <label for="s_collab_subtitle">Subtitlu secțiune Colaborare</label>
                <textarea id="s_collab_subtitle" name="collab_subtitle" rows="2"><?= h($settings['collab_subtitle']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="s_contact_title">Titlu secțiune Contact</label>
                <input type="text" id="s_contact_title" name="contact_title" value="<?= h($settings['contact_title']) ?>">
            </div>

            <div class="form-group">
                <label for="s_contact_subtitle">Subtitlu secțiune Contact</label>
                <textarea id="s_contact_subtitle" name="contact_subtitle" rows="2"><?= h($settings['contact_subtitle']) ?></textarea>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Cum funcționează – Pași</div>
            <?php foreach ($settings['steps'] as $i => $step): $n = $i + 1; ?>
            <div style="background:var(--sidebar-bg);border:1px solid var(--border);border-radius:4px;padding:14px 16px;margin-bottom:10px">
                <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">Pasul <?= $n ?></div>
                <div class="form-group">
                    <label>Titlu</label>
                    <input type="text" name="step_title[]" value="<?= h($step['title']) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Text</label>
                    <textarea name="step_text[]" rows="2"><?= h($step['text']) ?></textarea>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-title">FAQ – Întrebări frecvente</div>
            <div id="faq-editor">
                <?php foreach ($settings['faq_items'] as $i => $item): ?>
                <div class="faq-edit-item" style="background:var(--sidebar-bg);border:1px solid var(--border);border-radius:4px;padding:14px 16px;margin-bottom:10px;position:relative">
                    <button type="button" onclick="removeFaqItem(this)" title="Șterge" style="position:absolute;top:8px;right:10px;background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:15px;line-height:1;padding:2px 4px">✕</button>
                    <div class="form-group">
                        <label>Întrebare</label>
                        <input type="text" name="faq_q[]" value="<?= h($item['q']) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>Răspuns</label>
                        <textarea name="faq_a[]" rows="3"><?= h($item['a']) ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addFaqItem()" class="btn btn-secondary" style="margin-top:4px;font-size:13px">+ Adaugă întrebare</button>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-bottom:24px">Salvează setările</button>
    </form>
    <script>
    function addFaqItem() {
        const editor = document.getElementById('faq-editor');
        const div = document.createElement('div');
        div.className = 'faq-edit-item';
        div.style.cssText = 'background:var(--sidebar-bg);border:1px solid var(--border);border-radius:4px;padding:14px 16px;margin-bottom:10px;position:relative';
        div.innerHTML = '<button type="button" onclick="removeFaqItem(this)" title="Șterge" style="position:absolute;top:8px;right:10px;background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:15px;line-height:1;padding:2px 4px">✕</button>'
            + '<div class="form-group"><label>Întrebare</label><input type="text" name="faq_q[]" value=""></div>'
            + '<div class="form-group" style="margin-bottom:0"><label>Răspuns</label><textarea name="faq_a[]" rows="3"></textarea></div>';
        editor.appendChild(div);
        div.querySelector('input').focus();
    }
    function removeFaqItem(btn) { btn.closest('.faq-edit-item').remove(); }
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

<!-- Brand text + Nav links -->
<form method="post" action="/admin/?tab=aspect">
    <input type="hidden" name="action" value="save_aspect">
    <div class="card">
        <div class="card-title">Navbar</div>
        <div class="form-group">
            <label>Text brand (lângă logo)</label>
            <input type="text" name="nav_brand_text" value="<?= h($settings['nav_brand_text'] ?? 'Cursuri la Pahar') ?>">
        </div>
        <div class="form-group">
            <label>Linkuri meniu</label>
            <p class="form-desc" style="margin-bottom:8px">Un link per rând, format: <code>Nume|/url</code></p>
            <textarea name="nav_links_raw" rows="6" style="font-family:monospace"><?php
                foreach ($settings['nav_links'] ?? [] as $nl) {
                    echo h(($nl['label'] ?? '') . '|' . ($nl['url'] ?? '')) . "\n";
                }
            ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Salvează navbar</button>
    </div>
</form>

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
                    <?php foreach (['Nunito','Playfair Display','Montserrat','Raleway','Oswald','Lora','Poppins','DM Serif Display','Bebas Neue','Cormorant Garamond'] as $f): ?>
                    <option value="<?= h($f) ?>" <?= ($settings['font_heading'] ?? 'Nunito') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-desc">Font folosit pentru titluri și headinguri.</p>
            </div>
            <div class="form-group" style="margin:0">
                <label>Font text</label>
                <select name="font_body" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:4px;font-size:13px;background:#fff">
                    <?php foreach (['Inter','Roboto','Open Sans','Lato','Source Sans 3','DM Sans','Nunito','Mulish','Cabin','Karla'] as $f): ?>
                    <option value="<?= h($f) ?>" <?= ($settings['font_body'] ?? 'Inter') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-desc">Font folosit pentru textele din pagină.</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salvează design</button>
    </div>
</form>
<script src="https://cdn.jsdelivr.net/npm/@mdbassit/coloris@0.23.0/dist/coloris.min.js"></script>
<script>
Coloris({
    el: '[data-coloris]',
    format: 'hex',
    forceAlpha: false,
    focusInput: false,
    selectInput: true,
    clearButton: false,
    swatches: [
        '#0D0D0D','#161616','#1A1A1A','#ffffff',
        '#C9A84C','#b8922e','#FFB000','#d4a017',
        '#E8E4DC','#9CA3AF','#6B7280','#374151',
    ],
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

<?php
$log_file = dirname(SETTINGS_FILE) . '/messages.log';
if (!file_exists($log_file) || !filesize($log_file)):
?>
<div class="card">
    <p style="color:var(--text-muted);padding:8px 0">Nu există mesaje încă.</p>
</div>
<?php else:
    $raw    = file_get_contents($log_file);
    $blocks = preg_split('/(?=^===)/m', $raw);
    $blocks = array_values(array_filter(array_map('trim', $blocks)));
    $blocks = array_reverse($blocks);
    $type_labels = [
        'contact'     => '💬 Contact',
        'sustine'     => '🎤 Susține un curs',
        'gazduieste'  => '🏠 Găzduiește un curs',
        'parteneriat' => '🤝 Propune un parteneriat',
    ];
?>
<div class="card">
    <div class="card-title">Mesaje primite (<?= count($blocks) ?>)</div>
    <?php foreach ($blocks as $block):
        preg_match('/^===\s*(.*?)\s*\|\s*(\S+)\s*===/m', $block, $m);
        $date      = trim($m[1] ?? '');
        $type      = trim($m[2] ?? 'contact');
        $type_lbl  = $type_labels[$type] ?? ucfirst($type);
        $body      = trim(preg_replace('/^===.*===\n?/m', '', $block));
        $body      = trim(preg_replace('/\n---\nData:.*$/s', '', $body));
        $lines     = array_filter(explode("\n", $body));
        $email_val = '';
        foreach ($lines as $l) {
            if (stripos($l, 'email:') === 0) { $email_val = trim(substr($l, 6)); break; }
        }
    ?>
    <div style="border:1px solid var(--border);border-radius:6px;padding:16px 18px;margin-bottom:12px;background:var(--sidebar-bg)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;flex-wrap:wrap;gap:6px">
            <span style="font-weight:600;font-size:13px"><?= h($type_lbl) ?></span>
            <span style="font-size:12px;color:var(--text-muted)"><?= h($date) ?></span>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px">
        <?php foreach ($lines as $line):
            if (!$line) continue;
            $sep = strpos($line, ':');
            if ($sep === false): ?>
            <div style="font-size:13px;color:var(--text-muted)"><?= h($line) ?></div>
            <?php else:
                $lbl = trim(substr($line, 0, $sep));
                $val = trim(substr($line, $sep + 1));
            ?>
            <div style="display:flex;gap:8px;font-size:13px;line-height:1.5">
                <span style="color:var(--text-muted);min-width:120px;flex-shrink:0"><?= h($lbl) ?></span>
                <span style="color:var(--text)"><?= h($val) ?></span>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>
        <?php if ($email_val): ?>
        <div style="margin-top:10px">
            <a href="mailto:<?= h($email_val) ?>" class="btn btn-secondary" style="font-size:12px;padding:6px 14px">Răspunde ↗</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

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
