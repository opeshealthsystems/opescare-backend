<?php

/*
 * Le texte d’un rappel de rendez-vous.
 *
 * Écrit pour la cloche et la notification push du patient, dans SA langue —
 * App\Modules\Appointments\Services\AppointmentReminderService::deliver()
 * lit la locale dans les réglages de l’application du patient et non dans la
 * requête, car un rappel part d’une tâche planifiée, sans personne devant
 * l’écran.
 *
 * L’ancien SMS que ces clés remplacent se terminait par « Reply CANCEL to
 * cancel ». Aucun SMS entrant n’est lu sur cette plateforme : c’était une
 * promesse que le logiciel ne pouvait pas tenir. La notification renvoie
 * désormais vers le rendez-vous lui-même.
 */

return [

    'title_48h' => 'Rendez-vous dans deux jours',
    'title_24h' => 'Rendez-vous demain',
    'title_2h'  => 'Rendez-vous dans deux heures',

    'body' => 'Votre rendez-vous à :facility a lieu le :date à :time.',

    // Utilisé lorsque l’établissement du rendez-vous ne peut pas être nommé —
    // le rappel reste vrai et mérite toujours d’être envoyé.
    'the_facility' => 'votre établissement',

    'action' => 'Voir le rendez-vous',

];
