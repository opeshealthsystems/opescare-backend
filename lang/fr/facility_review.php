<?php

/*
|--------------------------------------------------------------------------
| Revue des imports d'etablissements — le bureau de comparaison
|--------------------------------------------------------------------------
|
| Chaines de la partie de l'ecran de revue qui permet a une personne de
| trancher une candidature d'import : la candidature et la fiche qu'elle
| pourrait dupliquer, affichees cote a cote, ainsi que l'attribution
| OpenStreetMap que la licence ODbL nous oblige a porter partout ou des
| donnees derivees d'OSM sont affichees.
|
| Doit rester 1:1 avec lang/en/facility_review.php (php scripts/i18n-audit.php).
|
*/

return [

    // ── Comparaison cote a cote ─────────────────────────────────────────────
    'compare_heading'   => 'Comparer avant de decider',
    'compare_candidate' => 'Candidature a l\'import',
    'compare_existing'  => 'Fiche existante potentiellement dupliquee',

    'field_name'   => 'Nom',
    'field_type'   => 'Type',
    'field_city'   => 'Ville',
    'field_region' => 'Region',
    'field_coords' => 'Coordonnees',
    'field_phone'  => 'Telephone',
    'field_source' => 'Source',
    'field_status' => 'Statut de la fiche',

    'value_missing' => 'Non renseigne',
    'no_match'      => 'L\'importateur n\'a trouve aucune fiche existante ressemblant a cette candidature. Elle ne peut qu\'etre ajoutee comme nouvel etablissement, differee ou rejetee.',

    'match_score'    => 'Similarite du nom :score',
    'match_distance' => 'Distants de :metres m',

    'cluster_warning' => ':count candidatures de cette file pointent vers cette meme fiche. Traitez-les ensemble.',

    // ── Attribution (obligation de licence ODbL, pas un ornement) ───────────
    'attribution'      => 'Donnees source : :attribution',
    'attribution_note' => 'Les candidatures ci-dessous sont derivees d\'OpenStreetMap et publiees sous licence Open Database (ODbL). L\'attribution doit etre conservee sur toute fiche creee a partir d\'elles.',

    // ── Report de decision ──────────────────────────────────────────────────
    'btn_defer'    => 'Differer',
    'defer_hint'   => 'Mettre cette candidature de cote sans la trancher. Elle quitte la file mais reste ouverte — elle pourra encore etre ajoutee, fusionnee ou rejetee.',
    'defer_reason' => 'Motif du report ?',

    'flash_deferred'      => 'Candidature differee. Elle est hors de la file mais toujours non tranchee.',
    'error_defer_decided' => 'Cette candidature a deja ete tranchee et ne peut plus etre differee.',

    // ── Filtre de statut ────────────────────────────────────────────────────
    'filter_status'   => 'Statut',
    'status_pending'  => 'En attente de decision',
    'status_deferred' => 'Differee',
    'status_imported' => 'Ajoutee',
    'status_merged'   => 'Fusionnee',
    'status_rejected' => 'Rejetee',

    'decided_meta'  => 'Tranchee par :name le :date',
    'deferred_meta' => 'Differee par :name le :date',
    'never_verified' => 'Accepter une candidature la reference. Cela ne la verifie pas — aucun etablissement de cet annuaire ne porte un statut verifie.',

];
