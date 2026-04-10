<?php
/**
 * Template Name: Găzduiește un curs
 *
 * Page template for the "Găzduiește un curs" collaboration form.
 */
get_header();
?>

<section class="page-hero">
    <div class="container">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-hero-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Înapoi
        </a>
        <h1>Găzduiește un<br><em style="font-style:italic;color:var(--accent)">Curs la Pahar</em></h1>
        <p>Ai o locație cu vibe fain și vrei să o transformi într-un loc de întâlnire al participanților Cursuri la Pahar? Căutăm parteneri care să devină „acasă" pentru evenimentele noastre!</p>
    </div>
</section>

<section class="page-content-section">
    <div class="container container-narrow">
        <p style="color:var(--text-muted);line-height:1.8;margin-bottom:28px;">
            Ai un bar, un pub, o cafenea sau un spațiu neconvențional care debordează de personalitate? Ne-ar plăcea să aducem conceptul Cursuri la Pahar la tine. Punem preț pe locurile care inspiră creativitate și care oferă cadrul perfect pentru networking și învățare relaxată.
        </p>

        <div class="benefits-list">
            <div class="benefit-item">
                <div class="benefit-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <div class="benefit-item-text">
                    <strong>Vizibilitate</strong>
                    <span>Atragi un public nou, dornic de experiențe de calitate.</span>
                </div>
            </div>
            <div class="benefit-item">
                <div class="benefit-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <div class="benefit-item-text">
                    <strong>Comunitate</strong>
                    <span>Spațiul tău devine un punct de reper pentru educație și socializare.</span>
                </div>
            </div>
            <div class="benefit-item">
                <div class="benefit-item-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="benefit-item-text">
                    <strong>Vibe</strong>
                    <span>Îți umpli locația cu energie pozitivă și oameni pasionați.</span>
                </div>
            </div>
        </div>

        <div class="inner-form">
            <h2>Hai să punem ceva frumos la cale!</h2>
            <form class="inner-page-form" data-form-type="gazduieste" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_name">Nume și prenume *</label>
                        <input type="text" id="guc_name" name="name" placeholder="Ion Popescu" required>
                    </div>
                    <div class="form-group">
                        <label for="guc_email">Email *</label>
                        <input type="email" id="guc_email" name="email" placeholder="email@exemplu.ro" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_phone">Număr de telefon</label>
                        <input type="tel" id="guc_phone" name="phone" placeholder="07xx xxx xxx">
                    </div>
                    <div class="form-group">
                        <label for="guc_venue">Cum se numește localul? *</label>
                        <input type="text" id="guc_venue" name="venue_name" placeholder="Ex: Twisted Olives" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guc_city">În ce oraș? *</label>
                        <input type="text" id="guc_city" name="city" placeholder="București" required>
                    </div>
                    <div class="form-group">
                        <label for="guc_capacity">Capacitate (seated)</label>
                        <input type="text" id="guc_capacity" name="capacity" placeholder="Ex: 50 persoane">
                    </div>
                </div>
                <div class="form-group">
                    <label>Ce facilități deține locația?</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="facilities[]" value="audio"> Sistem audio cu microfon
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="facilities[]" value="projector"> Videoproiector
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="facilities[]" value="screen"> Ecran de proiecție
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="facilities[]" value="tv"> Televizor pentru proiecție
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="guc_other">Mai e ceva ce vrei să ne transmiți?</label>
                    <textarea id="guc_other" name="other" rows="3" placeholder="Orice altceva relevant despre spațiu sau despre tine..."></textarea>
                </div>
                <button type="submit" class="btn btn-accent">Trimite</button>
                <div class="form-message" aria-live="polite"></div>
            </form>
        </div>
    </div>
</section>

<?php get_footer(); ?>
