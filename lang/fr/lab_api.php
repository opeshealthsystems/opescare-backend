<?php

/*
|--------------------------------------------------------------------------
| Poste de laboratoire — messages de l'API
|--------------------------------------------------------------------------
|
| Doit rester 1:1 avec lang/en/lab_api.php — vérifié par
| `php scripts/i18n-audit.php` (clés, encodage UTF-8 ET parité des
| :paramètres).
|
| Règle de rédaction : une notification s'affiche sur l'écran verrouillé, qui
| est une surface publique. La notification du patient nomme l'EXAMEN, jamais
| une valeur, un indicateur ou une interprétation.
|
*/

return [

    // Refus. « Introuvable » et « pas à cet établissement » donnent volontairement
    // la même réponse — confirmer qu'une demande existe ailleurs est une divulgation.
    'order_not_found'   => "Cette demande d'examen ne figure pas sur la liste de travail de ce laboratoire.",
    'request_not_found' => "Cette demande d'examen n'a pas été adressée à ce laboratoire.",
    'already_collected' => 'Un prélèvement a déjà été enregistré pour cette demande.',
    'already_released'  => 'Cette demande a déjà été validée. Saisissez un résultat corrigé plutôt que de la valider à nouveau.',
    'order_closed'      => 'Cette demande est :status et ne peut plus être traitée.',
    'no_results'        => 'Saisissez au moins un résultat avant de valider cette demande.',
    'already_decided'   => 'Cette demande a déjà été :status.',
    'no_facility'       => "Cette session n'est rattachée à aucun établissement. Reconnectez-vous.",

    // Les mots insérés dans :status ci-dessus.
    'order_status'      => [
        'cancelled'        => 'annulée',
        'canceled'         => 'annulée',
        'voided'           => 'invalidée',
        'entered_in_error' => 'marquée saisie par erreur',
        'rejected'         => 'rejetée',
    ],
    'request_status'    => [
        'approved'  => 'approuvée',
        'declined'  => 'refusée',
        'cancelled' => 'annulée par le patient',
    ],

    'validation'        => [
        'results_required' => 'Saisissez au moins un résultat.',
        'value_scalar'     => "La valeur d'un résultat doit être un nombre ou un texte court (100 caractères au plus).",
        'flag'             => 'Un indicateur doit être H (élevé), L (bas), N (normal) ou C (critique).',
        'reason_required'  => 'Donnez au patient la raison du refus de sa demande.',
    ],

    // Notifications émises par le poste.
    'notify'            => [
        'result_ready_title'      => 'Votre résultat :test est disponible',
        'result_ready_body'       => 'Le laboratoire de :facility a validé votre résultat. Ouvrez Résultats de laboratoire pour consulter le compte rendu complet.',
        'result_ready_action'     => 'Voir le résultat',
        'clinician_title'         => 'Résultat validé — :test',
        'clinician_body'          => ':patient — :test a été validé par le laboratoire de :facility.',
        'clinician_body_critical' => 'CRITIQUE — :patient — :test a été validé par le laboratoire de :facility. À examiner sans délai.',
        'request_approved_title'  => "Votre demande d'examen a été approuvée",
        'request_approved_body'   => ':facility a approuvé votre demande de :test. Le laboratoire vous indiquera quand un prélèvement sera nécessaire.',
        'request_declined_title'  => "Votre demande d'examen n'a pas été approuvée",
        'request_declined_body'   => ":facility n'a pas approuvé votre demande de :test. Motif : :reason",
        'request_action'          => 'Voir la demande',
    ],

];
