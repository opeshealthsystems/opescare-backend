<?php

/*
|------------------------------------------------------------------------------
| Surface d'authentification Finder — NOUVELLES chaînes uniquement (français)
|------------------------------------------------------------------------------
| Chaînes introduites par la refonte mobile-first. Tout ce qui existait avant
| la refonte reste dans onboarding.php / auth.php / passwords.php — les tests
| verrouillent ces fichiers ; ne pas déplacer de clés entre fichiers.
*/

return [

    // Habillage commun (layouts/auth.blade.php)
    'chrome' => [
        'secure_area'  => 'Espace de connexion sécurisé',
        'need_help'    => 'Besoin d\'aide ?',
        'contact_support' => 'Contacter l\'assistance',
        'back_to_site' => 'Retour à OpesCare Finder',
    ],

    // Bouton d'affichage du mot de passe (tous les champs mot de passe)
    'toggle_password' => 'Afficher ou masquer le mot de passe',

    // Inscription patient (auth/register/patient.blade.php)
    'signup' => [
        'benefit' => 'Un compte vous permet de prendre des rendez-vous et de gérer vos consentements au même endroit.',
    ],

    // Vérification à deux facteurs (auth/mfa_challenge.blade.php)
    'mfa' => [
        'title'           => 'Authentification à deux facteurs',
        'enter_code'      => 'Saisissez le code à 6 chiffres de votre application d\'authentification.',
        'setup_required'  => 'L\'authentification multifacteur est requise pour votre rôle. Contactez un administrateur pour finaliser l\'enrôlement avant de vous connecter.',
        'code_label'      => 'Code d\'authentification',
        'verify'          => 'Vérifier',
        'lost_access'     => 'Vous n\'avez plus accès à votre application d\'authentification ?',
        'contact_support' => 'Contacter l\'assistance',
    ],

    // Compte suspendu (auth/account_suspended.blade.php)
    'suspended' => [
        'note' => 'Par mesure de sécurité, les tentatives de connexion à ce compte sont enregistrées. L\'accès aux services connectés reste suspendu jusqu\'à la fin de l\'examen par un administrateur.',
    ],
];
