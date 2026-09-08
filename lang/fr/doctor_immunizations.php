<?php

/*
|--------------------------------------------------------------------------
| Mobile clinicien — la saisie des vaccinations
|--------------------------------------------------------------------------
|
| Chaque message renvoyé par MobileDoctorImmunizationController. Les clés
| DOIVENT rester 1:1 avec lang/en/doctor_immunizations.php
| (php scripts/i18n-audit.php).
|
| Règles de formulation :
|
|   • LES NOMS DE VACCINS NE SONT JAMAIS TRADUITS. Le BCG reste le BCG dans les
|     deux fichiers. Seuls les libellés autour d'un vaccin sont du texte
|     d'interface, et aucun d'eux n'apparaît ici sous forme de variable
|     pouvant interpoler un terme clinique.
|
|   • Un refus ne dit jamais si un dossier existe. « Introuvable » répond pour
|     la dose d'un patient que ce clinicien ne peut pas ouvrir comme pour un
|     identifiant qui n'a jamais existé.
|
|   • Rien ici ne prétend qu'une action a eu lieu si elle n'a pas eu lieu. Pour
|     une réaction grave, il est dit clairement que la déclaration aux
|     autorités reste une démarche humaine, plutôt que de laisser une
|     confirmation suggérer que l'autorité a été prévenue.
|
*/

return [

    // ── Identité et recherche ────────────────────────────────────────────
    'facility_required' => 'Cette session n\'est rattachée à aucun établissement. Reconnectez-vous.',
    'patient_not_found' => 'Patient introuvable.',
    'record_not_found'  => 'Vaccination introuvable.',

    // ── Accès ────────────────────────────────────────────────────────────
    'no_clinical_relationship' => 'Vous n\'avez pas de relation de soins avec ce patient. Demandez son consentement avant d\'enregistrer une vaccination.',
    'consent_required'         => 'Ce patient a demandé que son dossier complet reste soumis à son consentement. Demandez son consentement avant d\'écrire sur son carnet de vaccination.',
    'not_recording_facility'   => 'Seul l\'établissement qui a enregistré cette dose peut la retirer. Si la saisie est erronée, enregistrez ce qui s\'est réellement passé comme une nouvelle dose.',
    'audit_unavailable'        => 'Cette action ne peut pas être enregistrée pour le moment ; elle n\'a donc pas été effectuée. Réessayez sous peu.',

    // ── Enregistrer une dose ─────────────────────────────────────────────
    'not_done_reason_required' => 'Indiquez pourquoi ce vaccin n\'a pas été administré. Une dose notée comme non administrée sans motif est inexploitable pour le clinicien suivant.',
    'administered_in_future'   => 'Un vaccin ne peut pas être enregistré comme administré à une date et une heure futures.',
    'duplicate_dose'           => 'Ce patient a déjà une dose de ce vaccin enregistrée à cette date. Vérifiez la saisie existante avant d\'en enregistrer une autre.',

    // ── Retrait ──────────────────────────────────────────────────────────
    'voided'        => 'Cette dose a été marquée comme saisie erronée. Elle ne compte plus sur le carnet du patient, et la saisie a été conservée avec votre motif.',
    'already_voided' => 'Cette dose a déjà été marquée comme saisie erronée.',

    // ── Manifestations post-vaccinales indésirables ──────────────────────
    'reaction_on_voided_dose'   => 'Cette dose a été saisie par erreur ; aucune réaction ne peut y être rattachée.',
    'authority_report_pending'  => 'Cette réaction a été consignée dans le dossier du patient. Elle n\'a PAS été déclarée à l\'autorité sanitaire — cette démarche reste à effectuer.',

];
