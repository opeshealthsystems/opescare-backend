<?php

/*
|--------------------------------------------------------------------------
| Patient chart (web portal)
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/chart.php (scripts/i18n-audit.php).
|
| The safety-critical strings are the vitals ones: the provenance badges, the
| advisory-band wording, and the `no_bands` refusal that replaces every flag
| for a child, an unknown age or a pregnancy. They must say so plainly.
*/

return [

    'nav'   => 'Patient chart',
    'title' => 'Patient chart',

    // ── Lookup ────────────────────────────────────────────────────────────
    'lookup_title'   => 'Open a patient chart',
    'lookup_subtitle' => 'Enter the full Health ID of the patient in front of you.',
    'health_id'      => 'Health ID',
    'health_id_hint' => 'Exact Health ID only. Patients cannot be searched by name or listed.',
    'lookup'         => 'Open chart',
    'not_found'      => 'No chart is available for that Health ID. Either no patient holds it, or you have no care relationship with this patient: an appointment, care-team membership, a clinical act of yours, a current episode at this facility, or a consent grant to this facility.',
    'landing_hint'   => 'To confirm demographics only, use the Health-ID lookup on the facility dashboard.',
    'back_to_lookup' => 'Look up another patient',
    'no_facility_context' => 'No active facility. Select the facility you are working at before opening a chart.',
    'audit_unavailable'   => 'The access log could not be written, so the record was not shown. Try again shortly.',

    // ── Tabs ──────────────────────────────────────────────────────────────
    'tabs' => [
        'summary'       => 'Summary',
        'encounters'    => 'Encounters',
        'vitals'        => 'Vitals',
        'diagnoses'     => 'Diagnoses',
        'labs'          => 'Lab results',
        'immunizations' => 'Immunizations',
        'preconsult'    => 'Pre-consult',
        'triage'        => 'Triage history',
        'documents'     => 'Documents',
        'partner_reported' => 'Partner-reported',
    ],

    // ── Basis and scope ───────────────────────────────────────────────────
    'opened_on_basis' => 'Opened on the basis of',
    'basis' => [
        'appointment'        => 'your appointment with this patient',
        'care_team'          => 'your membership of this patient’s care team',
        'treating_clinician' => 'a clinical act you performed for this patient',
        'facility_episode'   => 'a current episode of care at this facility',
        'consent_grant'      => 'a consent grant the patient gave this facility',
    ],
    'scope_preview_title' => 'Preview only',
    'scope_preview_note'  => 'This patient keeps their full record behind consent, and this facility holds no active grant. Identity, allergies and current medicines are shown; every other tab needs the patient’s consent.',
    'consent_required_title' => 'The patient’s consent is required',
    'consent_required_note'  => 'This section is withheld because the patient asked that their full record stay behind consent and this facility has no active grant. Ask the patient for access. This refusal has been logged.',

    // ── Identity ──────────────────────────────────────────────────────────
    'patient'      => 'Patient',
    'sex'          => 'Sex',
    'sexes' => [
        'male'    => 'Male',
        'female'  => 'Female',
        'other'   => 'Other',
        'unknown' => 'Not recorded',
    ],
    'blood_group'  => 'Blood group',
    'age'          => 'Age',
    'age_years'    => ':years years',
    'age_unknown'  => 'Date of birth not recorded',
    'age_band'     => 'Age band',
    'age_bands' => [
        'neonate'    => 'Neonate',
        'infant'     => 'Infant',
        'child'      => 'Child',
        'adolescent' => 'Adolescent',
        'adult'      => 'Adult',
        'geriatric'  => 'Older adult',
    ],
    'not_recorded' => 'Not recorded',
    'privacy_title' => 'Patient privacy settings',
    'privacy_consent_on'   => 'Full record behind consent: ON',
    'privacy_consent_off'  => 'Full record behind consent: OFF',
    'privacy_emergency_on' => 'Emergency access: allowed',
    'privacy_emergency_off' => 'Emergency access: withheld by the patient',

    // ── Summary sections ──────────────────────────────────────────────────
    'allergies_title' => 'Allergies',
    'allergies_none'  => 'No allergy has been recorded. This is not a statement that the patient has none.',
    'col_substance'   => 'Substance',
    'col_severity'    => 'Severity',
    'col_status'      => 'Status',
    'col_recorded'    => 'Recorded',

    'problems_title'  => 'Active problems',
    'problems_none'   => 'No problem has been recorded on the problem list.',
    'col_code'        => 'Code',
    'col_description' => 'Description',
    'col_onset'       => 'Onset',

    'medicines_title' => 'Current medicines',
    'medicines_none'  => 'No active prescription is recorded.',
    'col_drug'        => 'Medicine',
    'col_dose'        => 'Dose',
    'col_route'       => 'Route',
    'col_frequency'   => 'Frequency',
    'col_duration'    => 'Duration',
    'duration_days'   => ':days days',
    'col_prescribed'  => 'Prescribed',
    'col_prescriber'  => 'Prescriber',

    // ── Encounters ────────────────────────────────────────────────────────
    'encounters_none' => 'No encounter has been recorded for this patient.',
    'col_started'     => 'Started',
    'col_ended'       => 'Ended',
    'col_type'        => 'Type',
    'col_facility'    => 'Facility',
    'col_doctor'      => 'Attending',
    'reason'          => 'Reason for encounter',
    'history'         => 'History of present illness',
    'examination'     => 'Examination findings',
    'plan'            => 'Treatment plan',
    'discharge'       => 'Discharge disposition',
    'note_none'       => 'No clinical note is attached to this encounter.',

    // ── Vitals ────────────────────────────────────────────────────────────
    'facility_vitals_title' => 'Facility-measured observations',
    'facility_vitals_none'  => 'No observation has been recorded at a facility.',
    'self_reported_title'   => 'The patient’s own readings',
    'self_reported_note'    => 'Recorded by the patient at home, on their own equipment. NOT verified, NOT a clinical measurement, and never interpreted or mixed with the observations above. Shown as background only.',
    'self_reported_empty'   => 'This patient has logged no readings of their own.',
    'col_measure'      => 'Measure',
    'col_value'        => 'Value',
    'col_flag'         => 'Flag',
    'col_provenance'   => 'Source',
    'col_recorded_by'  => 'Recorded by',
    'col_recorded_at'  => 'Recorded at',
    'entered_at_note'  => 'entry time — this row records no measurement time',
    'measured_at'      => 'Measured',
    'measure' => [
        'temperature'       => 'Temperature (°C)',
        'pulse_rate'        => 'Pulse (bpm)',
        'respiratory_rate'  => 'Respiratory rate (/min)',
        'blood_pressure'    => 'Blood pressure (mmHg)',
        'oxygen_saturation' => 'SpO₂ (%)',
        'weight_kg'         => 'Weight (kg)',
        'height_cm'         => 'Height (cm)',
        'gcs_score'         => 'GCS',
    ],
    'advisory_source' => 'Adult bands · advisory',
    'advisory_note'   => 'Flags are derived from published adult bands (NEWS2, ages 16 and over). Advisory only — a flag means “a person should look at this”, never “this patient is safe”, and the absence of a flag is not reassurance.',
    'no_bands_title'  => 'No vital-sign interpretation is available for this patient',
    'no_bands' => [
        'paediatric'  => 'This patient is under 16. The only vital-sign bands on this platform are adult bands (NEWS2, ages 16 and over), and paediatric values vary by age — a respiratory rate that is normal in a child is an emergency in an adult. No flag of any kind is shown. Assess this patient clinically.',
        'age_unknown' => 'This patient has no recorded date of birth, so their age cannot be established. This platform’s bands are valid only from age 16 and it will not assume an unknown patient is an adult. No flag of any kind is shown.',
        'pregnancy'   => 'This reading was taken during a recorded pregnancy. This platform’s bands (NEWS2) are explicitly not validated in pregnancy. No flag is shown for it.',
    ],
    'row_no_bands_pregnancy' => 'Recorded pregnancy — no bands',

    // ── Diagnoses ─────────────────────────────────────────────────────────
    'diagnoses_none'    => 'No diagnosis has been coded for this patient.',
    'col_title'         => 'Title',
    'col_rank'          => 'Rank',
    'primary'           => 'Primary',
    'secondary'         => 'Secondary',
    'col_provider'      => 'Recorded by',
    'col_visit'         => 'Visit',
    'title_from_record' => 'Title as recorded — code not in the dictionary',

    // ── Laboratory ────────────────────────────────────────────────────────
    'labs_none'      => 'No laboratory result has been released for this patient.',
    'col_test'       => 'Analyte',
    'col_loinc'      => 'LOINC',
    'col_range'      => 'Reference range',
    'col_resulted'   => 'Resulted',
    'flag_source' => [
        'lab'       => 'flagged by the laboratory',
        'range'     => 'against the laboratory’s range',
        'published' => 'against a published range',
    ],
    'labs_note'      => 'A flag says who compared the value and against what. “Not recorded” means nobody compared it — it is not “normal”.',

    // ── Immunizations ─────────────────────────────────────────────────────
    'immunizations_none' => 'No immunization has been recorded for this patient.',
    'col_vaccine'        => 'Vaccine',
    'col_dose_number'    => 'Dose',
    'col_administered'   => 'Administered',
    'col_verification'   => 'Verification',
    'historical'         => 'Historical (from a card)',

    // ── Pre-consult ───────────────────────────────────────────────────────
    'preconsult_badge'  => 'Patient-reported · unverified',
    'preconsult_note'   => 'Answers the patient gave before an appointment at this facility, in their own words. Patient-reported context, not a clinical finding: nothing here is a diagnosis, an allergy record or an observation, and it is kept apart from the clinical tabs for that reason.',
    'preconsult_none'   => 'No pre-consult questionnaire has been submitted for an appointment at this facility.',
    'col_submitted'     => 'Submitted',
    'answers_count'     => ':count answers',

    // ── Triage history ────────────────────────────────────────────────────
    'triage_none'      => 'No triage assessment has been recorded for this patient.',
    'col_assessed'     => 'Assessed',
    'col_complaint'    => 'Presenting complaint',
    'col_priority'     => 'Current priority',
    'col_nurse'        => 'Assessed by',
    'priority_none'    => 'No priority',
    'open_triage'      => 'Open at the triage desk',
    'other_facility'   => 'Another facility',

    // ── Documents ─────────────────────────────────────────────────────────
    'documents_none'  => 'No issued document is on record for this patient.',
    'col_document'    => 'Document',
    'col_number'      => 'Number',
    'col_issued'      => 'Issued',

    // ── Cross-links and notices ───────────────────────────────────────────
    'links_title'     => 'Desks',
    'link_triage'     => 'Triage desk',
    'link_queue'      => 'Patient queue',
    'link_blood'      => 'Blood requests',
    'read_only'       => 'Read-only. Nothing on this page writes to the record.',
    'partner_notice'  => 'Readings sent by partner systems are on the Partner-reported tab, labelled with the sending system and facility, and never mixed with readings taken here.',
    'consent_request_title' => 'Ask the patient for access',
    'consent_request_hint' => 'The patient receives the request on their phone and decides. If they approve, this facility can open the chart for 24 hours.',
    'consent_purpose' => 'Reason',
    'consent_justification' => 'Note for the patient (optional)',
    'consent_request_submit' => 'Send request',
    'consent_requested' => 'If this Health ID is registered, the patient has been asked. Look the Health ID up again once they have approved.',
];
