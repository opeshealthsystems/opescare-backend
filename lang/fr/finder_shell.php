<?php

/*
|-----------------------------------------------------------------------------
| Coquille Finder — chrome de mise en page uniquement (en-tête, nav, pied).
|-----------------------------------------------------------------------------
| Le contenu des pages vit dans le fichier de langue de chaque page, pas ici.
| Doit rester 1:1 avec lang/en/finder_shell.php (php scripts/i18n-audit.php).
*/

return [

    'brand' => [
        'name' => 'OpesCare',
        'tagline' => 'Trouvez médicaments, sang et soins partout au Cameroun.',
    ],

    'meta' => [
        'default_title' => 'OpesCare',
        'default_description' => 'Trouvez des médicaments en stock en pharmacie, du sang disponible et des rendez-vous dans 2 728 établissements de santé des 10 régions du Cameroun.',
    ],

    'a11y' => [
        'skip_to_content' => 'Aller au contenu',
        'searching' => 'Recherche en cours…',
        'open_menu' => 'Ouvrir le menu',
        'close_menu' => 'Fermer le menu',
        'main_navigation' => 'Navigation principale',
        'footer_navigation' => 'Pied de page',
        'language' => 'Langue',
        'home' => 'Accueil OpesCare',
    ],

    'nav' => [
        'find_medicine' => 'Trouver un médicament',
        'find_blood' => 'Trouver du sang',
        'book_appointment' => 'Prendre rendez-vous',
        'directory' => 'Annuaire',
        'for_pharmacies' => 'Pour les pharmacies',
        'login' => 'Connexion',
        'menu' => 'Menu',
    ],

    'footer' => [
        'col_services' => 'Services',
        'col_facilities' => 'Pour les établissements',
        'col_legal' => 'Mentions et aide',
        'emergency' => 'Soins d\'urgence',
        'for_hospitals' => 'Pour les hôpitaux et cliniques',
        'interop' => 'Interopérabilité',
        'mpi' => 'Index patient unique',
        'app' => "Obtenir l'application",
        'help' => 'Centre d\'aide',
        'contact' => 'Contact',
        'terms' => 'Conditions d\'utilisation',
        'privacy' => 'Politique de confidentialité',
        'attribution' => 'Données des établissements issues du registre national des formations sanitaires du Cameroun et © des contributeurs OpenStreetMap.',
        'disclaimer' => 'Les informations, horaires, stocks et disponibilités des établissements peuvent changer. Appelez avant de vous déplacer lorsque c\'est important.',
        'copyright' => '© :year OpesCare. Tous droits réservés.',
    ],

];
