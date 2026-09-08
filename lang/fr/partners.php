<?php

return [
    // Navigation et éléments de page
    'nav'              => 'Intégration des partenaires',
    'breadcrumb_home'  => 'Administration',
    'title'            => 'Intégration des partenaires',
    'subtitle'         => 'Les clients API que les systèmes externes utilisent pour accéder à OpesCare. Chaque identifiant délivré ici est une clé vers les données des patients.',

    // État vide — zéro client est un état réel, pas une erreur
    'none_title'       => 'Aucun partenaire n\'a encore été intégré',
    'none_body'        => 'Aucun client d\'intégration n\'existe sur cette plateforme. Aucun système externe ne peut s\'authentifier auprès de l\'API tant qu\'aucun n\'est créé ici.',

    // Liste
    'col_name'         => 'Partenaire',
    'col_client_id'    => 'Identifiant client',
    'col_organisation' => 'Organisation',
    'col_environment'  => 'Environnement',
    'col_scopes'       => 'Portées',
    'col_status'       => 'Statut',
    'col_last_used'    => 'Dernière utilisation',
    'never_used'       => 'Jamais utilisé',
    'no_organisation'  => 'Toute la plateforme',
    'filter_all'       => 'Tous les statuts',
    'filter_all_envs'  => 'Tous les environnements',
    'filter_apply'     => 'Filtrer',
    'total_clients'    => ':count client(s) intégré(s)',

    // Statuts
    'status_active'    => 'Actif',
    'status_suspended' => 'Suspendu',
    'status_revoked'   => 'Révoqué',
    'env_sandbox'      => 'Bac à sable',
    'env_production'   => 'Production',

    // Création
    'create_nav'       => 'Intégrer un partenaire',
    'create_title'     => 'Intégrer un partenaire',
    'create_subtitle'  => 'Délivrer des identifiants à un système externe. Le secret est affiché une seule fois, à la création, et ne peut plus être récupéré ensuite.',
    'f_name'           => 'Nom du partenaire',
    'f_name_help'      => 'Le système ou l\'organisation à qui appartiennent ces identifiants, tel qu\'il doit apparaître dans la piste d\'audit.',
    'f_description'    => 'Description',
    'f_contact_email'  => 'Courriel du contact technique',
    'f_environment'    => 'Environnement',
    'f_organisation'   => 'Organisation représentée',
    'f_organisation_help' => 'Facultatif. Laisser vide pour un partenaire couvrant toute la plateforme et n\'agissant pour aucun établissement en particulier.',
    'f_organisation_none' => 'Aucune — toute la plateforme',
    'f_scopes'         => 'Portées accordées',
    'f_scopes_help'    => 'Un nouveau client ne reçoit aucune portée par défaut. Ne sélectionner que ce dont le partenaire a besoin ; aucun caractère générique n\'existe.',
    'f_submit'         => 'Créer le client et délivrer les identifiants',
    'err_unknown_organisation' => 'Cette organisation n\'existe pas.',
    'err_scopes_required' => 'Sélectionner au moins une portée. Un client sans portée peut s\'authentifier mais ne peut rien lire.',

    // Détail
    'detail_title'     => 'Client partenaire',
    'back_to_list'     => 'Retour aux partenaires',
    'section_identity' => 'Identité',
    'section_scopes'   => 'Portées',
    'section_creds'    => 'Identifiants',
    'section_trail'    => 'Piste d\'audit',
    'created_at'       => 'Créé le',
    'approved_at'      => 'Approuvé le',
    'request_count'    => 'Requêtes enregistrées',
    'contact'          => 'Contact technique',

    // Le secret affiché une seule fois
    'secret_once_title' => 'Copier ce secret maintenant',
    'secret_once_body'  => 'C\'est la seule fois où ce secret sera affiché. OpesCare n\'en conserve qu\'une empreinte Argon2id et ne peut pas l\'afficher à nouveau. S\'il est perdu, il faut renouveler l\'identifiant.',
    'secret_client_id'  => 'Identifiant client',
    'secret_value'      => 'Secret client',
    'secret_not_stored' => 'Le secret n\'est pas conservé. Seule son empreinte Argon2id est stockée, et aucun écran ne peut la relire.',

    // Actions
    'act_rotate'        => 'Renouveler le secret',
    'act_rotate_help'   => 'Délivre un nouveau secret et invalide immédiatement l\'actuel. Tout système utilisant encore l\'ancien secret cessera de fonctionner.',
    'act_suspend'       => 'Suspendre',
    'act_suspend_help'  => 'Interrompt l\'authentification immédiatement, y compris pour les jetons déjà délivrés. Prévu pour être réversible.',
    'act_revoke'        => 'Révoquer',
    'act_revoke_help'   => 'Retire définitivement l\'accès de ce partenaire. Les jetons déjà délivrés cessent de fonctionner dès leur requête suivante.',
    'act_reinstate'     => 'Rétablir',
    'act_reason'        => 'Motif',
    'act_confirm'       => 'Confirmer',
    'act_scopes_save'   => 'Enregistrer les portées',

    // Piste d'audit
    'trail_none'        => 'Rien n\'est encore enregistré pour ce client.',
    'trail_col_when'    => 'Date',
    'trail_col_action'  => 'Action',
    'trail_col_actor'   => 'Auteur',
    'trail_col_reason'  => 'Motif',

    // Messages
    'flash_created'          => 'Client partenaire créé. Le secret ci-dessous est affiché une seule fois.',
    'flash_rotated'          => 'Secret renouvelé. Le secret précédent ne fonctionne plus.',
    'flash_scopes_changed'   => 'Portées mises à jour.',
    'flash_status_active'    => 'Client rétabli.',
    'flash_status_suspended' => 'Client suspendu. Les jetons déjà délivrés cessent immédiatement de fonctionner.',
    'flash_status_revoked'   => 'Client révoqué. Les jetons déjà délivrés cessent immédiatement de fonctionner.',
];
