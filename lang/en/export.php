<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exported documents — the OpesCare Secure sheet and the medical record PDF
    |--------------------------------------------------------------------------
    | Rendered by dompdf (resources/views/pdf/layouts/secure.blade.php and
    | resources/views/exports/*). Keep every string here, never in the view.
    */

    // Sheet chrome (every secure document)
    'brand'              => 'OpesCare',
    'secure_kicker'      => 'OpesCare Secure document',
    'reference'          => 'Reference',
    'issued'             => 'Issued',
    'holder'             => 'Patient',
    'health_id'          => 'Health ID',
    'page'               => 'Page',
    'microtext'          => 'OpesCare · Secure · Verified · Authentic · Interoperable · OpesCare · Secure · Verified · Authentic · Interoperable',
    'footer_confidential' => 'Confidential. This document belongs to the named patient and was produced from their OpesCare record at their request.',
    'footer_verify'      => 'A facility can confirm any OpesCare document by asking the patient to present their Health ID.',
    'not_recorded'       => 'Not recorded',

    // Medical record export
    'record_title'       => 'Medical Record',
    'record_subtitle'    => 'Complete record export',
    'section_identity'   => 'Patient',
    'full_name'          => 'Full name',
    'date_of_birth'      => 'Date of birth',
    'sex'                => 'Sex',
    'blood_group'        => 'Blood group',
    'phone'              => 'Phone',
    'generated'          => 'Generated',
    'sex_male'           => 'Male',
    'sex_female'         => 'Female',
    'sex_other'          => 'Other',

    'section_allergies'  => 'Allergies',
    'allergen'           => 'Allergen',
    'severity'           => 'Severity',
    'allergies_none'     => 'No allergies recorded.',

    'section_diagnoses'  => 'Active diagnoses',
    'diagnosis'          => 'Diagnosis',
    'code'               => 'Code',
    'status'             => 'Status',
    'recorded'           => 'Recorded',

    'section_medications' => 'Current medications',
    'medicine'           => 'Medicine',
    'dose'               => 'Dose',
    'frequency'          => 'Frequency',
    'duration'           => 'Duration',
    'days'               => ':count day|:count days',
    'prescribed'         => 'Prescribed',
    'prescribed_at_facility' => 'Prescribed :date at :facility',
    'prescription_notes' => 'Notes',

    'section_vitals'     => 'Recent vitals',
    'vitals_hint'        => 'Last :count readings',
    'date'               => 'Date',
    'blood_pressure'     => 'Blood pressure',
    'pulse'              => 'Pulse',
    'temperature'        => 'Temp (°C)',
    'spo2'               => 'SpO₂ (%)',
    'weight'             => 'Weight (kg)',

    'section_labs'       => 'Recent laboratory results',
    'labs_hint'          => 'Last :count results',
    'test'               => 'Test',
    'result'             => 'Result',
    'unit'               => 'Unit',
    'reference_range'    => 'Reference range',
    'flag'               => 'Flag',

    'section_immunizations' => 'Immunisation history',
    'vaccine'            => 'Vaccine',
    'date_given'         => 'Date given',
    'dose_number'        => 'Dose',
    'lot_number'         => 'Lot number',

];
