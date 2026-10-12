<?php

return [
    'generic'             => 'Une erreur s\'est produite. Veuillez réessayer.',
    'unauthorized'        => 'Vous n\'êtes pas autorisé à effectuer cette action.',
    'forbidden'           => 'Accès refusé.',
    'not_found'           => 'La ressource demandée est introuvable.',
    'validation'          => 'Veuillez vérifier les erreurs dans le formulaire.',
    'session_expired'     => 'Votre session a expiré. Veuillez vous reconnecter.',
    'consent_required'    => 'Le consentement du patient est requis avant d\'accéder à ce dossier.',
    'facility_mismatch'   => 'Ce dossier appartient à un autre établissement.',
    'limit_exceeded'      => 'Vous avez atteint la limite d\'utilisation de cette fonctionnalité.',
    'feature_disabled'    => 'Cette fonctionnalité n\'est pas disponible pour votre abonnement.',
    'phi_blocked'         => 'L\'accès à ces informations nécessite une autorisation supplémentaire.',
    'offline'             => 'Vous semblez être hors ligne. Certaines fonctionnalités peuvent être indisponibles.',
    'sync_conflict'       => 'Un conflit de données a été détecté lors de la synchronisation. Veuillez vérifier.',
    'availability_not_guaranteed' => 'La disponibilité des médicaments, du sang et des services affichée est indicative et non garantie.',

    // Pages d'erreur HTTP plein écran (resources/views/errors/_page.blade.php).
    'actions' => [
        'home'            => 'Retour à l\'accueil',
        'sign_in'         => 'Se connecter',
        'help_centre'     => 'Centre d\'aide',
        'contact_support' => 'Contacter le support',
        'status_page'     => 'page d\'état du système',
        'try_again'       => 'Réessayer',
    ],
    'pages' => [
        '401' => ['title' => 'Connexion requise',    'body' => 'Vous devez vous connecter pour voir cette page.'],
        '402' => ['title' => 'Paiement requis',      'body' => 'Cette action nécessite un paiement avant de continuer. Les soins ne sont jamais refusés — contactez le support si vous pensez qu\'il s\'agit d\'une erreur.'],
        '403' => ['title' => 'Accès refusé',         'body' => 'Vous n\'avez pas la permission de voir cette page. Si vous pensez qu\'il s\'agit d\'une erreur, connectez-vous avec le bon compte ou contactez votre administrateur.'],
        '404' => ['title' => 'Page introuvable',     'body' => 'La page que vous cherchez n\'existe pas ou a été déplacée. Vérifiez l\'adresse ou utilisez l\'un des liens ci-dessous.'],
        '419' => ['title' => 'Page expirée',         'body' => 'Votre session a expiré pendant que cette page était ouverte. Revenez en arrière et réessayez, ou reconnectez-vous.'],
        '429' => ['title' => 'Trop de requêtes',     'body' => 'Vous avez effectué trop de requêtes en peu de temps. Veuillez patienter une minute et réessayer.'],
        '500' => ['title' => 'Erreur du serveur',    'body' => 'Un problème est survenu de notre côté. Notre équipe a été prévenue et travaille à le corriger au plus vite.', 'note' => 'Vos données sont en sécurité. Si c\'est urgent, contactez directement le support.', 'footer_prefix' => 'Consultez notre', 'footer_suffix' => 'pour les mises à jour en direct.'],
        '503' => ['title' => 'Service indisponible', 'body' => 'OpesCare est momentanément indisponible, généralement pour une maintenance planifiée. Veuillez réessayer dans quelques minutes.'],
    ],
];
