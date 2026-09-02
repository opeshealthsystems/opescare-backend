<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpesCare Onboarding & Account Access Localization System (English)
    |--------------------------------------------------------------------------
    */
    
    // Core Layout & Sidebar Clinical Branding
    'brand' => [
        'tagline' => 'One Health ID. One Trusted Medical History.',
        'safety_disclaimer' => 'Secure Patient Information Gateway',
        'shield_note' => 'OpesCare utilizes enterprise-grade end-to-end encryption to secure medical history access records. Data is never shared without explicit patient consent.',
        'bullet_1_title' => 'Unified Patient Health ID',
        'bullet_1_desc' => 'Generate and manage a secure, unique identifier linking patient clinical histories across approved providers.',
        'bullet_2_title' => 'Controlled Patient Consent',
        'bullet_2_desc' => 'Patients maintain full, real-time authority to authorize, lock, or revoke facility access to their clinical logs.',
        'bullet_3_title' => 'Immutable Audit Trails',
        'bullet_3_desc' => 'Every single data request, access override, and record modification is permanently recorded and fully audited.',
        'need_help' => 'Need clinical or technical assistance?',
        'contact_support' => 'Contact Support Plane',
        'clinical_disclaimer' => 'OpesCare facilitates access to your health records but is not a substitute for clinical advice. Always consult a licensed healthcare provider for medical decisions.',
    ],

    // Common Action Buttons & General Labels
    'common' => [
        'required' => 'Required',
        'loading' => 'Processing request securely...',
        'email' => 'Email Address',
        'full_name' => 'Full Name',
        'phone' => 'Mobile Phone Number',
        'password' => 'Secure Password',
        'confirm_password' => 'Confirm Password',
        'accept_terms' => 'I agree to the Terms and Conditions',
        'accept_privacy' => 'I have read the Privacy Policy and Patient Data Notice',
        'back_to_home' => 'Return to Home',
        'back' => 'Go Back',
        'continue' => 'Continue',
        'submit' => 'Submit Application',
        'view_details' => 'View Status Details',
        'or' => 'or',
        'optional' => 'Optional',
        'select_option' => 'Choose option...',
        'upload_file' => 'Upload Supporting Document',
        'file_hint' => 'Accepted file formats: PDF, JPG, PNG (Max 5MB)',
    ],

    // Login View Strings
    'login' => [
        'head_title' => 'Sign in to OpesCare',
        'welcome_back' => 'Welcome back to OpesCare',
        'subheadline' => 'Access your Health ID, patient portal, facility dashboard, or integration workspace.',
        'email_or_phone' => 'Email address or phone number',
        'remember' => 'Remember this secure device',
        'forgot' => 'Forgot password?',
        'submit_signin' => 'Sign in securely',
        'submit_otp' => 'Sign in with OTP code',
        'no_account' => 'New to OpesCare?',
        'create_account' => 'Create an account',
        'security_note' => 'For your safety, never share your OpesCare login details, OTP codes, or temporary access links with anyone.',
        'errors' => [
            'invalid_credentials' => 'The email, phone number, or password is incorrect.',
            'account_pending' => 'Your account is waiting for approval. We will notify you when it is ready.',
            'account_suspended' => 'This account has been suspended. Contact support if you believe this is a mistake.',
            'facility_suspended' => 'This facility is currently suspended and cannot access OpesCare.',
            'too_many_attempts' => 'Too many login attempts. Please wait before trying again.',
        ],
    ],

    // OTP Code Verification View Strings
    'otp' => [
        'title' => 'Enter verification code',
        'subtitle' => 'We sent a 6-digit verification code to your registered mobile phone number or email address.',
        'code_label' => '6-Digit Verification Code',
        'submit_btn' => 'Verify & Authenticate',
        'resend_btn' => 'Resend Verification Code',
        'change_info' => 'Change phone or email',
        'timer_hint' => 'Code expires in',
        'security_note' => 'For your security, never share your OTP code with anyone. OpesCare staff will never ask for it.',
        'resent_notice' => 'A new 6-digit verification code has been transmitted to your secure inbox.',
        'errors' => [
            'incorrect' => 'The code is incorrect. Please verify and try again.',
            'expired' => 'The code has expired. Please request a new verification code.',
            'too_many' => 'Too many failed verification attempts. Request a new code.',
        ],
    ],

    // Signup / Onboarding Path Selector (Register)
    'selector' => [
        'title' => 'Get started with OpesCare',
        'subtitle' => 'Choose how you want to use OpesCare to route your account setup correctly.',
        'already_have' => 'Already have an account?',
        'signin' => 'Sign in',
        
        'cards' => [
            'patient_title' => 'I am a patient',
            'patient_desc' => 'Create or access your Health ID, manage consent, view health updates, and carry your medical history safely.',
            'patient_cta' => 'Continue as Patient',
            
            'guardian_title' => 'I manage care for someone',
            'guardian_desc' => 'Request access to manage a child, dependent, elderly relative, or someone under your care.',
            'guardian_cta' => 'Continue as Guardian',
            
            'hospital_title' => 'Hospital or Clinic',
            'hospital_desc' => 'Register your facility to use Health IDs, patient records, consent, referrals, and interoperability tools.',
            'hospital_cta' => 'Register Organization',
            
            'pharmacy_title' => 'Pharmacy',
            'pharmacy_desc' => 'Connect prescriptions, dispensing records, and medicine availability with verified patient workflows.',
            'pharmacy_cta' => 'Register Pharmacy',
            
            'laboratory_title' => 'Laboratory',
            'laboratory_desc' => 'Connect lab orders, sample tracking, result validation, and verified reports to patient timelines.',
            'laboratory_cta' => 'Register Laboratory',
            
            'insurer_title' => 'Insurance Company',
            'insurer_desc' => 'Support eligibility checks, preauthorization, claims, and controlled access to necessary information.',
            'insurer_cta' => 'Register Insurer',
            
            'developer_title' => 'Developer or System Vendor',
            'developer_desc' => 'Request access to OpesCare Connect API, SDKs, webhooks, sandbox, and integration documentation.',
            'developer_cta' => 'Request API Access',
            
            'public_health_title' => 'Public Health or Research',
            'public_health_desc' => 'Contact OpesCare for approved public health reporting, governance, or research collaboration.',
            'public_health_cta' => 'Contact Partnership Team',
        ],
    ],

    // Patient Self-Signup Form Strings
    'patient' => [
        'terms_notice' => 'By creating an account you agree to our :terms and :privacy.',
        'terms_link' => 'Terms of Service',
        'privacy_link' => 'Privacy Policy',
        'subheadline' => 'An email address and a password. That is all we need to get started.',
        'email' => 'Email address',
        'password' => 'Password',
        'submit' => 'Create account',
        'back' => 'Back',
        'headline' => 'Create your OpesCare account',
        'password_hint' => 'At least 8 characters.',
        'password_confirm' => 'Confirm password',
        'next_step_note' => 'Once you are in, we will ask for your name and date of birth so your Health ID can be issued.',
        'have_account' => 'Already have an account?',
        'sign_in' => 'Sign in',
        'email_taken' => 'An account already exists for that email address.',
        'account_created' => 'Account created. One more step to get your Health ID.',
        'profile_complete' => 'Your Health ID is :health_id. Take it to any OpesCare facility to have your identity verified.',
        'title' => 'Create your OpesCare account',
        'subtitle' => 'Your Health ID helps approved healthcare providers identify your records safely when you need care.',
        'sec_basic' => '1. Basic Information',
        'sec_identity' => '2. Identity Checklist',
        'sec_emergency' => '3. Emergency Contact Details',
        'sec_security' => '4. Account Credentials & Security',
        'sec_consent' => '5. Health ID Consent Notice',
        
        'first_name' => 'First Name',
        'middle_name' => 'Middle Name (Optional)',
        'last_name' => 'Last Name',
        'dob' => 'Date of Birth',
        'sex' => 'Biological Sex',
        'sex_male' => 'Male',
        'sex_female' => 'Female',
        'preferred_lang' => 'Preferred Language',
        'country' => 'Country',
        'city' => 'City / Town',
        
        'has_id_label' => 'Do you already have an OpesCare Health ID?',
        'health_id' => 'Existing Health ID (If known)',
        'national_id' => 'National ID / Social Security Number',
        'insurance_num' => 'Insurance Policy / Card Number',
        'prev_hosp_num' => 'Previous Hospital Patient Number',
        
        'emerg_name' => 'Emergency Contact Full Name',
        'emerg_rel' => 'Relationship to You',
        'emerg_phone' => 'Emergency Contact Phone Number',
        
        'consent_notice' => 'By creating an account, you can manage your Health ID, receive consent requests, and view your access logs. Your medical records are not public. Access depends on consent, authorization, and medical privacy policies.',
        'cta_btn' => 'Create Health ID Profile',
        
        'success' => [
            'title' => 'Your OpesCare account has been created',
            'desc' => 'Your profile is currently provisional. A registered healthcare facility will confirm your identity when you next receive care.',
            'cta' => 'View My Health ID',
        ],
    ],

    // Guardian/Caregiver Request Form Strings
    'guardian' => [
        'headline' => 'Create your caregiver account',
        'subheadline' => 'An email address and a password. That is all we need to get started.',
        'email' => 'Email address',
        'password' => 'Password',
        'submit' => 'Create account',
        'back' => 'Back',
        'next_step_note' => 'Once you are in, we will ask about you and the person you care for, so a facility can verify the relationship.',
        'email_taken' => 'An account already exists for that email address.',
        'account_created' => 'Account created. One more step before we can review your request.',
        'complete_title' => 'Complete your caregiver request | OpesCare',
        'complete_headline' => 'Tell us who you are caring for',
        'complete_subheadline' => 'A facility checks these details before caregiver access is granted, so they need to match your identity documents.',
        'complete_submit' => 'Submit for verification',
        'dep_health_id' => 'Their Health ID (if you know it)',
        'dep_health_id_hint' => 'Optional. It speeds up verification, but we can find them without it.',
        'verification_note' => 'Submitting this does not grant access. A registered facility verifies the relationship in person first.',
        'pending_title' => 'Awaiting verification | OpesCare',
        'pending_headline' => 'Your request is with a facility',
        'pending_body' => 'Caregiver access is granted by a registered facility once it has verified your relationship to the person you care for. Until then there is nothing in your account to show.',
        'pending_next' => 'Bring an identity document and, where you have one, proof of the relationship to any OpesCare facility.',
        'pending_contact' => 'Questions about your request?',
        'pending_signout' => 'Sign out',
        'title' => 'Manage care for a child or dependent',
        'subtitle' => 'Request access to help manage a child, dependent, elderly relative, or someone under your care.',
        'sec_guardian' => '1. Caregiver / Guardian Information',
        'sec_dependent' => '2. Dependent / Patient Information',
        'relationship_lbl' => 'Relationship to Patient',
        'reason_lbl' => 'Clinical Reason for Access Request',
        'reason_desc' => 'Please explain why you require caregiver access to this patient\'s timeline (e.g., parental custody, legal power of attorney).',
        'dep_name_lbl' => 'Patient / Dependent Full Name',
        'dep_dob_lbl' => 'Patient Date of Birth',
        'dep_sex_lbl' => 'Patient Biological Sex',
        'cta_btn' => 'Request Guardian Access',
        'success' => 'Your caregiver request has been submitted. Guardian access requires institutional verification before it becomes active.',
    ],

    // Organization Application Step Form Strings
    'org' => [
        'title' => 'Register your organization with OpesCare',
        'subtitle' => 'Apply to connect your healthcare organization to OpesCare. Our team will review your information before activation.',
        
        'step_1' => 'Organization Type',
        'step_2' => 'Organization Details',
        'step_3' => 'Primary Contact',
        'step_4' => 'Clinical Services',
        'step_5' => 'Documents Upload',
        'step_6' => 'Integration Context',
        
        'type_lbl' => 'Organization Category',
        'software_sync_lbl' => 'Is this organization already using clinical or administrative software?',
        'need_api_lbl' => 'Does the organization require backend API integration?',
        'need_lite_lbl' => 'Does this facility require OpesCare Lite (web interface)?',
        
        'legal_name' => 'Organization Legal Name',
        'trade_name' => 'Trade / Operating Name (If different)',
        'reg_number' => 'Company Registration Number',
        'license_number' => 'Clinical Facility License Number',
        'address' => 'Physical / Postal Address',
        'website' => 'Official Website URL (Optional)',
        'main_phone' => 'Primary Contact Phone',
        'main_email' => 'Primary Public Email',
        
        'contact_sec'   => 'Primary Contact Details',
        'contact_name' => 'Primary Contact Full Name',
        'contact_role' => 'Role / Official Title',
        'contact_email' => 'Contact Email Address',
        'contact_phone' => 'Direct Phone Number',
        
        'services_hosp' => 'Services Provided (Hospital/Clinic)',
        'services_pharma' => 'Services Provided (Pharmacy)',
        'services_lab' => 'Services Provided (Laboratory)',
        'services_insure' => 'Services Provided (Insurance)',
        'services_vendor' => 'Interoperability Services (Developer/Vendor)',
        
        'doc_business' => 'Business Registration Document',
        'doc_license' => 'Medical Facility Operating License',
        'doc_other' => 'Other Professional Supporting Document (Optional)',
        
        'connect_sys_lbl' => 'Do you want to connect an existing electronic system?',
        'software_name' => 'Name of Current Software / Vendor',
        'est_users' => 'Estimated number of staff users',
        'est_patients' => 'Estimated patients processed per month',
        
        'terms_accuracy' => 'I confirm that all information and files provided are accurate.',
        'terms_review' => 'I understand that OpesCare will review and manually verify this application before activating the account.',
        
        'cta_btn' => 'Submit Organization Application',
        
        'success' => [
            'title' => 'Application submitted successfully',
            'desc' => 'Thank you. Our team will review your organization application. We may contact you if more information is needed.',
            'cta' => 'Return to Home',
        ],
        
        'variants' => [
            'hospital_msg' => 'Register your hospital to use Health IDs, patient timelines, consent workflows, emergency access, referrals, billing, pharmacy, laboratory, and interoperability tools.',
            'clinic_msg' => 'Register your clinic to manage patient Health IDs, visits, prescriptions, lab results, referrals, and approved record sharing.',
            'pharmacy_msg' => 'Register your pharmacy to support prescription dispensing, medication history, medicine availability, and safe stock synchronization.',
            'pharmacy_notice' => 'Prescription-required medicines must be clearly marked. Expired, recalled, quarantined, or unavailable stock must never appear as available.',
            'laboratory_msg' => 'Register your laboratory to connect lab orders, sample tracking, result validation, critical alerts, and verified reports to patient timelines.',
            'laboratory_notice' => 'Released lab results must not be silently edited. Corrections must be recorded as amendments.',
            'insurer_msg' => 'Register your insurance organization to support eligibility checks, preauthorization, claims, and controlled access to necessary information.',
            'insurer_notice' => 'Insurers should only access the minimum necessary information required for eligibility, authorization, claims, or policy-related workflows.',
            'insurance_notice' => 'Insurers should only access the minimum necessary information required for eligibility, authorization, claims, or policy-related workflows.',
            'public_health_msg' => 'Contact OpesCare to discuss approved public health reporting, controlled data access, and governance-based collaboration.',
            'public_health_notice' => 'Public health and research data access must follow approved governance, privacy, and data minimization rules.',
        ]
    ],

    // Developer / Tech Vendor Request Form Strings
    'developer' => [
        'title' => 'Request OpesCare API access',
        'subtitle' => 'Connect healthcare systems to OpesCare using APIs, SDKs, webhooks, widgets, or Bridge Agent tools.',
        'sec_vendor' => 'Technology Vendor / System Context',
        'system_type_lbl' => 'Healthcare Software Type',
        'expected_flow_lbl' => 'Expected Interoperability Data Flow',
        'sandbox_lbl' => 'Request access to Sandbox Sandbox Environment?',
        'production_lbl' => 'Request direct production connectivity scopes?',
        'safety_notice' => 'Production API access requires organization verification, approved scopes, secure credentials, and compliance review.',
        'org_lbl' => 'Software Vendor / Organization',
        'role_lbl' => 'Role / Job Title',
        'purpose_lbl' => 'Integration Purpose / Clinical Value',
        'terms_label' => 'I agree to OpesCare Connect developer terms and sandboxing policies',
        'cta_btn' => 'Request Interoperability Scopes',
    ],

    // Staff Invitation Acceptance View Strings
    'invite' => [
        'title' => 'Accept your OpesCare invitation',
        'subtitle' => 'You have been invited to join an organization on OpesCare.',
        'sec_details' => 'Invitation Details',
        'org_lbl' => 'Target Organization Name',
        'facility_lbl' => 'Assigned Facility / Branch',
        'role_lbl' => 'Assigned Access Role',
        'invited_by_lbl' => 'Invited by Staff Member',
        'expiry_lbl' => 'Invitation Expiry Date',
        'sec_profile' => 'Create Your Secure Staff Account',
        'terms_label' => 'I accept the OpesCare terms of service and patient records access audits',
        'cta_btn' => 'Activate Account & Join',
        'errors' => [
            'expired' => 'This invitation link has expired. Please contact your organization administrator.',
            'used' => 'This invitation has already been accepted.',
            'revoked' => 'This invitation has been revoked by the issuer.',
        ],
    ],

    // Facility Selector View Strings
    'facility_selector' => [
        'title' => 'Choose your active facility',
        'subtitle' => 'Please select the clinical branch or facility where you are currently on duty.',
        'role_label' => 'Authorized Role',
        'status_active' => 'Active Access',
        'status_suspended' => 'Suspended Access',
        'cta_btn' => 'Authenticate into Session',
    ],

    // Pending Approval Screen Strings
    'pending' => [
        'title' => 'Your application is under review',
        'desc' => 'Thank you for applying to OpesCare. Our compliance team is currently reviewing your information. We will contact you if additional details are needed.',
        'card_header' => 'Application Review Status',
        'ref_number' => 'Reference Number',
        'org_name' => 'Organization Name',
        'status_label' => 'Verification Status',
        'status_under_review' => 'Under Review',
        'submitted_date' => 'Submission Date',
        'admin_notes' => 'Compliance Review Notes',
        'default_admin_note' => 'Document audit check passed. Pending clinical facility registration authority verification. Expected completion within 24 hours.',
        'contact_email' => 'Notification Email',
        'cta_support' => 'Contact Help Desk',
    ],

    // Account Suspended Screen Strings
    'suspended' => [
        'title' => 'Account access suspended',
        'desc' => 'This account cannot access OpesCare at the moment due to compliance, security, or policy reviews. Please contact support if you believe this is a mistake.',
        'security_warning' => 'Secure Override Protection Active',
    ],

    // Forgot Password & Reset Form Strings
    'forgot' => [
        'title' => 'Reset your secure password',
        'desc' => 'Enter your registered email address or phone number. If a matching account exists, we will transmit secure reset instructions.',
        'cta' => 'Transmit Reset Link',
        'success' => 'Instructions have been sent if the email or phone matches our registry.',
        
        'privacy_note' => 'To protect patient record privacy, we do not reveal whether a matching profile is found in our registry.',
        'reset_title' => 'Configure New Credentials',
        'new_pass' => 'New Password',
        'confirm_new' => 'Confirm New Password',
        'reset_cta' => 'Update Password',
        'reset_success' => 'Your password has been securely updated. You can now sign in.',
    ],

    /* Profile completion — the half of sign-up that moved to after login. */
    'complete' => [
        'title' => 'Complete your profile | OpesCare',
        'headline' => 'Tell us who you are',
        'subheadline' => 'Your Health ID is issued from these details, so they need to match your identity documents.',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'dob' => 'Date of birth',
        'sex' => 'Sex',
        'sex_choose' => 'Select',
        'phone' => 'Phone number',
        'city' => 'City',
        'emergency_toggle' => 'Add an emergency contact (optional)',
        'emergency_name' => 'Contact name',
        'emergency_phone' => 'Contact phone',
        'emergency_relationship' => 'Relationship',
        'submit' => 'Issue my Health ID',
        'health_id_note' => 'Your Health ID is provisional until a registered facility verifies your identity in person.',
    ],
];
