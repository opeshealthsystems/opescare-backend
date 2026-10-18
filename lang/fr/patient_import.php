<?php

/*
| Import de dossiers par le patient — données apportées d'un autre prestataire.
| Toujours fournies par le patient et non vérifiées.
*/

return [
    'unverified_label'       => 'Fourni par le patient, non vérifié',
    'not_a_bundle'           => 'Ce fichier n\'est pas un Bundle FHIR R4 contenant des entrées.',
    'invalid_json'           => 'Ce fichier n\'est pas un JSON valide.',
    'another_patient'        => 'Ce fichier mentionne le Health ID d\'une autre personne. Vous ne pouvez importer que dans votre propre dossier.',
    'document_type_rejected' => 'Seuls les documents PDF, JPEG, PNG ou WebP peuvent être importés.',
    'reason' => [
        'duplicate'                => 'Déjà importé',
        'unsupported_type'         => 'Type de dossier non pris en charge',
        'context_resource_ignored' => 'Contexte uniquement, non importé',
        'invalid_resource'         => 'Entrée illisible',
        'entered_in_error'         => 'Marqué comme saisi par erreur à la source',
        'resource_too_large'       => 'Entrée trop volumineuse',
        'too_many_entries'         => 'Au-delà de la limite d\'entrées par fichier',
    ],
];
