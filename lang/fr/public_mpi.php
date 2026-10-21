<?php

// Page publique /master-patient-index — voir lang/en/public_mpi.php.
return [
    'meta_title' => 'Index patient unique : une personne, un Health ID',
    'meta_desc' => 'Pourquoi un même patient se retrouve avec plusieurs dossiers au Cameroun, comment OpesCare repère les doublons probables entre structures, et comment une personne les examine et les fusionne en un seul Health ID, de façon réversible.',
    'breadcrumb' => 'Index patient unique',

    'hero' => [
        'kicker' => 'Index patient unique (MPI)',
        'title_1' => 'Une personne.',
        'title_2' => 'Un Health ID.',
        'lead' => 'Un même patient est souvent enregistré dans un hôpital, une clinique et un laboratoire, sous des noms légèrement différents. L’index patient unique d’OpesCare retrouve ces dossiers, les soumet à l’examen d’une personne, et les réunit en un seul Health ID d’une manière qui peut être annulée.',
        'cta_register' => 'Inscrire votre structure',
        'cta_solutions' => 'Voir les systèmes',
    ],

    'vis' => [
        'label' => 'Trois dossiers d’exemple de structures différentes réunis en un seul patient',
        'sample' => 'Dossiers d’exemple',
        'rec1_src' => 'Hôpital · 2019',
        'rec2_src' => 'Clinique · 2023',
        'rec3_src' => 'Laboratoire · 2026',
        'one' => 'Un seul patient',
        'linked' => 'Dossiers liés',
        'facilities' => 'Structures',
        'reviewed' => 'Examiné par',
        'reviewed_v' => 'Une personne',
        'merge' => 'Fusion',
        'merge_v' => 'Réversible',
    ],

    'what' => [
        'eyebrow' => 'De quoi s’agit-il',
        'title' => 'Ce que fait un index patient unique.',
        'sub' => 'Un index patient unique (MPI) est le registre qui sait quels dossiers appartiennent au même être humain. Sans lui, l’historique d’un patient est éclaté entre plusieurs fichiers, et un clinicien n’en voit qu’une partie : une allergie notée dans une structure manque dans une autre.',
        'causes_title' => 'Pourquoi les doublons apparaissent au Cameroun',
        'causes' => [
            'order' => ['title' => 'L’ordre des noms', 'desc' => 'Le nom de famille en premier à un guichet, en dernier à un autre : « DONGMO Amara » et « Amara Dongmo ».'],
            'spelling' => ['title' => 'Orthographe, accents et majuscules', 'desc' => '« Nshomé » et « Nshome », des majuscules, des espaces en trop, une initiale ajoutée ou oubliée.'],
            'dob' => ['title' => 'Dates de naissance absentes ou estimées', 'desc' => 'Beaucoup d’adultes ne connaissent que leur année de naissance : le dossier porte une estimation, ou rien.'],
            'facilities' => ['title' => 'Plusieurs structures, plusieurs dossiers', 'desc' => 'Chaque hôpital, clinique et laboratoire enregistre à nouveau le patient sous son propre numéro.'],
            'phones' => ['title' => 'Plus d’un numéro de téléphone', 'desc' => 'Deux cartes SIM sont courantes : une même personne peut donner un numéro différent à chaque visite.'],
        ],
    ],

    'match' => [
        'eyebrow' => 'Comment fonctionne le rapprochement',
        'title' => 'Des indices pour, des indices contre.',
        'sub' => 'Le rapprochement est prudent par conception : mieux vaut demander à une personne que réunir deux personnes différentes.',
        'items' => [
            'check' => ['title' => 'Une vérification à chaque inscription', 'desc' => 'Chaque parcours qui crée un patient pose d’abord une question : cette personne a-t-elle déjà un Health ID ? Il compare le numéro national d’identité, le numéro de téléphone (sous forme d’empreinte chiffrée, jamais en clair) et le nom avec la date de naissance, sans tenir compte des majuscules, des espaces ni du format de date. Une correspondance est renvoyée à la personne qui inscrit ; rien n’est fusionné.'],
            'blocking' => ['title' => 'Des candidats venant de toutes les structures', 'desc' => 'Les analyses comparent les dossiers qui partagent un numéro de téléphone, un numéro national d’identité, les mêmes prénom et nom une fois les accents retirés, ou un identifiant externe, dans toutes les structures sur OpesCare.'],
            'score' => ['title' => 'Un score avec ses raisons', 'desc' => 'Un accord sur le nom, le téléphone, la date de naissance, le sexe, le numéro national ou un identifiant augmente le score. Un sexe, une date de naissance ou un numéro national différents le font beaucoup baisser ; un téléphone différent, seulement un peu. La personne qui examine voit les raisons, par exemple « nom identique, téléphones différents ».'],
            'limit' => ['title' => 'Ce qu’il ne repère pas seul', 'desc' => 'Un ordre des noms inversé n’est pas rapproché sur le nom seul. Ces paires apparaissent grâce à un téléphone, un numéro national ou un identifiant partagé, ou une personne qui examine soumet la paire à la main.'],
            'scans' => ['title' => 'Analyses chaque nuit et chaque semaine', 'desc' => 'Chaque nuit, les dossiers créés ou modifiés récemment sont comparés à tout le registre ; une fois par semaine, un passage plus large a lieu. Une paire dont les dossiers ont changé est réévaluée.'],
        ],
    ],

    'flow' => [
        'eyebrow' => 'Comment ça marche',
        'title' => 'De plusieurs dossiers à un seul Health ID.',
        'sub' => 'Le chemin de chaque doublon, de la détection à une fusion qui peut être annulée.',
        'label' => 'Le parcours d’une fusion',
        'pause' => 'Pause',
        'play' => 'Lecture',
        'prev' => 'Étape précédente',
        'next' => 'Étape suivante',
        'step_of' => 'Étape :n sur :total',
        'steps' => [
            ['who' => 'Structures', 'title' => 'Plusieurs dossiers', 'desc' => 'La même personne est enregistrée dans un hôpital, une clinique et un laboratoire, avec de petites différences de nom, de date de naissance ou de téléphone.', 'chip' => 'Trois dossiers'],
            ['who' => 'OpesCare', 'title' => 'Une paire candidate est trouvée', 'desc' => 'Une analyse trouve deux dossiers qui partagent une clé et leur donne un score, ou une personne soumet la paire à la main. Les paires sous le seuil ne sont pas mises en file.', 'chip' => 'Score et raisons'],
            ['who' => 'Examinateur', 'title' => 'Examinés côte à côte', 'desc' => 'Sur le poste d’examen OpesCare, une personne compare les deux dossiers et leurs indices, et décide : la même personne, ou deux personnes.', 'chip' => 'Décision humaine'],
            ['who' => 'Examinateur', 'title' => 'Fusionnés en un seul Health ID', 'desc' => 'La personne choisit le dossier qui est conservé et écrit un motif. Tous les éléments liés y sont rattachés ; l’autre Health ID devient un alias qui ouvre toujours le dossier. Les informations du dossier conservé ne sont jamais écrasées, seuls les champs vides sont complétés.', 'chip' => 'Un seul Health ID'],
            ['who' => 'Audit', 'title' => 'Réversible et tracé', 'desc' => 'Chaque fusion enregistre qui, quand et pourquoi, et peut être annulée exactement, de la plus récente à la plus ancienne, avec un motif écrit. Elle ne peut plus l’être une fois un dossier supprimé à la demande du patient.', 'chip' => 'Annulation possible'],
        ],
    ],

    'how' => [
        'eyebrow' => 'Comment OpesCare le rend possible',
        'title' => 'Conçu pour que rien ne fusionne tout seul.',
        'sub' => 'Chacune de ces briques fait partie d’OpesCare aujourd’hui.',
        'cards' => [
            'check' => ['title' => 'Une seule vérification d’identité', 'desc' => 'La même vérification de doublon s’exécute quand un patient s’inscrit dans l’application, ajoute un membre de sa famille ou complète son profil, et quand l’accueil d’une structure inscrit un patient.'],
            'cross' => ['title' => 'Dans toutes les structures', 'desc' => 'La détection regarde tout le registre, pas une structure à la fois, en commençant par les dossiers les plus récents.'],
            'reasons' => ['title' => 'Des scores qui s’expliquent', 'desc' => 'Chaque candidat porte son score, un niveau (déterministe, probable ou possible) et les raisons qui le justifient.'],
            'human' => ['title' => 'Une personne décide, à chaque fois', 'desc' => 'Même une paire qui partage un numéro national attend une personne. Aucune fusion sans dossier conservé choisi et motif écrit.'],
            'merge' => ['title' => 'Des fusions complètes et réversibles', 'desc' => 'Chaque table qui pointe vers un patient est trouvée au moment de la fusion : tout est déplacé, et la trace de ce qui a bougé rend l’annulation exacte.'],
            'alias' => ['title' => 'Les anciens Health ID restent valables', 'desc' => 'Une carte émise sous le Health ID retiré ouvre toujours le dossier du patient, et les recherches suivent la fusion.'],
        ],
    ],

    'means' => [
        'eyebrow' => 'Ce que cela change',
        'title' => 'Pour les patients et pour les structures.',
        'patients' => 'Pour les patients',
        'facilities' => 'Pour les structures',
        'p1_title' => 'Un seul historique, où que vous ayez été vu',
        'p1_desc' => 'Allergies, résultats et ordonnances de chaque structure sont sur un seul Health ID.',
        'p2_title' => 'Votre ancienne carte fonctionne toujours',
        'p2_desc' => 'Si deux de vos dossiers sont réunis, le premier Health ID reçu ouvre toujours votre dossier.',
        'p3_title' => 'Personne n’est fusionné par un ordinateur seul',
        'p3_desc' => 'Une personne vérifie chaque paire, et une erreur peut être annulée.',
        'f1_title' => 'Moins de dossiers en double',
        'f1_desc' => 'Un patient qui revient est reconnu à l’inscription au lieu d’être inscrit une nouvelle fois.',
        'f2_title' => 'Des résultats sur la bonne personne',
        'f2_desc' => 'Résultats et références suivent la fusion vers le dossier conservé.',
        'f3_title' => 'Les décisions d’identité tracées',
        'f3_desc' => 'Chaque fusion, rejet et annulation est journalisé avec son auteur et son motif.',
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Questions fréquentes',
        'categories' => [
            ['items' => [
                ['q' => 'Qu’est-ce qu’un index patient unique ?', 'a' => 'C’est le registre qui sait quels dossiers patients appartiennent à la même personne, pour qu’une personne ait un seul dossier, ici un seul Health ID, dans toutes les structures.'],
                ['q' => 'OpesCare fusionne-t-il les dossiers automatiquement ?', 'a' => 'Non. Les analyses ne font que proposer des paires. Chaque fusion est faite par une personne sur le poste d’examen OpesCare, qui choisit le dossier conservé et écrit un motif, même lorsque les deux dossiers partagent un numéro national.'],
                ['q' => 'Et si deux personnes différentes sont fusionnées par erreur ?', 'a' => 'La fusion peut être annulée. OpesCare garde la trace de chaque ligne déplacée : l’annulation remet exactement ces lignes en place. Les fusions s’annulent de la plus récente à la plus ancienne, et une annulation exige aussi un motif écrit.'],
                ['q' => 'Mon nom est écrit différemment dans deux hôpitaux. Aurai-je deux Health ID ?', 'a' => 'L’inscription cherche un Health ID existant par numéro national, numéro de téléphone, et nom avec date de naissance. Si deux dossiers sont tout de même créés, les analyses peuvent les rapprocher par un téléphone, un numéro national ou un identifiant partagé, et une personne peut soumettre une paire à la main lorsque les noms sont écrits dans un ordre différent.'],
                ['q' => 'Mon ancienne carte Health ID fonctionnera-t-elle après une fusion ?', 'a' => 'Oui. Le Health ID retiré devient un alias du Health ID conservé : le scanner ou le saisir ouvre votre dossier.'],
                ['q' => 'Qui voit le poste d’examen ?', 'a' => 'Seuls les administrateurs de la plateforme OpesCare. Le personnel des structures n’y voit pas les patients des autres structures.'],
            ]],
        ],
    ],

    'cta' => [
        'title' => 'Réunissez vos patients sur un seul Health ID.',
        'sub' => 'Inscrivez votre structure : chaque patient que vous recevez est comparé au registre national avant la création d’un nouveau Health ID.',
    ],
];
