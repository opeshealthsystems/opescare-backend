<?php

// Page publique /health-id — voir lang/en/public_hid.php pour les sources dans le code.
return [
    'meta_title' => 'Le Health ID OpesCare',
    'meta_desc' => 'Tout sur le Health ID OpesCare au Cameroun : le numéro CM-HID, comment l’obtenir, la carte et son QR sécurisé, ce qu’il contient, confidentialité et consentement, journal d’accès, accès d’urgence, doublons et Index patient unique.',
    'breadcrumb' => 'Health ID',

    'hero' => [
        'kicker' => 'Health ID',
        'title_1' => 'Un seul numéro pour vos soins,',
        'title_2' => 'partout où vous êtes reçu.',
        'lead' => 'Le Health ID OpesCare est un identifiant personnel, de la forme CM-HID-XXXX-XXXX-XXXX, sous lequel chaque établissement sur OpesCare classe vos soins. Vous le portez sur une carte avec un QR sécurisé et dans l’application, et vous décidez qui voit votre dossier complet.',
        'cta_primary' => 'Obtenir mon Health ID',
        'cta_secondary' => 'Télécharger l’application',
    ],

    'net' => [
        'label' => 'Le Health ID au centre des soins du patient',
        'core' => 'Health ID',
        'core_sub' => 'CM-HID · carte · QR',
        'card' => 'Votre carte',
        'card_sub' => 'QR et code en direct',
        'desk' => 'Accueil',
        'desk_sub' => 'Vous trouve par l’ID',
        'verify' => 'Vérificateur',
        'verify_sub' => 'Confirme que c’est vous',
        'log' => 'Journal d’accès',
        'log_sub' => 'Qui a ouvert quoi',
    ],

    'what' => [
        'eyebrow' => 'Ce que c’est',
        'title' => 'Votre Health ID, simplement.',
        'sub' => 'Un Health ID est un numéro qui appartient à une seule personne. Cliniques, hôpitaux, laboratoires et pharmacies l’utilisent pour classer et retrouver vos soins, afin qu’un résultat ou une ordonnance vous parvienne à vous et non à un homonyme.',
        'b1_title' => 'Un numéro conçu pour être bien lu',
        'b1_desc' => 'CM pour le Cameroun, puis deux blocs et un bloc de contrôle final. Les caractères qui se ressemblent, comme 0 et O ou 1 et I, ne sont jamais utilisés, et le bloc de contrôle détecte une erreur de saisie.',
        'b2_title' => 'Un par personne',
        'b2_desc' => 'Chaque Health ID est unique. Avant d’en émettre un, OpesCare vérifie si la personne en a déjà un.',
        'b3_title' => 'Un identifiant, pas un mot de passe',
        'b3_desc' => 'Connaître un Health ID n’ouvre pas un dossier. La connexion demande toujours votre mot de passe, et un clinicien a toujours besoin d’une relation de soins ou de votre consentement.',
        'panel_label' => 'Un exemple de Health ID décomposé',
        'panel_title' => 'Lire un Health ID',
        'row1' => 'CM',
        'row1_pill' => 'Pays : Cameroun',
        'row2' => 'HID',
        'row2_pill' => 'Health ID',
        'row3' => 'XXXX-XXXX-XXXX',
        'row3_pill' => 'Deux blocs + un bloc de contrôle',
    ],

    'get' => [
        'eyebrow' => 'Comment l’obtenir',
        'title' => 'Trois façons d’obtenir un Health ID.',
        'sub' => 'Quelle que soit la façon de s’inscrire, la même vérification des doublons passe en premier.',
        'items' => [
            'self' => ['title' => 'Inscrivez-vous vous-même', 'desc' => 'Créez un compte dans l’application, ou sur le site puis complétez votre profil, avec votre nom et votre date de naissance. Votre Health ID est émis dès que ces informations sont saisies.'],
            'desk' => ['title' => 'À l’accueil d’un établissement', 'desc' => 'Un réceptionniste d’un établissement sur OpesCare peut vous inscrire. Si vous avez déjà un Health ID, l’accueil en est informé au lieu d’en créer un second.'],
            'family' => ['title' => 'Pour un enfant, par un tuteur', 'desc' => 'Un parent ou tuteur inscrit un enfant depuis son propre compte, même un nouveau-né. Le Health ID de l’enfant est d’abord provisoire, et le tuteur le gère via un lien familial.'],
        ],
    ],

    'card' => [
        'eyebrow' => 'La carte',
        'title' => 'Une carte que tout accueil peut vérifier.',
        'sub' => 'Votre carte Health ID est dans l’application et peut être téléchargée en PDF. Elle affiche votre nom, votre Health ID, votre sexe, votre date de naissance, votre groupe sanguin et votre photo, avec un code QR.',
        'items' => [
            'qr' => ['title' => 'Un QR sécurisé, pas vos données', 'desc' => 'Le QR contient un lien vers le vérificateur OpesCare, pas vos informations. Le code de la carte dans l’application est renouvelé chaque jour.'],
            'live' => ['title' => 'Mode présentation', 'desc' => 'À l’accueil, ouvrez le mode présentation dans l’application. Il affiche un code en direct valable 15 minutes, avec un compte à rebours, et retire le QR dès qu’il expire.'],
            'qrpage' => ['title' => 'Le vérificateur /verify/qr', 'desc' => 'Scanner le code ouvre une page qui indique vérifié, expiré, révoqué ou invalide. Un scan vérifié affiche nom, Health ID, date de naissance, sexe et groupe sanguin, et enregistre une référence.'],
            'lookup' => ['title' => 'Recherche saisie sur /verify/health-id', 'desc' => 'Le personnel peut saisir un Health ID et un motif. Le public voit une carte masquée ; le personnel connecté d’un établissement voit l’identité complète. Chaque recherche est enregistrée.'],
        ],
    ],

    'holds' => [
        'eyebrow' => 'Ce qu’il contient',
        'title' => 'Ce qui est classé sous votre Health ID.',
        'sub' => 'Votre identité sur la carte, et les soins que les établissements enregistrent.',
        'identity' => 'Identité',
        'identity_desc' => 'Nom, date de naissance, sexe, téléphone, photo et un contact d’urgence. La date de naissance et le téléphone sont stockés chiffrés.',
        'blood' => 'Groupe sanguin',
        'blood_desc' => 'Affiché sur la carte et au vérificateur, car il compte en urgence.',
        'allergies' => 'Allergies',
        'allergies_desc' => 'Font partie de l’aperçu qu’un clinicien ayant une relation de soins voit en premier.',
        'care' => 'Dossier de soins',
        'care_desc' => 'Diagnostics, ordonnances, signes vitaux, résultats de laboratoire, vaccinations et plans de soins, classés par les établissements qui vous ont soigné.',
    ],

    'flow' => [
        'eyebrow' => 'Comment ça marche',
        'title' => 'De l’inscription aux soins enregistrés.',
        'sub' => 'Une personne s’inscrit, reçoit un Health ID, puis est reçue dans un établissement. Voici ce qui se passe à chaque étape.',
        'label' => 'Le parcours du Health ID',
        'pause' => 'Pause',
        'play' => 'Lecture',
        'prev' => 'Étape précédente',
        'next' => 'Étape suivante',
        'step_of' => 'Étape :n sur :total',
        'steps' => [
            ['who' => 'Patient', 'title' => 'Inscription', 'desc' => 'La personne s’inscrit, est inscrite à l’accueil ou par un tuteur, avec son nom et sa date de naissance.', 'chip' => 'Informations saisies'],
            ['who' => 'OpesCare', 'title' => 'Vérification des doublons', 'desc' => 'OpesCare cherche un Health ID existant avec les mêmes nom et date de naissance, ou le même téléphone. S’il en existe un, l’inscription s’arrête et renvoie vers l’identifiant existant. Rien n’est fusionné sans décision humaine.', 'chip' => 'Pas de second ID'],
            ['who' => 'OpesCare', 'title' => 'Health ID émis', 'desc' => 'Un numéro CM-HID unique est généré avec son bloc de contrôle, et la carte avec son QR apparaît dans l’application.', 'chip' => 'CM-HID émis'],
            ['who' => 'Établissement', 'title' => 'Présenté et vérifié', 'desc' => 'Dans un établissement, le patient montre sa carte ou un code en direct du mode présentation. L’accueil le scanne et le vérificateur confirme l’identité.', 'chip' => 'Identité vérifiée'],
            ['who' => 'Patient', 'title' => 'Consentement', 'desc' => 'Une relation de soins, comme un rendez-vous, ouvre un aperçu. Quand le patient garde son dossier complet derrière le consentement, comme l’application le fait par défaut, l’établissement demande et le patient approuve dans l’application.', 'chip' => 'Autorisation'],
            ['who' => 'Établissement + patient', 'title' => 'Soins enregistrés', 'desc' => 'Résultats, ordonnances et notes sont classés sous le même Health ID. Chaque section ouverte écrit une entrée dans le journal d’accès que le patient consulte.', 'chip' => 'Journal d’accès'],
        ],
    ],

    'privacy' => [
        'eyebrow' => 'Confidentialité et consentement',
        'title' => 'Vous décidez qui voit votre dossier complet.',
        'sub' => 'Deux interrupteurs dans vos paramètres de confidentialité, et un journal de tout ce qui a été ouvert.',
        'items' => [
            'consent' => ['title' => 'Exiger mon consentement pour le dossier complet', 'desc' => 'Activé par défaut. Un clinicien ayant une relation de soins ne voit alors que votre identité, vos allergies et vos traitements en cours ; le dossier complet demande votre approbation dans l’application.'],
            'glass' => ['title' => 'Autoriser l’accès d’urgence', 'desc' => 'Activé par défaut. En urgence, un clinicien peut forcer l’accès avec un motif écrit ; l’accès dure quatre heures, vous êtes alerté avec le motif, et il est examiné. Désactivez-le et l’accès d’urgence est refusé.'],
            'log' => ['title' => 'Le journal d’accès', 'desc' => 'Chaque section de votre dossier ouverte est une entrée : qui, depuis quel établissement, et sur quelle base. Vous le lisez dans l’application.'],
            'grants' => ['title' => 'Des autorisations révocables', 'desc' => 'Chaque autorisation de consentement concerne un seul établissement, a une date de fin, et peut être retirée par vous à tout moment.'],
        ],
    ],

    'layer' => [
        'eyebrow' => 'La couche d’interopérabilité',
        'title' => 'Comment le Health ID relie les établissements.',
        'sub' => 'Le Health ID permet à une clinique, un hôpital, un laboratoire et une pharmacie de travailler sur le même patient. La couche qui l’entoure a quatre parties.',
        'items' => [
            'gate' => ['title' => 'Un contrôle du consentement à chaque lecture', 'desc' => 'Avant qu’un clinicien ouvre un dossier, OpesCare cherche une base : rendez-vous, équipe soignante, clinicien traitant, épisode récent dans l’établissement, ou autorisation de consentement. Pas de base, pas de dossier.'],
            'scope' => ['title' => 'Des lectures limitées', 'desc' => 'Ce qui s’ouvre dépend de la base et de vos paramètres : un aperçu de l’identité, des allergies et des traitements en cours, ou le dossier complet si vous l’avez autorisé.'],
            'fhir' => ['title' => 'Des données FHIR R4 entrantes', 'desc' => 'Les systèmes partenaires peuvent envoyer des ressources DiagnosticReport, Immunization et Observation, ou un bundle de transaction, avec un jeton signé. Chacune doit désigner un Health ID existant et nécessite votre autorisation à cet établissement. Les partenaires ne peuvent ni créer de Health ID ni lire des dossiers.'],
            'log' => ['title' => 'La journalisation des accès', 'desc' => 'Chaque ouverture est écrite dans le journal d’accès avec sa base, pour vous dans l’application et pour la piste d’audit OpesCare.'],
        ],
        'more' => 'Comment fonctionne l’interopérabilité',
    ],

    'mpi' => [
        'eyebrow' => 'Doublons et Index patient unique',
        'title' => 'Une personne, un Health ID, même après une erreur.',
        'sub' => 'Il arrive qu’une même personne soit inscrite deux fois, par exemple avec un nom mal orthographié. L’Index patient unique repère les paires probables de façon planifiée, un agent des dossiers les examine, et une fusion peut être annulée. Un Health ID retiré continue de fonctionner : il mène à celui qui est conservé.',
        'more' => 'Comment fonctionne l’Index patient unique',
    ],

    'lost' => [
        'eyebrow' => 'Carte perdue ou mot de passe oublié',
        'title' => 'Votre Health ID ne change pas.',
        'items' => [
            'card' => ['title' => 'Carte perdue', 'desc' => 'Connectez-vous et téléchargez à nouveau la carte depuis l’application ou le portail patient. Le numéro reste le même.'],
            'signin' => ['title' => 'Connexion avec votre Health ID', 'desc' => 'Vous pouvez vous connecter avec votre Health ID au lieu de votre e-mail ou téléphone. Il désigne seulement le compte ; votre mot de passe est toujours demandé.'],
            'password' => ['title' => 'Mot de passe oublié', 'desc' => 'Réinitialisez-le depuis la page de connexion. Si vous essayez de vous réinscrire, OpesCare vous indique qu’un Health ID existe déjà au lieu d’en émettre un second.'],
        ],
    ],

    'family' => [
        'eyebrow' => 'Famille',
        'title' => 'Des Health ID pour vos proches.',
        'sub' => 'Un tuteur peut inscrire un enfant, ou inviter un adulte qui a déjà un Health ID, via un lien familial. La personne invitée accepte ou refuse. Le tuteur bascule ensuite vers la personne à charge dans l’application pour voir sa carte, ses dossiers et ses rendez-vous.',
        'cta' => 'Inscrire un enfant',
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Questions fréquentes',
        'categories' => [
            ['items' => [
                ['q' => 'Qu’est-ce que le Health ID OpesCare ?', 'a' => 'C’est un identifiant personnel unique de la forme CM-HID-XXXX-XXXX-XXXX. Chaque établissement sur OpesCare classe vos soins sous ce numéro, et vous le portez sur une carte avec un QR sécurisé et dans l’application.'],
                ['q' => 'Comment obtenir un Health ID ?', 'a' => 'Inscrivez-vous sur le site ou dans l’application, faites-vous inscrire à l’accueil d’un établissement sur OpesCare, ou faites-vous inscrire par un parent ou tuteur si vous êtes un enfant.'],
                ['q' => 'Puis-je me retrouver avec deux Health ID ?', 'a' => 'OpesCare vérifie le nom et la date de naissance, ainsi que le téléphone, avant d’en émettre un, et refuse un second identifiant en cas de correspondance. Si deux dossiers d’une même personne passent quand même, l’Index patient unique les repère et un agent des dossiers les fusionne, de façon réversible. Rien n’est fusionné sans qu’une personne décide.'],
                ['q' => 'Quelqu’un peut-il lire mon dossier s’il connaît mon Health ID ?', 'a' => 'Non. Le Health ID ne fait que vous identifier. Un clinicien a besoin d’une relation de soins ou de votre consentement, et avec l’interrupteur de consentement activé, le dossier complet demande votre approbation. Chaque ouverture est journalisée.'],
                ['q' => 'Que montre le QR de ma carte ?', 'a' => 'Il ouvre le vérificateur OpesCare, qui confirme votre nom, votre Health ID, votre date de naissance, votre sexe et votre groupe sanguin. Le QR ne contient aucune donnée personnelle, et le code en direct du mode présentation expire après 15 minutes.'],
                ['q' => 'J’ai perdu ma carte. Que faire ?', 'a' => 'Votre Health ID ne change pas. Connectez-vous, avec votre Health ID si vous le souhaitez, et téléchargez à nouveau la carte.'],
                ['q' => 'OpesCare est-il disponible en français ?', 'a' => 'Oui. Chaque page, l’application et les bureaux des établissements fonctionnent en français et en anglais.'],
            ]],
        ],
    ],

    'cta' => [
        'title' => 'Obtenez votre Health ID dès aujourd’hui.',
        'sub' => 'Créez votre Health ID en quelques minutes, puis portez-le dans l’application.',
        'primary' => 'Obtenir mon Health ID',
        'secondary' => 'Télécharger l’application',
    ],
];
