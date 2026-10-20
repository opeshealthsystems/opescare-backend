<?php

/*
|--------------------------------------------------------------------------
| Imaging report desk (web portal, /facility/imaging/reports,
| /facility/imaging/orders/{id}/report) and the patient's IMAGING_REPORT sheet
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/imaging_report.php (scripts/i18n-audit.php).
*/

return [

    'nav_reports' => 'Reports',
    'no_facility' => 'No active facility for this session.',

    // ── Queue page ────────────────────────────────────────────────────────
    'queue_title'          => 'Imaging reports',
    'queue_subtitle'       => 'Completed studies at this facility waiting for a radiologist, and signed reports not yet handed to the patient.',
    'queue_awaiting'       => 'Awaiting a report',
    'queue_awaiting_hint'  => 'Oldest completed study first. A study with a draft is listed here until the draft is finalized.',
    'queue_undistributed'  => 'Signed, not yet distributed',
    'queue_undistributed_hint' => 'The patient cannot see a report until it is distributed.',
    'queue_empty'          => 'Nothing is waiting for a report.',
    'queue_undistributed_empty' => 'Every signed report has been handed to its patient.',
    'col_patient'          => 'Patient',
    'col_study'            => 'Study',
    'col_accession'        => 'Accession',
    'col_completed'        => 'Completed',
    'col_report'           => 'Report',
    'col_author'           => 'Author',
    'col_finalized'        => 'Finalized',
    'col_actions'          => '',
    'open_report'          => 'Open report',
    'write_report'         => 'Write report',
    'back_to_queue'        => 'Back to reports',
    'back_to_order'        => 'Back to the order',
    'back_to_worklist'     => 'Back to the worklist',
    'unknown'              => 'Unknown',
    'not_yet'              => 'Not yet',

    // ── Report page ───────────────────────────────────────────────────────
    'page_title'           => 'Radiology report',
    'page_subtitle'        => ':modality · :body_part',
    'order_facts'          => 'The study',
    'patient'              => 'Patient',
    'age_band'             => 'Age band',
    'sex'                  => 'Sex',
    'health_id'            => 'Health ID',
    'modality_label'       => 'Modality',
    'body_part'            => 'Body part',
    'accession'            => 'Accession number',
    'urgency'              => 'Urgency',
    'ordered_by'           => 'Ordered by',
    'referring'            => 'Referring physician',
    'completed_at'         => 'Study completed',
    'order_status'         => 'Order status',
    'order_not_completed'  => 'This study is not marked completed yet. A report can be drafted, but it is usually written once the images are acquired.',

    'form_title'           => 'Report',
    'form_amend_title'     => 'Amend this report',
    'form_amend_hint'      => 'An amendment is a new version. The signed text you are replacing is kept and can be read below; the patient sees only the current version once you distribute it again.',
    'indication'           => 'Clinical indication',
    'findings'             => 'Findings',
    'findings_hint'        => 'What the images show, as you would dictate it.',
    'impression'           => 'Impression',
    'impression_hint'      => 'Your conclusion. This is what the referring clinician reads first.',
    'recommendation'       => 'Recommendation',
    'recommendation_hint'  => 'Optional. Follow-up imaging, correlation, referral.',
    'reason'               => 'Reason for amendment',
    'attachment'           => 'Attach a PDF (optional)',
    'attachment_hint'      => 'PDF only, 10 MB at most. The patient receives the signed text as a verifiable sheet regardless; the file is an extra, released with the report.',
    'attachment_current'   => 'Attached file',
    'attachment_replace'   => 'Uploading another file replaces it.',
    'download_attachment'  => 'Download attachment',
    'save_draft'           => 'Save draft',
    'finalize'             => 'Finalize and sign',
    'finalize_confirm'     => 'Sign this report? Once final it can only be changed by an amendment, which keeps this version on record.',
    'amend'                => 'Issue amendment',
    'amend_confirm'        => 'Issue a new version of this report? The current signed version is kept.',
    'distribute'           => 'Distribute to patient',
    'distribute_confirm'   => 'Hand this report to the patient? It appears in their documents and they are notified.',
    'redistribute_hint'    => 'This version has not been handed to the patient yet. The previous version was.',
    'readonly_note'        => 'This report is signed. To change it, issue an amendment.',

    // ── Panel (on the order detail page) ──────────────────────────────────
    'panel_title'          => 'Radiology report',
    'panel_none'           => 'No report yet.',
    'panel_none_hint'      => 'A radiologist writes it once the study is completed.',
    'panel_status'         => 'Status',
    'panel_version'        => 'Version',
    'panel_author'         => 'Author',
    'panel_finalized'      => 'Finalized',
    'panel_distributed'    => 'Distributed to patient',
    'panel_not_distributed' => 'Not yet distributed',
    'panel_open'           => 'Open the report',
    'panel_versions'       => 'Earlier versions',
    'panel_version_row'    => 'Version :n · :status · :date',
    'panel_impression'     => 'Impression',

    'status' => [
        'none'        => 'No report',
        'draft'       => 'Draft',
        'preliminary' => 'Preliminary',
        'final'       => 'Final',
        'amended'     => 'Amended',
        'corrected'   => 'Corrected',
        'superseded'  => 'Superseded',
    ],

    'version_n' => 'v:n',

    'modality' => [
        'xray'        => 'X-ray',
        'ct'          => 'CT scan',
        'mri'         => 'MRI',
        'ultrasound'  => 'Ultrasound',
        'echo'        => 'Echocardiogram',
        'nuclear'     => 'Nuclear medicine',
        'pet'         => 'PET scan',
        'fluoroscopy' => 'Fluoroscopy',
        'mammography' => 'Mammography',
        'other'       => 'Other',
    ],

    'urgency_label' => [
        'routine' => 'Routine',
        'urgent'  => 'Urgent',
        'stat'    => 'STAT',
    ],

    'age_bands' => [
        'neonate'    => 'Neonate',
        'infant'     => 'Infant',
        'child'      => 'Child',
        'adolescent' => 'Adolescent',
        'adult'      => 'Adult',
        'geriatric'  => 'Older adult',
        'unknown'    => 'Not recorded',
    ],

    // DX-3: the bell for the clinician who ordered the study.
    'notify_clinician' => [
        'title' => 'Imaging report ready: :study',
        'body'  => 'The report for :patient (:study) at :facility has been signed and sent.',
    ],

    'flash' => [
        'draft_saved'       => 'Draft saved.',
        'finalized'         => 'Report finalized and signed.',
        'amended'           => 'Amendment issued as version :n. Distribute it so the patient receives the current text.',
        'distributed'       => 'Report distributed to the patient.',
        'not_editable'      => 'This report is signed and cannot be edited. Issue an amendment instead.',
        'cannot_finalize'   => 'The report could not be finalized. It needs findings and an impression, and must not already be signed.',
        'cannot_amend'      => 'Only a signed, current report can be amended.',
        'cannot_distribute' => 'Only a signed, current report can be distributed.',
    ],

    // ── The patient's sheet (documents/imaging-report.blade.php) ──────────
    'doc' => [
        'title'          => 'Imaging Report',
        'subtitle'       => 'Report as signed by the radiologist',
        'modality'       => 'Modality',
        'body_part'      => 'Body part',
        'accession'      => 'Accession',
        'study_date'     => 'Study date',
        'reported_by'    => 'Reported by',
        'finalized'      => 'Signed',
        'version'        => 'Version',
        'version_n'      => 'Version :n',
        'amended_note'   => 'This report was amended on :date and replaces an earlier version.',
        'indication'     => 'Clinical indication',
        'findings'       => 'Findings',
        'impression'     => 'Impression',
        'recommendation' => 'Recommendation',
        'advice'         => 'Discuss this report with the clinician who requested the study. A radiology report is an interpretation of images, not a diagnosis on its own.',
    ],
];
