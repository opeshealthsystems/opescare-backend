<?php

/*
|--------------------------------------------------------------------------
| Dossier patient du clinicien — messages de l'API
|--------------------------------------------------------------------------
|
| Toutes les chaînes que MobileDoctorChartController envoie au client. Le
| contrôleur respecte l'en-tête Accept-Language : un clinicien francophone
| reçoit son refus en français et non en anglais.
|
| lang/en/doctor_chart.php et lang/fr/doctor_chart.php doivent rester
| strictement 1:1 — mêmes clés, UTF-8 valide, mêmes :placeholders des deux
| côtés. Vérification : php scripts/i18n-audit.php
|
*/

return [

    // Le patient n'a pas pu être résolu à partir de l'identifiant de l'URL.
    // Volontairement la même réponse pour « dossier inexistant » et « dossier
    // isolé par ce déploiement » : ni l'une ni l'autre ne confirme qu'un
    // identifiant de santé existe.
    'patient_not_found' => 'Aucun dossier patient ne correspond à cet identifiant.',

    // Le refus. Il indique ce qui rendrait la lecture licite sans rien laisser
    // deviner du dossier, et sans confirmer ni infirmer que le patient a déjà
    // été pris en charge quelque part.
    'no_clinical_relationship' => "Vous n'avez pas de relation de soins avec ce patient : son dossier ne peut pas être ouvert. Demandez au patient d'accorder l'accès à votre établissement, ou ouvrez le dossier depuis votre propre agenda.",

    // La divulgation n'a pas pu être inscrite au journal d'accès : elle n'a
    // donc pas eu lieu. C'est un refus de divulguer, pas un échec de lecture.
    'audit_unavailable' => "Ce dossier ne peut pas être ouvert pour le moment, car l'accès qu'il créerait n'a pas pu être enregistré. Veuillez réessayer.",

    // Durée d'un traitement prescrit, rendue depuis prescription_items.duration_days.
    'duration_days' => ':days jours',

];
