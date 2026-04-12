<?php
// Restaureaza settings.json cu setarile corecte + parola + Voteaza in meniu
$dir = __DIR__ . '/data';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$settings = [
    'admin_password'    => 'clp2026admin',
    'auth_secret'       => bin2hex(random_bytes(32)),
    'webhook_secret'    => bin2hex(random_bytes(32)),
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
        ['label' => 'Votează',            'url' => '/voteaza-cursuri'],
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

// Restaureaza si vote_courses.json
$votes = [
    ['id' => 'vc_educatie_montana', 'name' => 'Educație montană', 'emoji' => '🏔️', 'description' => 'Cum să te pregătești pentru munte, echipament, trasee și siguranță.', 'likes' => 0],
    ['id' => 'vc_psihologie_relatii', 'name' => 'Psihologia relațiilor', 'emoji' => '💑', 'description' => 'Ce ne spune știința despre atașament, comunicare și relații sănătoase.', 'likes' => 0],
    ['id' => 'vc_arta_negocierii', 'name' => 'Arta negocierii', 'emoji' => '🤝', 'description' => 'Tehnici practice de negociere aplicabile în viața de zi cu zi.', 'likes' => 0],
];

file_put_contents($dir . '/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
file_put_contents($dir . '/vote_courses.json', json_encode($votes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
if (!file_exists($dir . '/courses.json')) file_put_contents($dir . '/courses.json', '[]', LOCK_EX);

echo "OK. Settings restaurate. Parola: clp2026admin. Meniu cu Voteaza.\n";
echo "Sterg restore_settings.php...\n";
unlink(__FILE__);
// Sterg si set_password.php daca mai exista
@unlink(__DIR__ . '/set_password.php');
echo "Done. Mergi la cursurilapahar.ro";
