<?php

/*
|--------------------------------------------------------------------------
| Laboratory desk (web portal, /facility/lab)
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/lab_desk.php (scripts/i18n-audit.php).
|
| The `no_range`, `unflagged` and `flag_source_*` strings are the
| safety-critical ones: they are what the bench reads when this platform has
| nothing to compare a value against, and they must say so rather than hedge.
*/

return [

    'nav'            => 'Laboratory desk',
    'nav_collection' => 'Sample collection',
    'title'          => 'Laboratory desk',

    // ── Worklist ──────────────────────────────────────────────────────────
    'worklist_title'    => 'Laboratory worklist',
    'worklist_subtitle' => 'This facility’s orders, most urgent first and oldest first within an urgency.',
    'empty_worklist'    => 'No order in this column.',
    'status'            => [
        'ordered'     => 'Awaiting sample',
        'collected'   => 'Sample in',
        'in_progress' => 'In progress',
        'resulted'    => 'Released',
        'validated'   => 'Validated',
        'cancelled'   => 'Closed',
    ],
    'urgency'           => [
        'stat'    => 'STAT',
        'urgent'  => 'Urgent',
        'routine' => 'Routine',
    ],
    'col_patient'     => 'Patient',
    'col_test'        => 'Test',
    'col_urgency'     => 'Urgency',
    'col_ordered_by'  => 'Ordered by',
    'col_ordered_at'  => 'Ordered',
    'col_results'     => 'Results',
    'col_open'        => 'Open',
    'open_order'      => 'Open',
    'health_id'       => 'Health ID',
    'health_id_hint'  => 'Enter the full Health ID exactly. Patients cannot be searched by name.',
    'lookup'          => 'Look up',
    'clear'           => 'Clear',
    'filtered_by'     => 'Showing orders for :health_id only.',

    // ── Sample collection ─────────────────────────────────────────────────
    'collection_title'    => 'Sample collection',
    'collection_subtitle' => 'Orders waiting for a sample at this facility, oldest first.',
    'empty_collection'    => 'Nothing is waiting for a sample.',
    'specimen_type'       => 'Specimen',
    'specimen_hint'       => 'Optional. Pre-filled from the LOINC dictionary when the ordered test names one; otherwise leave blank rather than guess.',
    'specimen_unknown'    => 'The dictionary names no specimen for this test.',
    'mark_collected'      => 'Mark collected',
    'collected'           => 'Sample recorded. The order has moved to the bench.',
    'back_to_worklist'    => 'Back to the worklist',

    // ── Order page ────────────────────────────────────────────────────────
    'order_title'       => 'Laboratory order',
    'patient'           => 'Patient',
    'test'              => 'Test',
    'test_code'         => 'Local code',
    'loinc'             => 'LOINC',
    'loinc_none'        => 'No LOINC mapping for this test. None is written on its results.',
    'dictionary'        => 'Dictionary',
    'dictionary_specimen' => 'Specimen',
    'dictionary_scale'    => 'Scale',
    'dictionary_units'    => 'Example units',
    'indication'        => 'Clinical indication',
    'order_notes'       => 'Order notes',
    'ordered_by'        => 'Ordered by',
    'ordered_at'        => 'Ordered',
    'collected_at'      => 'Sample collected',
    'resulted_at'       => 'Released',
    'validated_by'      => 'Validated by',
    'not_yet'           => 'Not yet',
    'unknown'           => 'Not recorded',
    'age_years'         => ':years years',

    'results_title'     => 'Results',
    'empty_results'     => 'No result has been entered on this order.',
    'col_analyte'       => 'Analyte',
    'col_value'         => 'Value',
    'col_unit'          => 'Unit',
    'col_reference'     => 'Reference interval',
    'col_flag'          => 'Flag',
    'col_entered_by'    => 'Entered by',
    'col_resulted_at'   => 'Resulted',
    'col_actions'       => 'Actions',
    'no_range'          => 'No reference interval — not compared',
    'no_unit'           => 'No unit recorded',
    'flag'              => [
        'critical'  => 'Critical',
        'high'      => 'High',
        'low'       => 'Low',
        'abnormal'  => 'Abnormal',
        'normal'    => 'Normal',
        'unflagged' => 'Not compared',
    ],
    'flag_source_published' => 'derived from a published reference range',
    'flag_source_range'     => 'derived from the laboratory’s own interval',
    'flag_source_lab'       => 'recorded by the laboratory',
    'superseded'            => 'Superseded',
    'corrects'              => 'Correction',
    'correct'               => 'Correct',
    'cancel'                => 'Cancel',

    // ── Critical values ───────────────────────────────────────────────────
    'alert_title'          => 'Critical value',
    'alert_pending'        => 'Awaiting clinician acknowledgement',
    'alert_acknowledged'   => 'Acknowledged by :name on :at',
    'alert_threshold'      => 'Crossed :threshold',
    'alert_note'           => 'The ordering clinician acknowledges a critical value from their results inbox. Until then it shows here as pending.',

    // ── Entering a result ─────────────────────────────────────────────────
    'entry_title'        => 'Enter a result',
    'entry_hint'         => 'Every entry is a new row. Nothing already entered is changed.',
    'parameter_name'     => 'Analyte',
    'parameter_hint'     => 'Defaults to the ordered test.',
    'value'              => 'Value',
    'value_hint'         => 'A number, or a short text such as “Negative”. Text is stored as typed and never compared.',
    'unit'               => 'Unit',
    'unit_hint'          => 'As reported by the analyser. Leave blank if there is none — a unit is never filled in for you.',
    'unit_suggested'     => 'Dictionary example units: :units',
    'notes'              => 'Notes',
    'range_preview'      => 'Published interval for this test and patient',
    'range_preview_none' => 'No published reference interval matches this test for this patient. Values will be stored without a flag.',
    'range_critical'     => 'Critical below :low / above :high',
    'submit_result'      => 'Enter result',
    'result_entered'     => 'Result entered.',

    'correct_title'      => 'Correct this result',
    'correct_hint'       => 'The original stays on the record. A new row carries the corrected value and names this one. On a validated order the panel returns to “Released” and must be validated again.',
    'reason'             => 'Reason for the correction',
    'submit_correction'  => 'Record correction',
    'result_corrected'   => 'Correction recorded. The original result is unchanged.',

    // ── Release & validation ──────────────────────────────────────────────
    'release'            => 'Release results',
    'release_hint'       => 'Releases every result on this order to the patient and the ordering clinician. This cannot be undone.',
    'released'           => 'Results released.',
    'validate'           => 'Validate',
    'validate_hint'      => 'A second person confirms the released panel. Whoever entered or released a result on it cannot validate it.',
    'validated'          => 'Order validated.',
    'validated_note'     => 'This order is validated. Its results can only be corrected, never edited.',
    'validation_locked'  => 'Validation is taken by a laboratory validator or manager.',

    // ── Refusals ──────────────────────────────────────────────────────────
    'err' => [
        'order_not_found'     => 'That order is not on this laboratory’s worklist.',
        'result_not_found'    => 'That result is not on this order.',
        'no_facility_context' => 'This session is not bound to a facility. Sign in again.',
        'validated_locked'    => 'This order is validated. Correct a result instead of entering a new one.',
        'not_released'        => 'Only a released, not-yet-validated order can be validated.',
        'self_validation'     => 'You entered or released a result on this order and cannot validate it. A second person must.',
        'order_closed'        => 'This order is :status and cannot be worked further.',
        'reason_required'     => 'Give the reason for the correction.',
        'refused'             => 'The laboratory refused that action.',
    ],
];
