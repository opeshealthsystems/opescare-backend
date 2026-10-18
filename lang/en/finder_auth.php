<?php

/*
|------------------------------------------------------------------------------
| Finder auth surface — NEW strings only (English)
|------------------------------------------------------------------------------
| Strings introduced by the mobile-first auth redesign. Everything that existed
| before the redesign stays in onboarding.php / auth.php / passwords.php —
| tests pin those files; do not move keys between files.
*/

return [

    // Shared chrome (layouts/auth.blade.php)
    'chrome' => [
        'secure_area'  => 'Secure sign-in area',
        'need_help'    => 'Need help?',
        'contact_support' => 'Contact support',
        'back_to_site' => 'Back to OpesCare',
    ],

    // Password visibility toggle (all password fields)
    'toggle_password' => 'Show or hide password',

    // Patient signup (auth/register/patient.blade.php)
    'signup' => [
        'benefit' => 'An account lets you book appointments and manage your consents in one place.',
    ],

    // Two-factor challenge (auth/mfa_challenge.blade.php)
    'mfa' => [
        'title'           => 'Two-factor authentication',
        'enter_code'      => 'Enter the 6-digit code from your authenticator app.',
        'setup_required'  => 'Multi-factor authentication is required for your role. Contact an administrator to complete enrollment before signing in.',
        'code_label'      => 'Authentication code',
        'verify'          => 'Verify',
        'lost_access'     => 'Lost access to your authenticator?',
        'contact_support' => 'Contact support',
    ],

    // Suspended account (auth/account_suspended.blade.php)
    'suspended' => [
        'note' => 'For security, sign-in attempts on this account are recorded. Access to connected services stays paused until an administrator completes the review.',
    ],
];
