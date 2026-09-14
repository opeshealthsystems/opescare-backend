<?php

/*
|--------------------------------------------------------------------------
| Bureau de la file d'attente (/facility/queue)
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/queue_desk.php (scripts/i18n-audit.php).
|
| Espace de noms distinct de lang/queue.php, une ancienne ebauche inutilisee
| pour un afficheur public jamais construit.
*/

return [

    'nav'   => 'File d’attente',
    'title' => 'File d’attente',

    // ── La file en direct ─────────────────────────────────────────────────
    'queue_title'     => 'Salle d’attente',
    'queue_subtitle'  => 'Toutes les personnes en cours dans cet établissement, les plus urgentes d’abord, puis celles qui attendent depuis le plus longtemps.',
    'empty_queue'     => 'Personne n’attend dans cet établissement pour le moment.',
    'check_in'        => 'Enregistrer un patient',
    'back_to_queue'   => 'Retour à la file',
    'closed_title'    => 'Terminés aujourd’hui',
    'closed_empty'    => 'Aucun ticket n’a été clôturé aujourd’hui.',

    'col_number'      => 'N°',
    'col_patient'     => 'Patient',
    'col_priority'    => 'Priorité',
    'col_status'      => 'Statut',
    'col_checked_in'  => 'Arrivée',
    'col_waited'      => 'Attente',
    'col_served'      => 'En salle',
    'col_with'        => 'Avec',
    'col_actions'     => 'Actions',

    // ── Les chiffres du jour ──────────────────────────────────────────────
    'summary_title'          => 'Aujourd’hui, mesuré',
    'summary_hint'           => 'Chaque chiffre est calculé à partir d’horodatages écrits une seule fois et jamais réécrits.',
    'summary_active'         => 'Présents en ce moment',
    'summary_waiting'        => 'En attente d’appel',
    'summary_seen'           => 'Vus aujourd’hui',
    'summary_left'           => 'Absents ou annulés',
    'summary_checked_in'     => 'Enregistrés aujourd’hui',
    'summary_median_wait'    => 'Attente médiane avant l’appel',
    'summary_longest_wait'   => 'Attente la plus longue avant l’appel',
    'summary_median_service' => 'Durée médiane en salle',
    'summary_none_yet'       => 'Personne n’a encore été appelé aujourd’hui.',

    // ── Durées ────────────────────────────────────────────────────────────
    'duration_minutes' => ':n min',
    'duration_hours'   => ':h h :m min',
    'still_waiting'    => 'toujours en attente',
    'still_in_room'    => 'en cours',

    // ── Priorité ──────────────────────────────────────────────────────────
    'priority_from_triage'  => 'Triage',
    'priority_from_arrival' => 'Accueil',
    'arrival_priority'      => 'Priorité à l’accueil',
    'arrival_priority_hint' => 'Ce n’est pas une acuité clinique. Si une infirmière trie le patient, la priorité de triage remplace celle-ci dans l’ordre de la file.',
    'arrival_levels'        => [
        1 => 'Urgence — envoyer au triage immédiatement',
        3 => 'File prioritaire — enceinte, nourrisson, personne âgée, handicap',
        5 => 'Standard',
    ],
    'priority_reason'       => 'Motif',
    'priority_reason_hint'  => 'Obligatoire pour tout niveau au-dessus de standard.',

    // ── Statuts ───────────────────────────────────────────────────────────
    'status' => [
        'waiting'         => 'En attente',
        'called'          => 'Appelé',
        'service_started' => 'En consultation',
        'completed'       => 'Terminé',
        'no_show'         => 'Absent',
        'cancelled'       => 'Annulé',
    ],

    // ── Actions ───────────────────────────────────────────────────────────
    'action_call'      => 'Appeler',
    'action_recall'    => 'Rappeler',
    'action_start'     => 'Commencer',
    'action_complete'  => 'Terminer',
    'action_no_show'   => 'Absent',
    'action_cancel'    => 'Annuler',
    'action_open'      => 'Ouvrir',
    'cancel_reason'    => 'Motif de l’annulation',

    // ── Enregistrement ────────────────────────────────────────────────────
    'create_title'      => 'Enregistrer un patient',
    'find_patient'      => 'Trouver le patient',
    'health_id'         => 'Identifiant santé',
    'health_id_hint'    => 'Saisissez l’identifiant santé complet, exactement. Les patients ne peuvent pas être recherchés par nom.',
    'lookup'            => 'Rechercher',
    'not_found'         => 'Aucun patient ne porte cet identifiant santé.',
    'cancel'            => 'Annuler',
    'patient'           => 'Patient',
    'change_patient'    => 'Changer de patient',
    'already_in_queue'  => 'Ce patient est déjà dans la file sous le numéro :number.',
    'confirm_check_in'  => 'Enregistrer',
    'walk_in_note'      => 'Sans rendez-vous. Une visite est ouverte dans cet établissement, ou la visite ouverte est réutilisée.',
    'todays_bookings'   => 'Rendez-vous du jour non encore enregistrés',
    'bookings_empty'    => 'Aucun rendez-vous du jour n’attend d’être enregistré.',
    'col_time'          => 'Heure',
    'col_clinician'     => 'Clinicien',
    'col_booking_status'=> 'Rendez-vous',
    'check_in_booking'  => 'Enregistrer',
    'checked_in'        => 'Enregistré — ticket :number.',

    // ── Page du ticket ────────────────────────────────────────────────────
    'ticket_title'      => 'Ticket :number',
    'timeline'          => 'Chronologie',
    'checked_in_at'     => 'Arrivée',
    'called_at'         => 'Appelé',
    'service_started_at'=> 'Début de la consultation',
    'completed_at'      => 'Terminé',
    'cancelled_at'      => 'Clôturé',
    'history_title'     => 'Historique',
    'history_hint'      => 'En ajout seulement. Chaque transition est une ligne ; rien n’est modifié.',
    'col_from'          => 'De',
    'col_to'            => 'À',
    'col_by'            => 'Par',
    'col_at'            => 'Le',
    'col_reason'        => 'Motif',
    'not_yet'           => 'pas encore',
    'booking'           => 'Rendez-vous',
    'walk_in'           => 'Sans rendez-vous',
    'waited_total'      => 'Attente avant l’appel',
    'served_total'      => 'Durée en salle',

    // ── Résultats ─────────────────────────────────────────────────────────
    'done' => [
        'called'    => 'Ticket :number appelé.',
        'started'   => 'Ticket :number en consultation.',
        'completed' => 'Ticket :number terminé.',
        'no_show'   => 'Ticket :number marqué absent.',
        'cancelled' => 'Ticket :number annulé.',
    ],

    'refusal' => [
        'already_in_queue'                 => 'Ce patient est déjà dans la file.',
        'appointment_not_checkable'        => 'Ce rendez-vous ne peut pas être enregistré depuis son statut actuel.',
        'number_exhausted'                 => 'Impossible d’attribuer un numéro de ticket. Réessayez.',
        'not_allowed_from_waiting'         => 'Cette étape n’est pas possible pour un patient encore en attente.',
        'not_allowed_from_called'          => 'Cette étape n’est pas possible pour un patient déjà appelé.',
        'not_allowed_from_service_started' => 'Cette étape n’est pas possible pendant une consultation. Terminez-la plutôt.',
        'not_allowed_from_completed'       => 'Ce ticket est déjà terminé.',
        'not_allowed_from_no_show'         => 'Ce ticket est déjà clôturé comme absent.',
        'not_allowed_from_cancelled'       => 'Ce ticket est déjà annulé.',
    ],

    'no_facility_context' => 'Aucun établissement n’est sélectionné pour cette session.',
];
