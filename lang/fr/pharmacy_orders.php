<?php

/*
|--------------------------------------------------------------------------
| Comptoir des commandes (web) — réservations de médicaments des patients
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/pharmacy_orders.php (scripts/i18n-audit.php).
| Les statuts viennent de enums.reservation_status via @enum(..., 'reservation_status').
*/

return [

    'title'    => 'Commandes',
    'subtitle' => "Médicaments réservés par les patients dans cette pharmacie. Acceptez la réservation, marquez-la prête, puis remettez-la ou expédiez-la — chaque étape est notifiée au patient.",

    'no_facility_context' => "Aucune pharmacie active n'est sélectionnée pour cette session.",

    // ── Onglets ──────────────────────────────────────────────────────────
    'tab_pending'     => 'À traiter',
    'tab_in_progress' => 'En cours',
    'tab_done'        => 'Terminées',

    // ── Colonnes ─────────────────────────────────────────────────────────
    'col_reference' => 'Référence',
    'col_patient'   => 'Patient',
    'col_medicine'  => 'Médicament',
    'col_qty'       => 'Qté',
    'col_pack'      => 'Conditionnement',
    'col_total'     => 'Total',
    'col_status'    => 'Statut',
    'col_placed'    => 'Passée le',
    'col_expires'   => 'Expire le',
    'col_action'    => 'Action',

    'empty_pending'     => "Aucune commande n'attend votre intervention.",
    'empty_in_progress' => "Aucune commande n'est en préparation pour le moment.",
    'empty_done'        => "Aucune commande n'a encore été clôturée.",

    'mode_pickup'   => 'Retrait sur place',
    'mode_delivery' => 'Livraison',
    'no_price'      => 'Prix non renseigné',
    'no_expiry'     => "Sans date d'expiration",
    'expired'       => 'Expirée',

    // ── Actions ──────────────────────────────────────────────────────────
    'btn_accept'    => 'Accepter',
    'btn_ready'     => 'Marquer prête',
    'btn_collected' => 'Remise au patient',
    'btn_dispatch'  => 'Expédier',
    'btn_delivered' => 'Livrée',
    'btn_cancel'    => 'Annuler la commande',
    'btn_open'      => 'Ouvrir',
    'btn_back'      => 'Retour aux commandes',

    'confirm_accept'    => 'Accepter cette commande ? Le patient sera informé que vous la préparez.',
    'confirm_ready'     => 'Marquer cette commande prête ? Le patient sera invité à venir la retirer.',
    'confirm_collected' => 'Confirmer que le patient a retiré cette commande ?',
    'confirm_dispatch'  => "Marquer cette commande en cours de livraison ? Le patient sera informé qu'elle est en route.",
    'confirm_delivered' => 'Confirmer que cette commande a été livrée ?',
    'confirm_cancel'    => 'Annuler cette commande ? Le patient verra le motif que vous indiquez.',

    // ── Page de détail ───────────────────────────────────────────────────
    'show_title'          => 'Commande :reference',
    'section_order'       => 'Commande',
    'section_patient'     => 'Patient',
    'section_delivery'    => 'Livraison',
    'section_timeline'    => 'Chronologie',
    'section_actions'     => 'Prochaine étape',
    'lbl_health_id'       => 'Health ID',
    'lbl_phone'           => 'Téléphone',
    'lbl_fulfilment'      => 'Mode de remise',
    'lbl_delivery_address' => 'Adresse de livraison',
    'lbl_delivery_phone'  => 'Téléphone de livraison',
    'lbl_patient_note'    => 'Note du patient',
    'lbl_pharmacy_note'   => 'Note de la pharmacie',
    'lbl_cancel_reason'   => 'Motif indiqué',
    'lbl_unit_price'      => 'Prix unitaire',
    'lbl_prescription'    => 'Ordonnance',
    'lbl_reason'          => 'Motif (le patient le lira)',
    'reason_placeholder'  => "ex. rupture de stock jusqu'à jeudi",
    'not_provided'        => 'Non renseigné',
    'closed_hint'         => 'Cette commande est clôturée. Plus aucune action possible.',

    'ts_placed'     => 'Passée',
    'ts_accepted'   => 'Acceptée',
    'ts_ready'      => 'Prête',
    'ts_dispatched' => 'Expédiée',
    'ts_delivered'  => 'Livrée',
    'ts_collected'  => 'Retirée',
    'ts_cancelled'  => 'Annulée',

    // ── Messages flash ───────────────────────────────────────────────────
    'flash_accept'    => 'Commande :reference acceptée.',
    'flash_ready'     => 'Commande :reference prête — le patient a été informé.',
    'flash_collected' => 'Commande :reference remise au patient.',
    'flash_dispatch'  => 'Commande :reference en cours de livraison — le patient a été informé.',
    'flash_delivered' => 'Commande :reference livrée.',
    'flash_cancel'    => 'Commande :reference annulée.',
    // ── Second pass: orders ↔ stock ledger seam, unlinked pharmacy ─────
    'flash_stock_not_recorded' => "Le registre de stock n'a pas été modifié : cette pharmacie ne suit aucun lot pour ce médicament, ou trop peu de boîtes utilisables. Enregistrez le mouvement à la main.",
    'lbl_stock_ledger' => "Registre de stock",
    'stock_recorded' => ":count boîte(s) retirée(s) du registre des lots.",
    'stock_view_log' => "Voir les mouvements",
    'stock_not_recorded' => "Non enregistré dans le registre des lots.",
    'stock_record_by_hand' => "Enregistrer un mouvement",
    'no_listing' => "Cette pharmacie n'est liée à aucune fiche publique de l'annuaire : les patients ne peuvent ni la trouver ni y réserver de médicaments. Un bureau vide ne signifie pas que personne n'a essayé.",
    'dashboard_pending' => "{0} Commandes : aucune en attente|{1} Commandes : :count en attente|[2,*] Commandes : :count en attente",
];
