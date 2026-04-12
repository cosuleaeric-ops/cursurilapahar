<?php
$dir = __DIR__ . '/data';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Exact settings from git history + Voteaza in meniu + parola
$s = [
    'admin_password'    => 'clp2026admin',
    'auth_secret'       => bin2hex(random_bytes(32)),
    'webhook_secret'    => bin2hex(random_bytes(32)),
    'announcement'      => "\xF0\x9F\x8E\x89 Peste 1.000 de participan\xC8\x9Bi au descoperit c\xC4\x83 educa\xC8\x9Bia are un gust mai bun la un pahar. Tu e\xC8\x99ti urm\xC4\x83torul?",
    'hero_title'        => "Cursuri \xC8\x9Binute de exper\xC8\x9Bi<br><em>la un pahar \xC3\xAEn ora\xC8\x99.</em>",
    'hero_btn'          => "Vezi urm\xC4\x83toarele cursuri",
    'courses_title'     => "Urm\xC4\x83toarele cursuri",
    'newsletter_title'  => "Fii primul care afl\xC4\x83 c\xC3\xA2nd au loc evenimentele Cursuri la Pahar",
    'newsletter_desc'   => "Vei primi \xC3\xAEn exclusivitate data \xC8\x99i tema viitoarelor evenimente Cursuri la Pahar, cu 2 s\xC4\x83pt\xC4\x83m\xC3\xA2ni \xC3\xAEnainte ca acestea s\xC4\x83 aib\xC4\x83 loc.",
    'collab_title'      => 'Colaborare',
    'collab_subtitle'   => "Vrei s\xC4\x83 faci parte din comunitatea Cursuri la Pahar? Hai s\xC4\x83 construim ceva frumos \xC3\xAEmpreun\xC4\x83.",
    'contact_title'     => 'Contact',
    'contact_subtitle'  => "Ai o \xC3\xAEntrebare sau o idee? Scrie-ne.",
    'hero_images'       => ['/assets/images/hero1.jpg','/assets/images/hero2.jpg','/assets/images/hero3.jpg','/assets/images/hero4.jpg','/assets/images/hero5.jpg'],
    'logo_path'         => '/assets/images/logo.webp',
    'favicon_path'      => '',
    'nav_brand_text'    => 'Cursuri la Pahar',
    'nav_links'         => [
        ['label'=>'Cursuri','url'=>'/#cursuri'],
        ['label'=>"Cum func\xC8\x9Bioneaz\xC4\x83",'url'=>'/#cum-functioneaza'],
        ['label'=>'FAQ','url'=>'/#faq'],
        ['label'=>'Colaborare','url'=>'/#colaborare'],
        ['label'=>'Contact','url'=>'/#contact'],
        ['label'=>"Voteaz\xC4\x83",'url'=>'/voteaza-cursuri'],
    ],
    'steps' => [
        ['title'=>'Verifici calendarul','text'=>"R\xC4\x83sfoi\xC8\x99ti cursurile disponibile \xC8\x99i g\xC4\x83se\xC8\x99ti tema care te st\xC3\xA2rne\xC8\x99te curiozitatea."],
        ['title'=>'Cumperi biletul','text'=>"Achizi\xC8\x9Bionezi biletul online prin LiveTickets, simplu \xC8\x99i rapid, de pe orice dispozitiv."],
        ['title'=>'Vii la eveniment','text'=>"Te prezin\xC8\x9Bi la loca\xC8\x9Bie, \xC3\xAE\xC8\x9Bi iei o b\xC4\x83utur\xC4\x83 preferat\xC4\x83 \xC8\x99i ocupi un loc confortabil."],
        ['title'=>"\xC3\x8Enve\xC8\x9Bi & socializezi",'text'=>"Ascul\xC8\x9Bi expertul, pui orice \xC3\xAEntrebare la Q&A \xC8\x99i cuno\xC8\x99ti oameni faini cu acelea\xC8\x99i interese."],
    ],
    'faq_items' => [
        ['q'=>"Ce este Cursuri la Pahar?",'a'=>"Cursuri la Pahar este un eveniment care scoate educa\xC8\x9Bia din amfiteatre \xC8\x99i o aduce \xC3\xAEn baruri. Exper\xC8\x9Bi \xC8\x99i profesori vin s\xC4\x83 discute teme complexe \xC3\xAEntr-un cadru relaxat, la un pahar cu publicul."],
        ['q'=>"C\xC3\xA2t dureaz\xC4\x83 un eveniment?",'a'=>"Rezerv\xC4\x83m cam 2 ore pentru \xC3\xAEntreaga experien\xC8\x9B\xC4\x83. Primele 60\xE2\x80\x9390 de minute sunt dedicate prezent\xC4\x83rii, iar restul timpului \xC3\xAEl petrecem la un Q&A, unde po\xC8\x9Bi pune orice fel de \xC3\xAEntreb\xC4\x83ri."],
        ['q'=>"C\xC3\xA2t cost\xC4\x83 un bilet?",'a'=>"Biletul standard cost\xC4\x83 50 de lei, iar biletul pentru studen\xC8\x9Bi cost\xC4\x83 30 de lei."],
        ['q'=>'Despre ce sunt cursurile?','a'=>"Alegem teme care st\xC3\xA2rnesc curiozitatea oric\xC3\xA2nui: de la psihologie \xC8\x99i misterele istoriei, p\xC3\xA2n\xC4\x83 la univers \xC8\x99i tehnologie. Practic, \xC3\xAEncerc\xC4\x83m s\xC4\x83 transform\xC4\x83m subiectele \xE2\x80\x9Egrele\xE2\x80\x9D \xC3\xAEn pove\xC8\x99ti numai bune de ascultat la un pahar."],
        ['q'=>'Unde au loc evenimentele?','a'=>"Ne vedem \xC3\xAEn baruri, pub-uri \xC8\x99i alte spa\xC8\x9Bii relaxate din Bucure\xC8\x99ti (momentan). Alegem loca\xC8\x9Bii unde atmosfera este cald\xC4\x83 \xC8\x99i unde po\xC8\x9Bi savura o b\xC4\x83utur\xC4\x83 \xC3\xAEn timp ce ascul\xC8\x9Bi ceva interesant."],
        ['q'=>'Cine poate participa?','a'=>"Oricine este curios \xC8\x99i are peste 16 ani. Nu ai nevoie de preg\xC4\x83tire special\xC4\x83 sau studii \xC3\xAEn domeniu; evenimentul este creat pentru to\xC8\x9Bi cei care vor s\xC4\x83 \xC3\xAEmbine socializarea cu o doz\xC4\x83 de cunoa\xC8\x99tere."],
        ['q'=>"C\xC3\xA2nd va avea loc urm\xC4\x83torul eveniment?",'a'=>"Dac\xC4\x83 vrei s\xC4\x83 te anun\xC8\x9B\xC4\x83m direct pe email c\xC3\xA2nd punem biletele la v\xC3\xA2nzare, aboneaz\xC4\x83-te la newsletter-ul nostru. Pe l\xC3\xA2ng\xC4\x83 asta, po\xC8\x9Bi vedea calendarul \xC8\x99i pe pagina noastr\xC4\x83 de Instagram."],
    ],
    'color_bg'          => '#0D0D0D',
    'color_accent'      => '#C9A84C',
    'color_text'        => '#E8E4DC',
    'color_text_muted'  => '#9CA3AF',
    'color_surface'     => '#161616',
    'color_btn_hover'   => '#b8922e',
    'color_banner'      => '#FFB000',
    'font_heading'      => 'Rubik',
    'font_body'         => 'Inter',
    'kit_api_key'       => 'kit_3ad1bb636169002be3359bd1048e0204',
    'kit_form_id'       => '',
    'head_scripts'      => '',
    'pages' => [
        'sustine' => [
            'title' => "Sus\xC8\x9Bine un curs",
            'subtitle' => "\xC3\x8Emp\xC4\x83rt\xC4\x83\xC8\x99e\xC8\x99te-\xC8\x9Bi expertiza cu comunitatea noastr\xC4\x83.",
            'description' => "E\xC8\x99ti expert \xC3\xAEntr-un domeniu care te pasioneaz\xC4\x83? Vino s\xC4\x83 sus\xC8\x9Bii un curs \xC3\xAEn fa\xC8\x9Ba unei comunit\xC4\x83\xC8\x9Bi curioase, \xC3\xAEntr-un cadru relaxat, la un pahar.",
        ],
        'gazduieste' => [
            'title' => "G\xC4\x83zduie\xC8\x99te un curs",
            'subtitle' => "Transform\xC4\x83-\xC8\x9Bi loca\xC8\x9Bia \xC3\xAEn spa\xC8\x9Biul unde se nasc conexiunile.",
            'description' => "Ai o loca\xC8\x9Bie cu atmosfer\xC4\x83? Bar, caf\xC3\xA9, spa\xC8\x9Biu cultural sau altceva? Hai s\xC4\x83 aducem un curs la tine \xC8\x99i s\xC4\x83 umpleam locul de oameni curio\xC8\x99i.",
        ],
        'parteneriat' => [
            'title' => 'Propune un parteneriat',
            'subtitle' => "Construim ceva frumos \xC3\xAEmpreun\xC4\x83.",
            'description' => "Reprezin\xC8\x9Bi un brand, o platform\xC4\x83 media sau o organiza\xC8\x9Bie? Explor\xC4\x83m \xC3\xAEmpreun\xC4\x83 oportunit\xC4\x83\xC8\x9Bi de colaborare care aduc valoare comunit\xC4\x83\xC8\x9Bii noastre.",
        ],
    ],
    'section_bgs' => [
        'cursuri'          => ['image'=>'','blur'=>6,'overlay'=>0.72],
        'newsletter'       => ['image'=>'','blur'=>6,'overlay'=>0.72],
        'cum-functioneaza' => ['image'=>'','blur'=>6,'overlay'=>0.72],
        'faq'              => ['image'=>'','blur'=>6,'overlay'=>0.72],
        'colaborare'       => ['image'=>'','blur'=>6,'overlay'=>0.72],
        'contact'          => ['image'=>'','blur'=>6,'overlay'=>0.72],
    ],
];

file_put_contents($dir . '/settings.json', json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

// Vote courses - EXACT din git history
$votes = [
    [
        'id' => 'vc_educatie_montana',
        'name' => 'Educație montană',
        'emoji' => '🏔️',
        'description' => 'Mulți oameni urcă pe munte conduși de entuziasm, nu de pregătire, și consecințele pot fi grave. Cursul acoperă practic ce contează: cum alegi traseul potrivit nivelului tău, ce trebuie să ai în rucsac și cum iei decizii bune când condițiile se schimbă. Educație montană reală, nu după reels.',
        'likes' => 0,
    ],
    [
        'id' => 'vc_iubim_oameni_care_ne_ranesc',
        'name' => 'De ce iubim oameni care ne rănesc',
        'emoji' => '💔',
        'description' => 'De ce ajungem să iubim oameni care ne fac rău, chiar și când mintea știe că ceva e greșit? Cursul explorează mecanismele din spatele atașamentului dureros, de la traumă relațională la semnalele pe care le trimite corpul, și explică de ce înțelegerea singură nu e suficientă pentru schimbare.',
        'likes' => 0,
    ],
    [
        'id' => 'vc_numerologie',
        'name' => 'Numerologie',
        'emoji' => '🔮',
        'description' => 'Un curs introductiv care explică de la zero cum funcționează numerologia și ce poți afla despre tine pornind de la câteva cifre simple. Participanții învață să-și calculeze și interpreteze propria hartă numerologică, cu aplicații concrete în autocunoaștere, relații și decizii de zi cu zi.',
        'likes' => 0,
    ],
];
file_put_contents($dir . '/vote_courses.json', json_encode($votes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
if (!file_exists($dir . '/courses.json')) file_put_contents($dir . '/courses.json', '[]', LOCK_EX);

header('Content-Type: text/plain');
echo "DONE. Setari restaurate din git history (versiunea originala + Voteaza in meniu).\n";
echo "Parola: clp2026admin\n";
echo "Kit API key restaurat.\n";
echo "Sterg fisierul...\n";
@unlink(__FILE__);
echo "Mergi la cursurilapahar.ro\n";
