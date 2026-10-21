<?php

/*
|--------------------------------------------------------------------------
| Facility onboarding flow — organisation wizard, sign-up selector, claim
|--------------------------------------------------------------------------
| Copy for the split-layout onboarding (design 2026-10-21). Field labels the
| controller already had keep living in onboarding.php / ui_sweep.php.
*/

return [
    'rail' => [
        'title'         => 'Bring your facility onto OpesCare',
        'lead'          => 'About 10 minutes. You can stop at any step and continue later on this device.',
        'trust_review'  => 'Reviewed by OpesCare before activation',
        'trust_private' => 'Documents stored privately, never public',
        'help'          => 'Help: :contact',
        'help_page'     => 'Contact support',
        'steps_label'   => 'Registration steps',
    ],

    'steps' => [
        'type'      => ['title' => 'Facility type', 'hint' => 'Hospital, clinic, pharmacy…'],
        'details'   => ['title' => 'Facility details', 'hint' => 'Legal name, licence, address'],
        'person'    => ['title' => 'Responsible person', 'hint' => 'Who we contact and verify'],
        'services'  => ['title' => 'Services', 'hint' => 'What patients can find'],
        'documents' => ['title' => 'Documents', 'hint' => 'Licence and registration'],
        'review'    => ['title' => 'Review and submit', 'hint' => 'Check and agree'],
    ],

    'step_of'  => 'Step :current of :total',
    'progress' => 'Registration progress',

    'type' => [
        'eyebrow' => 'Facility type',
        'h1'      => 'What kind of facility are you registering?',
        'sub'     => 'This sets up the right desks, roles and public listing for your team.',
        'legend'  => 'Facility type',
        'cards' => [
            'hospital'      => ['title' => 'Hospital', 'desc' => 'Wards, theatre, emergency, inpatient care'],
            'clinic'        => ['title' => 'Clinic or health centre', 'desc' => 'Outpatient consultations, maternity'],
            'pharmacy'      => ['title' => 'Pharmacy', 'desc' => 'Stock, dispensing, prescriptions'],
            'laboratory'    => ['title' => 'Laboratory', 'desc' => 'Samples, results, critical values'],
            'imaging'       => ['title' => 'Imaging centre', 'desc' => 'Worklist, reporting, sign-off'],
            'blood_bank'    => ['title' => 'Blood bank', 'desc' => 'Blood stock and requests'],
            'public_health' => ['title' => 'Public health or research', 'desc' => 'Programmes, surveillance, aggregate reporting'],
        ],
        'claim_title' => 'Already listed on OpesCare?',
        'claim_desc'  => 'Claim your existing profile instead: find it in the directory and keep your map pin.',
        'free_title'  => 'No system yet?',
        'free_desc'   => 'Community and NGO facilities can receive an OPES system free.',
    ],

    'details' => [
        'eyebrow'           => 'Facility details',
        'h1'                => 'Tell us about your facility',
        'sub'               => 'Use the names exactly as they appear on your operating licence.',
        'identity'          => 'Identity',
        'identity_help'     => 'Shown to patients on your public profile.',
        'registration'      => 'Registration',
        'registration_help' => 'Checked by our team; never shown publicly.',
        'license_help'      => 'Found at the top of your operating authorisation.',
        'contact'           => 'Location and contact',
        'contact_help'      => 'Where patients and our team can reach the facility.',
        'address_ph'        => 'e.g. Behind Mvog-Ada market, Yaoundé',
        'phone_help'        => 'Cameroon number; we add +237 for you.',
        'match_note'        => 'Already on the OpesCare map? Our team matches your application to your existing listing so patients keep finding you.',
    ],

    'person' => [
        'eyebrow'     => 'Responsible person',
        'h1'          => 'Who should we contact and verify?',
        'sub'         => 'The person accountable for this facility on OpesCare. We verify them before activation.',
        'legend'      => 'Primary contact',
        'legend_help' => 'Used only for this application and account activation.',
        'role_ph'     => 'Medical director, IT administrator, chief pharmacist…',
    ],

    'services' => [
        'eyebrow'            => 'Services',
        'h1'                 => 'What will patients find here?',
        'sub'                => 'Choose the services you offer. You can change them later from your dashboard.',
        'legend'             => 'Services offered',
        'legend_help'        => 'Tick all that apply.',
        'systems'            => 'Your current systems',
        'systems_help'       => 'Helps us plan your setup. None of this is shown publicly.',
        'software_ph'        => 'e.g. a custom platform, a spreadsheet, none',
        'svc_reporting'      => 'Imaging reports and sign-off',
        'svc_blood_stock'    => 'Blood stock by group',
        'svc_blood_requests' => 'Blood requests from facilities',
        'imaging_msg'        => 'Imaging centres get a worklist, reporting and sign-off desk once approved.',
        'imaging_notice'     => 'Imaging reports are released to patients only after a qualified reader signs them off.',
        'blood_bank_msg'     => 'Blood banks publish availability by group so patients and hospitals can find blood.',
        'blood_bank_notice'  => 'Blood availability is only shown from your live stock records, never estimated.',
    ],

    'documents' => [
        'eyebrow'     => 'Documents',
        'h1'          => 'Upload your licence and registration',
        'sub'         => 'Our team checks these before your facility goes live.',
        'legend'      => 'Supporting documents',
        'legend_help' => 'PDF, JPG or PNG. Stored privately and seen only by the review team.',
        'not_saved'   => 'Files are not kept between visits. If you leave, choose them again before submitting.',
    ],

    'review' => [
        'eyebrow'     => 'Review and submit',
        'h1'          => 'Check before you submit',
        'sub'         => 'Go back to any step to change an answer.',
        'summary'     => 'Your answers',
        'agree'       => 'Declarations',
        'agree_help'  => 'All three are required to submit.',
        'timing'      => 'Our team reviews every application before activation and contacts you by email and phone.',
        'not_given'   => 'Not given',
        'files_count' => ':count of 2 chosen',
        'l_type'      => 'Type',
        'l_name'      => 'Legal name',
        'l_trade'     => 'Known as',
        'l_reg'       => 'Registration number',
        'l_licence'   => 'Licence',
        'l_address'   => 'Address',
        'l_phone'     => 'Main phone',
        'l_email'     => 'Main email',
        'l_person'    => 'Responsible person',
        'l_contact'   => 'Contact',
        'l_services'  => 'Services',
        'l_documents' => 'Documents',
    ],

    'foot' => [
        'back'      => 'Back',
        'continue'  => 'Continue',
        'submit'    => 'Submit application',
        'saved'     => 'Progress saved on this device',
        'not_saved' => 'Progress cannot be saved in this browser',
        'required'  => 'Please complete the fields marked with *.',
        'errors'    => 'Some answers need attention:',
    ],

    'success' => [
        'eyebrow' => 'Application received',
    ],

    'selector' => [
        'eyebrow'         => 'Create an account',
        'people'          => 'For people',
        'facilities'      => 'For facilities and organisations',
        'facilities_help' => 'Applications are reviewed by OpesCare before activation.',
        'partners'        => 'For partners',
    ],

    'claim' => [
        'eyebrow'    => 'Claim your listing',
        'you'        => 'About you',
        'you_help'   => 'We verify the person who claims a listing.',
        'proof'      => 'Proof of authority',
        'proof_help' => 'One document showing you represent this facility.',
        'after'      => 'After you submit, confirm the code we send by SMS from your claims page.',
    ],
];
