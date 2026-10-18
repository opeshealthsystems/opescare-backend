<?php

/*
|--------------------------------------------------------------------------
| Tableau de bord du centre de diagnostic (portail web, /facility/diagnostic)
|--------------------------------------------------------------------------
| Laboratoire + imagerie sous un même toit. Doit rester 1:1 avec
| lang/en/diagnostic_desk.php (scripts/i18n-audit.php).
*/

return [

    'nav'            => 'Tableau de bord du centre',
    'title'          => 'Centre de diagnostic',
    'subtitle'       => 'Laboratoire et imagerie, aujourd’hui, à :facility.',
    'type_diagnostic'=> 'Centre de diagnostic',
    'type_imaging'   => 'Centre d’imagerie',
    'not_a_centre'   => 'Cet établissement est un laboratoire sans service d’imagerie ; son propre tableau de bord est affiché.',

    // ── Volet laboratoire ─────────────────────────────────────────────────
    'lab_title'            => 'Laboratoire',
    'lab_open'             => 'Demandes ouvertes',
    'lab_open_hint'        => 'En attente de prélèvement, prélevées ou en cours',
    'lab_resulted_today'   => 'Résultats rendus aujourd’hui',
    'lab_resulted_hint'    => 'Libérés depuis minuit',
    'lab_turnaround'       => 'Délai médian',
    'lab_turnaround_hint'  => 'Prélèvement → résultat, :days derniers jours',
    'lab_turnaround_none'  => 'Données insuffisantes',
    'lab_awaiting_validation' => 'En attente de validation',
    'lab_critical_open'    => 'Valeurs critiques ouvertes',
    'lab_requests_pending' => 'Demandes de patients en attente',
    'lab_open_dashboard'   => 'Tableau de bord du laboratoire',
    'lab_open_worklist'    => 'Liste de travail du laboratoire',
    'lab_empty_title'      => 'Aucune demande d’analyse pour l’instant',
    'lab_empty_body'       => 'Rien n’est encore parvenu au laboratoire. Le travail arrive lorsqu’un clinicien prescrit une analyse, qu’un patient envoie une demande depuis l’application, ou qu’un patient sans rendez-vous est enregistré au poste de prélèvement.',
    'lab_empty_action'     => 'Ouvrir le poste de prélèvement',

    // ── Volet imagerie ────────────────────────────────────────────────────
    'imaging_title'           => 'Imagerie',
    'imaging_pending'         => 'En attente',
    'imaging_pending_hint'    => 'Demandés, pas encore programmés',
    'imaging_scheduled'       => 'Programmés',
    'imaging_in_progress'     => 'En cours',
    'imaging_today'           => 'Demandés aujourd’hui',
    'imaging_today_hint'      => 'Demandes enregistrées depuis minuit',
    'imaging_awaiting_report' => 'En attente de compte rendu',
    'imaging_awaiting_hint'   => 'Examen réalisé, aucun compte rendu signé',
    'imaging_reported'        => 'Compte rendu signé',
    'imaging_reported_hint'   => 'Un compte rendu signé existe',
    'imaging_open_worklist'   => 'Liste de travail d’imagerie',
    'imaging_open_new'        => 'Nouvelle demande d’imagerie',
    'imaging_empty_title'     => 'Aucune demande d’imagerie pour l’instant',
    'imaging_empty_body'      => 'Aucun examen n’a été demandé dans ce centre. Enregistrez le premier patient sans rendez-vous ou la première référence depuis le poste d’imagerie ; la demande apparaît sur la liste de travail dès son enregistrement.',
    'imaging_empty_action'    => 'Enregistrer une demande d’imagerie',

    // ── Publication ───────────────────────────────────────────────────────
    'pub_title'            => 'Sur les annuaires publics',
    'pub_subtitle'         => 'Ce que les patients peuvent trouver de ce centre, lu directement dans les lignes publiées.',
    'pub_unlinked'         => 'Ce centre n’est pas relié à une fiche de l’annuaire public ; ni ses analyses ni ses examens ne peuvent donc apparaître dans un annuaire. Revendiquez votre fiche, ou demandez à un administrateur OpesCare de la relier.',
    'pub_listing_inactive' => 'La fiche « :listing » n’est pas active ; les annuaires ne l’affichent donc pas, même si des services sont publiés.',
    'pub_listing'          => 'Fiche publique',
    'pub_lab'              => 'Annuaire des analyses',
    'pub_imaging'          => 'Annuaire d’imagerie',
    'pub_published'        => 'Publiés',
    'pub_in_catalogue'     => 'Au catalogue',
    'pub_last'             => 'Dernière publication',
    'pub_never'            => 'Jamais',
    'pub_lab_none'         => 'Aucune analyse de laboratoire (LOINC) active au catalogue. Ajoutez-en une et elle est publiée aussitôt.',
    'pub_imaging_none'     => 'Aucun examen d’imagerie actif au catalogue. Ajoutez un terme LOINC d’imagerie ou un acte ICHI d’imagerie diagnostique et il est publié aussitôt.',
    'pub_open_catalogue'   => 'Ouvrir le catalogue',
    'pub_open_lab_finder'  => 'Voir l’annuaire des analyses',
    'pub_open_imaging_finder' => 'Voir l’annuaire d’imagerie',

    // ── Badge du catalogue ────────────────────────────────────────────────
    'badge_not_published'  => 'Sur aucun annuaire',
];
