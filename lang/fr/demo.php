<?php

return [
    'title' => 'Accès démo OpesCare',
    'subtitle' => 'Explorez OpesCare avec des comptes de démonstration sécurisés et des données de santé fictives.',
    'header' => 'Explorez OpesCare avec l’accès démo',
    'header_subtitle' => 'Utilisez des comptes de démonstration sécurisés pour voir comment les patients, hôpitaux, cliniques, pharmacies, laboratoires, assureurs, équipes de santé publique, développeurs et administrateurs travaillent ensemble sur OpesCare.',
    
    'warning_banner_title' => 'ENVIRONNEMENT DE DÉMONSTRATION',
    'warning_banner_text' => 'Tous les comptes et dossiers sur cette page utilisent des données fictives pour la démonstration. Ne saisissez aucune information réelle de patient, médicale, d’assurance, d’établissement ou de gouvernement.',
    
    'select_role' => 'Sélectionnez un rôle ci-dessous pour ouvrir le tableau de bord correspondant et tester le parcours.',
    'try_flow' => 'Suivez des parcours guidés pour comprendre le consentement, les dossiers patients, la disponibilité des médicaments, la disponibilité du sang, les demandes d’assurance, les rapports de santé publique et la synchronisation API.',
    'footer_note' => 'Les données de démonstration sont réinitialisées régulièrement. Les modifications effectuées en mode démo servent uniquement aux tests.',

    'roles' => [
        'patient' => 'Démo Patient',
        'guardian' => 'Démo Tuteur',
        'doctor' => 'Démo Docteur',
        'multi_hospital_doctor' => 'Démo Docteur Multi-Hôpitaux',
        'nurse' => 'Démo Infirmière',
        'hospital_admin' => 'Démo Hôpital',
        'clinic_admin' => 'Démo Clinique',
        'pharmacy' => 'Démo Pharmacie',
        'laboratory' => 'Démo Laboratoire',
        'insurance' => 'Démo Assurance',
        'public_health' => 'Démo Santé publique',
        'developer' => 'Démo Développeur',
    ],

    'buttons' => [
        'launch_demo' => 'Lancer la démo',
        'view_guide' => 'Voir le guide de démonstration',
        'try_flow' => 'Tester un parcours',
        'login_as' => 'Se connecter comme utilisateur démo',
        'copy_password' => 'Copier le mot de passe',
    ],

    'labels' => [
        'demo_data' => 'Données fictives',
        'not_real_info' => 'Informations patient non réelles',
        'known_limitations' => 'Limites connues de la démo',
        'session_expires_soon' => 'La session expirera bientôt',
        'demo_reset_notice' => 'Les données de démonstration ont été réinitialisées. Veuillez démarrer une nouvelle session de démonstration.',
        'what_is_simulated' => 'Ce qui est simulé dans cette démo',
    ],

    'limitations' => [
        'sms' => 'Les SMS sont simulés.',
        'email' => 'Les e-mails sont simulés.',
        'payments' => 'Les paiements sont simulés.',
        'insurance' => 'Les soumissions d’assurance sont simulées.',
        'government' => 'Les soumissions gouvernementales/de santé publique sont simulées.',
        'webhook' => 'La livraison des webhooks est simulée sauf si un récepteur de démonstration approuvé est configuré.',
        'api' => 'Aucun identifiant API de production n’est créé.',
        'facility' => 'Aucune vérification réelle d’établissement n’est effectuée.',
        'fake_data' => 'Tous les patients et dossiers sont fictifs.',
        'resets' => 'Les données de démonstration sont réinitialisées régulièrement.',
    ],
];
