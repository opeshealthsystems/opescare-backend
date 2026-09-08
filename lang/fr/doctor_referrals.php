<?php

/*
|--------------------------------------------------------------------------
| Mobile clinicien — orientations de clinicien à clinicien
|--------------------------------------------------------------------------
|
| Chaque message renvoyé par MobileDoctorReferralController. Les clés DOIVENT
| rester 1:1 avec lang/en/doctor_referrals.php (php scripts/i18n-audit.php).
|
| Règle de formulation : un refus ne dit jamais si un dossier existe dans un
| autre établissement. « Introuvable » est la réponse pour l'orientation d'un
| autre établissement comme pour un identifiant qui n'a jamais existé. Et une
| orientation qui ne donne aucun accès au dossier le dit clairement — un
| clinicien à qui l'on ne dit pas que l'envoi est vide supposera que l'autre
| établissement peut lire le dossier.
|
*/

return [

    // ── Identité et recherche ────────────────────────────────────────────
    'facility_required'             => 'Cette session n\'est rattachée à aucun établissement. Reconnectez-vous.',
    'patient_not_found'             => 'Patient introuvable.',
    'referral_not_found'            => 'Orientation introuvable.',
    'receiving_facility_not_found'  => 'L\'établissement destinataire est introuvable.',

    // ── Accès ────────────────────────────────────────────────────────────
    'no_clinical_relationship' => 'Vous n\'avez pas de relation de soins avec ce patient. Demandez son consentement avant de l\'orienter.',
    'consent_required'         => 'Ce patient a demandé que son dossier complet reste soumis à son consentement. Demandez son consentement avant de l\'orienter.',
    'wrong_party'              => 'Cette action revient à l\'autre établissement de cette orientation.',
    'audit_unavailable'        => 'Cette action ne peut pas être enregistrée pour le moment ; elle n\'a donc pas été effectuée. Réessayez sous peu.',

    // ── Créer une orientation ────────────────────────────────────────────
    'destination_required'   => 'Indiquez la destination de cette orientation : un établissement, une spécialité ou un clinicien nommé.',
    'self_referral'          => 'Une orientation ne peut pas être adressée à votre propre établissement.',
    'request_not_actionable' => 'Cette demande d\'orientation n\'est pas ouverte, ou n\'appartient pas à ce patient.',

    // ── Cycle de vie ─────────────────────────────────────────────────────
    'illegal_transition' => 'Cette orientation ne peut pas passer à cet état depuis son état actuel.',

    // ── Ce qui est réellement transmis ───────────────────────────────────
    'records_shared'     => 'L\'établissement destinataire peut lire les types de données indiqués, sous le consentement du patient.',
    'records_not_shared' => 'Cette orientation ne donne aucun accès au dossier. L\'établissement destinataire ne verra que le motif et le résumé, jusqu\'à ce que le patient lui accorde son consentement.',

];
