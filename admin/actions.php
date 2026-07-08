<?php
// POST handlers — included from admin/index.php when authenticated.
// Expects: $action (string)
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
        clp_delete_statistici_course_by_external_id($id);
        $courses = clp_load_courses_for_admin();
        $courses = array_filter($courses, fn($c) => ($c['id'] ?? '') !== $id);
        clp_save_courses($courses);
        header('Location: /admin/?tab=cursuri');
        exit;
    }

    // ── Toggle course active (doar cu link LiveTickets apare pe site)
    if ($action === 'toggle_course') {
        $id = $_POST['id'] ?? '';
        $courses = clp_load_courses_for_admin();
        foreach ($courses as &$c) {
            if (($c['id'] ?? '') === $id) {
                if (trim($c['livetickets_url'] ?? '') === '') {
                    $c['active'] = false;
                } else {
                    $c['active'] = !($c['active'] ?? false);
                }
                break;
            }
        }
        unset($c);
        clp_save_courses($courses);
        header('Location: /admin/?tab=cursuri');
        exit;
    }

    // ── Save course
    if ($action === 'save_course') {
        require_once dirname(__DIR__) . '/lib/course_image.php';

        $id = trim($_POST['course_id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $date_raw = trim($_POST['date_raw'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $speaker_id = trim($_POST['speaker_id'] ?? '');
        $livetickets_url = trim($_POST['livetickets_url'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $location = trim($_POST['location'] ?? '');

        $err = '';
        if ($title === '') {
            $err = 'Completează numele cursului.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_raw) || !strtotime($date_raw)) {
            $err = 'Alege o dată validă.';
        } elseif (!in_array($time, clp_allowed_course_times(), true)) {
            $err = 'Alege ora din listă (17:00, 17:30, 18:00, 18:30 sau 19:00).';
        } else {
            $speaker = clp_find_speaker_by_id($speaker_id);
            if (!$speaker) {
                $err = 'Alege un speaker din listă.';
            }
        }

        if ($err !== '') {
            header('Location: /admin/?tab=cursuri&course_error=' . urlencode($err));
            exit;
        }

        if ($livetickets_url !== '' && $image_url === '') {
            $lt = clp_fetch_course_meta_by_url($livetickets_url);
            if (!empty($lt['success']) && !empty($lt['data']['image_url'])) {
                $image_url = $lt['data']['image_url'];
                if ($location === '' && !empty($lt['data']['location'])) {
                    $location = $lt['data']['location'];
                }
            }
        }

        $courses = clp_load_courses_for_admin();
        $entry = [
            'id'              => $id ?: uniqid('c', true),
            'title'           => $title,
            'date_display'    => clp_date_display_from_raw($date_raw),
            'date_raw'        => $date_raw,
            'time'            => $time,
            'speaker_id'      => $speaker_id,
            'speaker_name'    => trim($speaker['name'] ?? ''),
            'location'        => $location,
            'livetickets_url' => $livetickets_url,
            'image_url'       => $image_url,
            'active'          => $livetickets_url !== '',
            'admin_stats'     => true,
        ];
        if ($id) {
            $found = false;
            foreach ($courses as &$c) {
                if (($c['id'] ?? '') === $id) {
                    foreach (['discount_percent', 'discount_ends_at'] as $k) {
                        if (isset($c[$k])) {
                            $entry[$k] = $c[$k];
                        }
                    }
                    $c = $entry;
                    $found = true;
                    break;
                }
            }
            unset($c);
            if (!$found) {
                $courses[] = $entry;
            }
        } else {
            $courses[] = $entry;
        }
        clp_normalize_course($entry);
        foreach ($courses as &$c) {
            if (($c['id'] ?? '') === $entry['id']) {
                $c = $entry;
                break;
            }
        }
        unset($c);
        clp_save_courses($courses);
        clp_sync_course_to_statistici_db($entry);

        // If a course now has a LiveTickets link, queue Andy's "publish on partners" task.
        require_once dirname(__DIR__) . '/lib/recurring.php';
        clp_process_course_publish_tasks();

        [$redirect_year, $redirect_month] = array_pad(explode('-', $entry['date_raw']), 2, '');
        $redirect = '/admin/?tab=cursuri&year=' . (int)$redirect_year . '&month=' . (int)$redirect_month . '&ctab=cursuri&saved=1&edit=' . urlencode($entry['id']);
        header('Location: ' . $redirect);
        exit;
    }

    // ── Save / clear discount for a course
    if ($action === 'save_discount') {
        $id = trim($_POST['id'] ?? '');
        $clear = !empty($_POST['clear']);
        $courses = clp_load_courses_for_admin();
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
        clp_save_courses($courses);
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
                    if ($w > 2560) {
                        $img2 = imagescale($img, 2560, -1);
                        if ($img2) { imagedestroy($img); $img = $img2; }
                    }
                    if (imagewebp($img, $dest, 88)) {
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
            // Curăță referințele orfane din hero/galerie ca să nu rămână slide-uri albe
            $del_url  = UPLOADS_URL . '/' . $filename;
            $settings = load_settings();
            $settings['hero_images']      = array_values(array_filter($settings['hero_images'] ?? [], fn($u) => $u !== $del_url));
            $settings['gallery_featured'] = array_values(array_filter($settings['gallery_featured'] ?? [], fn($u) => $u !== $del_url));
            save_settings($settings);
        }
        header('Location: /admin/?tab=imagini');
        exit;
    }

    // ── Save hero images + gallery featured
    if ($action === 'save_hero_images') {
        $settings = load_settings();
        $settings['hero_images']      = array_values(array_filter(array_map('trim', $_POST['hero_images'] ?? [])));
        $settings['gallery_featured'] = array_values(array_filter(array_map('trim', $_POST['gallery_featured'] ?? [])));
        // Poziționare hero per-imagine (x/y/zoom), doar pentru imaginile hero curente
        $transforms = json_decode($_POST['hero_transforms'] ?? '[]', true);
        $clean_tr = [];
        if (is_array($transforms)) {
            foreach ($settings['hero_images'] as $hu) {
                $t = $transforms[$hu] ?? null;
                if (!is_array($t)) continue;
                $x = max(0, min(100, (float)($t['x'] ?? 50)));
                $y = max(0, min(100, (float)($t['y'] ?? 50)));
                $z = max(100, min(220, (float)($t['zoom'] ?? 100)));
                if ($x != 50 || $y != 50 || $z != 100) { // stochează doar ce diferă de default
                    $clean_tr[$hu] = ['x' => $x, 'y' => $y, 'zoom' => $z];
                }
            }
        }
        $settings['hero_transforms'] = $clean_tr;
        $ok = save_settings($settings);
        header('Location: /admin/?tab=imagini&' . ($ok ? 'saved=1' : 'err=save'));
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

    // ── Save templates (Eric + Andy)
    if ($action === 'save_templates') {
        $settings = load_settings();
        $labels = $_POST['tpl_label'] ?? [];
        $texts  = $_POST['tpl_text']  ?? [];
        $icons  = $_POST['tpl_icon']  ?? [];
        $tpls   = [];
        for ($i = 0; $i < count($labels); $i++) {
            $lbl = trim($labels[$i] ?? '');
            $txt = trim($texts[$i]  ?? '');
            if ($lbl && $txt) {
                $tpls[] = ['icon' => trim($icons[$i] ?? '') ?: '📋', 'label' => $lbl, 'text' => $txt];
            }
        }
        $settings['templates'] = $tpls;
        save_settings($settings);
        header('Location: /admin/?tab=templates&saved=1');
        exit;
    }

    // ── Recurring tasks (Owner only) ──────────────────────────────────────────
    if (in_array($action, ['add_recurring', 'save_recurring', 'delete_recurring', 'save_recurring_system'], true) && is_owner()) {
        require_once dirname(__DIR__) . '/lib/recurring.php';
        $items = clp_load_recurring();

        if ($action === 'add_recurring') {
            $items[] = ['id' => clp_recurring_new_id(), 'type' => 'monthly', 'title' => 'Task nou', 'assigned_to' => 'eric6', 'days' => []];
        }

        if ($action === 'save_recurring') {
            $id       = $_POST['id'] ?? '';
            $title    = trim($_POST['title'] ?? '');
            $assigned = $_POST['assigned_to'] ?? 'eric6';
            $valid    = array_column(load_users(), 'username');
            if (!in_array($assigned, $valid, true)) $assigned = 'eric6';
            $days = array_values(array_unique(array_filter(
                array_map('intval', (array)($_POST['days'] ?? [])),
                fn($d) => $d >= 1 && $d <= 31
            )));
            sort($days);
            foreach ($items as &$t) {
                if (($t['id'] ?? '') === $id && ($t['type'] ?? '') === 'monthly') {
                    if ($title !== '') $t['title'] = $title;
                    $t['assigned_to'] = $assigned;
                    $t['days'] = $days;
                    break;
                }
            }
            unset($t);
        }

        if ($action === 'delete_recurring') {
            $id = $_POST['id'] ?? '';
            $items = array_values(array_filter($items, fn($t) => !(($t['id'] ?? '') === $id && ($t['type'] ?? '') === 'monthly')));
        }

        if ($action === 'save_recurring_system') {
            $titles = (array)($_POST['sys_title'] ?? []);
            foreach ($items as &$t) {
                if (($t['type'] ?? '') === 'system' && isset($titles[$t['id'] ?? ''])) {
                    $nt = trim((string)$titles[$t['id']]);
                    if ($nt !== '') $t['title'] = $nt;
                }
            }
            unset($t);
        }

        $_rec_ok = clp_save_recurring($items);
        $_rec_writable = is_writable(dirname(RECURRING_FILE)) && (!is_file(RECURRING_FILE) || is_writable(RECURRING_FILE));
        header('Location: /admin/?tab=config&rec=' . ($_rec_ok ? 'ok' : ($_rec_writable ? 'fail' : 'perm')) . '#rec');
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

    // ── Save Brevo settings
    if ($action === 'save_brevo') {
        $settings = load_settings();
        $settings['brevo_api_key'] = trim($_POST['brevo_api_key'] ?? '');
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
        $data_dir = dirname(clp_settings_file());
        $export_settings = file_exists(clp_settings_file()) ? json_decode(file_get_contents(clp_settings_file()), true) : [];
        // Strip secrets from export
        foreach (['admin_password','auth_secret','webhook_secret','sync_token'] as $k) {
            unset($export_settings[$k]);
        }
        $bundle = [
            'settings'     => $export_settings,
            'courses'      => file_exists(COURSES_FILE)      ? json_decode(file_get_contents(COURSES_FILE), true)      : [],
            'vote_courses' => file_exists(clp_vote_courses_file()) ? json_decode(file_get_contents(clp_vote_courses_file()), true) : [],
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
                $data_dir = dirname(clp_settings_file());
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
                    file_put_contents(clp_vote_courses_file(), json_encode(array_values($bundle['vote_courses']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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

    // ── Save design (colors)
    if ($action === 'save_design') {
        $settings = load_settings();
        $color_fields = ['color_bg','color_accent','color_text','color_text_muted','color_surface','color_btn_hover','color_banner'];
        foreach ($color_fields as $f) {
            $val = trim($_POST[$f] ?? '');
            if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) $settings[$f] = $val;
        }
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
            'status'  => in_array($_POST['sp_status'] ?? '', ['RECURENT','MID','NOPE','CONTACTAT','URMEAZĂ']) ? $_POST['sp_status'] : 'MID',
            'notes'   => trim($_POST['sp_notes']   ?? ''),
        ];
        // preserve existing meet data and merge new meet fields
        $meet_fields = ['auzit','ocupatie','pasiune','teme','dinamica','experienta','contract','curiozitati','program'];
        $meet = [];
        foreach ($meet_fields as $f) $meet[$f] = trim($_POST['meet_' . $f] ?? '');
        if (array_filter($meet)) $entry['meet'] = $meet;
        if ($id) {
            $found = false;
            foreach ($items as &$it) {
                if (($it['id'] ?? '') === $id) {
                    if (empty(array_filter($meet)) && !empty($it['meet'])) $entry['meet'] = $it['meet'];
                    $it = $entry; $found = true; break;
                }
            }
            unset($it);
            if (!$found) {
                $idx = clp_find_speaker_index_by_contact($items, $entry['email'], $entry['phone']);
                if ($idx >= 0) {
                    $entry['id'] = $items[$idx]['id'] ?? $entry['id'];
                    if (empty(array_filter($meet)) && !empty($items[$idx]['meet'])) $entry['meet'] = $items[$idx]['meet'];
                    $items[$idx] = clp_merge_speaker_entries($items[$idx], $entry);
                } else {
                    $items[] = $entry;
                }
            }
        } else {
            $idx = clp_find_speaker_index_by_contact($items, $entry['email'], $entry['phone']);
            if ($idx >= 0) {
                $entry['id'] = $items[$idx]['id'] ?? $entry['id'];
                if (empty(array_filter($meet)) && !empty($items[$idx]['meet'])) $entry['meet'] = $items[$idx]['meet'];
                $items[$idx] = clp_merge_speaker_entries($items[$idx], $entry);
            } else {
                $items[] = $entry;
            }
        }
        save_speakers($items);
        header('Location: /admin/?tab=speakeri&edit=' . urlencode($entry['id']) . '&saved=1');
        exit;
    }

    // ── Quick status change
    if ($action === 'save_speaker_status') {
        $id     = trim($_POST['id'] ?? '');
        $status = trim($_POST['status'] ?? '');
        if ($id && in_array($status, ['RECURENT','MID','NOPE','CONTACTAT','URMEAZĂ'])) {
            $items = load_speakers();
            foreach ($items as &$it) {
                if (($it['id'] ?? '') === $id) { $it['status'] = $status; break; }
            }
            unset($it);
            save_speakers($items);
        }
        header('Location: /admin/?tab=speakeri');
        exit;
    }

    // ── Save meet notes for speaker
    if ($action === 'save_meet') {
        $id    = trim($_POST['meet_speaker_id'] ?? '');
        $items = load_speakers();
        $fields = ['auzit','ocupatie','pasiune','teme','dinamica','experienta','contract','curiozitati','program'];
        foreach ($items as &$it) {
            if (($it['id'] ?? '') !== $id) continue;
            $meet = [];
            foreach ($fields as $f) $meet[$f] = trim($_POST['meet_' . $f] ?? '');
            $it['meet'] = $meet;
            break;
        }
        unset($it);
        save_speakers($items);
        header('Location: /admin/?tab=speakeri');
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

    // ── Save course ideas (cursuri posibile)
    if ($action === 'save_course_ideas') {
        $emojis = (array)($_POST['cat_emoji'] ?? []);
        $titles = (array)($_POST['cat_title'] ?? []);
        $topics = (array)($_POST['cat_topics'] ?? []);
        $cats = [];
        foreach ($titles as $i => $title) {
            $title = trim((string)$title);
            if ($title === '') continue;
            $lines = array_values(array_filter(
                array_map('trim', explode("\n", (string)($topics[$i] ?? ''))),
                fn($l) => $l !== ''
            ));
            $cats[] = [
                'emoji'  => trim((string)($emojis[$i] ?? '')),
                'title'  => $title,
                'topics' => $lines,
            ];
        }
        // array_merge peste datele existente ca să nu piardă flag-urile de migrație
        clp_save_course_ideas(array_merge(clp_load_course_ideas(), [
            'intro'      => trim($_POST['ideas_intro'] ?? ''),
            'categories' => $cats,
        ]));
        header('Location: /admin/?tab=cursuri-posibile&saved=1');
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
        $log_file = clp_messages_log_file();
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

