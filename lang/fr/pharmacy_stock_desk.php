<?php

/*
|--------------------------------------------------------------------------
| Bureau du stock pharmacie — fiche article, détail d’un mouvement, fournisseurs
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/pharmacy_stock_desk.php (scripts/i18n-audit.php).
| Les libellés de colonnes partagés avec les autres écrans sont dans pharmacy_ops.php.
*/

return [

    // ── Navigation ────────────────────────────────────────────────────────
    'nav_suppliers'     => 'Fournisseurs',
    'link_ledger'       => 'Ouvrir le registre',
    'back_to_batches'   => 'Retour aux lots',
    'back_to_log'       => 'Retour au journal des mouvements',
    'back_to_suppliers' => 'Retour aux fournisseurs',
    'sections'          => 'Sections de cette page',

    // ── Fiche article ─────────────────────────────────────────────────────
    'item_title'        => ':name — registre de stock',
    'usable_sub'        => 'Boîtes annoncées aux patients',
    'finder_status'     => 'Medicine Finder',
    'never_reported'    => 'jamais déclaré',
    'finder'            => [
        'published'     => 'Publié',
        'not_reported'  => 'Non publié',
        'synthetic'     => 'Non publié',
        'no_listing'    => 'Non publié',
        'listing_issue' => 'Publié, mais invisible',
    ],
    'finder_reason'     => [
        'published'     => 'Les patients peuvent voir ce médicament.',
        'not_reported'  => 'Aucune disponibilité n’a encore été déclarée pour ce médicament. Réceptionnez une livraison ou déclarez-le sur la page de disponibilité publique.',
        'synthetic'     => 'La ligne du finder provient d’un jeu de données (:source), jamais montré aux patients. Le prochain mouvement la remplacera par le registre.',
        'no_listing'    => 'Cette pharmacie n’a pas de fiche publique liée : rien de ce qu’elle stocke ne peut être trouvé par les patients.',
        'listing_issue' => 'La fiche est inactive ou sans coordonnées GPS : les patients ne peuvent pas la trouver.',
    ],
    'price'             => 'Prix de vente',
    'price_history'     => 'Historique des prix',

    'btn_receive'       => 'Réceptionner une livraison',
    'btn_adjust'        => 'Enregistrer un mouvement',
    'btn_reorder_rule'  => 'Définir le seuil',

    'tab_batches'       => 'Lots',
    'tab_movements'     => 'Mouvements',
    'tab_reorder'       => 'Seuil de réapprovisionnement',
    'tab_suppliers'     => 'Fournisseurs',

    'col_location'      => 'Emplacement',
    'location_unknown'  => 'Sans emplacement',
    'batch_state'       => [
        'usable'   => 'Utilisable',
        'expiring' => 'Bientôt périmé',
        'expired'  => 'Périmé',
        'closed'   => 'Clôturé',
        'empty'    => 'Vide',
    ],
    'batches_empty'     => 'Aucun lot n’a encore été enregistré pour ce médicament.',

    'adjust_title'      => 'Enregistrer un mouvement sur ce médicament',
    'confirm_adjust'    => 'Enregistrer ce mouvement ? Le journal est en ajout seul : il ne pourra plus être modifié.',

    'col_kind'          => 'Type',
    'kind'              => [
        'receipt'     => 'Réception',
        'reservation' => 'Commande patient',
        'adjustment'  => 'Journal des mouvements',
        'other'       => 'Autre',
    ],
    'movement_type'     => [
        'receipt'       => 'Réception',
        'dispense'      => 'Délivrance',
        'transfer'      => 'Transfert',
        'adjustment'    => 'Ajustement',
        'return'        => 'Retour',
        'write_off'     => 'Sortie pour perte',
        'opening_stock' => 'Stock initial',
    ],
    'movements_empty'   => 'Aucun mouvement n’a encore été enregistré pour ce médicament.',

    'rule_needs_medicine' => 'Un seuil ne peut être défini que pour un médicament du catalogue.',

    'col_last_delivery' => 'Dernière livraison',
    'col_deliveries'    => 'Livraisons',
    'suppliers_for_item_empty' => 'Aucun fournisseur n’a encore livré ce médicament.',

    // ── Détail d’un mouvement ─────────────────────────────────────────────
    'adj_title'         => 'Mouvement',
    'adj_facts'         => 'Ce qui a été enregistré',
    'col_before'        => 'Lot avant',
    'col_after'         => 'Lot après',
    'lot_packs'         => 'boîtes dans le lot',
    'col_reference'     => 'Enregistré depuis',

    // ── Fournisseurs ──────────────────────────────────────────────────────
    'suppliers_title'    => 'Fournisseurs',
    'suppliers_subtitle' => 'Qui livre cette pharmacie, ce qui a été livré et ce qui reste en commande.',
    'suppliers_empty'    => 'Aucun fournisseur n’a encore été ajouté. Ajoutez-en un ci-dessus, puis nommez-le sur une réception.',
    'supplier_subtitle'  => 'Contact, livraisons et articles fournis.',
    'col_contact'        => 'Contact',
    'col_open_orders'    => 'Commandes ouvertes',
    'supplier_status'    => [
        'active'      => 'Actif',
        'inactive'    => 'Inactif',
        'blacklisted' => 'Sur liste noire',
    ],
    'contact_title'      => 'Contact',
    'lbl_address'        => 'Adresse',
    'btn_save_supplier'  => 'Enregistrer le fournisseur',
    'flash_supplier_saved' => 'Fournisseur « :name » enregistré.',
    'no_delivery_yet'    => 'Aucune livraison pour l’instant',
    'deliveries_title'   => 'Livraisons',
    'deliveries_empty'   => 'Aucune réception ne nomme encore ce fournisseur.',
    'items_supplied'     => 'Articles fournis',
    'items_supplied_empty' => 'Rien n’a encore été reçu de ce fournisseur.',
    'open_orders_title'  => 'Commandes en attente de livraison',
    'open_orders_empty'  => 'Aucune commande à ce fournisseur n’attend de livraison.',
    'col_expected'       => 'Attendue le',
    'order_status'       => [
        'approved' => 'Approuvée',
        'sent'     => 'Envoyée',
        'partial'  => 'Partiellement reçue',
    ],

    // ── Habillage « file d’attente » (restylage 2026-10-13) ───────────────
    'btn_open'              => 'Ouvrir',
    'all_items'             => 'Tous les médicaments',
    'ledger_title'          => 'Registre de stock',
    'at_a_glance'           => 'En un coup d’œil',
    'reports_window_title'  => 'Période du rapport',
    'controlled_kpi_items'  => 'Médicaments contrôlés',
    'controlled_kpi_alerts' => 'Stock faible ou épuisé',
    'controlled_kpi_log'    => 'Dispensations récentes',
    'controlled_export_title' => 'Export mensuel du registre',
    'controlled_export_hint'  => 'Dispensations, témoins et rapprochements d’un mois, en PDF sécurisé ou en CSV.',
];
