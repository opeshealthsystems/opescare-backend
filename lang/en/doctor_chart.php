<?php

/*
|--------------------------------------------------------------------------
| Clinician patient chart — API messages
|--------------------------------------------------------------------------
|
| Every string MobileDoctorChartController puts on the wire. The controller
| honours Accept-Language, so a French-speaking clinician is refused in
| French rather than in English.
|
| lang/en/doctor_chart.php and lang/fr/doctor_chart.php MUST stay 1:1 —
| identical keys, valid UTF-8, and the same :placeholders on both sides.
| Verify with: php scripts/i18n-audit.php
|
*/

return [

    // The patient could not be resolved from the id in the URL. Deliberately
    // the same answer for "no such record" and "a record this deployment
    // isolates away" — neither confirms that a Health ID exists.
    'patient_not_found' => 'No patient record matches that identifier.',

    // The refusal. It says what would make the read lawful without hinting at
    // anything in the record, and without confirming or denying that the
    // patient has ever been seen anywhere.
    'no_clinical_relationship' => 'You do not have a care relationship with this patient, so their record cannot be opened. Ask the patient to grant your facility access, or open the chart from your own schedule.',

    // The disclosure could not be written to the audit trail, so it did not
    // happen. This is a refusal to disclose, not a failure to read.
    'audit_unavailable' => 'This record cannot be opened right now because the access it would create could not be recorded. Please try again.',

    // A prescribed course length, rendered from prescription_items.duration_days.
    'duration_days' => ':days days',

    'consent_required' => 'This patient keeps their full record behind their consent. You can see their identity, allergies and current medicines; ask them to grant access for the rest.',
];
