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

    // Prescrire un examen de laboratoire depuis le téléphone
    // (MobileDoctorLabOrderController). « Introuvable » et « pas à vous »
    // donnent ici aussi la même réponse, et le refus de relation est celui du
    // dossier, mot pour mot : la même règle décide qui peut lire un dossier
    // et qui peut y prescrire un examen.
    'patient_not_found'        => "Aucun dossier patient ne correspond à cet identifiant.",
    'no_clinical_relationship' => "Vous n'avez pas de relation de soins avec ce patient ; aucun examen ne peut donc être prescrit pour lui. Demandez au patient d'accorder l'accès à votre établissement, ou ouvrez le dossier depuis votre propre agenda.",
    'order_no_facility'        => "Votre session n'est rattachée à aucun établissement ; la demande n'a donc nulle part où être enregistrée. Connectez-vous à un établissement et réessayez.",
    'order_failed'             => "La demande n'a pas pu être enregistrée. Réessayez, ou enregistrez-la depuis un poste de travail.",

    // Ce que reçoit le PATIENT — cloche et notification push. Ces textes
    // s'affichent sur un écran verrouillé : ils nomment l'examen et
    // l'événement, jamais une valeur, un indicateur ni le commentaire privé
    // du clinicien.
    'patient_notice'           => [
        'reviewed_title'     => "Votre clinicien a examiné votre résultat : :test",
        'reviewed_body_note' => "Votre clinicien vous a laissé une note au sujet de ce résultat. Ouvrez-le pour la lire.",
        'reviewed_body'      => "Votre résultat a été examiné par votre clinicien. Ouvrez-le pour en voir le détail.",
        'reviewed_action'    => "Voir le résultat",
        'ordered_title'      => "Un examen de laboratoire a été prescrit pour vous — :test",
        'ordered_body'       => ":facility a prescrit :test pour vous. Ouvrez la demande pour connaître la suite.",
        'ordered_action'     => "Voir la demande",
        // Remplace :facility lorsque la fiche de l'établissement n'a pas de nom.
        'your_facility'      => "Votre établissement de soins",
    ],

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
