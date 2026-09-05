<?php

/*
|--------------------------------------------------------------------------
| Registre des etablissements — ce qu'une fiche peut reellement faire
|--------------------------------------------------------------------------
|
| OpesCare tient deux registres d'etablissements :
|
|   care_facilities — l'annuaire public que le patient consulte. Une ligne
|                     signifie « ce lieu existe et peut etre trouve ».
|   facilities      — le locataire operationnel. Une ligne signifie « des
|                     soins peuvent avoir lieu ici, dans ce systeme ».
|
| Une fiche de l'annuaire est reliee a un locataire par
| care_facilities.facility_id. Seule une fiche reliee peut publier un
| soignant, ouvrir un creneau ou accepter une reservation. La plupart des
| fiches ne sont pas reliees, et ces messages existent pour le dire
| clairement plutot que de mener le patient dans une impasse.
|
| Doit rester 1:1 avec lang/en/facility_registry.php
| (php scripts/i18n-audit.php).
|
*/

return [

    'roster' => [
        'not_connected' => 'Cet établissement figure uniquement dans l’annuaire public. Il n’a pas rejoint OpesCare : il ne publie donc aucun soignant et ne peut pas recevoir de rendez-vous en ligne. Vous pouvez toutefois l’appeler directement.',

        'none_published' => 'Cet établissement est sur OpesCare mais n’a encore publié aucun profil de soignant.',

        'slots_without_roster' => 'Cet établissement n’a pas publié de profils de soignants, mais son agenda de rendez-vous est ouvert : vous pouvez réserver ici sans choisir de médecin au préalable.',

        'published' => ':count soignant publié par cet établissement.|:count soignants publiés par cet établissement.',
    ],

    'summary' => [
        'title'                => 'Registre des établissements',
        'directory_total'      => 'Fiches dans l’annuaire public',
        'connected_total'      => 'Connectés à OpesCare',
        'bookable_total'       => 'Acceptant les rendez-vous en ligne',
        'with_providers_total' => 'Publiant des profils de soignants',
        'explainer'            => 'Chaque fiche ici peut être trouvée, appelée et visitée. Seuls les établissements connectés peuvent aussi publier des soignants, ouvrir des créneaux de rendez-vous et ajouter une visite à votre dossier OpesCare.',
    ],

    'filters' => [
        'connected_only' => 'Connectés uniquement',
        'all_listings'   => 'Toutes les fiches',
        'bookable_only'  => 'Réservables maintenant',
        'showing'        => ':shown sur :total affichés',
    ],

];
