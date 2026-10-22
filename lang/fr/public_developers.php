<?php

// Page publique /developers (resources/views/public/developers.blade.php).
// Chaque affirmation correspond au code : routes/api.php (v1/connect, v1/care-map),
// routes/fhir.php + App\Services\Fhir\FhirIngestGuard, routes/web.php
// (/signup/developer, portail /developer, demandes de production), api_plans.
// Ingestion seulement : pas de lecture FHIR, pas de serveur FHIR sortant, pas de HL7 v2.
return [
    'meta_title' => 'API OpesCare pour les développeurs',
    'meta_desc' => "Construisez sur OpesCare au Cameroun : l'API Connect, l'ingestion FHIR R4 sur un Health ID existant avec le consentement du patient, la synchronisation des stocks, l'API de la carte des soins, les webhooks et un bac à sable.",
    'breadcrumb' => 'Développeurs',

    'hero' => [
        'kicker' => 'Développeurs',
        'title_1' => 'Construisez sur OpesCare,',
        'title_2' => 'avec le consentement intégré.',
        'lead' => "Connectez un système hospitalier, un middleware de laboratoire ou un outil de stock de pharmacie à OpesCare. Chaque écriture clinique est liée à un Health ID existant, à l'établissement de votre jeton et au consentement du patient.",
        'cta_access' => "Demander l'accès développeur",
        'cta_docs' => 'Lire la documentation',
        'cta_portal' => 'Se connecter au portail développeur',
        'panel_label' => "Points d'accès disponibles aujourd'hui",
        'panel_title' => 'opescare.com/api',
        'row_token' => 'Jeton client-credentials',
        'row_fhir' => 'Ingestion FHIR R4',
        'row_stock' => 'Synchronisation du stock pharmacie',
        'row_map' => 'Répertoire des établissements',
    ],

    'offer' => [
        'eyebrow' => "Disponible aujourd'hui",
        'title' => "Ce que propose l'API",
        'sub' => "Uniquement ce qui tourne aujourd'hui sur opescare.com. Chaque élément est couvert par la documentation.",
        'cards' => [
            'auth' => ['title' => "Jetons de l'API Connect", 'desc' => "Échangez votre identifiant client et votre secret contre un jeton porteur de courte durée, et inspectez-le. L'établissement pour lequel agit votre application vient du jeton, jamais de la requête."],
            'fhir' => ['title' => 'Ingestion FHIR R4', 'desc' => "Envoyez des ressources Observation, DiagnosticReport et Immunization, ou un Bundle de transaction qui les regroupe, dans le dossier d'un Health ID existant."],
            'stock' => ['title' => 'Synchronisation des stocks', 'desc' => 'Envoyez le stock de pharmacie et de sang depuis votre propre système pour que les patients qui cherchent voient ce que vous avez.'],
            'map' => ['title' => 'API de la carte des soins', 'desc' => 'Interrogez le répertoire national des établissements : établissements proches, médicaments, examens de laboratoire et disponibilité du sang.'],
            'widget' => ['title' => 'Widget intégrable', 'desc' => 'Ouvrez une session de widget depuis votre serveur et intégrez la recherche OpesCare à votre propre site.'],
            'webhooks' => ['title' => 'Webhooks', 'desc' => 'Recevez des événements sur votre point de terminaison et consultez chaque tentative de livraison depuis le portail développeur.'],
            'sandbox' => ['title' => 'Bac à sable', 'desc' => 'Chaque nouvelle application reçoit des identifiants de bac à sable, pour construire et tester avant de toucher à de vrais patients.'],
            'docs' => ['title' => 'Documentation', 'desc' => "Authentification, référence de l'API, notes sur le SDK, webhooks, codes d'erreur et bac d'essai, au même endroit."],
        ],
    ],

    'fhir' => [
        'eyebrow' => 'FHIR R4',
        'title' => 'Ingestion FHIR R4 entrante',
        'sub' => 'Une porte pour que les systèmes partenaires écrivent des données cliniques dans OpesCare, gardée de trois côtés.',
        'b1_title' => 'Jeton porteur avec portée',
        'b1_desc' => 'Chaque ressource exige sa portée système SMART : system/Observation.write, system/DiagnosticReport.write ou system/Immunization.write. Dans un Bundle de transaction, chaque entrée est vérifiée contre vos portées.',
        'b2_title' => 'Un Health ID existant',
        'b2_desc' => 'Le sujet est identifié par le système https://opescare.com/fhir/health-id et un Health ID comme CM-HID-XXXX-XXXX-XXXX. Un partenaire ne peut jamais créer de patient : les identifiants inconnus sont refusés.',
        'b3_title' => 'Le consentement du patient',
        'b3_desc' => "Le patient doit avoir accordé un consentement actif à votre établissement. Sans lui, l'écriture est refusée avec un OperationOutcome, et rien n'est enregistré.",
        'b4_title' => 'Ingestion seulement',
        'b4_desc' => "Cette interface écrit ; elle ne sert pas de lectures. Il n'y a ni serveur FHIR sortant ni interface HL7 v2.",
        'panel_label' => "Points d'accès FHIR R4",
        'panel_title' => '/api/fhir/R4',
        'row_metadata' => 'CapabilityStatement',
        'row_observation' => 'Observation',
        'row_report' => 'DiagnosticReport',
        'row_immunization' => 'Immunization',
        'row_bundle' => 'Bundle de transaction',
    ],

    'steps' => [
        'eyebrow' => "Obtenir l'accès",
        'title' => 'De la demande à la production',
        'request_title' => "Demander l'accès",
        'request_desc' => "Dites-nous qui vous êtes et ce que vous voulez connecter, dans le formulaire d'inscription développeur.",
        'review_title' => 'Examen',
        'review_desc' => "L'équipe OpesCare examine votre demande et revient vers vous.",
        'portal_title' => 'Portail développeur',
        'portal_desc' => "Connectez-vous, acceptez les conditions de l'API et ouvrez votre compte développeur.",
        'keys_title' => 'Créer une application',
        'keys_desc' => 'Enregistrez une application pour obtenir son identifiant client et son secret. Renouvelez le secret quand vous le souhaitez.',
        'sandbox_title' => 'Construire dans le bac à sable',
        'sandbox_desc' => 'Testez avec les identifiants du bac à sable et suivez les livraisons de vos webhooks.',
        'prod_title' => 'Passer en production',
        'prod_desc' => "Soumettez une demande de production. Une fois approuvée par un administrateur OpesCare, votre application obtient l'accès production.",
    ],

    'plans' => [
        'eyebrow' => 'Formules',
        'title' => "Formules de l'API",
        'sub' => 'Prix en FCFA (XAF), facturés chaque mois via MTN Mobile Money ou Orange Money. Le bac à sable est gratuit.',
    ],

    'cta' => [
        'title' => 'Prêt à vous connecter ?',
        'sub' => "Demandez l'accès, ou lisez d'abord la documentation.",
        'interop' => "Comment fonctionne l'interopérabilité",
    ],
];
