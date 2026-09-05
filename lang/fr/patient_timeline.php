<?php

/*
|--------------------------------------------------------------------------
| Fil du dossier patient — le résumé d'une ligne
|--------------------------------------------------------------------------
|
| Traduction 1:1 de lang/en/patient_timeline.php. Les clés, l'encodage UTF-8 et
| les :placeholders sont vérifiés par `php scripts/i18n-audit.php` (gate CI).
*/

return [

    // La ligne complète, comme titre de rangée.
    'visit'        => 'Visite clinique — :type',
    'lab_result'   => 'Résultat de laboratoire — :test',
    'prescription' => 'Ordonnance — :count médicament|Ordonnance — :count médicaments',

    // Seulement la fin, pour une rangée dont le titre indique déjà le type.
    'detail_prescription' => ':count médicament|:count médicaments',

    /*
     * Types de visite tels qu'enregistrés dans `visits.visit_type`. Une valeur
     * sans entrée ici est affichée exactement telle qu'elle a été enregistrée,
     * sans lui inventer de nom.
     */
    'visit_type' => [
        'outpatient'     => 'Consultation externe',
        'inpatient'      => 'Hospitalisation',
        'emergency'      => 'Urgences',
        'lab-only'       => 'Laboratoire uniquement',
        'pharmacy-only'  => 'Pharmacie uniquement',
    ],

];
