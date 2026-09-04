<?php

return [
    // Flash
    'issued'          => 'Ordonnance émise.',
    'voided'          => 'Ordonnance annulée.',
    'issue_failed'    => 'L’ordonnance n’a pas pu être émise. Vérifiez les informations et réessayez.',
    'void_failed'     => 'Cette ordonnance n’a pas pu être annulée.',
    'needs_one_item'  => 'Ajoutez au moins un médicament à l’ordonnance.',

    // Index
    'title'           => 'Ordonnances',
    'subtitle'        => 'Ordonnances rédigées dans cet établissement.',
    'new'             => 'Nouvelle ordonnance',
    'empty'           => 'Aucune ordonnance n’a encore été rédigée ici.',
    'empty_desc'      => 'Les ordonnances émises apparaissent ici et rejoignent la file de la pharmacie.',
    'col_patient'     => 'Patient',
    'col_items'       => 'Médicaments',
    'col_status'      => 'Statut',
    'col_issued'      => 'Émise le',
    'col_prescriber'  => 'Prescripteur',
    'filter_all'      => 'Toutes',
    'view'            => 'Voir',

    // Create
    'create_title'    => 'Nouvelle ordonnance',
    'create_subtitle' => 'Trouvez le patient par son identifiant santé, puis ajoutez chaque médicament.',
    'find_patient'    => 'Trouver le patient',
    'health_id'       => 'Identifiant santé',
    'health_id_hint'  => 'Saisissez l’identifiant santé exact — la recherche par nom n’est pas possible.',
    'lookup'          => 'Rechercher',
    'not_found'       => 'Aucun patient ne correspond à cet identifiant santé.',
    'patient'         => 'Patient',
    'change_patient'  => 'Changer de patient',
    'medicines'       => 'Médicaments',
    'add_item'        => 'Ajouter un autre médicament',
    'remove_item'     => 'Retirer',
    'drug_name'       => 'Médicament',
    'dose'            => 'Dose',
    'dose_ph'         => 'ex. 500 mg',
    'frequency'       => 'Fréquence',
    'frequency_ph'    => 'ex. 3 fois par jour',
    'route'           => 'Voie',
    'duration_days'   => 'Durée (jours)',
    'quantity'        => 'Quantité',
    'notes'           => 'Notes pour la pharmacie',
    'notes_ph'        => 'Instructions ou contexte (facultatif)',
    'validity_days'   => 'Valable (jours)',
    'issue'           => 'Émettre l’ordonnance',
    'cancel'          => 'Annuler',

    // Show
    'detail_title'    => 'Ordonnance',
    'issued_at'       => 'Émise le',
    'expires_at'      => 'Expire le',
    'dispensed_at'    => 'Délivrée le',
    'status'          => 'Statut',
    'void'            => 'Annuler l’ordonnance',
    'void_reason'     => 'Motif',
    'void_confirm'    => 'Annuler cette ordonnance ? Le dossier est conservé avec votre motif.',
    'back'            => 'Retour aux ordonnances',
    'item_status'     => 'Statut de la ligne',

    // Statuses
    'st_active'              => 'Active',
    'st_dispensed'           => 'Délivrée',
    'st_partially_dispensed' => 'Partiellement délivrée',
    'st_pending'             => 'En attente',
    'st_cancelled'           => 'Annulée',
    'st_voided'              => 'Annulée',
    'st_expired'             => 'Expirée',

    // Counter dispensing
    'counter_title'   => 'Délivrer au comptoir',
    'counter_sub'     => 'Saisissez l’identifiant santé présenté par le patient pour voir ses ordonnances actives.',
    'counter_lookup'  => 'Rechercher les ordonnances',
    'counter_none'    => 'Aucune ordonnance active sur cet identifiant santé.',
    'counter_nopat'   => 'Aucun patient ne correspond à cet identifiant santé.',
    'counter_from'    => 'Émise à',
    'counter_dispense'=> 'Délivrer',
    'counter_notes'   => 'Notes de délivrance',
    'counter_link'    => 'Recherche comptoir',

    // Nav
    'nav'             => 'Ordonnances',
];
