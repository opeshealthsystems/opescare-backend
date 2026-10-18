<?php

return [
    'button_title'    => 'Se connecter avec OPESCARD',
    'button_subtitle' => 'Scannez le code QR de votre OPESCARD',
    'modal_title'     => 'Scanner votre OPESCARD',
    'modal_intro'     => 'Placez le code QR de votre carte Health ID dans le cadre. Nous lisons uniquement votre Health ID ; vous saisirez toujours votre mot de passe.',
    'close'           => 'Fermer',
    'starting'        => 'Démarrage de la caméra…',
    'scanning'        => 'Recherche d’un code QR…',
    'checking'        => 'Vérification de votre carte…',
    'success'         => 'Health ID renseigné. Saisissez maintenant votre mot de passe.',
    'upload_label'    => 'Importer une photo de votre carte',
    'upload_hint'     => 'Utilisez cette option si votre caméra n’est pas disponible.',
    'or'              => 'ou',
    'errors' => [
        'camera_unavailable' => 'Impossible d’utiliser votre caméra. Vous pouvez importer une photo de votre carte.',
        'camera_denied'      => 'L’accès à la caméra a été refusé. Autorisez la caméra dans les réglages du navigateur, ou importez une photo de votre carte.',
        'no_qr_in_photo'     => 'Aucun code QR trouvé sur cette photo. Essayez une photo plus nette et plus proche.',
        'not_recognised'     => 'Ce code QR n’est pas une OPESCARD valide. Saisissez plutôt votre Health ID ou votre e-mail.',
        'network'            => 'Impossible de vérifier la carte pour le moment. Veuillez réessayer.',
    ],
    'login' => [
        'throttled' => 'Trop de tentatives de connexion. Réessayez dans :seconds secondes.',
    ],
];
