<?php

/*
|--------------------------------------------------------------------------
| Demandes de consentement — un établissement DEMANDE l'accès au dossier
|--------------------------------------------------------------------------
|
| Traduction 1:1 de lang/en/consent_requests.php. Les clés, l'encodage UTF-8 et
| les :placeholders sont vérifiés par `php scripts/i18n-audit.php` (gate CI).
*/

return [

    // ── Messages de l'API ────────────────────────────────────────────────
    'created'               => "Demande d'accès envoyée au patient.",
    'withdrawn'             => "Demande d'accès retirée.",
    'not_found'             => "Cette demande d'accès n'existe pas, ou elle n'a pas été formulée par votre établissement.",
    'patient_not_found'     => "Aucun patient ne porte cet identifiant santé. Vérifiez l'identifiant complet auprès du patient — il ne peut pas faire l'objet d'une recherche.",
    'identity_unresolvable' => "Votre établissement n'a pas pu être déterminé à partir de votre session. Reconnectez-vous.",
    'already_pending'       => "Votre établissement a déjà demandé l'accès à ce patient et attend sa réponse.",
    'already_granted'       => "Ce patient a déjà accordé l'accès à votre établissement. Il n'y a rien à demander.",
    'not_open'              => "Cette demande d'accès n'est plus ouverte — elle est :status.",
    'unknown_facility'      => 'Un établissement de santé',

    // ── La notification du patient ───────────────────────────────────────
    'notify_title'  => 'Un établissement demande à consulter votre dossier',
    'notify_body'   => ":facility demande l'accès à votre dossier de santé pour :purpose. C'est vous qui décidez — rien n'est partagé tant que vous n'avez pas approuvé.",
    'notify_action' => 'Examiner la demande',

    // ── Statut de la demande ─────────────────────────────────────────────
    'status' => [
        'pending'                    => 'En attente du patient',
        'pending_patient_approval'   => 'En attente du patient',
        'approved'                   => 'Approuvée',
        'denied'                     => 'Refusée',
        'expired'                    => 'Expirée',
        'withdrawn'                  => 'Retirée',
    ],

    // ── Motif de la demande d'accès ──────────────────────────────────────
    'purpose' => [
        'treatment'          => 'La prise en charge de ce patient',
        'referral'           => 'Une orientation vers un confrère',
        'second_opinion'     => 'Un deuxième avis',
        'continuity_of_care' => 'La continuité des soins',
        'emergency_followup' => "Le suivi après des soins d'urgence",
    ],

    // ── Ce qui est demandé ───────────────────────────────────────────────
    'scope' => [
        'patient' => [
            'summary' => 'Résumé du dossier',
        ],
        'allergies' => [
            'read' => 'Allergies',
        ],
        'problems' => [
            'read' => 'Problèmes en cours',
        ],
        'encounters' => [
            'read' => 'Visites et consultations',
        ],
        'vitals' => [
            'read' => 'Signes vitaux',
        ],
        'medications' => [
            'read' => 'Médicaments et ordonnances',
        ],
        'diagnoses' => [
            'read' => 'Diagnostics',
        ],
        'orders' => [
            'read' => "Examens de laboratoire et d'imagerie",
        ],
    ],

];
