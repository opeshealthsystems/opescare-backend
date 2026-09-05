<?php

/*
|--------------------------------------------------------------------------
| Boîte de résultats du clinicien — messages de l'API
|--------------------------------------------------------------------------
|
| Doit rester 1:1 avec lang/en/doctor_results.php — vérifié par
| `php scripts/i18n-audit.php` (clés, encodage UTF-8 ET parité des
| :paramètres).
|
| Règle de rédaction : ne jamais affirmer plus que ce que le dossier contient.
| « Le laboratoire n'a enregistré aucun indicateur » et « le laboratoire a
| enregistré un résultat normal » sont deux phrases différentes.
|
*/

return [

    // Erreurs. « Introuvable » et « pas à vous » donnent volontairement la même
    // réponse : dire à un clinicien qu'un résultat existe mais appartient à un
    // autre établissement confirme l'existence du dossier à qui n'y a pas droit.
    'not_found'          => "Ce résultat ne figure pas dans votre boîte.",
    'order_not_found'    => "Cette demande d'examen ne figure pas dans votre boîte.",
    'acknowledge_failed' => "L'accusé de réception n'a pas pu être enregistré. Réessayez, ou accusez réception depuis un poste de travail.",

    // Catégories de ligne.
    'category'           => [
        'lab_result' => 'Résultat de laboratoire',
        'radiology'  => "Compte rendu d'imagerie",
    ],

    'radiology_report'   => "Compte rendu d'imagerie",

    // Modalité d'imagerie, telle qu'enregistrée dans radiology_reports.
    // Jamais la valeur brute de l'énumération.
    'modality'           => [
        'xray'       => 'Radiographie',
        'ct'         => 'Scanner',
        'mri'        => 'IRM',
        'ultrasound' => 'Échographie',
        'echo'       => 'Échocardiographie',
        'nuclear'    => 'Médecine nucléaire',
        'pet'        => 'TEP',
        'other'      => 'Imagerie',
    ],

    // Le résumé d'une ligne, sous le titre. Formes comptées, pour qu'une
    // valeur unique ne s'affiche jamais « 1 valeurs ».
    'body'               => [
        'no_values' => "Le laboratoire a validé cette demande sans valeur individuelle.",
        // Compté sur :abnormal — voir la note dans la version anglaise :
        // « valeur » et son participe s'accordent sur le nombre signalé.
        'abnormal'  => "{1} :abnormal valeur sur :total signalée par le laboratoire.|[2,*] :abnormal valeurs sur :total signalées par le laboratoire.",
        'normal'    => "{1} :total valeur, enregistrée comme normale par le laboratoire.|[2,*] :total valeurs, toutes enregistrées comme normales par le laboratoire.",
        'unflagged' => "{1} :total valeur validée. Le laboratoire n'a enregistré aucun indicateur.|[2,*] :total valeurs validées. Le laboratoire n'a enregistré aucun indicateur.",
    ],

];
