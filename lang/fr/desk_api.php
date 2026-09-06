<?php

/*
|--------------------------------------------------------------------------
| Accueil mobile — le carnet de rendez-vous de l’établissement, l’arrivée
| des patients et les inscriptions sans rendez-vous
|--------------------------------------------------------------------------
|
| Chaque message renvoyé par les contrôleurs /api/mobile/desk et écrit par
| App\Services\FrontDesk\FrontDeskService. Les clés DOIVENT rester 1:1 avec
| lang/en/desk_api.php (php scripts/i18n-audit.php).
|
*/

return [

    // ── Recherche ────────────────────────────────────────────────────────
    'patient_not_found'        => 'Aucun patient ne détient cet identifiant santé. Vérifiez l’identifiant exactement tel qu’il figure sur la carte.',
    'appointment_not_found'    => 'Rendez-vous introuvable.',
    'provider_not_found'       => 'Compte du personnel introuvable.',
    'facility_required'        => 'Cette session n’est rattachée à aucun établissement. Reconnectez-vous.',
    'provider_not_at_facility' => 'Ce clinicien n’est pas affecté à cet établissement ; le rendez-vous ne peut pas lui être orienté.',

    // ── Refus de transition ──────────────────────────────────────────────
    'appointment_closed'    => 'Ce rendez-vous est clos et ne peut plus être modifié.',
    'already_confirmed'     => 'Ce rendez-vous est déjà confirmé.',
    'not_confirm_eligible'  => 'Seul un rendez-vous réservé peut être confirmé.',
    'already_checked_in'    => 'L’arrivée de ce patient est déjà enregistrée.',
    'not_check_in_eligible' => 'Seul un rendez-vous réservé ou confirmé peut être enregistré à l’arrivée.',
    'already_no_show'       => 'Ce rendez-vous est déjà marqué comme absence.',
    'not_no_show_eligible'  => 'Seul un rendez-vous réservé, confirmé ou arrivé peut être marqué comme absence.',
    'already_cancelled'     => 'Ce rendez-vous est déjà annulé.',

    // ── Refus de réservation ─────────────────────────────────────────────
    'facility_not_available' => 'L’établissement est fermé à cette heure. Choisissez un horaire dans ses heures d’ouverture.',
    'provider_not_available' => 'Ce clinicien n’est pas disponible à cette heure. Choisissez un autre horaire ou laissez le rendez-vous non attribué.',

    // ── Échecs ───────────────────────────────────────────────────────────
    'booking_failed'      => 'Le rendez-vous n’a pas pu être réservé. Veuillez réessayer.',
    'reschedule_failed'   => 'Le rendez-vous n’a pas pu être déplacé. Veuillez réessayer.',
    'route_failed'        => 'Le rendez-vous n’a pas pu être orienté. Veuillez réessayer.',
    'confirm_failed'      => 'La confirmation n’a pas pu être enregistrée. Veuillez réessayer.',
    'check_in_failed'     => 'L’arrivée n’a pas pu être enregistrée. Veuillez réessayer.',
    'no_show_failed'      => 'L’absence n’a pas pu être enregistrée. Veuillez réessayer.',
    'cancel_failed'       => 'Le rendez-vous n’a pas pu être annulé. Veuillez réessayer.',
    'registration_failed' => 'Le patient n’a pas pu être inscrit. Veuillez réessayer.',

    // ── Inscription sans rendez-vous ─────────────────────────────────────
    'duplicate_identity' => 'Une personne correspondant à ces informations détient déjà un identifiant santé. Recherchez-la par cet identifiant plutôt que de l’inscrire à nouveau ; si l’identifiant est perdu, orientez-la vers la récupération d’identifiant santé.',

    // ── Motifs enregistrés (inscrits dans la piste d’audit) ───────────────
    'reason_booked'      => 'Rendez-vous réservé par l’accueil depuis l’application mobile.',
    'reason_rescheduled' => 'Rendez-vous déplacé par l’accueil depuis l’application mobile.',
    'reason_routed'      => 'Rendez-vous orienté vers un clinicien par l’accueil depuis l’application mobile.',
    'reason_confirmed'   => 'Rendez-vous confirmé par l’accueil depuis l’application mobile.',
    'reason_checked_in'  => 'Arrivée du patient enregistrée par l’accueil depuis l’application mobile.',
    'reason_no_show'     => 'Marqué comme absence par l’accueil depuis l’application mobile.',
    'reason_cancelled'   => 'Annulé par l’accueil depuis l’application mobile.',
    'reason_follow_up'   => 'Suivi réservé par le clinicien depuis l’application mobile.',

    // ── Notifications au patient (la cloche, et la notification push le cas échéant) ──
    'the_facility'               => 'l’établissement',
    'notify_action_label'        => 'Voir le rendez-vous',
    'notify_booked_title'        => 'Rendez-vous réservé',
    'notify_booked_message'      => 'Un rendez-vous a été réservé pour vous à :facility le :date à :time.',
    'notify_confirmed_title'     => 'Rendez-vous confirmé',
    'notify_confirmed_message'   => 'Votre rendez-vous à :facility le :date à :time est confirmé.',
    'notify_rescheduled_title'   => 'Rendez-vous déplacé',
    'notify_rescheduled_message' => 'Votre rendez-vous à :facility a été déplacé au :date à :time.',
    'notify_routed_title'        => 'Votre clinicien est désigné',
    'notify_routed_message'      => 'Votre rendez-vous à :facility le :date à :time est désormais avec :provider.',
    'notify_cancelled_title'     => 'Rendez-vous annulé',
    'notify_cancelled_message'   => 'Votre rendez-vous à :facility le :date à :time a été annulé. Contactez l’établissement pour reprendre rendez-vous.',
    'notify_follow_up_title'     => 'Rendez-vous de suivi réservé',
    'notify_follow_up_message'   => ':provider a réservé votre suivi à :facility le :date à :time.',

];
