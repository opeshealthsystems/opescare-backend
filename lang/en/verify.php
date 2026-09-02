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
];
