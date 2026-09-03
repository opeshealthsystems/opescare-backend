<?php

/*
|--------------------------------------------------------------------------
| Finder — portail patient (parcours de conversion)
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/finder_patient.php.
*/

return [

    // Tableau de bord
    'greeting'          => 'Bonjour :name',
    'greeting_generic'  => 'Bonjour',
    'greeting_sub'      => 'Votre santé, au même endroit.',
    'quick_actions'     => 'Actions rapides',
    'next_appointment'  => 'Prochain rendez-vous',
    'no_upcoming_title' => 'Aucun rendez-vous à venir',
    'no_upcoming_body'  => 'Prenez rendez-vous dans un établissement près de chez vous.',
    'with_provider'     => 'Avec :name',

    // Assistant de prise de rendez-vous
    'steps_label'     => 'Étape :current sur :total',
    'step_facility'   => 'Établissement',
    'step_type'       => 'Type de rendez-vous',
    'step_time'       => 'Date et heure',
    'step_confirm'    => 'Confirmation',
    'btn_continue'    => 'Continuer',
    'btn_back'        => 'Retour',
    'choose_facility' => 'Choisissez un établissement…',
    'facility_help'   => 'Choisissez l’hôpital, la clinique ou le centre de santé où vous souhaitez vous rendre.',
    'type_help'       => 'Quel est le motif de ce rendez-vous ?',
    'time_help'       => 'Choisissez la date et l’heure qui vous conviennent. L’établissement peut proposer un autre créneau lors de la confirmation.',
    'step_required'   => 'Veuillez renseigner cette étape avant de continuer.',
    'confirm_title'   => 'Confirmez votre demande',
    'confirm_check'   => 'Veuillez vérifier les informations ci-dessous avant l’envoi.',
    'confirm_facility' => 'Établissement',
    'confirm_type'    => 'Type',
    'confirm_time'    => 'Horaire souhaité',
    'confirm_reason'  => 'Motif',
    'not_provided'    => 'Non renseigné',
    'lifecycle_note'  => 'Vous envoyez une demande — pas une réservation définitive. L’établissement l’examinera puis confirmera votre rendez-vous ; vous pourrez suivre son statut dans la liste de vos rendez-vous.',

    // Cartes de rendez-vous et cycle de vie
    'requested_hint'  => 'En attente de confirmation par l’établissement.',
    'reschedule_note' => 'Après le report, le rendez-vous repasse au statut « demandé » jusqu’à confirmation du nouvel horaire par l’établissement.',
    'current_appointment' => 'Rendez-vous actuel',

    // Messagerie / notifications
    'open_conversation' => 'Ouvrir la conversation',

];
