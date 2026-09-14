<?php

return [
    'nav' => 'Sauvegardes',
    'title' => 'Sauvegardes',
    'subtitle' => 'Où sont conservées les sauvegardes de la plateforme, et comment en placer des copies hors de ce serveur.',

    // Verdict général
    'state_off_host' => 'Les sauvegardes quittent ce serveur',
    'state_off_host_body' => 'Au moins une sauvegarde est confirmée sur un stockage externe. Une panne du disque de ce serveur n\'emporterait plus toutes les copies.',
    'state_configured' => 'Le stockage externe est configuré, mais aucune sauvegarde ne l\'a encore atteint',
    'state_configured_body' => 'Les clés sont en place. Lancez une sauvegarde de test (étape 4 ci-dessous) pour confirmer qu\'une copie arrive bien.',
    'state_misconfigured' => 'Les sauvegardes externes sont activées mais ne fonctionnent pas',
    'state_misconfigured_body' => 'Un élément requis manque, ou le stockage n\'a pas pu être lu. Vérifiez le tableau et les étapes ci-dessous, puis lancez une sauvegarde de test.',
    'state_on_host_only' => 'Les sauvegardes n\'existent que sur ce serveur',
    'state_on_host_only_body' => 'Ce serveur ne dispose que d\'un seul disque. La base de données et toutes ses sauvegardes se trouvent sur ce même disque : une panne ferait perdre les deux. Ajoutez des clés de stockage externe en suivant les étapes ci-dessous.',

    // Tableau de configuration
    'section_config' => 'Configuration',
    'config_hint' => 'Lu depuis la configuration en cours. Aucune clé ni aucun secret n\'est jamais affiché ici, même partiellement.',
    'row_s3_enabled' => 'Sauvegardes externes activées (BACKUP_S3_ENABLED)',
    'row_access_key' => 'Clé d\'accès (AWS_ACCESS_KEY_ID)',
    'row_secret' => 'Clé secrète (AWS_SECRET_ACCESS_KEY)',
    'row_bucket' => 'Nom du compartiment (AWS_BUCKET)',
    'row_region' => 'Région (AWS_DEFAULT_REGION)',
    'row_endpoint' => 'Point de terminaison (AWS_ENDPOINT)',
    'row_encryption' => 'Mot de passe de chiffrement des sauvegardes (BACKUP_ENCRYPTION_PASSWORD)',
    'yes' => 'Oui',
    'no' => 'Non',
    'set' => 'Défini',
    'missing' => 'Manquant',
    'optional_aws' => 'Inutile pour Amazon S3',

    // Archives
    'section_archives' => 'Copies de sauvegarde',
    'on_this_server' => 'Sur ce serveur',
    'off_site' => 'Stockage externe',
    'copies' => 'Copies',
    'latest' => 'La plus récente',
    'size' => 'Taille',
    'none_yet' => 'Aucune pour l\'instant',
    'not_configured' => 'Non configuré',
    'read_error' => 'Lecture impossible (:error). Les détails figurent dans le journal de l\'application.',
    'stale' => 'Plus ancienne que :hours heures. Les sauvegardes s\'exécutent chaque jour à 01h00 ; vérifiez que le planificateur fonctionne.',

    // Instructions
    'section_steps' => 'Comment ajouter les clés de sauvegarde externe',
    'steps_intro' => 'Les clés s\'ajoutent sur le serveur, pas sur cette page. Un formulaire web capable d\'écrire des secrets dans la configuration du serveur serait lui-même un moyen de prendre le contrôle de la plateforme ; il n\'en existe donc aucun.',
    'step0_title' => 'Installer le pilote de stockage S3 sur le serveur',
    'step0_body' => "L'application est livrée sans le paquet du pilote S3 — il n'est listé qu'en suggestion, et une tentative de sauvegarde du 5 septembre a échoué précisément pour cela (« PortableVisibilityConverter not found » dans le journal). Installez-le une fois, en tant qu'utilisateur du site, avant d'ajouter les clés :",
    'step1_title' => 'Créer un compartiment et une clé dédiée',
    'step1_body' => 'Utilisez Amazon S3 ou tout fournisseur compatible S3 (Backblaze B2, Cloudflare R2, Wasabi). Créez un compartiment privé, puis une clé qui n\'a accès qu\'à ce seul compartiment, avec le droit de lister, lire, écrire et supprimer des objets. La suppression est nécessaire pour nettoyer les anciennes sauvegardes. Ne réutilisez pas une clé qui donne accès à autre chose.',
    'step2_title' => 'Placer les clés dans le fichier .env du serveur',
    'step2_body' => 'Connectez-vous au serveur en SSH et ouvrez le fichier d\'environnement de Finder :',
    'step2_after' => 'Définissez ces lignes en remplaçant les valeurs d\'exemple. Laissez tout le reste du fichier tel quel.',
    'step2_other_providers' => 'Pour Backblaze B2, Cloudflare R2 ou Wasabi, définissez aussi AWS_ENDPOINT avec l\'URL du point de terminaison S3 du fournisseur, et AWS_USE_PATH_STYLE_ENDPOINT=true. Pour Amazon S3, laissez AWS_ENDPOINT vide.',
    'step3_title' => 'Recharger la configuration',
    'step3_body' => 'L\'application met sa configuration en cache : elle ne verra pas les nouvelles clés tant que cette commande n\'a pas été exécutée.',
    'step4_title' => 'Lancer une sauvegarde de test',
    'step4_body' => 'Cette commande crée immédiatement une sauvegarde et la téléverse. La commande de liste affiche ensuite les copies présentes sur chaque emplacement.',
    'step5_title' => 'Revenir sur cette page',
    'step5_body' => 'Actualisez-la. L\'état en haut doit indiquer que les sauvegardes quittent ce serveur, et la colonne du stockage externe doit afficher au moins une copie.',

    'password_title' => 'Conservez le mot de passe de chiffrement en lieu sûr, hors de ce serveur',
    'password_body' => 'Les sauvegardes sont chiffrées avec BACKUP_ENCRYPTION_PASSWORD. Sans ce mot de passe, une sauvegarde ne peut pas être restaurée : copier les sauvegardes à l\'extérieur ne sert à rien si la seule copie du mot de passe se trouve sur ce serveur. Conservez-le dans un gestionnaire de mots de passe.',

    'refresh' => 'Actualiser',
];
