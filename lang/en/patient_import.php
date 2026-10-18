<?php

/*
| Patient record import — records a patient brings in from another provider.
| Always patient-provided and unverified; never worded as clinician-verified.
*/

return [
    'unverified_label'       => 'Patient-provided, unverified',
    'not_a_bundle'           => 'This file is not a FHIR R4 Bundle with entries.',
    'invalid_json'           => 'This file is not valid JSON.',
    'another_patient'        => 'This file names another person\'s Health ID. Records can only be imported into your own record.',
    'document_type_rejected' => 'Only PDF, JPEG, PNG or WebP documents can be imported.',
    'reason' => [
        'duplicate'                => 'Already imported',
        'unsupported_type'         => 'Record type not supported',
        'context_resource_ignored' => 'Context only, not imported',
        'invalid_resource'         => 'Unreadable entry',
        'entered_in_error'         => 'Marked as entered in error at the source',
        'resource_too_large'       => 'Entry too large',
        'too_many_entries'         => 'Over the per-file entry limit',
    ],
];
