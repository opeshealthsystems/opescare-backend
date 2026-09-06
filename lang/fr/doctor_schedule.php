<?php

/*
|--------------------------------------------------------------------------
| Mobile clinicien — journée, agenda, rendez-vous et demandes de rendez-vous
|--------------------------------------------------------------------------
|
| Chaque message renvoyé par MobileDoctorScheduleController. Les clés doivent
| rester 1:1 avec lang/en/doctor_schedule.php (php scripts/i18n-audit.php).
|
| Règle de formulation : un refus n'indique jamais si un enregistrement existe
| dans un autre établissement. « Introuvable » est la réponse aussi bien pour
| le rendez-vous d'autrui que pour un identifiant jamais émis.
|
*/

return [

    // ── Recherche ────────────────────────────────────────────────────────
    'appointment_not_found' => 'Rendez-vous introuvable.',
    'request_not_found'     => 'Demande de rendez-vous introuvable.',
    'provider_not_found'    => 'Compte clinicien introuvable.',
    'facility_required'     => 'Cette session n’est rattachée à aucun établissement. Reconnectez-vous.',

    // ── Refus de transition ──────────────────────────────────────────────
    'not_check_in_eligible' => 'Seul un rendez-vous réservé ou confirmé peut être enregistré à l’arrivée.',
    'already_checked_in'    => 'L’arrivée de ce rendez-vous est déjà enregistrée.',
    'already_no_show'       => 'Ce rendez-vous est déjà marqué comme absence.',
    'not_no_show_eligible'  => 'Seul un rendez-vous réservé, confirmé ou arrivé peut être marqué comme absence.',
    'already_cancelled'     => 'Ce rendez-vous est déjà annulé.',
    'appointment_closed'    => 'Ce rendez-vous est clos et ne peut plus être modifié.',
    'not_pending'           => 'Cette demande de rendez-vous a déjà été tranchée.',
    'unsupported_status'    => 'Ce rendez-vous ne peut pas passer à ce statut.',

    // ── Échecs ───────────────────────────────────────────────────────────
    'cancel_failed'     => 'Le rendez-vous n’a pas pu être annulé. Veuillez réessayer.',
    'check_in_failed'   => 'L’arrivée n’a pas pu être enregistrée. Veuillez réessayer.',
    'no_show_failed'    => 'L’absence n’a pas pu être enregistrée. Veuillez réessayer.',
    'reschedule_failed' => 'Le rendez-vous n’a pas pu être déplacé. Veuillez réessayer.',
    'decision_failed'   => 'La décision n’a pas pu être enregistrée. Veuillez réessayer.',

    // ── Valeurs critiques de laboratoire sur l’écran d’accueil ───────────
    'critical_value_category' => 'Valeur critique de laboratoire',
    'critical_value_body'     => 'Résultat :value pour un seuil critique de :threshold.',

    // ── Motifs enregistrés (inscrits dans la piste d’audit) ───────────────
    'reason_cancelled'   => 'Annulé par le clinicien depuis l’application mobile.',
    'reason_declined'    => 'Demande refusée par le clinicien depuis l’application mobile.',
    'reason_accepted'    => 'Demande acceptée par le clinicien depuis l’application mobile.',
    'reason_checked_in'  => 'Arrivée du patient enregistrée par le clinicien depuis l’application mobile.',
    'reason_no_show'     => 'Marqué comme absence par le clinicien depuis l’application mobile.',
    'reason_rescheduled' => 'Rendez-vous déplacé par le clinicien depuis l’application mobile.',
    'reason_status'      => 'Statut modifié par le clinicien depuis l’application mobile.',

    // ── Réservation d’un suivi (POST /doctor/patients/{id}/follow-up) ────
    'patient_not_found'      => 'Patient introuvable.',
    'no_clinical_relationship' => 'Vous pourrez programmer un suivi dès que ce patient vous aura donné accès à son dossier.',
    'follow_up_failed'       => 'Le suivi n’a pas pu être réservé. Veuillez réessayer.',
    'facility_not_available' => 'L’établissement est fermé à cette heure. Choisissez un horaire dans ses heures d’ouverture.',
    'provider_not_available' => 'Vous n’êtes pas disponible à cette heure selon vos horaires affichés. Choisissez un autre horaire.',
    'reason_follow_up'       => 'Suivi réservé par le clinicien depuis l’application mobile.',

];
