<?php

// Page publique /download (resources/views/public/download.blade.php).
// Les faits (version, build, date, taille, SHA-256) viennent de config('mobile.apk').
return [
    'meta_title' => "Obtenir l'application OpesCare pour Android",
    'meta_desc' => "Téléchargez l'application Android OpesCare : votre Health ID, dossiers et résultats, consentement et journal d'accès, famille, rendez-vous, recherche de médicaments et de sang. Gratuite.",
    'breadcrumb' => "Obtenir l'application",

    'hero' => [
        'kicker' => 'OpesCare pour Android',
        'title_1' => "Obtenez l'application OpesCare",
        'title_2' => 'Votre Health ID dans la poche.',
        'lead' => 'Gardez votre Health ID sur vous, consultez vos résultats et ordonnances, décidez qui peut ouvrir votre dossier, et trouvez un médicament ou du sang près de chez vous. Gratuit.',
        'version' => 'Version :version (build :build)',
        'size' => ':size Mo',
        'released' => 'Publiée le :date',
        'cta_download' => 'Télécharger pour Android (opescare.apk)',
        'cta_web' => "Ouvrir l'application web",
        'android_only' => "Pour téléphones Android. S'installe directement depuis opescare.com.",
        'qr_title' => 'Sur un ordinateur ?',
        'qr_note' => "Scannez avec l'appareil photo de votre téléphone pour ouvrir cette page.",
        'qr_label' => 'Code QR menant à la page de téléchargement OpesCare',
    ],

    'install' => [
        'eyebrow' => 'Installation',
        'title' => 'Quatre étapes sur Android',
        'sub' => "L'application s'installe directement depuis opescare.com.",
        'steps' => [
            'download' => ['title' => 'Télécharger', 'desc' => 'Touchez le bouton de téléchargement. Votre téléphone enregistre opescare.apk.'],
            'allow' => ['title' => 'Autoriser cette source', 'desc' => 'Quand Android le demande, autorisez votre navigateur à installer des applications depuis cette source. Une seule fois suffit.'],
            'open' => ['title' => 'Ouvrir', 'desc' => 'Ouvrez le fichier téléchargé, touchez Installer, puis Ouvrir.'],
            'sign_in' => ['title' => 'Se connecter', 'desc' => 'Connectez-vous avec votre Health ID, ou créez-en un en quelques minutes.'],
        ],
        'play_note' => "Une fiche Google Play est en préparation. D'ici là, cette page est la source officielle de l'application.",
    ],

    'features' => [
        'eyebrow' => "Dans l'application",
        'title' => "Ce que fait l'application",
        'cards' => [
            'health_id' => ['title' => 'Health ID', 'desc' => 'Votre carte Health ID numérique, avec un code QR vérifiable par une structure.'],
            'records' => ['title' => 'Dossiers et résultats', 'desc' => 'Vos consultations, résultats de laboratoire, ordonnances et vaccinations au même endroit.'],
            'consent' => ['title' => "Consentement et journal d'accès", 'desc' => "Acceptez ou refusez les demandes d'ouverture de votre dossier, et voyez chaque consultation."],
            'family' => ['title' => 'Famille', 'desc' => 'Suivez un enfant ou un parent depuis votre propre compte, en tant que tuteur.'],
            'appointments' => ['title' => 'Rendez-vous', 'desc' => 'Réservez une visite dans une structure et suivez vos rendez-vous à venir.'],
            'finders' => ['title' => 'Trouver médicaments et sang', 'desc' => 'Cherchez un médicament en stock ou la disponibilité du sang près de chez vous.'],
        ],
    ],

    'verify' => [
        'eyebrow' => 'Vérifier le téléchargement',
        'title' => "Vérifiez que le fichier est l'original",
        'sub' => "L'empreinte SHA-256 du fichier officiel figure ci-dessous. Si la vôtre est identique, le fichier est exactement celui que nous avons publié.",
        'label' => 'SHA-256',
        'copy' => 'Copier',
        'copied' => 'Copié',
        'how_title' => 'Comment vérifier',
        'how_windows' => 'Windows (PowerShell) : Get-FileHash opescare.apk -Algorithm SHA256',
        'how_mac' => 'macOS ou Linux : shasum -a 256 opescare.apk',
        'how_android' => 'Sur Android, un gestionnaire de fichiers ou une application de somme de contrôle affichant le SHA-256 convient aussi.',
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Questions fréquentes',
        'categories' => [
            [
                'items' => [
                    ['q' => 'Existe-t-il une application iPhone ?', 'a' => "Pas encore. Sur iPhone, ouvrez l'application web OpesCare dans votre navigateur : elle fait la même chose, sans installation."],
                    ['q' => "Est-il sûr d'installer une application hors du Play Store ?", 'a' => "Ce fichier est publié par OPES Health Systems sur opescare.com et servi en HTTPS. Comparez son empreinte SHA-256 avec celle de cette page pour vous assurer qu'il n'a pas été modifié."],
                    ['q' => 'Comment obtenir les mises à jour ?', 'a' => "L'application vérifie au démarrage. Quand une version plus récente est requise, elle vous le signale et vous renvoie ici pour la télécharger."],
                    ['q' => "L'application est-elle payante ?", 'a' => "Non. L'application est gratuite à télécharger et à utiliser."],
                ],
            ],
        ],
    ],
];
