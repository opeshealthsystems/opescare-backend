<?php

/*
|--------------------------------------------------------------------------
| Patient timeline — the one-line summary of a record entry
|--------------------------------------------------------------------------
|
| Used by MobilePatientController::getTimeline() to build `summary_label` and
| `detail_label`, the localised half of each timeline row.
|
| `summary` itself is an English string the endpoint has always emitted
| ("Prescription — 3 item(s)"), and it stays exactly as it is: clients in the
| field parse it. The home screen printed it verbatim as a row title, so a
| French-speaking patient read "Prescription — 1 item(s)" — untranslated, and
| with the lazy "(s)" plural English readers got too.
|
| The count is a real plural here, not a parenthesis: `trans_choice` picks the
| form, and French agrees the noun.
|
| Must stay 1:1 with lang/fr/patient_timeline.php — `php scripts/i18n-audit.php`
| is a CI gate on keys, encoding and :placeholders.
*/

return [

    // The whole line, as a row title.
    'visit'        => 'Clinical visit — :type',
    'lab_result'   => 'Lab result — :test',
    'prescription' => 'Prescription — :count medicine|Prescription — :count medicines',

    // Just the tail, for a row that already carries the kind as its title.
    'detail_prescription' => ':count medicine|:count medicines',

    /*
     * Visit types as recorded on `visits.visit_type`. A value with no entry
     * here is passed through exactly as stored rather than being dressed up —
     * the patient sees the token, which is honest, instead of a name invented
     * from its spelling.
     */
    'visit_type' => [
        'outpatient'     => 'Outpatient',
        'inpatient'      => 'Inpatient',
        'emergency'      => 'Emergency',
        'lab-only'       => 'Laboratory only',
        'pharmacy-only'  => 'Pharmacy only',
    ],

];
