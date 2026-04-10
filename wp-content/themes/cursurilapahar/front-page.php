<?php
/**
 * Front Page Template – Cursuri la Pahar
 */
get_header();

// ── Load active courses via WP_Query ──────────────────────────────────────────
$courses_query = new WP_Query( [
    'post_type'      => 'curs',
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'   => '_curs_active',
            'value' => '1',
        ],
    ],
    'meta_key'  => '_curs_date_raw',
    'orderby'   => 'meta_value',
    'order'     => 'ASC',
] );
?>

<!-- ── HERO ────────────────────────────────── -->
<section class="hero" id="hero">
    <div class="hero-slides">
        <div class="hero-slide active" style="background-image:url('<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero1.jpg')"></div>
        <div class="hero-slide" style="background-image:url('<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero2.jpg')"></div>
        <div class="hero-slide" style="background-image:url('<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero3.jpg')"></div>
        <div class="hero-slide" style="background-image:url('<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero4.jpg')"></div>
        <div class="hero-slide" style="background-image:url('<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/hero5.jpg')"></div>
    </div>
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <h1 class="hero-title">Cursuri ținute de experți<br><em>la un pahar în oraș.</em></h1>
        <a href="#cursuri" class="btn btn-primary">Vezi următoarele cursuri</a>
    </div>

    <div class="hero-scroll-hint" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
    </div>
</section>

<!-- ── ANNOUNCEMENT BANNER ────────────────── -->
<div class="announcement-banner">
    🎉 Peste 1.000 de participanți au descoperit că educația are un gust mai bun la un pahar. Tu ești următorul?
</div>

<!-- ── CURSURI ─────────────────────────────── -->
<section class="section" id="cursuri">
    <div class="container">
        <h2 class="section-title">Următoarele cursuri</h2>

        <?php if ( ! $courses_query->have_posts() ) : ?>
        <p class="no-events">Nu există cursuri programate momentan.<br>
        Abonează-te la newsletter să fii primul care află!</p>
        <?php else : ?>
        <div class="events-grid">
            <?php while ( $courses_query->have_posts() ) : $courses_query->the_post();
                $post_id         = get_the_ID();
                $date_display    = get_post_meta( $post_id, '_curs_date_display', true );
                $date_raw        = get_post_meta( $post_id, '_curs_date_raw', true );
                $time            = get_post_meta( $post_id, '_curs_time', true );
                $location        = get_post_meta( $post_id, '_curs_location', true );
                $livetickets_url = get_post_meta( $post_id, '_curs_livetickets_url', true );

                // Parse date for badge
                $badge_day   = '';
                $badge_month = '';
                if ( $date_raw ) {
                    $ts          = strtotime( $date_raw );
                    $badge_day   = date( 'd', $ts );
                    $badge_month = strtoupper( date( 'M', $ts ) );
                }

                $card_url = $livetickets_url ? $livetickets_url : get_permalink();
            ?>
            <a href="<?php echo esc_url( $card_url ); ?>" target="<?php echo $livetickets_url ? '_blank' : '_self'; ?>" rel="noopener" class="event-card">
                <div class="event-card-img">
                    <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'large', [ 'loading' => 'lazy', 'alt' => esc_attr( get_the_title() ) ] ); ?>
                    <?php else : ?>
                    <div class="event-card-img-placeholder"></div>
                    <?php endif; ?>
                    <?php if ( $badge_day ) : ?>
                    <div class="event-card-date-badge">
                        <span class="badge-day"><?php echo esc_html( $badge_day ); ?></span>
                        <span class="badge-month"><?php echo esc_html( $badge_month ); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="event-card-body">
                    <h3 class="event-card-title"><?php the_title(); ?></h3>
                    <div class="event-card-meta">
                        <?php if ( $time ) : ?>
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <?php echo esc_html( $time ); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ( $location ) : ?>
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?php echo esc_html( $location ); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <span class="btn btn-secondary">Cumpără bilet →</span>
                </div>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── NEWSLETTER ─────────────────────────── -->
<section class="section section-dark" id="newsletter">
    <div class="container container-narrow">
        <div class="newsletter-icon" aria-hidden="true">✉</div>
        <h2 class="section-title">Fii primul care află când au loc evenimentele Cursuri la Pahar</h2>
        <p class="newsletter-desc">Vei primi în exclusivitate data și tema viitoarelor evenimente Cursuri la Pahar, cu 2 săptămâni înainte ca acestea să aibă loc.</p>
        <form class="newsletter-form" id="newsletterForm" novalidate>
            <div class="newsletter-fields">
                <input type="email" name="email" id="nlEmail" placeholder="Adresa ta de email" required autocomplete="email">
                <button type="submit" class="btn btn-accent">Anunță-mă</button>
            </div>
            <p class="newsletter-note">100% gratuit. Te poți dezabona oricând.</p>
            <div class="form-message" id="nlMessage" aria-live="polite"></div>
        </form>
    </div>
</section>

<!-- ── CUM FUNCȚIONEAZĂ ────────────────────── -->
<section class="section" id="cum-functioneaza">
    <div class="container">
        <h2 class="section-title">Cum funcționează</h2>
        <div class="steps-grid">
            <div class="step">
                <div class="step-number">01</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </div>
                <h3>Verifici calendarul</h3>
                <p>Răsfoiești cursurile disponibile și găsești tema care te stârnește curiozitatea.</p>
            </div>
            <div class="step">
                <div class="step-number">02</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/></svg>
                </div>
                <h3>Cumperi biletul</h3>
                <p>Achiziționezi biletul online prin LiveTickets, simplu și rapid, de pe orice dispozitiv.</p>
            </div>
            <div class="step">
                <div class="step-number">03</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/></svg>
                </div>
                <h3>Vii la eveniment</h3>
                <p>Te prezinți la locație, îți iei o băutură preferată și ocupi un loc confortabil.</p>
            </div>
            <div class="step">
                <div class="step-number">04</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <h3>Înveți &amp; socializezi</h3>
                <p>Asculți expertul, pui orice întrebare la Q&amp;A și cunoști oameni faini cu aceleași interese.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── FAQ ────────────────────────────────── -->
<section class="section section-dark" id="faq">
    <div class="container container-narrow">
        <h2 class="section-title">Întrebări frecvente</h2>
        <div class="faq-list">

            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    Ce este Cursuri la Pahar?
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Cursuri la Pahar este un eveniment care scoate educația din amfiteatre și o aduce în baruri. Experți și profesori vin să discute teme complexe într-un cadru relaxat, la un pahar cu publicul.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    Cât durează un eveniment?
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Rezervăm cam 2 ore pentru întreaga experiență. Primele 60–90 de minute sunt dedicate prezentării, iar restul timpului îl petrecem la un Q&amp;A, unde poți pune orice fel de întrebări.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    Cât costă un bilet?
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Biletul standard costă <strong>50 de lei</strong>, iar biletul pentru studenți costă <strong>30 de lei</strong>.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    Despre ce sunt cursurile?
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Alegem teme care stârnesc curiozitatea oricui: de la psihologie și misterele istoriei, până la univers și tehnologie. Practic, încercăm să transformăm subiectele „grele" în povești numai bune de ascultat la un pahar.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    Unde au loc evenimentele?
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Ne vedem în baruri, pub-uri și alte spații relaxate din București (momentan). Alegem locații unde atmosfera este caldă și unde poți savura o băutură în timp ce asculți ceva interesant.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    Cine poate participa?
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Oricine este curios și are peste 16 ani. Nu ai nevoie de pregătire specială sau studii în domeniu; evenimentul este creat pentru toți cei care vor să îmbine socializarea cu o doză de cunoaștere.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" aria-expanded="false">
                    Când va avea loc următorul eveniment?
                    <span class="faq-icon" aria-hidden="true"></span>
                </button>
                <div class="faq-answer">
                    <p>Dacă vrei să te anunțăm direct pe email când punem biletele la vânzare, abonează-te la newsletter-ul nostru. Pe lângă asta, poți vedea calendarul și pe pagina noastră de Instagram.</p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ── COLABORARE ─────────────────────────── -->
<section class="section" id="colaborare">
    <div class="container">
        <h2 class="section-title">Colaborare</h2>
        <p class="section-subtitle">Vrei să faci parte din comunitatea Cursuri la Pahar? Hai să construim ceva frumos împreună.</p>
        <div class="collab-grid">
            <a href="<?php echo esc_url( home_url( '/sustine-un-curs/' ) ); ?>" class="collab-card">
                <div class="collab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <h3>Susține un curs</h3>
                <p>Ai expertiză într-un domeniu care te pasionează? Vino să susții un curs în fața comunității noastre.</p>
                <span class="collab-link">Află mai mult →</span>
            </a>
            <a href="<?php echo esc_url( home_url( '/gazduieste-un-curs/' ) ); ?>" class="collab-card">
                <div class="collab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/></svg>
                </div>
                <h3>Găzduiește un curs</h3>
                <p>Ai o locație cu vibe fain? Transformă-o în spațiul unde se nasc conexiunile și ideile noi.</p>
                <span class="collab-link">Află mai mult →</span>
            </a>
            <a href="<?php echo esc_url( home_url( '/propune-un-parteneriat/' ) ); ?>" class="collab-card">
                <div class="collab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14l2 2 4-4"/></svg>
                </div>
                <h3>Propune un parteneriat</h3>
                <p>Reprezinți un brand sau o platformă media? Hai să explorăm ce putem construi împreună.</p>
                <span class="collab-link">Află mai mult →</span>
            </a>
        </div>
    </div>
</section>

<!-- ── CONTACT ────────────────────────────── -->
<section class="section section-dark" id="contact">
    <div class="container container-narrow">
        <h2 class="section-title">Contact</h2>
        <p class="section-subtitle">Ai o întrebare sau o idee? Scrie-ne.</p>
        <form class="contact-form" id="contactForm" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="contactName">Nume</label>
                    <input type="text" id="contactName" name="name" placeholder="Numele tău" required>
                </div>
                <div class="form-group">
                    <label for="contactEmail">Email</label>
                    <input type="email" id="contactEmail" name="email" placeholder="email@exemplu.ro" required>
                </div>
            </div>
            <div class="form-group">
                <label for="contactMsg">Mesaj</label>
                <textarea id="contactMsg" name="message" rows="5" placeholder="Scrie mesajul tău aici..." required></textarea>
            </div>
            <button type="submit" class="btn btn-accent">Trimite mesajul</button>
            <div class="form-message" id="contactMessage" aria-live="polite"></div>
        </form>
    </div>
</section>

<?php get_footer(); ?>
