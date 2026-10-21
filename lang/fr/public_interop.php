<?php

// Page publique /interoperability — voir lang/en/public_interop.php.
return [
    'meta_title' => 'Interopérabilité des soins au Cameroun',
    'meta_desc' => 'Comment OpesCare permet aux cliniques, hôpitaux, laboratoires et pharmacies du Cameroun de partager ce dont les soins ont besoin grâce à un seul Health ID, avec le consentement du patient et un journal d’accès qu’il peut lire.',
    'breadcrumb' => 'Interopérabilité',

    'hero' => [
        'kicker' => 'Interopérabilité',
        'title_1' => 'Le dossier suit le patient,',
        'title_2' => 'seulement avec son consentement.',
        'lead' => 'L’interopérabilité, c’est la capacité d’une clinique, d’un hôpital, d’un laboratoire et d’une pharmacie à partager ce dont les soins d’un patient ont besoin, à propos de la même personne, sans lettres papier, appels ni examens répétés. OpesCare le fait grâce au Health ID, au consentement du patient et à un journal d’accès sur lequel chaque partie peut compter.',
        'cta_register' => 'Inscrire votre structure',
        'cta_solutions' => 'Voir les systèmes',
    ],

    'net' => [
        'label' => 'Structures reliées par la couche OpesCare',
        'core' => 'Couche OpesCare',
        'core_sub' => 'Health ID · consentement · journal',
        'hospital' => 'Hôpital',
        'hospital_sub' => 'Ouvre ce qui est accordé',
        'clinic' => 'Clinique',
        'clinic_sub' => 'Envoie une référence',
        'lab' => 'Laboratoire',
        'lab_sub' => 'Renvoie les résultats',
        'pharmacy' => 'Pharmacie',
        'pharmacy_sub' => 'Délivre l’ordonnance',
    ],

    'what' => [
        'eyebrow' => 'De quoi s’agit-il',
        'title' => 'L’interopérabilité, en termes simples.',
        'sub' => 'Aujourd’hui, un patient qui passe d’une structure à l’autre porte souvent son historique sur papier, ou pas du tout. L’interopérabilité permet à des structures différentes d’échanger des informations sur le même patient d’une manière à laquelle chacune peut se fier.',
        'b1_title' => 'Un patient, un identifiant',
        'b1_desc' => 'Chaque structure enregistre les soins sous le même Health ID (CM-HID) : une référence ou un résultat atteint la bonne personne.',
        'b2_title' => 'Partagé à dessein, pas par défaut',
        'b2_desc' => 'L’information circule lorsqu’il existe une relation de soins ou le consentement du patient, et seulement dans ces limites.',
        'b3_title' => 'Chaque ouverture est enregistrée',
        'b3_desc' => 'Chaque fois qu’un clinicien ouvre une partie d’un dossier, une entrée est écrite dans un journal d’accès que le patient lit dans son application.',
        'panel_label' => 'Avant et après l’interopérabilité',
        'panel_title' => 'Ce qui change',
        'row1' => 'Lettre de référence manuscrite',
        'row1_pill' => 'Référence sur le Health ID',
        'row2' => 'Résultats perdus entre structures',
        'row2_pill' => 'Classés sur le même dossier',
        'row3' => 'Aucune idée de qui a consulté',
        'row3_pill' => 'Journal dans l’application',
    ],

    'flow' => [
        'eyebrow' => 'Comment ça marche',
        'title' => 'Une référence, étape par étape.',
        'sub' => 'Un patient est référé d’une clinique vers un hôpital, puis un résultat de laboratoire revient. Voici ce qui se passe à chaque étape.',
        'label' => 'Le parcours d’une référence',
        'pause' => 'Pause',
        'play' => 'Lecture',
        'prev' => 'Étape précédente',
        'next' => 'Étape suivante',
        'step_of' => 'Étape :n sur :total',
        'steps' => [
            ['who' => 'Clinique', 'title' => 'La clinique réfère', 'desc' => 'Un clinicien crée une référence vers un hôpital depuis le dossier du patient. La structure qui réfère est tirée de la session connectée du clinicien, jamais d’un champ de formulaire.', 'chip' => 'Référence envoyée'],
            ['who' => 'Patient', 'title' => 'Le patient approuve', 'desc' => 'L’hôpital demande l’accès avec le Health ID exact. La demande apparaît dans l’application du patient, qui l’approuve ou la refuse. Une approbation devient une autorisation de consentement pour cette structure, avec une date de fin, que le patient peut retirer.', 'chip' => 'Autorisation de consentement'],
            ['who' => 'Couche OpesCare', 'title' => 'OpesCare vérifie', 'desc' => 'Avant l’ouverture de toute partie du dossier, OpesCare cherche une relation de soins : un rendez-vous, l’équipe soignante, le clinicien traitant, un épisode récent dans cette structure, ou une autorisation de consentement. Pas de relation, pas de dossier.', 'chip' => 'Motif enregistré'],
            ['who' => 'Hôpital', 'title' => 'L’hôpital ouvre ce qui est accordé', 'desc' => 'Lorsque le patient garde son dossier derrière le consentement, comme l’application le fait par défaut, une relation seule n’ouvre qu’un aperçu : identité, allergies et médicaments en cours. Le dossier complet exige l’autorisation du patient.', 'chip' => 'Aperçu ou dossier complet'],
            ['who' => 'Laboratoire', 'title' => 'Le résultat revient', 'desc' => 'Le laboratoire classe le résultat sous le même Health ID. Le système propre d’un laboratoire peut l’envoyer en FHIR R4 (DiagnosticReport ou Observation), accepté seulement si le patient a accordé l’accès à ce laboratoire.', 'chip' => 'Classé sur le Health ID'],
            ['who' => 'Les deux parties', 'title' => 'Chaque partie garde le journal', 'desc' => 'Chaque section ouverte écrit une entrée au journal d’accès : qui, depuis quelle structure et sur quel motif. Le patient la lit dans l’application, et l’entrée reste dans la piste d’audit OpesCare.', 'chip' => 'Journal d’accès'],
        ],
    ],

    'how' => [
        'eyebrow' => 'Comment OpesCare le rend possible',
        'title' => 'Les briques derrière chaque échange.',
        'sub' => 'Chacune fait partie d’OpesCare aujourd’hui.',
        'cards' => [
            'id' => ['title' => 'Un seul Health ID', 'desc' => 'Un identifiant par personne, porté sur une carte avec QR code et dans l’application. Les structures demandent un dossier par le Health ID exact, jamais en parcourant une liste.'],
            'gate' => ['title' => 'Cinq vérifications de relation de soins', 'desc' => 'Rendez-vous, équipe soignante, clinicien traitant, épisode récent dans la même structure, ou autorisation de consentement. Une seule règle, appliquée à chaque lecture par un clinicien.'],
            'consent' => ['title' => 'Demandes et autorisations de consentement', 'desc' => 'Une structure demande ; le patient approuve ou refuse dans l’application. Les autorisations sont par structure, ont une date de fin et peuvent être retirées.'],
            'log' => ['title' => 'Un journal d’accès que le patient lit', 'desc' => 'Chaque section ouverte est une entrée distincte, avec le motif d’ouverture.'],
            'glass' => ['title' => 'Accès d’urgence, avec des comptes à rendre', 'desc' => 'Le bris de glace exige un motif écrit, dure quatre heures, alerte le patient avec ce motif et fait l’objet d’une revue. Le patient peut désactiver l’accès d’urgence.'],
            'fhir' => ['title' => 'FHIR R4 pour les systèmes partenaires', 'desc' => 'Les systèmes partenaires peuvent envoyer des ressources DiagnosticReport, Immunization et Observation, ou un bundle de transaction, avec un jeton signé. Le patient doit déjà avoir un Health ID ; un partenaire ne peut pas en créer.'],
            'docs' => ['title' => 'Des documents vérifiables par tous', 'desc' => 'Les PDF émis par OpesCare portent un QR code qui ouvre une page de vérification de ce document.'],
            'export' => ['title' => 'La copie du patient', 'desc' => 'Un patient peut exporter son dossier depuis l’application en fichier FHIR et décider à qui le remettre.'],
        ],
    ],

    'means' => [
        'eyebrow' => 'Ce que cela change',
        'title' => 'Pour les patients et pour les structures.',
        'patients' => 'Pour les patients',
        'facilities' => 'Pour les structures',
        'p1_title' => 'Vous décidez qui voit votre dossier complet',
        'p1_desc' => 'Gardez le consentement activé : seules les structures que vous approuvez vont au-delà de l’aperçu.',
        'p2_title' => 'Vous voyez qui l’a ouvert',
        'p2_desc' => 'Votre historique d’accès liste chaque ouverture, la structure et le motif.',
        'p3_title' => 'Moins de papier, moins d’examens répétés',
        'p3_desc' => 'Références et résultats circulent sur votre Health ID entre les structures que vous fréquentez.',
        'f1_title' => 'Des références qui arrivent prêtes',
        'f1_desc' => 'Le patient, la référence et le consentement sont sur le même Health ID.',
        'f2_title' => 'Des résultats sur le bon patient',
        'f2_desc' => 'Chaque écriture est rattachée à un Health ID existant, jamais à un nom ressaisi.',
        'f3_title' => 'Un motif enregistré pour chaque accès',
        'f3_desc' => 'Quand on vous demande pourquoi votre personnel a ouvert un dossier, la réponse est déjà écrite.',
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Questions fréquentes',
        'categories' => [
            ['items' => [
                ['q' => 'Qu’est-ce que l’interopérabilité des soins ?', 'a' => 'C’est la capacité de structures de santé différentes à échanger des informations sur le même patient sous une forme que chacune peut utiliser et à laquelle elle peut se fier. Sur OpesCare, cet échange passe par le Health ID du patient, avec consentement et journal d’accès.'],
                ['q' => 'Un hôpital peut-il lire tout mon dossier sans me demander ?', 'a' => 'Pas tant que l’option « exiger le consentement pour le dossier complet » de votre application est activée, et elle l’est par défaut. Une relation de soins, comme un rendez-vous, n’ouvre alors que votre identité, vos allergies et vos médicaments en cours. Le dossier complet exige votre autorisation, et chaque ouverture est journalisée.'],
                ['q' => 'Que se passe-t-il en cas d’urgence ?', 'a' => 'Un clinicien peut utiliser l’accès d’urgence (bris de glace) en écrivant un motif. L’accès dure quatre heures, vous êtes alerté avec ce motif, et l’événement fait l’objet d’une revue. Vous pouvez désactiver l’accès d’urgence dans vos paramètres de confidentialité.'],
                ['q' => 'Le système de notre hôpital peut-il envoyer des données à OpesCare ?', 'a' => 'Oui. OpesCare accepte des ressources FHIR R4 DiagnosticReport, Immunization et Observation ainsi que des bundles de transaction venant de systèmes partenaires, avec un jeton signé limité à ces ressources. Chaque écriture doit désigner un Health ID existant et exige une autorisation de consentement active de ce patient envers votre structure.'],
                ['q' => 'Un autre système peut-il lire des dossiers patients depuis OpesCare ?', 'a' => 'Non. L’interface partenaire reçoit des données ; elle ne répond pas aux requêtes de dossiers patients. Un patient peut exporter son propre dossier depuis l’application en fichier FHIR et choisir à qui le remettre.'],
                ['q' => 'OpesCare est-il disponible en français ?', 'a' => 'Oui. Chaque page, l’application et les postes des structures fonctionnent en anglais et en français.'],
            ]],
        ],
    ],

    'cta' => [
        'title' => 'Reliez votre structure au Health ID.',
        'sub' => 'Inscrivez votre structure, faites-la vérifier, et recevez références et résultats sur le même dossier que tous les autres.',
    ],
];
