<?php

/*
|--------------------------------------------------------------------------
| Journal d'accès — « qui a ouvert mon dossier, et pourquoi ? »
|--------------------------------------------------------------------------
|
| Traduction 1:1 de lang/en/access_log.php. Les clés, l'encodage UTF-8 et les
| :placeholders sont vérifiés par `php scripts/i18n-audit.php` (gate CI).
*/

return [

    // ── Pourquoi le dossier a été ouvert ─────────────────────────────────
    'purpose' => [
        'treatment'                     => 'Vos soins et votre traitement',
        'emergency'                     => "Accès d'urgence",
        'clinical_messaging'            => 'Un message sécurisé au sujet de vos soins',
        'results_inbox_review'          => 'Examen de vos résultats',
        'results_inbox_acknowledgement' => 'Validation de vos résultats',
        'prescription_dispensing'       => 'Délivrance de votre ordonnance',
        'patient_consent_review'        => 'Vous avez consulté la liste des accès',
        'patient_request'               => "Vous l'avez demandé",
        'patient_self_export'           => 'Vous avez exporté votre propre dossier',
        'data_subject_rights'           => 'Traitement de votre demande relative à vos données',
    ],

    // ── En vertu de quoi ─────────────────────────────────────────────────
    'basis' => [
        'appointment'        => 'ils avaient un rendez-vous avec vous',
        'care_team'          => 'ils font partie de votre équipe soignante',
        'treating_clinician' => 'ils vous avaient soigné',
        'facility_episode'   => 'vous étiez pris en charge dans leur établissement',
        'consent_grant'      => 'vous leur aviez accordé un accès',
        'break_glass'        => "une dérogation d'urgence, dont vous avez été informé",
        // Écrit lorsqu'une lecture du dossier a été REFUSÉE. Cela a autant sa
        // place dans le journal du patient qu'une lecture réussie.
        'no_relationship'    => "aucune relation de soins n'était enregistrée",
    ],

    'purpose_with_basis'   => ':purpose — :basis',
    'purpose_unrecognised' => "OpesCare n'a pas de libellé en langage clair pour ce code de motif : il est affiché exactement tel qu'il a été enregistré. Demandez à l'établissement ce qu'il signifie.",

    // ── Quelle partie du dossier ─────────────────────────────────────────
    'category' => [
        'account'            => 'Votre compte',
        'allergies'          => 'Allergies',
        'clinical_summary'   => 'Résumé du dossier',
        'communication'      => 'Messages',
        'consent'            => 'Consentement et accès',
        'correction_filing'  => 'Une correction que vous avez demandée',
        'data_export'        => 'Une copie de votre dossier',
        'demographics'       => 'Vos données personnelles',
        'diagnoses'          => 'Diagnostics',
        'diagnostic_results' => 'Résultats d’examens',
        'emergency_profile'  => "Profil d'urgence",
        'encounters'         => 'Visites et consultations',
        'full_record'        => 'Votre dossier complet',
        'imaging'            => 'Imagerie',
        'immunizations'      => 'Vaccinations',
        'lab_results'        => 'Résultats de laboratoire',
        'medication'         => 'Médicaments',
        'medications'        => 'Médicaments et ordonnances',
        'orders'             => "Examens de laboratoire et d'imagerie",
        'orders_imaging'     => "Demandes d'imagerie",
        'patient_identity'   => 'Votre identité',
        'prescriptions'      => 'Ordonnances',
        'problem_list'       => 'Problèmes en cours',
        'vitals'             => 'Signes vitaux',
    ],

];
