<?php

/*
|--------------------------------------------------------------------------
| Guichet banque de sang — surface mobile des professionnels
|--------------------------------------------------------------------------
|
| Tous les messages renvoyés par MobileBloodDeskController. Les clés doivent
| rester 1:1 avec lang/en/blood_desk.php (php scripts/i18n-audit.php).
|
*/

return [

    'facility_required'       => 'Votre session n’est rattachée à aucun établissement.',
    'request_not_found'       => 'Demande de sang introuvable pour cet établissement.',
    'audit_unavailable'       => 'Action refusée : elle n’a pas pu être inscrite au journal d’accès.',
    'transition_not_allowed'  => 'Cette demande de sang ne peut plus passer à ce statut.',

    'decision_recorded'       => 'Demande de sang mise à jour.',
    'confirmed_recorded'      => 'Demande confirmée. Le patient est invité à se présenter au guichet ; cette confirmation ne réserve aucune poche.',
    'rejected_recorded'       => 'Demande refusée.',
    'ready_recorded'          => 'Marquée prête pour le retrait.',
    'fulfilled_recorded'      => 'Marquée retirée.',

];
