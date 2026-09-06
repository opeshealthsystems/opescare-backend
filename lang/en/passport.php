<?php

/*
|--------------------------------------------------------------------------
| Health Passport
|--------------------------------------------------------------------------
|
| The thirty pages of the OpesCare Health Passport, in canonical order. The
| Health ID says who you are; the Passport organises the record behind it.
|
| Page titles live here rather than in the builder so both languages are
| edited together and stay 1:1 (scripts/i18n-audit.php enforces it).
|
*/

return [

    'title'          => 'Health Passport',
    'open'           => 'Open my Health Passport',
    'subtitle'       => 'Your record, organised as thirty pages',
    'pages_label'    => 'Pages',
    'page_of'        => 'Page :number of :total',
    'previous'       => 'Previous',
    'next'           => 'Next',
    'loading'        => 'Loading your Health Passport…',
    'empty'          => 'Nothing has been recorded on this page yet.',
    'empty_hint'     => 'It fills in as your care is recorded. Nothing here means nothing recorded — not that anything is missing.',
    'error'          => 'This page could not be loaded.',
    'try_again'      => 'Try again',
    'current'        => 'Current',
    'cached'         => 'Saved copy',
    'demo'           => 'Demonstration account — this is not a live credential',
    'secure'         => 'OpesCare Secure',
    'tagline'        => 'PEOPLE • DATA • TRUST • BETTER HEALTH • AFRICA',
    'not_recorded'   => 'Not recorded',
    'status'         => 'Status',
    'health_id'      => 'Health ID',
    'version'        => 'Version',
    'last_updated'   => 'Last updated',
    'none_recorded'  => 'None recorded',
    'common_name'    => 'Name',
    'dob'            => 'Date of birth',
    'sex'            => 'Sex',
    'sex_male'       => 'Male',
    'sex_female'     => 'Female',
    'blood_group'    => 'Blood group',
    'allergies_label' => 'Allergies',
    'emergency_contact' => 'Emergency contact',
    'recorded'       => 'Recorded',
    'as_prescribed'  => 'As prescribed',
    'care_team_member' => 'Care team member',
    'a_facility'     => 'A facility',
    'until'          => 'Until :date',
    'next_appointment' => 'Next appointment',
    'checked_against' => 'Checked against',
    'checked_against_value' => 'The national register, at the moment of the check',
    'status_active'      => 'Active',
    'status_provisional' => 'Provisional',
    'status_suspended'   => 'Suspended',
    'status_revoked'     => 'Revoked',
    'status_expired'     => 'Expired',
    'status_ordered'     => 'Ordered',
    'status_collected'   => 'Sample collected',
    'status_in_progress' => 'In progress',
    'status_resulted'    => 'Resulted',
    'status_cancelled'   => 'Cancelled',

    'pages' => [
        'cover' => [
            'title'    => 'Health Passport',
            'subtitle' => 'Your secure longitudinal health record',
        ],
        'patient-identity' => [
            'title'    => 'Patient Identity',
            'subtitle' => 'Who this record belongs to',
        ],
        'emergency-profile' => [
            'title'    => 'Emergency Profile',
            'subtitle' => 'What a clinician needs in an emergency',
        ],
        'medical-summary' => [
            'title'    => 'Medical Summary',
            'subtitle' => 'A high-level view of your record',
        ],
        'allergies-alerts' => [
            'title'    => 'Allergies & Alerts',
            'subtitle' => 'Safety information to check first',
        ],
        'current-medications' => [
            'title'    => 'Current Medications',
            'subtitle' => 'Medicines you are taking now',
        ],
        'conditions' => [
            'title'    => 'Conditions',
            'subtitle' => 'Your problem list over time',
        ],
        'encounters' => [
            'title'    => 'Clinical Encounters',
            'subtitle' => 'Visits and consultations',
        ],
        'hospital-admissions' => [
            'title'    => 'Hospital Admissions',
            'subtitle' => 'Inpatient stays',
        ],
        'procedures' => [
            'title'    => 'Procedures',
            'subtitle' => 'Operations and clinical procedures',
        ],
        'laboratory' => [
            'title'    => 'Laboratory Results',
            'subtitle' => 'Tests and their results',
        ],
        'radiology' => [
            'title'    => 'Radiology & Imaging',
            'subtitle' => 'Imaging studies and reports',
        ],
        'cardiology' => [
            'title'    => 'Cardiology',
            'subtitle' => 'Cardiac studies and findings',
        ],
        'immunization' => [
            'title'    => 'Immunisation Record',
            'subtitle' => 'Vaccines you have received',
        ],
        'prescriptions' => [
            'title'    => 'Prescriptions',
            'subtitle' => 'Medicines prescribed to you',
        ],
        'dispensing' => [
            'title'    => 'Medication Dispensing',
            'subtitle' => 'What a pharmacy has dispensed',
        ],
        'referrals' => [
            'title'    => 'Referrals',
            'subtitle' => 'Onward care and specialist referrals',
        ],
        'medical-certificates' => [
            'title'    => 'Medical Certificates',
            'subtitle' => 'Certificates issued to you',
        ],
        'discharge-summary' => [
            'title'    => 'Discharge Summary',
            'subtitle' => 'How your stays concluded',
        ],
        'maternal-child' => [
            'title'    => 'Maternal & Child Health',
            'subtitle' => 'Pregnancy and child health records',
        ],
        'care-providers' => [
            'title'    => 'Care Providers',
            'subtitle' => 'The team involved in your care',
        ],
        'insurance' => [
            'title'    => 'Insurance & Coverage',
            'subtitle' => 'How your care is covered',
        ],
        'consent-sharing' => [
            'title'    => 'Consent & Sharing',
            'subtitle' => 'Who you have allowed to see your record',
        ],
        'verification' => [
            'title'    => 'Verification Centre',
            'subtitle' => 'Prove this record is yours and current',
        ],
        'vitals' => [
            'title'    => 'Vital Signs',
            'subtitle' => 'Measurements over time',
        ],
        'family-genetic' => [
            'title'    => 'Family & Genetic History',
            'subtitle' => 'Conditions that run in your family',
        ],
        'preventive-screening' => [
            'title'    => 'Preventive Care & Screening',
            'subtitle' => 'Checks that keep you well',
        ],
        'devices-transfusion' => [
            'title'    => 'Devices, Implants & Transfusions',
            'subtitle' => 'Implanted devices and transfusion history',
        ],
        'care-plans' => [
            'title'    => 'Care Plans & Follow-up',
            'subtitle' => 'What happens next in your care',
        ],
        'back-cover' => [
            'title'    => 'My health. My data.',
            'subtitle' => 'A healthier Africa',
        ],
    ],

    'sections' => [
        'identity' => 'Identity',
        'credential' => 'Credential',
        'summary' => 'At a glance',
        'allergies' => 'Allergies',
        'medications' => 'Medicines',
        'conditions' => 'Conditions',
        'encounters' => 'Recent encounters',
        'admissions' => 'Admissions',
        'laboratory' => 'Recent results',
        'imaging' => 'Imaging',
        'immunisations' => 'Vaccines given',
        'prescriptions' => 'Prescriptions',
        'dispensing' => 'Dispensed',
        'referrals' => 'Referrals',
        'documents' => 'Documents',
        'discharge' => 'Discharges',
        'providers' => 'Your care team',
        'insurance' => 'Cover',
        'consent' => 'Active permissions',
        'verification' => 'How to verify',
        'vitals' => 'Latest measurements',
        'care_plans' => 'Plans',
        'emergency' => 'In an emergency',
    ],

];
