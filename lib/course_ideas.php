<?php
// Idei de teme de curs — pagina publică /cursuri-posibile + editor în admin.

function clp_course_ideas_file(): string {
    return dirname(__DIR__) . '/data/course_ideas.json';
}

function clp_default_course_ideas(): array {
    return [
        'intro' => 'Cauți inspirație pentru un curs la pahar? Mai jos găsești tipurile de teme pe care ni le dorim: subiecte clare și concrete, care stârnesc curiozitatea și se pot povesti relaxat, la o bere. Sunt doar exemple menite să-ți dea o direcție — dacă ai o pasiune sau o expertiză în spiritul lor, ne-ar plăcea să o aducem pe scenă.',
        'categories' => [
            [
                'emoji' => '🔬',
                'title' => 'Știință & Tehnologie',
                'topics' => [
                    'Cum funcționează inteligența artificială, pe înțelesul tuturor',
                    'Ce înseamnă cu adevărat fizica cuantică',
                    'Marile invenții care au schimbat istoria omenirii',
                    'Viitorul: roboți, AI și ce urmează pentru noi',
                    'Mituri științifice în care încă credem',
                    'Cum ne schimbă tehnologia creierul și atenția',
                ],
            ],
            [
                'emoji' => '🌌',
                'title' => 'Spațiu & Cosmos',
                'topics' => [
                    'Cum a apărut universul și cum se va sfârși',
                    'Există viață altundeva? Căutarea extratereștrilor',
                    'Găurile negre și cele mai mari mistere ale cosmosului',
                    'Ce sunt materia și energia întunecată',
                    'Cum explorăm spațiul: de la Lună la Marte',
                ],
            ],
            [
                'emoji' => '📜',
                'title' => 'Istorie',
                'topics' => [
                    'Momente care au schimbat cursul istoriei',
                    'Cum arăta viața de zi cu zi în lumea antică',
                    'Marile revoluții și cum se nasc',
                    'Istoria ascunsă a lucrurilor banale (cafea, sare, bani)',
                    'Conspirații și operațiuni secrete reale din istorie',
                    'Mari epidemii și cum au schimbat societatea',
                ],
            ],
            [
                'emoji' => '🧠',
                'title' => 'Psihologie',
                'topics' => [
                    'De ce luăm decizii proaste: capcanele minții',
                    'Psihologia manipulării și a persuasiunii',
                    'Cum funcționează memoria (și de ce ne înșală)',
                    'Ce ne face cine suntem: personalitatea, explicată',
                    'Latura întunecată: narcisism, psihopatie, manipulare',
                    'Emoțiile: de ce le simțim și cum le gestionăm',
                ],
            ],
            [
                'emoji' => '👥',
                'title' => 'Sociologie & Societate',
                'topics' => [
                    'De ce urmăm mulțimea: psihologia grupului',
                    'Cum ne modelează rețelele sociale comportamentul',
                    'Brainrot: ce ne face scrollul infinit minții și atenției',
                    'Dezinformarea: cum recunoști o minciună',
                    'Subculturi și triburi moderne',
                    'Cum se schimbă normele de la o generație la alta',
                ],
            ],
            [
                'emoji' => '💭',
                'title' => 'Filozofie & Idei',
                'topics' => [
                    'Avem liber arbitru sau totul e predeterminat?',
                    'Ce înseamnă o viață bună? Lecții de la filozofi',
                    'Marile întrebări: conștiință, sens și moarte',
                    'Există un adevăr universal sau totul e relativ?',
                    'Etica dilemelor moderne: AI, clonare, tehnologie',
                ],
            ],
            [
                'emoji' => '🎬',
                'title' => 'Film & Cultură Pop',
                'topics' => [
                    'Cum ne manipulează emoțiile filmele de groază',
                    'De ce ne atașăm de personaje fictive',
                    'Cum spun regizorii povești prin imagine și sunet',
                    'Ce ne învață filmele SF despre viitor și despre noi',
                    'Evoluția supereroilor și ce spun despre societate',
                    'Psihologia serialelor: de ce nu ne putem opri',
                ],
            ],
            [
                'emoji' => '🎨',
                'title' => 'Artă & Literatură',
                'topics' => [
                    'Cum a evoluat arta, de la peșteri la digital',
                    'De ce aceleași mituri apar în toate culturile',
                    'Puterea poveștilor: de ce avem nevoie de ficțiune',
                    'Cărți și artă interzise: istoria cenzurii',
                    'Culoarea: știință, percepție și simbol',
                ],
            ],
            [
                'emoji' => '🎵',
                'title' => 'Muzică',
                'topics' => [
                    'De ce ne dă muzica fiori: știința emoției',
                    'Cum au schimbat lumea genuri ca rock-ul și hip-hop-ul',
                    'Matematica ascunsă din spatele muzicii',
                    'Muzica și marile mișcări sociale',
                ],
            ],
            [
                'emoji' => '🏛️',
                'title' => 'Politică & Putere',
                'topics' => [
                    'Cum se construiește (și cade) un dictator',
                    'Cum funcționează cu adevărat propaganda',
                    'De ce ne împărțim în tabere: polarizarea',
                    'Marile idei politice, explicate simplu',
                    'Cine deține puterea și cum o folosește',
                ],
            ],
            [
                'emoji' => '🩺',
                'title' => 'Sănătate, Corp & Minte',
                'topics' => [
                    'Știința somnului și de ce contează atât de mult',
                    'Ce se întâmplă în creier când folosim droguri',
                    'Anxietate și stres: ce spune de fapt știința',
                    'Mituri despre nutriție și despre slăbit',
                    'Longevitate: ce ne ajută cu adevărat să trăim mai mult',
                ],
            ],
            [
                'emoji' => '💰',
                'title' => 'Bani & Economie',
                'topics' => [
                    'Economia explicată pe înțelesul tuturor',
                    'Psihologia banilor: de ce cheltuim irațional',
                    'Pot banii să cumpere fericirea?',
                    'Cum ne manipulează reclamele și marketingul',
                    'Bule, crize și escrocherii financiare celebre',
                ],
            ],
            [
                'emoji' => '🌍',
                'title' => 'Natură, Mâncare & Lume',
                'topics' => [
                    'Istoria și cultura din spatele mâncării și băuturii',
                    'Cum comunică și cum gândesc animalele',
                    'Schimbările climatice, dincolo de panică',
                    'Cele mai bizare și fascinante forme de viață',
                    'Călătorii și culturi: ce ne învață despre noi',
                ],
            ],
            ...clp_general_course_idea_categories(),
            clp_media_journalism_category(),
        ],
    ];
}

function clp_media_journalism_category(): array {
    return [
        'emoji' => '📰',
        'title' => 'Media & Jurnalism',
        'topics' => [
            'Cum se naște o știre: din teren până pe ecranul tău',
            'Mai există obiectivitate în presă?',
            'Economia ascunsă a presei: cine plătește ce citești',
            'De la ziare la TikTok: cum s-a schimbat informarea',
        ],
    ];
}

// Categorii generale adăugate în iulie 2026 (inspirate din alte serii de tip
// „lectures on tap"). Referite și de migrația care le adaugă pe site-ul live.
function clp_general_course_idea_categories(): array {
    return [
        [
            'emoji' => '❤️',
            'title' => 'Dragoste & Relații',
            'topics' => [
                'Știința atracției: de ce ne place cine ne place',
                'Ce se întâmplă în creier când ne îndrăgostim',
                'De ce înșală oamenii: știința fidelității',
                'Algoritmii aplicațiilor de dating, demascați',
                'Prietenia: de ce e vitală și de ce o neglijăm',
            ],
        ],
        [
            'emoji' => '⚖️',
            'title' => 'Crimă & Justiție',
            'topics' => [
                'Cum ajung oameni nevinovați condamnați',
                'Psihologia criminalilor: realitate vs. filme',
                'Marile escrocherii și cum gândește un escroc',
                'De ce ne fascinează poveștile cu crime',
            ],
        ],
        [
            'emoji' => '🗣️',
            'title' => 'Limbă & Comunicare',
            'topics' => [
                'De ce vorbim: originea și evoluția limbajului',
                'Limbajul corpului: ce comunicăm fără cuvinte',
                'Istoria ciudată a cuvintelor pe care le folosim zilnic',
                'Cum ne schimbă creierul o limbă străină',
            ],
        ],
        [
            'emoji' => '⚽',
            'title' => 'Sport',
            'topics' => [
                'Psihologia suporterului: de ce ne doare o înfrângere',
                'Ce ne învață sportul despre presiune și performanță',
                'Sport și politică: când jocul devine protest',
            ],
        ],
        [
            'emoji' => '🔢',
            'title' => 'Matematică',
            'topics' => [
                'Infinitul: cum numeri până la nesfârșit',
                'Teoria jocurilor: matematica negocierilor de zi cu zi',
                'Matematica din spatele lumii moderne: GPS, AI, hărți',
            ],
        ],
        [
            'emoji' => '🙏',
            'title' => 'Religie, Mituri & Credințe',
            'topics' => [
                'De ce inventăm zei: religiile, explicate',
                'Magie, superstiții și ocultism de-a lungul istoriei',
                'De ce creierul nostru caută sacrul',
            ],
        ],
        [
            'emoji' => '🏙️',
            'title' => 'Orașe, Arhitectură & Design',
            'topics' => [
                'Cum ne schimbă orașul comportamentul și starea de spirit',
                'De ce arată orașele așa: o istorie a urbanismului',
                'Psihologia designului: de ce cumpărăm cu ochii',
            ],
        ],
        [
            'emoji' => '💼',
            'title' => 'Muncă, Carieră & Performanță',
            'topics' => [
                'Cum negociezi orice: psihologie și tactici',
                'Știința creativității: poate fi antrenată?',
                'Performanță sub presiune: lecții din sport și scenă',
            ],
        ],
    ];
}

function clp_load_course_ideas(): array {
    $file = clp_course_ideas_file();
    if (!file_exists($file)) return clp_default_course_ideas();
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data) || !is_array($data['categories'] ?? null)) return clp_default_course_ideas();
    return clp_migrate_course_ideas(array_merge(clp_default_course_ideas(), $data));
}

// Adaugă o singură dată categoriile generale noi în JSON-ul de pe server.
// Flag-ul rămâne în fișier, deci categoriile șterse ulterior din admin nu reapar.
function clp_migrate_course_ideas(array $data): array {
    $changed = false;
    if (empty($data['migration_general_categories_2026_07'])) {
        $data['migration_general_categories_2026_07'] = true;
        $existing = array_map(fn($c) => $c['title'] ?? '', $data['categories']);
        foreach (clp_general_course_idea_categories() as $cat) {
            if (!in_array($cat['title'], $existing, true)) $data['categories'][] = $cat;
        }
        $changed = true;
    }
    if (empty($data['migration_media_jurnalism_2026_07'])) {
        $data['migration_media_jurnalism_2026_07'] = true;
        $cat = clp_media_journalism_category();
        $existing = array_map(fn($c) => $c['title'] ?? '', $data['categories']);
        if (!in_array($cat['title'], $existing, true)) $data['categories'][] = $cat;
        $changed = true;
    }
    if (empty($data['migration_brainrot_2026_07'])) {
        $data['migration_brainrot_2026_07'] = true;
        $topic = 'Brainrot: ce ne face scrollul infinit minții și atenției';
        foreach ($data['categories'] as &$cat) {
            if (($cat['title'] ?? '') === 'Sociologie & Societate') {
                if (!in_array($topic, $cat['topics'] ?? [], true)) $cat['topics'][] = $topic;
                break;
            }
        }
        unset($cat);
        $changed = true;
    }
    if ($changed) clp_save_course_ideas($data);
    return $data;
}

function clp_save_course_ideas(array $data): bool {
    $file = clp_course_ideas_file();
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;
    return file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    ) !== false;
}
