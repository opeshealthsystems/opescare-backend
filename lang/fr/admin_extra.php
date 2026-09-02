<?php

return [
    // bridge/index.blade.php — API documentation prose
    'bridge_api_desc_sync'      => 'Envoyer un lot d\'enregistrements. Prend en charge ehr_records, appointments, pharmacy_stock, blood_stock.',
    'bridge_api_desc_heartbeat' => 'Annoncer la version de l\'agent, le nom d\'hôte et les capacités. Met à jour l\'horodatage de dernière connexion.',
    'bridge_api_desc_status'    => 'Interroger les résultats récents des lots et l\'état de synchronisation de cet agent.',

    // Recurring control labels
    'btn_filter' => 'Filtrer',
    'btn_reset'  => 'Réinitialiser',
    'btn_apply'  => 'Appliquer',

    // aria-labels / titles
    'aria_type'  => 'Type',
    'aria_role'  => 'Rôle',
    'title_view' => 'Afficher',

    // legal — boolean badge / placeholder
    'yes'            => 'Oui',
    'no'             => 'Non',
    'legal_terms_of_use' => 'Conditions d\'utilisation',

    // breadcrumb home label
    'breadcrumb_admin' => 'Admin',

    // parameterised count nouns
    'count_batches'      => ':n lots',
    'count_invoices'     => ':n factures',
    'count_transactions' => ':n transactions',
    'count_facilities'   => ':n établissements',
    'count_users'        => ':n utilisateurs',

    // payments — refund + filtered note
    'pay_refund'   => 'remboursement',
    'pay_filtered' => '(filtré)',

    // payments — placeholder
    'pay_ph_reference' => 'Référence ou téléphone...',

    // report by service — period hint
    'rbs_period' => 'Période :',

    // payment method / gateway option labels
    'opt_cash'          => 'Espèces',
    'opt_card'          => 'Carte',
    'opt_insurance'     => 'Assurance',
    'opt_bank_transfer' => 'Virement bancaire',
    'opt_wallet'        => 'Portefeuille',

    // payment status option labels
    'opt_successful' => 'Réussi',
    'opt_pending'    => 'En attente',
    'opt_failed'     => 'Échoué',
    'opt_refunded'   => 'Remboursé',
    'opt_completed'  => 'Terminé',

    // support/index — table data-labels
    'sup_col_ticket'   => 'Ticket nº',
    'sup_col_subject'  => 'Sujet',
    'sup_col_category' => 'Catégorie',
    'sup_col_priority' => 'Priorité',
    'sup_col_status'   => 'Statut',
    'sup_col_assignee' => 'Assigné à',
    'sup_col_created'  => 'Créé le',
    'sup_col_actions'  => 'Actions',

    // support/index — fallback values
    'sup_fallback_general' => 'Général',
    'sup_fallback_medium'  => 'Moyenne',
    'sup_fallback_open'    => 'Ouvert',

    // bridge/index — table data-labels
    'bridge_col_agent_name' => 'Nom de l\'agent',
    'bridge_col_key_prefix' => 'Préfixe de clé',
    'bridge_col_status'     => 'Statut',
    'bridge_col_version'    => 'Version',
    'bridge_col_last_seen'  => 'Vu pour la dernière fois',
    'bridge_col_last_sync'  => 'Dernière synchronisation',
    'bridge_col_batches'    => 'Lots',
    'bridge_col_actions'    => 'Actions',

    // bridge/index — modal trailing word
    'bridge_agent' => 'agent',

    // users/index — role fallback
    'users_role_none' => 'aucun',

    // payments/payments — actions column data-label
    'col_actions' => 'Actions',

    // legal/show — change-summary input placeholder
    'ph_change_summary' => 'Qu\'est-ce qui a changé dans cette version ?',

    // users — primary facility assignment
    'users_col_facility'      => 'Établissement',
    'users_facility_none'     => 'Aucun établissement',
    'users_stat_facility'     => 'Établissement principal',
    'users_lbl_facility'      => 'Établissement principal',
    'users_ph_facility'       => 'Aucun établissement (compte plateforme)',
    'users_facility_help'     => 'Un compte du personnel sans établissement ne peut ouvrir aucun portail.',
    'users_facility_find'     => 'Rechercher un établissement',
    'users_facility_find_ph'  => 'Saisissez une partie du nom de l\'établissement',
    'users_facility_find_btn' => 'Rechercher',
    'users_facility_hint'     => 'Affichage de :n établissements au maximum. Recherchez par nom pour en atteindre un autre.',
    'users_facility_no_match' => 'Aucun établissement ne correspond à cette recherche.',
    'users_facility_locked'   => 'Vous ne pouvez affecter du personnel qu\'à l\'établissement que vous administrez.',

    // users/index — create form
    'users_create_title'   => 'Créer un utilisateur',
    'users_create_name'    => 'Nom complet',
    'users_create_email'   => 'E-mail',
    'users_create_pw'      => 'Mot de passe temporaire',
    'users_create_role'    => 'Rôle',
    'users_create_role_ph' => 'Sélectionner un rôle',
    'users_create_btn'     => 'Créer l\'utilisateur',

    // users/show — status control
    'users_lbl_status' => 'Statut',
];
