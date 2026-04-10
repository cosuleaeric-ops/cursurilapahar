<?php
/**
 * Cursuri la Pahar – functions.php
 */

// ── Theme Setup ──────────────────────────────────────────────────────────────
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style' ] );
    add_theme_support( 'customize-selective-refresh-widgets' );
} );

// ── Enqueue Assets ───────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function () {
    $css_file = get_template_directory() . '/assets/css/style.css';
    $ver = file_exists($css_file) ? filemtime($css_file) : '1.0.0';

    wp_enqueue_style(
        'clp-style',
        get_template_directory_uri() . '/assets/css/style.css',
        [],
        $ver
    );

    wp_enqueue_script(
        'clp-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $ver,
        true // load in footer
    );

    wp_localize_script( 'clp-main', 'clpAjax', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'clp_nonce' ),
    ] );
} );

// ── Custom Post Type: Curs ───────────────────────────────────────────────────
add_action( 'init', function () {
    register_post_type( 'curs', [
        'labels' => [
            'name'               => __( 'Cursuri', 'cursuri-la-pahar' ),
            'singular_name'      => __( 'Curs', 'cursuri-la-pahar' ),
            'add_new'            => __( 'Adaugă curs', 'cursuri-la-pahar' ),
            'add_new_item'       => __( 'Adaugă curs nou', 'cursuri-la-pahar' ),
            'edit_item'          => __( 'Editează cursul', 'cursuri-la-pahar' ),
            'new_item'           => __( 'Curs nou', 'cursuri-la-pahar' ),
            'view_item'          => __( 'Vezi cursul', 'cursuri-la-pahar' ),
            'search_items'       => __( 'Caută cursuri', 'cursuri-la-pahar' ),
            'not_found'          => __( 'Niciun curs găsit', 'cursuri-la-pahar' ),
            'not_found_in_trash' => __( 'Niciun curs în coș', 'cursuri-la-pahar' ),
        ],
        'public'        => true,
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-tickets-alt',
        'supports'      => [ 'title', 'thumbnail' ],
        'has_archive'   => false,
        'rewrite'       => [ 'slug' => 'cursuri' ],
    ] );
} );

// ── Meta Boxes for Curs ──────────────────────────────────────────────────────
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'clp_curs_details',
        'Detalii curs',
        'clp_curs_meta_box_html',
        'curs',
        'normal',
        'high'
    );
} );

function clp_curs_meta_box_html( $post ) {
    wp_nonce_field( 'clp_save_curs_meta', 'clp_curs_nonce' );

    $date_display    = get_post_meta( $post->ID, '_curs_date_display', true );
    $date_raw        = get_post_meta( $post->ID, '_curs_date_raw', true );
    $time            = get_post_meta( $post->ID, '_curs_time', true );
    $location        = get_post_meta( $post->ID, '_curs_location', true );
    $livetickets_url = get_post_meta( $post->ID, '_curs_livetickets_url', true );
    $active          = get_post_meta( $post->ID, '_curs_active', true );
    ?>
    <div style="background:#f0f7ff;border:1px solid #b3d4f5;border-radius:4px;padding:12px 16px;margin-bottom:16px;">
        <strong>⚡ Import automat din LiveTickets</strong>
        <div style="margin-top:8px;display:flex;gap:8px;align-items:center;">
            <input type="url" id="clp_lt_import_url" style="flex:1;" class="regular-text" placeholder="https://www.livetickets.ro/bilete/...">
            <button type="button" id="clp_lt_import_btn" class="button button-primary">Importă</button>
            <span id="clp_lt_status" style="color:#666;font-size:13px;"></span>
        </div>
    </div>
    <table class="form-table">
        <tr>
            <th><label for="clp_date_display">Dată afișată (ex: 15 Mai)</label></th>
            <td><input type="text" id="clp_date_display" name="clp_date_display" value="<?php echo esc_attr( $date_display ); ?>" class="regular-text" placeholder="15 Mai 2025"></td>
        </tr>
        <tr>
            <th><label for="clp_date_raw">Dată brută (YYYY-MM-DD)</label></th>
            <td><input type="date" id="clp_date_raw" name="clp_date_raw" value="<?php echo esc_attr( $date_raw ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="clp_time">Ora (ex: 19:00)</label></th>
            <td><input type="text" id="clp_time" name="clp_time" value="<?php echo esc_attr( $time ); ?>" class="regular-text" placeholder="19:00"></td>
        </tr>
        <tr>
            <th><label for="clp_location">Locație</label></th>
            <td><input type="text" id="clp_location" name="clp_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text" placeholder="Twisted Olives, București"></td>
        </tr>
        <tr>
            <th><label for="clp_livetickets_url">URL bilete (LiveTickets)</label></th>
            <td><input type="url" id="clp_livetickets_url" name="clp_livetickets_url" value="<?php echo esc_attr( $livetickets_url ); ?>" class="regular-text" placeholder="https://www.livetickets.ro/bilete/..."></td>
        </tr>
        <tr>
            <th><label for="clp_active">Activ (vizibil pe site)</label></th>
            <td><input type="checkbox" id="clp_active" name="clp_active" value="1" <?php checked( $active, '1' ); ?>></td>
        </tr>
    </table>
    <script>
    document.getElementById('clp_lt_import_btn').addEventListener('click', function() {
        var url = document.getElementById('clp_lt_import_url').value.trim();
        var status = document.getElementById('clp_lt_status');
        if (!url) { status.textContent = 'Introdu un URL.'; return; }
        status.textContent = 'Se încarcă...';
        var postId = document.getElementById('post_ID') ? document.getElementById('post_ID').value : 0;
        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=clp_fetch_livetickets&nonce=<?php echo wp_create_nonce("clp_lt_nonce"); ?>&url=' + encodeURIComponent(url) + '&post_id=' + encodeURIComponent(postId)
        })
        .then(r => r.json())
        .then(function(res) {
            if (!res.success) { status.textContent = '❌ ' + (res.data.message || 'Eroare'); return; }
            var d = res.data;
            document.getElementById('clp_date_display').value = d.date_display || '';
            document.getElementById('clp_date_raw').value     = d.date_raw || '';
            document.getElementById('clp_time').value         = d.time || '';
            document.getElementById('clp_location').value     = d.location || '';
            document.getElementById('clp_livetickets_url').value = url;
            var titleInput = document.getElementById('title');
            if (titleInput && !titleInput.value) titleInput.value = d.title || '';
            status.textContent = d.image_set ? '✅ Date + poză importate! Salvează postarea.' : '✅ Date importate! Eroare poză: ' + (d.image_error || '?');
        })
        .catch(function() { status.textContent = '❌ Eroare de rețea.'; });
    });
    </script>
    <?php
}

add_action( 'save_post', function ( $post_id ) {
    // Verify nonce
    if ( ! isset( $_POST['clp_curs_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['clp_curs_nonce'], 'clp_save_curs_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    if ( get_post_type( $post_id ) !== 'curs' ) return;

    $fields = [
        '_curs_date_display'    => 'clp_date_display',
        '_curs_date_raw'        => 'clp_date_raw',
        '_curs_time'            => 'clp_time',
        '_curs_location'        => 'clp_location',
        '_curs_livetickets_url' => 'clp_livetickets_url',
    ];

    foreach ( $fields as $meta_key => $field_name ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field_name ] ) );
        }
    }

    // Checkbox: active
    $active = isset( $_POST['clp_active'] ) ? '1' : '0';
    update_post_meta( $post_id, '_curs_active', $active );
} );

// ── AJAX: clp_fetch_livetickets ─────────────────────────────────────────────
add_action( 'wp_ajax_clp_fetch_livetickets', function () {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'clp_lt_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Nonce invalid.' ], 403 );
    }

    $input_url = sanitize_text_field( $_POST['url'] ?? '' );
    $slug = preg_replace( '#^https?://[^/]+/bilete/#', '', $input_url );
    $slug = trim( $slug, '/' );

    if ( empty( $slug ) ) {
        wp_send_json_error( [ 'message' => 'URL invalid.' ], 400 );
    }

    $api_url  = 'https://api.livetickets.ro/public/events/getbyurl?url=' . urlencode( $slug );
    $response = wp_remote_get( $api_url, [ 'timeout' => 15 ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => 'Nu s-a putut contacta LiveTickets.' ], 500 );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $data['name'] ) ) {
        wp_send_json_error( [ 'message' => 'Evenimentul nu a fost găsit.' ], 404 );
    }

    $start   = new DateTime( $data['start_date'] );
    $months  = [ 1=>'Ianuarie',2=>'Februarie',3=>'Martie',4=>'Aprilie',5=>'Mai',6=>'Iunie',
                 7=>'Iulie',8=>'August',9=>'Septembrie',10=>'Octombrie',11=>'Noiembrie',12=>'Decembrie' ];
    $date_display = $start->format('j') . ' ' . $months[ (int) $start->format('n') ];

    $location_parts = array_filter([
        $data['location']['name']    ?? '',
        $data['location']['address'] ?? '',
        $data['location']['city']    ?? '',
    ]);

    // Find Background image URL (prefer MEDIUM, fallback to any Background)
    $image_url = '';
    if ( ! empty( $data['images'] ) ) {
        $fallback = '';
        foreach ( $data['images'] as $img ) {
            if ( ( $img['name'] ?? '' ) === 'Background' ) {
                $url = 'https://livetickets-cdn.azureedge.net/itemimages/' . $img['path'] . '?' . $img['token'];
                if ( empty( $fallback ) ) $fallback = $url;
                if ( ( $img['size'] ?? '' ) === 'MEDIUM' ) { $image_url = $url; break; }
            }
        }
        if ( empty( $image_url ) ) $image_url = $fallback;
    }

    // Sideload image and set as featured image if post_id provided
    $post_id     = intval( $_POST['post_id'] ?? 0 );
    $image_set   = false;
    $image_error = '';
    if ( $image_url && $post_id && current_user_can( 'edit_post', $post_id ) ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $thumb_id = media_sideload_image( $image_url, $post_id, sanitize_text_field( $data['name'] ), 'id' );
        if ( ! is_wp_error( $thumb_id ) ) {
            set_post_thumbnail( $post_id, $thumb_id );
            $image_set = true;
        } else {
            $image_error = $thumb_id->get_error_message();
        }
    } elseif ( ! $post_id ) {
        $image_error = 'post_id=0';
    } elseif ( ! $image_url ) {
        $image_error = 'no image_url';
    }

    wp_send_json_success( [
        'title'        => $data['name'],
        'date_display' => $date_display,
        'date_raw'     => $start->format('Y-m-d'),
        'time'         => $start->format('H:i'),
        'location'     => implode( ', ', $location_parts ),
        'image_set'    => $image_set,
        'image_error'  => $image_error,
    ] );
} );

// ── AJAX: clp_subscribe ──────────────────────────────────────────────────────
function clp_handle_subscribe() {
    $raw   = file_get_contents( 'php://input' );
    $body  = json_decode( $raw, true );
    $email = filter_var( trim( $body['email'] ?? '' ), FILTER_VALIDATE_EMAIL );

    if ( ! $email ) {
        wp_send_json_error( [ 'message' => 'Adresă de email invalidă.' ], 400 );
    }

    $api_url = 'https://api.kit.com/v4/subscribers';
    $payload = wp_json_encode( [
        'email_address' => $email,
        'state'         => 'active',
    ] );

    $response = wp_remote_post( $api_url, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer kit_3ad1bb636169002be3359bd1048e0204',
        ],
        'body'    => $payload,
        'timeout' => 15,
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => 'Eroare de conexiune. Încearcă din nou.' ], 500 );
    }

    $http_code = wp_remote_retrieve_response_code( $response );
    $data      = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $http_code >= 200 && $http_code < 300 ) {
        wp_send_json_success();
    } else {
        $msg = $data['errors'][0]['title'] ?? 'Eroare la abonare. Încearcă din nou.';
        wp_send_json_error( [ 'message' => $msg ], 422 );
    }
}
add_action( 'wp_ajax_clp_subscribe',        'clp_handle_subscribe' );
add_action( 'wp_ajax_nopriv_clp_subscribe', 'clp_handle_subscribe' );

// ── AJAX: clp_contact ────────────────────────────────────────────────────────
function clp_handle_contact() {
    $raw  = file_get_contents( 'php://input' );
    $body = json_decode( $raw, true );

    if ( ! is_array( $body ) ) {
        wp_send_json_error( [ 'message' => 'Date invalide.' ], 400 );
    }

    $email = filter_var( trim( $body['email'] ?? '' ), FILTER_VALIDATE_EMAIL );
    if ( ! $email ) {
        wp_send_json_error( [ 'message' => 'Email invalid.' ], 400 );
    }

    $form_type = sanitize_text_field( $body['form_type'] ?? 'contact' );

    $subject_map = [
        'contact'             => 'Mesaj nou de pe site',
        'sustine'             => 'Cerere nouă: Susține un curs',
        'sustine-un-curs'     => 'Cerere nouă: Susține un curs',
        'gazduieste'          => 'Cerere nouă: Găzduiește un curs',
        'gazduieste-un-curs'  => 'Cerere nouă: Găzduiește un curs',
        'parteneriat'         => 'Cerere nouă: Propune un parteneriat',
    ];
    $subject = ( $subject_map[ $form_type ] ?? 'Mesaj nou' ) . ' — Cursuri la Pahar';

    // Build email body from all fields
    $lines = [];
    foreach ( $body as $key => $value ) {
        if ( $key === 'form_type' ) continue;
        $label = ucfirst( str_replace( '_', ' ', $key ) );
        if ( is_array( $value ) ) {
            $value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
        } else {
            $value = sanitize_text_field( (string) $value );
        }
        $lines[] = "{$label}: {$value}";
    }
    $body_text  = implode( "\n", $lines );
    $body_text .= "\n\n---\nData: " . current_time( 'Y-m-d H:i:s' );

    // Log to file
    $log_dir  = WP_CONTENT_DIR . '/data';
    $log_file = $log_dir . '/messages.log';
    if ( ! file_exists( $log_dir ) ) {
        wp_mkdir_p( $log_dir );
    }
    $log_entry = "=== " . current_time( 'Y-m-d H:i:s' ) . " | {$form_type} ===\n{$body_text}\n\n";
    @file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );

    // Send email
    $headers = [
        'From: noreply@cursurilapahar.ro',
        'Reply-To: ' . $email,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    wp_mail( 'contact@cursurilapahar.ro', $subject, $body_text, $headers );

    wp_send_json_success();
}
add_action( 'wp_ajax_clp_contact',        'clp_handle_contact' );
add_action( 'wp_ajax_nopriv_clp_contact', 'clp_handle_contact' );
