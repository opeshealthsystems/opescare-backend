<?php

/*
|--------------------------------------------------------------------------
| Guichet banque de sang — surface mobile des professionnels
|--------------------------------------------------------------------------
|
| Tous les messages renvoyés par MobileBloodDeskController. Les clés doivent
| rester 1:1 avec lang/en/blood_desk.php (php scripts/i18n-audit.php).
|
*/

return [

    'facility_required'       => 'Votre session n’est rattachée à aucun établissement.',
    'request_not_found'       => 'Demande de sang introuvable pour cet établissement.',
    'audit_unavailable'       => 'Action refusée : elle n’a pas pu être inscrite au journal d’accès.',
    'transition_not_allowed'  => 'Cette demande de sang ne peut plus passer à ce statut.',

    'decision_recorded'       => 'Demande de sang mise à jour.',
    'confirmed_recorded'      => 'Demande confirmée. Le patient est invité à se présenter au guichet ; cette confirmation ne réserve aucune poche.',
    'rejected_recorded'       => 'Demande refusée.',
    'ready_recorded'          => 'Marquée prête pour le retrait.',
    'fulfilled_recorded'      => 'Marquée retirée.',

    /*
    |----------------------------------------------------------------------
    | Guichet WEB des professionnels — /facility/blood-requests
    |----------------------------------------------------------------------
    |
    | La file d’attente dans le navigateur (BloodRequestDeskController). Elle
    | réutilise les messages ci-dessus plutôt que de les répéter ; seuls les
    | libellés propres à un écran sont ajoutés ici.
    |
    */

    'nav'                     => 'Demandes de sang',
    'title'                   => 'Demandes de sang',
    'breadcrumb'              => 'Demandes de sang',
    'subtitle'                => 'Demandes adressées à cette banque de sang par des patients. Les demandes sans réponse figurent en tête.',

    'stat_open'               => 'En cours',
    'stat_pending'            => 'En attente de réponse',
    'stat_confirmed'          => 'Confirmées',
    'stat_ready'              => 'Prêtes pour le retrait',

    'filter_open_only'        => 'Demandes en cours',
    'filter_every_status'     => 'Toutes les demandes',
    'filter_btn'              => 'Filtrer',
    'clear_btn'               => 'Effacer',

    'col_reference'           => 'Référence',
    'col_patient'             => 'Patient',
    'col_request'             => 'Demande',
    'col_urgency'             => 'Urgence',
    'col_status'              => 'Statut',
    'col_filed'               => 'Déposée le',
    'col_actions'             => 'Actions',

    'empty_title'             => 'Aucune demande à traiter',
    'empty_desc'              => 'Lorsqu’un patient demande des poches à cette banque de sang, la demande apparaît ici.',
    'no_facility_notice'      => 'Aucun établissement n’est sélectionné pour cette session : il n’y a donc aucune file à afficher. Sélectionnez d’abord un établissement.',
    'no_listing_notice'       => 'Cet établissement n’a pas encore de fiche dans l’annuaire public : aucun patient ne peut donc lui adresser une demande de sang.',
    'audit_blocked_notice'    => 'La file est masquée : cette divulgation n’a pas pu être inscrite au journal d’accès.',

    'view_btn'                => 'Ouvrir',
    'back_to_queue'           => 'Retour à la file',
    'detail_title'            => 'Demande de sang :reference',

    'section_request'         => 'Ce qui est demandé',
    'section_patient'         => 'Qui vient retirer',
    'section_decision'        => 'La réponse de cette banque de sang',
    'section_actions'         => 'Répondre à cette demande',

    'lbl_group'               => 'Groupe sanguin',
    'lbl_component'           => 'Composant',
    'lbl_quantity'            => 'Poches demandées',
    'lbl_urgency'             => 'Urgence',
    'lbl_needed_by'           => 'Nécessaire avant le',
    'lbl_expires'             => 'Fin de la réservation',
    'lbl_filed'               => 'Déposée le',
    'lbl_health_id'           => 'Identifiant santé',
    'lbl_name'                => 'Nom',
    'lbl_phone'               => 'Téléphone de contact',
    'lbl_patient_note'        => 'Note du patient',
    'lbl_facility_note'       => 'Note enregistrée par ce guichet',
    'lbl_decided_by'          => 'Répondu par',
    'lbl_decided_at'          => 'Répondu le',
    'lbl_status'              => 'Statut',
    'units_label'             => 'poches',
    'not_recorded'            => 'Non renseigné',

    'note_label'              => 'Note pour le dossier (facultatif)',
    'note_placeholder'        => 'Ce que la prochaine personne à ce guichet doit savoir.',
    'action_confirm'          => 'Confirmer — inviter le patient à venir',
    'action_reject'           => 'Refuser cette demande',
    'action_ready'            => 'Les poches sont au guichet',
    'action_fulfil'           => 'Retirée',
    'no_moves'                => 'Cette demande est close. Son historique reste tel qu’il a été enregistré : rien ici ne peut la rouvrir ni la modifier.',
    'no_hold_notice'          => 'Confirmer ne réserve aucune poche. Cela invite le patient à venir ; le sang ne quitte l’étagère que lorsqu’il est délivré depuis l’écran de stock.',

    'status_pending'          => 'En attente de la banque de sang',
    'status_confirmed'        => 'Confirmée',
    'status_ready'            => 'Prête pour le retrait',
    'status_fulfilled'        => 'Retirée',
    'status_rejected'         => 'Refusée par la banque de sang',
    'status_cancelled'        => 'Annulée par le patient',
    'status_expired'          => 'Expirée sans réponse',

    'urgency_routine'         => 'Ordinaire',
    'urgency_urgent'          => 'Urgente',
    'urgency_emergency'       => 'Urgence vitale',

    /*
    |----------------------------------------------------------------------
    | Déposer une demande depuis le guichet — /facility/blood-requests/new
    |----------------------------------------------------------------------
    */

    'create_btn'                     => 'Nouvelle demande',
    'create_title'                   => 'Nouvelle demande de sang',
    'create_subtitle'                => 'Déposez une demande au nom d’un patient auprès de cette banque de sang. Elle suit le même chemin qu’une demande venue de l’application patient et apparaît aussitôt dans la file.',
    'create_no_hold_notice'          => 'Déposer une demande ne réserve aucune poche. Elle invite la banque de sang à répondre ; le sang ne quitte l’étagère que lorsqu’il est délivré depuis l’écran de stock.',
    'create_health_id_placeholder'   => 'OC-…',
    'create_health_id_help'          => 'L’identifiant santé exact du patient, tel qu’imprimé sur sa carte. Ce guichet n’offre aucune recherche par nom.',
    'create_lbl_listing'             => 'Fiche de la banque de sang',
    'create_lbl_indication'          => 'Indication clinique',
    'create_indication_placeholder'  => 'Pourquoi les poches sont nécessaires — ce que la banque de sang doit savoir avant de répondre.',
    'create_units_help'              => 'Entre 1 et :max poches par demande.',
    'create_submit'                  => 'Déposer la demande',
    'create_recorded'                => 'Demande de sang :reference déposée. Elle est dans la file, en attente de la réponse de la banque de sang.',
    'create_patient_not_found'       => 'Aucun patient ne porte cet identifiant santé. Vérifiez la carte et saisissez-le exactement.',
    'create_err_blood_not_available' => 'Cette banque de sang ne déclare pas ce groupe et ce composant comme disponibles pour le moment ; aucune demande ne peut être déposée.',
    'create_err_too_many_open_requests' => 'Ce patient a déjà atteint le nombre maximal de demandes de sang ouvertes.',
    'create_err_request_already_open'   => 'Ce patient a déjà une demande ouverte pour ce groupe et ce composant auprès de cette banque de sang.',

    'component_whole_blood'          => 'Sang total',
    'component_red_cells'            => 'Concentré de globules rouges',
    'component_platelets'            => 'Plaquettes',
    'component_plasma'               => 'Plasma',

];
