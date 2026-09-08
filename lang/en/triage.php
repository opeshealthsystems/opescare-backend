<?php

/*
|--------------------------------------------------------------------------
| Triage desk
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/triage.php (scripts/i18n-audit.php).
|
| The `no_bands.*` and `advisory_*` strings are the safety-critical ones.
| They are what the nurse reads when this platform has no valid bands for the
| patient in front of her, and they must say so plainly rather than hedge.
*/

return [

    'nav'   => 'Triage',
    'title' => 'Triage',

    // ── Queue ─────────────────────────────────────────────────────────────
    'queue_title'      => 'Triage queue',
    'queue_subtitle'   => 'Patients assessed at this facility, most recent first.',
    'empty_queue'      => 'No triage assessment has been recorded at this facility yet.',
    'new_triage'       => 'New triage',
    'back_to_queue'    => 'Back to the queue',
    'col_patient'      => 'Patient',
    'col_complaint'    => 'Presenting complaint',
    'col_priority'     => 'Priority',
    'col_assessed_by'  => 'Assessed by',
    'col_assessed_at'  => 'Assessed at',
    'col_open'         => 'Open',
    'open_record'      => 'Open',

    // ── Finding the patient ───────────────────────────────────────────────
    'find_patient'    => 'Find the patient',
    'health_id'       => 'Health ID',
    'health_id_hint'  => 'Enter the full Health ID exactly. Patients cannot be searched by name.',
    'lookup'          => 'Look up',
    'not_found'       => 'No patient holds that Health ID.',
    'cancel'          => 'Cancel',
    'patient'         => 'Patient',
    'change_patient'  => 'Change patient',
    'age_years'       => ':years years',
    'age_unknown'     => 'Date of birth not recorded',

    // ── The assessment ────────────────────────────────────────────────────
    'create_title'          => 'Record a triage assessment',
    'assessment'            => 'Assessment',
    'presenting_complaint'  => 'Presenting complaint',
    'complaint_hint'        => 'In the patient’s own words where possible.',
    'pain_score'            => 'Pain score (0–10)',
    'pain_hint'             => 'As the patient reports it. Leave blank if not asked.',
    'pregnancy_status'      => 'Pregnancy status',
    'pregnancy'             => [
        'not_pregnant'   => 'Not pregnant',
        'pregnant'       => 'Pregnant',
        'unknown'        => 'Unknown',
        'not_applicable' => 'Not applicable',
    ],
    'pregnancy_hint'        => 'Recorded because this platform’s vital-sign bands are not valid in pregnancy.',
    'submit'                => 'Record triage',
    'recorded'              => 'Triage assessment recorded.',

    // ── Observations ──────────────────────────────────────────────────────
    'vitals'              => 'Observations',
    'vitals_hint'         => 'Only what was measured here, now. Leave a field blank if it was not taken — blank means “not measured”, never “unchanged”.',
    'temperature'         => 'Temperature (°C)',
    'pulse_rate'          => 'Pulse (bpm)',
    'respiratory_rate'    => 'Respiratory rate (/min)',
    'systolic_bp'         => 'Systolic BP (mmHg)',
    'diastolic_bp'        => 'Diastolic BP (mmHg)',
    'oxygen_saturation'   => 'Oxygen saturation (%)',
    'weight_kg'           => 'Weight (kg)',
    'height_cm'           => 'Height (cm)',
    'gcs_score'           => 'GCS (3–15)',
    'consciousness_level' => 'Consciousness (AVPU)',
    'consciousness'       => [
        'alert'        => 'Alert',
        'voice'        => 'Responds to voice',
        'pain'         => 'Responds to pain',
        'unresponsive' => 'Unresponsive',
    ],
    'no_vitals'      => 'No observations were recorded with this assessment.',
    'recorded_by'    => 'Recorded by',
    'recorded_at'    => 'Recorded at',
    'not_measured'   => 'Not measured',

    // ── Acuity ────────────────────────────────────────────────────────────
    'acuity'         => 'Acuity',
    'acuity_title'   => 'Set the acuity',
    'acuity_hint'    => 'You set this. This platform does not calculate a triage priority and offers no suggested level — the observations below are advisory information only.',
    'acuity_choose'  => 'Choose a priority',
    'acuity_levels'  => [
        'P1_immediate'   => 'P1 — Immediate',
        'P2_urgent'      => 'P2 — Urgent',
        'P3_less_urgent' => 'P3 — Less urgent',
        'P4_standard'    => 'P4 — Standard',
        'P5_non_urgent'  => 'P5 — Non-urgent',
    ],
    // The vocabulary key App\Support\TriageAcuity::labelKey() points at.
    'acuity_legacy'  => 'Recorded before this desk existed (:value)',
    'acuity_none'    => 'No priority recorded',
    'current_priority' => 'Current priority',
    'assigned_by_nurse' => 'Assigned by a clinician',
    'score_history'  => 'Priority history',
    'scoring_manual' => 'Set manually',

    // ── The advisory band panel ───────────────────────────────────────────
    'advisory_title'   => 'Advisory flags',
    'advisory_note'    => 'Derived from published adult bands (NEWS2, ages 16 and over). Advisory only — it means “a person should look at this”, never “this patient is safe”. It is not a triage priority and nothing here has assigned one.',
    'advisory_source'  => 'Adult bands · advisory',
    'advisory_none'    => 'No observation fell outside its adult band. This is not a statement that the patient is well.',

    'no_bands_title' => 'No vital-sign interpretation is available for this patient',
    'no_bands'       => [
        'paediatric'  => 'This patient is under 16. This platform’s only vital-sign bands are adult bands (NEWS2, ages 16 and over), and paediatric ranges differ by age — a respiratory rate that is normal in a child is an emergency in an adult. Applying adult bands to a child systematically under-triages sick children, so no interpretation and no suggested acuity is shown. Assess this patient clinically.',
        'age_unknown' => 'This patient has no recorded date of birth, so their age cannot be established. This platform’s vital-sign bands are valid only for ages 16 and over, and it will not assume an unknown patient is an adult. No interpretation and no suggested acuity is shown. Assess this patient clinically.',
        'pregnancy'   => 'This patient is recorded as pregnant. This platform’s vital-sign bands (NEWS2) are explicitly not validated in pregnancy, so no interpretation and no suggested acuity is shown. Assess this patient clinically.',
    ],

    // ── Patient-reported readings ─────────────────────────────────────────
    'self_reported_title' => 'The patient’s own readings',
    'self_reported_note'  => 'Recorded by the patient at home, on their own equipment. NOT verified, NOT a clinical measurement, and deliberately not copied into the observations above or used for any flag. Shown as background only.',
    'self_reported_badge' => 'Patient-reported · unverified',
    'self_reported_empty' => 'This patient has logged no readings of their own.',
    'measured_at'         => 'Measured',

    // ── Reassessment ──────────────────────────────────────────────────────
    'show_title'        => 'Triage assessment',
    'reassess_title'    => 'Reassess',
    'reassess_hint'     => 'A waiting patient whose condition has changed is re-triaged, not edited. The original assessment and its observations stay exactly as they were taken.',
    'new_priority'      => 'New priority',
    'reason_for_change' => 'Reason for the change',
    'reason_hint'       => 'Required. What changed, and what you observed.',
    'retake_vitals'     => 'Observations retaken now (optional)',
    'reassess_submit'   => 'Record reassessment',
    'reassessed'        => 'Reassessment recorded.',
    'history_title'     => 'Reassessment history',
    'no_reassessments'  => 'This patient has not been reassessed.',
    'from_to'           => ':from → :to',
    'escalated'         => 'Escalated',
    'de_escalated'      => 'De-escalated',
    'unchanged'         => 'Priority unchanged',
    'reassessed_by'     => 'Reassessed by',
    'reassessed_at'     => 'Reassessed at',
    'vitals_retaken'    => 'Observations retaken',

    // ── Refusals ──────────────────────────────────────────────────────────
    'no_facility_context' => 'Select a facility before opening the triage desk.',
];
