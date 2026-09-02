<?php

return [
    'title' => 'OpesCare Demo Access',
    'subtitle' => 'Explore OpesCare using safe demo accounts and fake healthcare data.',
    'header' => 'Explore OpesCare with Demo Access',
    'header_subtitle' => 'Use safe demo accounts to see how patients, hospitals, clinics, pharmacies, labs, insurers, public health teams, developers, and administrators work together on OpesCare.',
    
    'warning_banner_title' => 'DEMO ENVIRONMENT',
    'warning_banner_text' => 'All accounts and records on this page use fake data for demonstration. Do not enter real patient, medical, insurance, facility, or government information.',
    
    'select_role' => 'Select a role below to open the matching dashboard and test the workflow.',
    'try_flow' => 'Follow guided demo flows to understand consent, patient records, medicine availability, blood availability, insurance claims, public health reporting, and API sync.',
    'footer_note' => 'Demo data resets regularly. Changes made in demo mode are for testing only.',

    'roles' => [
        'patient' => 'Patient Demo',
        'guardian' => 'Guardian Demo',
        'doctor' => 'Doctor Demo',
        'multi_hospital_doctor' => 'Multi-Hospital Doctor Demo',
        'nurse' => 'Nurse Demo',
        'hospital_admin' => 'Hospital Demo',
        'clinic_admin' => 'Clinic Demo',
        'pharmacy' => 'Pharmacy Demo',
        'laboratory' => 'Laboratory Demo',
        'insurance' => 'Insurance Demo',
        'public_health' => 'Public Health Demo',
        'developer' => 'Developer Demo',
    ],

    'buttons' => [
        'launch_demo' => 'Launch Demo',
        'view_guide' => 'View Demo Guide',
        'try_flow' => 'Try a Flow',
        'login_as' => 'Login as Demo User',
        'copy_password' => 'Copy Password',
    ],

    'labels' => [
        'demo_data' => 'Demo Data',
        'not_real_info' => 'Not Real Patient Information',
        'known_limitations' => 'Known Demo Limitations',
        'session_expires_soon' => 'Session Expires Soon',
        'demo_reset_notice' => 'Demo data has been reset. Please start a new demo session.',
        'what_is_simulated' => 'What is simulated in this demo',
    ],

    'limitations' => [
        'sms' => 'SMS messages are simulated.',
        'email' => 'Emails are simulated.',
        'payments' => 'Payments are simulated.',
        'insurance' => 'Insurance submissions are simulated.',
        'government' => 'Government/public health submissions are simulated.',
        'webhook' => 'Webhook delivery is simulated unless an approved demo receiver is configured.',
        'api' => 'Production API credentials are not created.',
        'facility' => 'Real facility verification is not performed.',
        'fake_data' => 'All patients and records are fake.',
        'resets' => 'Demo data resets regularly.',
    ],
];
