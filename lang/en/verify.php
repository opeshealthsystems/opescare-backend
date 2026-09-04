<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpesCare Provider Verification Tool — English
    |--------------------------------------------------------------------------
    */

    // Health ID lookup page
    'health_id_title'      => 'Health ID Verification — OpesCare',
    'badge_provider_tool'  => 'Provider Verification Tool',
    'health_id_heading'    => 'Health ID Verification',
    'health_id_subheading' => 'Look up a patient\'s verified identity and active record status. For authorised clinical providers only.',

    // QR verification page
    'qr_title'             => 'QR Verification — OpesCare',
    'qr_heading'           => 'QR Health ID Verification',
    'qr_subheading'        => 'Scanning this token will reveal the patient\'s verified identity for clinical purposes only.',
    'qr_processing'        => 'Validating token…',
    'qr_processing_note'   => 'This usually takes less than a second.',
    'qr_expired_title'     => 'QR Code Expired',
    'qr_expired_body'      => 'This QR code has expired. Ask the patient to generate a new one from their patient portal.',
    'qr_invalid_title'     => 'Invalid Token',
    'qr_invalid_body'      => 'This token is not recognised. It may have been tampered with or already used. Contact support if this persists.',
    'qr_token_expired_ui'  => 'Token not found or expired. Please ask the patient to regenerate their QR code.',

    // Shared disclaimer
    'disclaimer'           => 'This tool is for authorised healthcare providers. Every verification is logged and auditable by the patient. Unauthorised access is a criminal offence.',

    // Form fields
    'field_health_id'          => 'Patient Health ID',
    'field_health_id_hint'     => 'Enter the alphanumeric Health ID as shown on the patient\'s card or QR code.',
    'field_purpose'            => 'Purpose of Access',
    'field_purpose_placeholder'=> '— Select purpose —',
    'field_purpose_hint'       => 'This is logged against your staff ID for audit purposes.',

    // Purpose options
    'purpose_emergency'    => 'Emergency Care',
    'purpose_scheduled'    => 'Scheduled Clinical Visit',
    'purpose_lab'          => 'Lab Result Delivery',
    'purpose_prescription' => 'Prescription Dispensing',
    'purpose_insurance'    => 'Insurance Claim Processing',
    'purpose_referral'     => 'Referral / Transfer',
    'purpose_other'        => 'Other',

    // Submit
    'btn_verify'           => 'Verify Health ID',

    // Result panel
    'result_verified'      => 'Identity Verified',
    'result_name'          => 'Name',
    'result_health_id'     => 'Health ID',
    'result_dob'           => 'Date of Birth',
    'result_blood_type'    => 'Blood Type',
    'allergies'            => 'Allergies:',
    'audit_note'           => 'This access has been logged against your provider credentials and is visible to the patient.',

    // Footer
    'footer_note'          => 'Having trouble?',
    'footer_help'          => 'Visit Help Center',
    'footer_contact'       => 'Contact Support',
    'footer_manual_verify' => 'Manual ID Lookup',

    // Errors
    'error_not_found'      => 'No verified Health ID found for the provided identifier. Please check the ID and try again.',

    /*
    |--------------------------------------------------------------------------
    | Prescription scan-target — GET /verify/rx/{token}
    |--------------------------------------------------------------------------
    | The page a pharmacist lands on after scanning a printed script.
    */

    'rx_title'             => 'Prescription Verification — OpesCare',
    'rx_heading'           => 'Prescription Verification',
    'rx_subheading'        => 'This is the prescription behind the code you scanned. Check the reference and the patient before dispensing.',

    // Letterhead — the page has to look like the official document it is.
    'rx_platform_line'     => 'National Digital Health Platform',
    'rx_doc_kicker'        => 'Prescription Verification Service',
    'rx_verified_label'    => 'Checked',
    'rx_authority_note'    => 'This screen is the authority, not the paper. It is served live by OpesCare from the issuing facility\'s own record; a printed prescription is only a copy of it. If the paper and this screen disagree, this screen is correct.',
    'rx_reference_hint'    => 'Read this reference back against the printed prescription before dispensing.',
    'rx_privacy_note'      => 'This link discloses the patient\'s name and Health ID only — no date of birth, no contact details, no clinical notes and no other prescription.',
    'rx_table_scroll_hint' => 'Swipe the table sideways to see every column.',
    'rx_medicines_region'  => 'Prescribed medicines — scrollable table',

    // Single refusal — invalid, expired and withdrawn are one answer.
    'rx_invalid_title'     => 'Prescription Not Available',
    'rx_invalid_body'      => 'This link is invalid, has expired, or has been withdrawn. Ask the patient for a current prescription code.',

    // Reference
    'rx_reference_label'   => 'Prescription Reference',
    'rx_no_reference'      => 'No reference assigned',

    // Verdict banners
    'rx_do_not_dispense'   => 'Do Not Dispense',
    'rx_retracted_body'    => 'This prescription has been withdrawn (:status) and is no longer a valid order. Do not dispense against it. Contact the issuing facility if the patient needs a replacement.',
    'rx_expired_body'      => 'This prescription is past its validity date and is no longer a valid order. Do not dispense against it. The patient needs a new prescription from their prescriber.',
    'rx_dispensed_title'   => 'Already Dispensed',
    'rx_dispensed_body'    => 'This prescription has already been dispensed in full. Do not dispense it a second time.',
    'rx_valid_title'       => 'Valid Prescription',
    'rx_valid_body'        => 'This prescription is active and may be dispensed. Confirm the patient name and Health ID below before handing over any medicine.',

    // Sections
    'rx_section_patient'   => 'Patient',
    'rx_section_order'     => 'Prescription',
    'rx_section_medicines' => 'Prescribed Medicines',

    // Fields
    'rx_patient'           => 'Name',
    'rx_health_id'         => 'Health ID',
    'rx_facility'          => 'Issuing Facility',
    'rx_prescriber'        => 'Prescriber',
    'rx_prescribed_at'     => 'Prescribed',
    'rx_expires_at'        => 'Valid Until',
    'rx_no_expiry'         => 'No expiry recorded',
    'rx_status'            => 'Status',
    'rx_dispensed_at'      => 'Dispensed',
    'rx_not_dispensed'     => 'Not yet dispensed',
    'rx_not_recorded'      => 'Not recorded',

    // Medicines table
    'rx_col_medicine'      => 'Medicine',
    'rx_col_dose'          => 'Dose',
    'rx_col_frequency'     => 'Frequency',
    'rx_col_route'         => 'Route',
    'rx_col_duration'      => 'Duration',
    'rx_col_quantity'      => 'Qty',
    'rx_col_status'        => 'Status',
    'rx_days'              => ':count day(s)',
    'rx_no_items'          => 'No medicines are recorded on this prescription.',

    // Audit
    'rx_audit_note'        => 'This access has been logged. The patient can see every time this prescription was opened, and can withdraw the link at any time from their portal.',

    // Status labels
    'rx_status_active'              => 'Active',
    'rx_status_pending'             => 'Pending',
    'rx_status_partially_dispensed' => 'Partially dispensed',
    'rx_status_dispensed'           => 'Dispensed',
    'rx_status_expired'             => 'Expired',
    'rx_status_cancelled'           => 'Cancelled',
    'rx_status_voided'              => 'Voided',
    'rx_status_entered_in_error'    => 'Entered in error',
    'rx_status_amended'             => 'Amended — superseded',
    'rx_status_unknown'             => 'Unknown',
];
