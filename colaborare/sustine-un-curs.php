<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Susține un curs – Cursuri la Pahar</title>
    <meta name="description" content="Ai expertiză într-un domeniu care te pasionează? Vino să susții un curs la Cursuri la Pahar.">
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
        <h1>Susține un<br><em style="font-style:italic;color:var(--accent)">Curs la Pahar</em></h1>
        <p>Căutăm voci noi pentru Cursuri la Pahar! Dacă ai experiență într-un domeniu care te pasionează și vrei să dai mai departe din învățăturile tale, te așteptăm să susții un curs în cadrul evenimentelor noastre.</p>
    </div>
</section>

<section class="page-content-section">
    <div class="container container-narrow">
        <p style="color:var(--text-muted);line-height:1.8;margin-bottom:32px;">
            Punem preț pe calitatea informației și pe vibe-ul bun, așa că, dacă ești gata să inspiri comunitatea cu învățăturile tale, completează formularul de mai jos!
        </p>

        <div class="inner-form">
            <h2>Completează formularul</h2>
            <form class="inner-page-form" data-form-type="sustine-un-curs" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="suc_name">Nume și prenume *</label>
                        <input type="text" id="suc_name" name="name" placeholder="Ion Popescu" required>
                    </div>
                    <div class="form-group">
                        <label for="suc_email">Email *</label>
                        <input type="email" id="suc_email" name="email" placeholder="email@exemplu.ro" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="suc_phone">Număr de telefon</label>
                        <input type="tel" id="suc_phone" name="phone" placeholder="07xx xxx xxx">
                    </div>
                    <div class="form-group">
                        <label for="suc_social">Link profil social media</label>
                        <input type="url" id="suc_social" name="social" placeholder="https://linkedin.com/in/...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="suc_course_name">Nume curs susținut *</label>
                    <input type="text" id="suc_course_name" name="course_name" placeholder="Ex: Psihologia deciziilor" required>
                </div>
                <div class="form-group">
                    <label for="suc_desc">Descrie cursul susținut *</label>
                    <textarea id="suc_desc" name="course_desc" rows="4" placeholder="Despre ce este, care sunt capitolele, ce învață concret un participant etc." required></textarea>
                </div>
                <div class="form-group">
                    <label for="suc_why">De ce îți dorești să susții acest curs? *</label>
                    <textarea id="suc_why" name="motivation" rows="3" placeholder="Motivele tale..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="suc_experience">Ce experiențe sau competențe te califică?</label>
                    <textarea id="suc_experience" name="experience" rows="3" placeholder="Experiența ta relevantă..."></textarea>
                </div>
                <div class="form-group">
                    <label>Ai mai susținut astfel de prezentări?</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="previous_presentations" value="yes_often"> Da, o fac deseori.
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="previous_presentations" value="yes_few"> Da, de puține ori.
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="previous_presentations" value="no"> Nu, dar vreau să încerc.
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="suc_city">În ce oraș ai vrea să susții cursul?</label>
                    <input type="text" id="suc_city" name="city" placeholder="București, Cluj-Napoca, etc.">
                </div>
                <div class="form-group">
                    <label for="suc_other">Mai e ceva ce vrei să ne transmiți?</label>
                    <textarea id="suc_other" name="other" rows="2" placeholder="Orice altceva relevant..."></textarea>
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
