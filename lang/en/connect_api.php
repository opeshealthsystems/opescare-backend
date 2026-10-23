<?php

// OpesCare Connect partner API (resolve / verify / consent / record pull) messages.
return [
    'missing_lookup_criteria' => 'Provide a Health ID, or a full name and date of birth.',
    'consent_required_lookup' => 'A demographic lookup needs the patient\'s agreement: send consent_acknowledged=true once the patient has agreed.',
    'health_id_not_found'     => 'This Health ID is not registered on OpesCare.',
    'patient_not_found'       => 'No OpesCare patient matches these details. The patient can register for a Health ID in the OpesCare app.',
    'ambiguous_match'         => 'More than one patient matches these details. Ask the patient for their Health ID or phone number.',
    'health_id_invalid'       => 'This is not a valid OpesCare Health ID (format or check block).',
    'facility_required'       => 'This app is not bound to a facility. Request production access for a facility first.',
    'consent_required'        => 'No active consent grant from this patient to your facility covers this request.',
    'consent_facility'        => 'This consent grant belongs to another facility.',
    'consent_scope'           => 'This consent grant does not cover the patients:read scope.',
    'health_id_suspended'     => 'This Health ID is not active.',
    'invalid_payload'         => 'The request is invalid.',
    'consent_requested'       => 'Consent requested. The patient approves or denies it in the OpesCare app.',
    'consent_already_open'    => 'A consent request from your facility is already waiting for this patient.',
    'consent_already_granted' => 'Your facility already holds an active consent grant covering this scope.',
];
