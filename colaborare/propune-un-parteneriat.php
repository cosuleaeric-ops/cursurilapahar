<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propune un parteneriat – Cursuri la Pahar</title>
    <meta name="description" content="Compania ta x Cursuri la Pahar. Explorează cum putem construi împreună.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,600;0,700;0,800;1,400;1,700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<section class="page-hero">
    <div class="container">
        <a href="../index.php" class="page-hero-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Înapoi
        </a>
        <h1>Compania ta<br><em style="font-style:italic;color:var(--accent)">× Cursuri la Pahar</em></h1>
        <p>Credem în puterea colaborării și în ideea că proiectele faine cresc prin conexiuni valoroase. Dacă reprezinți un brand, o platformă media sau un proiect care rezonează cu misiunea noastră de a aduce educația într-un format relaxat, ne-ar plăcea să explorăm cum putem construi împreună.</p>
    </div>
</section>

<section class="page-content-section">
    <div class="container container-narrow">
        <p style="color:var(--text-muted);line-height:1.8;margin-bottom:32px;">
            Căutăm parteneri care pun preț pe calitate și care vor să se implice activ în experiența pe care o oferim comunității noastre. Deci, dacă te regăsești în această descriere, completează formularul de mai jos.
        </p>

        <div class="inner-form">
            <h2>Propune un parteneriat</h2>
            <form class="inner-page-form" data-form-type="parteneriat" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="pp_partner">Nume partener / companie *</label>
                        <input type="text" id="pp_partner" name="partner_name" placeholder="Ex: Brandul Tău SRL" required>
                    </div>
                    <div class="form-group">
                        <label for="pp_contact">Persoana de contact *</label>
                        <input type="text" id="pp_contact" name="contact_person" placeholder="Nume și prenume" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="pp_email">Email *</label>
                        <input type="email" id="pp_email" name="email" placeholder="email@companie.ro" required>
                    </div>
                    <div class="form-group">
                        <label for="pp_phone">Număr de telefon</label>
                        <input type="tel" id="pp_phone" name="phone" placeholder="07xx xxx xxx">
                    </div>
                </div>
                <div class="form-group">
                    <label for="pp_type">Tipul parteneriatului *</label>
                    <select id="pp_type" name="partnership_type" required>
                        <option value="" disabled selected>Alege tipul...</option>
                        <option value="media">Parteneriat Media (vizibilitate, promovare, PR)</option>
                        <option value="product">Activare de produs (sampling, experiență directă cu participanții)</option>
                        <option value="strategic">Parteneriat Strategic / Financiar (sponsorizare)</option>
                        <option value="other">Altul</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pp_vision">Cum vizualizezi colaborarea cu Cursuri la Pahar? *</label>
                    <textarea id="pp_vision" name="vision" rows="4" placeholder="Descrie cum îți imaginezi parteneriatul..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="pp_values">De ce crezi că valorile noastre se aliniază?</label>
                    <textarea id="pp_values" name="values_alignment" rows="3" placeholder="Ce vă apropie ca misiune și viziune..."></textarea>
                </div>
                <div class="form-group">
                    <label for="pp_other">Mai e ceva ce vrei să ne transmiți?</label>
                    <textarea id="pp_other" name="other" rows="2" placeholder="Orice altceva relevant..."></textarea>
                </div>
                <button type="submit" class="btn btn-accent">Trimite</button>
                <div class="form-message" aria-live="polite"></div>
            </form>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Cursuri la Pahar. <a href="../index.php" style="color:var(--accent);">← Înapoi la site</a></p>
        </div>
    </div>
</footer>

<script src="../assets/js/main.js"></script>
</body>
</html>
