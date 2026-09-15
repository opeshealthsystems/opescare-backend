<?php

// Alertes d'interactions médicamenteuses sur le poste du prescripteur (importé
// de la plateforme, docs/PLATFORM_IMPORTS.md groupe C). Avertissement uniquement.
return [
    'panel_title'   => 'Interactions médicamenteuses possibles',
    'panel_hint'    => 'Ce sont des avertissements, pas un blocage. Vérifiez-les au regard du patient avant d’émettre ; l’ordonnance sera tout de même enregistrée.',
    'acknowledge'   => 'J’ai pris connaissance de ces alertes d’interaction',
    'coverage_note' => 'La vérification ne couvre qu’une courte liste de référence. L’absence d’alerte ne signifie pas qu’une association est sans risque.',
    'saved_with_warnings' => '{1} Ordonnance enregistrée avec 1 alerte d’interaction, consignée dans le journal d’audit.|[2,*] Ordonnance enregistrée avec :count alertes d’interaction, consignées dans le journal d’audit.',

    'severity_minor'           => 'Mineure',
    'severity_moderate'        => 'Modérée',
    'severity_major'           => 'Majeure',
    'severity_contraindicated' => 'Contre-indiquée',

    'pair_aspirin_warfarin'           => 'L’association augmente nettement le risque hémorragique.',
    'pair_clopidogrel_warfarin'       => 'L’association anticoagulant/antiagrégant augmente le risque d’hémorragie.',
    'pair_clarithromycin_simvastatin' => 'L’inhibition du CYP3A4 élève les concentrations de simvastatine — risque de myopathie.',
    'pair_methotrexate_nsaids'        => 'Les AINS réduisent la clairance du méthotrexate — risque de toxicité grave.',
];
