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

];
