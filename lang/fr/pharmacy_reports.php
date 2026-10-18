<?php

/*
|--------------------------------------------------------------------------
| Rapports de pharmacie — péremptions, valorisation, volume de dispensation,
| stock bas, et le registre mensuel des stupéfiants
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/pharmacy_reports.php (scripts/i18n-audit.php).
*/

return [

    'nav_reports'         => "Rapports",
    'no_facility_context' => "Aucune pharmacie active n'est sélectionnée pour cette session.",

    // ── Page ──────────────────────────────────────────────────────────────
    'index_title'         => "Rapports",
    'index_subtitle'      => "Quatre vues de votre propre registre. Chaque tableau lit les lots que vous avez enregistrés — rien n'est estimé, et un tableau vide signifie qu'il n'y a rien à signaler.",
    'preview_hint'        => "Affichage des :count premières lignes. L'export contient toutes les lignes.",
    'btn_csv'             => "CSV",
    'btn_pdf'             => "PDF",
    'btn_apply'           => "Appliquer",
    'empty'               => "Rien à signaler.",
    'rows_total'          => ":count lignes",
    'currency'            => "FCFA",
    'unpriced'            => "sans prix",

    // ── Filtres ───────────────────────────────────────────────────────────
    'lbl_window'          => "Périmant sous",
    'window_days'         => ":days jours",
    'lbl_from'            => "Du",
    'lbl_to'              => "Au",

    // ── Cartes ────────────────────────────────────────────────────────────
    'card_expiry'         => "Stock en voie de péremption",
    'card_expiry_sub'     => "Lots ouverts contenant encore des boîtes et atteignant leur date de péremption dans la fenêtre.",
    'card_valuation'      => "Valorisation du stock",
    'card_valuation_sub'  => "Boîtes utilisables × prix de vente, en francs entiers. Les articles sans prix sont listés mais non valorisés.",
    'card_dispensing'     => "Volume de dispensation",
    'card_dispensing_sub' => "Ordonnances et lignes délivrées à ce comptoir, par jour et par pharmacien. Comptages seulement — aucun patient n'apparaît ici.",
    'card_low_stock'      => "Stock bas et rupture",
    'card_low_stock_sub'  => "Articles sous leur minimum actif, avec la règle de réapprovisionnement à côté du comptage.",

    // ── Lignes de synthèse ────────────────────────────────────────────────
    'sum_lots'            => ":count lots · :packs boîtes",
    'sum_valuation'       => ":items articles · :packs boîtes · :total FCFA",
    'sum_unpriced'        => ":count sans prix",
    'sum_dispensing'      => ":scripts ordonnances · :lines lignes · :days jours actifs",
    'sum_low'             => ":count articles · :out en rupture",

    // ── Colonnes ──────────────────────────────────────────────────────────
    'col_medicine'        => "Médicament",
    'col_lot'             => "Lot",
    'col_expiry'          => "Péremption",
    'col_days_left'       => "Jours restants",
    'col_packs'           => "Boîtes",
    'col_usable_packs'    => "Boîtes utilisables",
    'col_price_xaf'       => "Prix (FCFA)",
    'col_value_xaf'       => "Valeur (FCFA)",
    'col_group'           => "Regroupement",
    'col_day_or_pharmacist' => "Jour / pharmacien",
    'col_scripts'         => "Ordonnances",
    'col_lines'           => "Lignes",
    'col_reorder_level'   => "Minimum",
    'col_reorder_qty'     => "Qté à commander",
    'col_state'           => "État",
    'group_day'           => "Jour",
    'group_pharmacist'    => "Pharmacien",
    'unknown_pharmacist'  => "Non enregistré",
    'state_low'           => "Bas",
    'state_out'           => "En rupture",

    // ── Feuille PDF ───────────────────────────────────────────────────────
    'pdf_title_expiry'      => "Rapport des péremptions",
    'pdf_title_valuation'   => "Rapport de valorisation du stock",
    'pdf_title_dispensing'  => "Rapport du volume de dispensation",
    'pdf_title_low_stock'   => "Rapport de stock bas",
    'pdf_facility'          => "Pharmacie",
    'pdf_window'            => "Fenêtre",
    'pdf_period'            => "Période",
    'pdf_generated'         => "Généré le",
    'pdf_total'             => "Total",
    'pdf_no_rows'           => "Aucune entrée pour ce rapport.",

    // ── Registre des stupéfiants ──────────────────────────────────────────
    'register_title'        => "Registre des stupéfiants",
    'register_subtitle'     => "Dispensations, confirmations de témoin et rapprochements d'un mois.",
    'register_month'        => "Mois",
    'register_export_pdf'   => "Registre PDF",
    'register_export_csv'   => "Registre CSV",
    'register_sum'          => ":dispensed dispensations · :witnessed avec témoin · :reconciliations rapprochements · :awaiting en attente de témoin",
    'register_no_rows'      => "Aucune entrée au registre ce mois-ci.",
    'entry_dispense'        => "Dispensation",
    'entry_witness'         => "Témoin",
    'entry_reconciliation'  => "Rapprochement",
    'witness_confirmed'     => "Confirmé",
    'witness_pending'       => "En attente",
    'witness_none'          => "Non requis",
    'col_when'              => "Quand",
    'col_entry'             => "Entrée",
    'col_drug'              => "Produit",
    'col_schedule'          => "Tableau",
    'col_quantity'          => "Quantité",
    'col_unit'              => "Unité",
    'col_balance_before'    => "Solde avant",
    'col_balance_after'     => "Solde après",
    'col_health_id'         => "Health ID",
    'col_by'                => "Par",
    'col_witness'           => "Témoin",
];
