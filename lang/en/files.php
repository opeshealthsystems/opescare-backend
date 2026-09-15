<?php

// Patient file attachments + radiology report release (docs/PLATFORM_IMPORTS.md, group A).
return [
    'title'               => 'Patient files',
    'subtitle'            => 'Scans, reports and signed documents this facility attached to the record.',
    'chart_link'          => 'Patient files',
    'back_to_chart'       => 'Back to chart',
    'back'                => 'Back to files',
    'upload_btn'          => 'Upload a file',
    'upload_title'        => 'Upload a patient file',
    'upload_subtitle'     => 'The file stays private to this facility until you release it to the patient.',
    'attachments'         => 'Files attached here',
    'none'                => 'This facility has not attached any file to this patient.',
    'no_facility_context' => 'No facility is selected for this session.',
    'audit_unavailable'   => 'The access trail could not be written, so the file is not shown.',

    'col_file'        => 'File',
    'col_category'    => 'Kind',
    'col_description' => 'Description',
    'col_size'        => 'Size',
    'col_date'        => 'Uploaded',
    'col_status'      => 'Status',
    'col_actions'     => 'Actions',
    'col_released'    => 'Released',

    'status_voided'       => 'Voided',
    'status_released'     => 'Released to patient',
    'status_not_released' => 'Not released',

    'download'        => 'Download',
    'release'         => 'Release to patient',
    'release_confirm' => 'Release now',
    'release_hint'    => 'The patient will see this file in their documents.',
    'void'            => 'Void',
    'void_confirm'    => 'Void this file',
    'void_hint'       => 'A voided file is hidden from the patient. It is never deleted.',

    'lbl_file'        => 'File',
    'lbl_category'    => 'Kind of document',
    'lbl_description' => 'Description',
    'lbl_void_reason' => 'Reason for voiding',
    'lbl_modality'    => 'Modality',
    'lbl_body_part'   => 'Body part',
    'lbl_study_date'  => 'Study date',
    'select_category' => 'Choose a kind',
    'ph_description'  => 'For example: chest X-ray report',
    'click_browse'    => 'Click to choose a file, or drop it here',
    'size_hint'       => 'PDF, JPEG or PNG, up to :max MB',
    'upload_attach'   => 'Upload and attach',
    'cancel'          => 'Cancel',

    'categories' => [
        'lab_result'       => 'Lab result',
        'imaging'          => 'Imaging',
        'radiology_report' => 'Radiology report',
        'consent'          => 'Consent form',
        'prescription'     => 'Prescription',
        'referral'         => 'Referral letter',
        'report'           => 'Clinical report',
        'id_document'      => 'ID document',
        'other'            => 'Other',
    ],

    'modalities' => [
        'xray'       => 'X-ray',
        'ct'         => 'CT scan',
        'mri'        => 'MRI',
        'ultrasound' => 'Ultrasound',
        'echo'       => 'Echocardiography',
        'nuclear'    => 'Nuclear medicine',
        'pet'        => 'PET scan',
        'other'      => 'Other',
    ],

    'err' => [
        'type'      => 'Only PDF, JPEG or PNG files can be attached.',
        'too_large' => 'The file is larger than :max MB.',
        'voided'    => 'This file has been voided.',
    ],

    'flash' => [
        'uploaded'         => 'The file was attached to the record.',
        'voided'           => 'The file was voided. It is kept but hidden from the patient.',
        'released'         => 'The file was released to the patient.',
        'already_released' => 'This file was already released to the patient.',
    ],

    'patient_title' => 'Files from your facilities',
    'patient_hint'  => 'Reports and scans a facility shared with you.',

    'notify' => [
        'title'  => 'Your radiology report is ready',
        'body'   => ':facility shared a radiology report with you.',
        'action' => 'Open documents',
    ],
];
