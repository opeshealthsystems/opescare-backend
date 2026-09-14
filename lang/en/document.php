<?php

/*
|--------------------------------------------------------------------------
| Downloadable patient documents
|--------------------------------------------------------------------------
|
| The lab report, prescription, immunisation record and discharge summary a
| patient can download for themselves. Each is rendered at the moment it is
| asked for and never stored, so these strings describe the record as it is
| now rather than as it was when some file was written.
|
| EN and FR are generated from one source and must stay 1:1
| (scripts/i18n-audit.php enforces it).
|
*/

return [

    'issued'            => 'Issued',
    'issued_to'         => 'Issued to',
    'health_id'         => 'Health ID',
    'date_of_birth'     => 'Date of birth',
    'sex'               => 'Sex',
    'not_recorded'      => 'Not recorded',
    'none_recorded'     => 'Nothing recorded',
    'reference'         => 'Reference',
    'facility'          => 'Facility',
    'status'            => 'Status',
    'date'              => 'Date',
    'generated_note'    => 'Generated from the live record at the moment of download. It is not a stored copy.',
    'lab_title'         => 'Laboratory Report',
    'lab_subtitle'      => 'Results as released by the laboratory',
    'lab_test'          => 'Test',
    'lab_ordered'       => 'Ordered',
    'lab_reported'      => 'Reported',
    'lab_ordered_by'    => 'Ordered by',
    'lab_analyte'       => 'Analyte',
    'lab_result'        => 'Result',
    'lab_unit'          => 'Unit',
    'lab_range'         => 'Reference range',
    'lab_flag'          => 'Flag',
    'lab_no_results'    => 'This order carries no released results.',
    'lab_interpretation'=> 'Interpretation',
    'lab_advice'        => 'Discuss these results with the clinician who ordered them. A value outside the reference range is not a diagnosis.',
    'rx_title'          => 'Prescription',
    'rx_subtitle'       => 'As written by the prescriber',
    'rx_prescriber'     => 'Prescriber',
    'rx_prescribed'     => 'Prescribed',
    'rx_expires'        => 'Valid until',
    'rx_drug'           => 'Medicine',
    'rx_dose'           => 'Dose',
    'rx_route'          => 'Route',
    'rx_frequency'      => 'Frequency',
    'rx_duration'       => 'Duration',
    'rx_quantity'       => 'Quantity',
    'rx_days'           => ':count days',
    'rx_no_items'       => 'This prescription has no medicine lines.',
    'rx_dispensed'      => 'Dispensed',
    'rx_pharmacy'       => 'Pharmacy',
    'rx_not_a_supply'   => 'This sheet records what was prescribed. It is not proof that any medicine was supplied.',
    'imm_title'         => 'Immunisation Record',
    'imm_subtitle'      => 'Every dose recorded for this Health ID',
    'imm_vaccine'       => 'Vaccine',
    'imm_dose'          => 'Dose',
    'imm_given'         => 'Given',
    'imm_batch'         => 'Batch',
    'imm_no_doses'      => 'No vaccinations are recorded for this Health ID.',
    'imm_completeness'  => 'This shows what has been recorded on this platform. A dose given elsewhere and never recorded will not appear.',
    'dis_title'         => 'Discharge Summary',
    'dis_subtitle'      => 'Admission and discharge as recorded',
    'dis_admitted'      => 'Admitted',
    'dis_discharged'    => 'Discharged',
    'dis_ward'          => 'Ward',
    'dis_reason'        => 'Reason for admission',
    'dis_outcome'       => 'Discharge reason',
    'dis_destination'   => 'Discharge destination',
    'dis_length'        => 'Length of stay',

    'available_title' => 'Documents you can download',
    'available_hint' => 'Each is generated when you download it, from your record as it stands now. Nothing is stored.',

    'cert_title' => 'Certificate',
    'cert_subtitle' => 'As issued and registered',
    'document_id' => 'Document ID',
    'cert_valid_until' => 'Valid until',
    'cert_no_expiry' => 'No expiry',
    'cert_printed' => 'Printed',
    'cert_particulars' => 'Particulars',
    'cert_state_active' => 'Valid',
    'cert_state_expired' => 'Expired',
    'cert_state_revoked' => 'Revoked',
    'cert_verify' => 'Verification code :code — check it at the OpesCare verification page.',

    /*
    | Ward / on-demand clinical charts assembled by
    | App\Services\Documents\DocumentDataAssembler and DocumentOnDemandAssembler
    | (MAR, ICU flowsheet, nursing chart, progress note, glucose log, handover,
    | partograph, NICU chart, growth chart, surgical safety checklist, nursing
    | admission assessment, investigation request, fall risk, pressure ulcer,
    | wound care). Values are what is printed when the record holds nothing.
    */

    // Shared placeholders
    'not_assigned'          => 'Not assigned',
    'na'                    => 'N/A',
    'pending'               => 'Pending',
    'pending_documentation' => 'Pending documentation',
    'pending_assessment'    => 'Pending assessment',
    'none'                  => 'None',
    'none_known'            => 'None known',
    'none_documented'       => 'None documented',
    'not_documented'        => 'Not documented',
    'not_specified'         => 'Not specified',
    'not_set'               => 'Not set',
    'no_records'            => 'No records',
    'unknown'               => 'Unknown',
    'unknown_patient'       => 'Unknown Patient',
    'per_prescription'      => 'Per prescription',
    'per_order'             => 'Per order',
    'monitoring'            => 'Monitoring',
    'staff'                 => 'Staff',
    'ward'                  => 'Ward',
    'room_air'              => 'Room air',
    'skin_intact'           => 'Intact',
    'diet_normal'           => 'Normal',
    'laboratory'            => 'Laboratory',
    'urgency_routine'       => 'ROUTINE',
    'gestational_age'       => ':weeks w :days d',
    'date_range'            => ':from – :to',

    // Role placeholders printed until a named clinician signs
    'role_assigned_nurse'         => 'Assigned Nurse',
    'role_attending_physician'    => 'Attending Physician',
    'role_resident'               => 'Resident',
    'role_nursing_team'           => 'Nursing Team',
    'role_nursing_staff'          => 'Nursing Staff',
    'role_assigned_midwife'       => 'Assigned Midwife',
    'role_attending_obstetrician' => 'Attending Obstetrician',
    'role_neonatologist'          => 'Neonatologist',
    'role_attending_surgeon'      => 'Attending Surgeon',
    'role_anaesthetist'           => 'Anaesthetist',
    'role_scrub_nurse'            => 'Scrub Nurse',
    'role_admitting_nurse'        => 'Admitting Nurse',
    'role_requesting_clinician'   => 'Requesting Clinician',
    'role_wound_care_nurse'       => 'Wound Care Nurse',
    'shift_outgoing'              => 'Outgoing shift',
    'shift_incoming'              => 'Incoming shift',

    // Per-document defaults
    'icu_bed_default'        => 'ICU Bed',
    'nicu_bed_default'       => 'NICU Incubator',
    'glu_target_range'       => 'Fasting: 4.4–7.2 mmol/L | Post-prandial: < 10.0 mmol/L',
    // Clinical term. Rendered from clinical_code_dictionary (ICD-10 E11) when
    // that row exists; this string is only the fallback when it does not.
    'glu_diabetes_type_fallback' => 'Type 2 Diabetes Mellitus',
    'growth_who_standard'    => 'WHO Child Growth Standards 2006',
    'ssc_scheduled_procedure' => 'Scheduled procedure',
    'naa_admission_mode_emergency' => 'Emergency',
    'naa_reaction'           => 'Reaction',
    'inv_test_default'       => 'Test',
    'inv_tube_as_required'   => 'As required',
    'inv_indication_default' => 'As per clinical assessment',
    'inv_turnaround_default' => 'As per laboratory protocol',
    'fall_tool'              => 'Morse Fall Scale',
    'fall_item_history'      => 'History of falling (immediate or within 3 months)',
    'fall_item_secondary_dx' => 'Secondary diagnosis',
    'fall_item_aid'          => 'Ambulatory aid',
    'fall_item_iv'           => 'IV / Heparin lock',
    'fall_item_gait'         => 'Gait / Transferring',
    'fall_item_mental'       => 'Mental status',
    'fall_pending_intervention' => 'Fall risk assessment pending — complete on admission',
    'braden_tool'            => 'Braden Scale',
    'braden_sensory'         => 'Sensory Perception',
    'braden_moisture'        => 'Moisture',
    'braden_activity'        => 'Activity',
    'braden_mobility'        => 'Mobility',
    'braden_nutrition'       => 'Nutrition',
    'braden_friction'        => 'Friction & Shear',
    'braden_site_sacrum'     => 'Sacrum',
    'braden_site_heels'      => 'Heels',
    'braden_site_elbows'     => 'Elbows',
    'braden_pending_plan'    => 'Assessment pending — complete at admission',

];
