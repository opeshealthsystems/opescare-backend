<?php

return [

    // Immunizations — record form
    'breadcrumb_record'  => 'Record',
    'ph_vaccine_code'    => 'e.g. BCG, OPV, DPT',
    'hint_vaccine_code'  => 'WHO-EPI code or local code',
    'ph_vaccine_name'    => 'e.g. Bacillus Calmette-Guérin',
    'opt_select'         => '— Select —',
    'route_im'           => 'IM (Intramuscular)',
    'route_sc'           => 'SC (Subcutaneous)',
    'route_oral'         => 'Oral',
    'route_intradermal'  => 'Intradermal',
    'route_intranasal'   => 'Intranasal',
    'ph_site'            => 'e.g. Left deltoid',
    'opt_completed'      => 'Completed',
    'opt_not_done'       => 'Not Done',

    // Immunizations — list
    'lbl_dose_n'         => 'Dose :n',

    // Wards — bed map + create form
    'lbl_floor_n'        => 'Floor :n',
    'ph_ward_name'       => 'e.g. General Ward A',
    'ph_floor'           => 'e.g. 2',
    'ph_building'        => 'e.g. Block A',
    'ward_unknown'       => 'Unknown',

    // Wards — admissions
    'lbl_optional'       => '(optional)',

    // Visits — triage
    'opt_na'             => 'N/A',

    // Visits — triage JS vital-range hints (rendered to staff via textContent)
    'vital_spo2'         => 'SpO₂',
    'vital_pulse'        => 'Pulse',
    'vital_bp_sys'       => 'BP Systolic',
    'vital_temp'         => 'Temp',
    'vital_rr'           => 'Resp. Rate',
    'note_spo2_crit'     => 'Severe hypoxia — suggest Resuscitation',
    'note_spo2_warn'     => 'Low O₂ — suggest Critical',
    'note_pulse_crit'    => 'Extreme HR — suggest Critical',
    'note_pulse_warn'    => 'Abnormal HR',
    'note_bp_crit'       => 'Hypotension — suggest Critical',
    'note_bp_warn'       => 'Low blood pressure',
    'note_temp_crit'     => 'Extreme temperature',
    'note_temp_warn'     => 'Abnormal temperature',
    'note_rr_crit'       => 'Respiratory failure risk',
    'note_rr_warn'       => 'Abnormal breathing rate',

    // Prescribing — the clinician's create form
    'rx_btn_new'            => 'New Prescription',
    'rx_new_title'          => 'New Prescription',
    'rx_new_subtitle'       => 'Prescribe from the national medicine catalogue so the pharmacy dispenses exactly what you ordered.',
    'rx_breadcrumb_new'     => 'New Prescription',
    'rx_patient'            => 'Patient',
    'rx_select_patient'     => '— Select a patient —',
    'rx_patient_hint'       => 'Only patients registered at, treated by, or consented to this facility are listed.',
    'rx_no_patients'        => 'No patient at this facility yet. Register or admit a patient before prescribing.',
    'rx_validity_days'      => 'Valid for (days)',
    'rx_validity_hint'      => 'After this the prescription expires and can no longer be dispensed.',
    'rx_notes'              => 'Notes for the pharmacist',
    'rx_notes_ph'           => 'e.g. Take after food. Review in two weeks.',
    'rx_items'              => 'Medicines',
    'rx_item_n'             => 'Medicine :n',
    'rx_medicine'           => 'Medicine',
    'rx_select_medicine'    => '— Select a medicine —',
    'rx_dose'               => 'Dose',
    'rx_dose_ph'            => 'e.g. 500 mg',
    'rx_dose_hint'          => 'Leave empty to use the catalogue strength.',
    'rx_frequency'          => 'Frequency',
    'rx_frequency_ph'       => 'e.g. 3 times daily',
    'rx_route'              => 'Route',
    'rx_duration_days'      => 'Duration (days)',
    'rx_quantity'           => 'Quantity',
    'rx_add_item'           => 'Add another medicine',
    'rx_remove_item'        => 'Remove',
    'rx_submit'             => 'Issue Prescription',
    'rx_immutable_note'     => 'A prescription cannot be edited or deleted once issued. To correct it, void it and issue a new one.',
    'rx_controlled'         => 'Controlled',
    'rx_prescription_only'  => 'Prescription only',
    'rx_col_actions'        => 'Actions',
    'rx_void'               => 'Void',
    'rx_void_title'         => 'Void this prescription',
    'rx_void_reason'        => 'Reason',
    'rx_void_reason_ph'     => 'e.g. Wrong dose prescribed — replaced by a corrected prescription.',
    'rx_void_entered_error' => 'This prescription should never have existed (entered in error)',
    'rx_void_submit'        => 'Void prescription',
    'rx_void_cancel'        => 'Keep it',
    'rx_voided_reason'      => 'Reason: :reason',

];
