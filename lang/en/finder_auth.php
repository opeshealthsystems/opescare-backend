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
        'setup_required'  => 'Your role requires two-factor authentication. Set it up now: it takes about a minute.',
        'setup_step_app'  => 'Install an authenticator app on your phone (Google Authenticator, Microsoft Authenticator, Authy or similar).',
        'setup_step_scan' => 'In the app, add an account and scan this QR code.',
        'setup_qr_alt'    => 'QR code to add OpesCare to your authenticator app',
        'setup_manual'    => 'Cannot scan? Enter this key in the app instead:',
        'setup_step_confirm' => 'Enter the 6-digit code the app now shows',
        'setup_submit'    => 'Confirm and sign in',
        'recovery_title'  => 'Save your recovery codes',
        'recovery_intro'  => 'Two-factor authentication is on. If you lose your phone, each of these codes lets you sign in once.',
        'recovery_once'   => 'They are shown only now. Store them somewhere safe, such as a password manager.',
        'recovery_continue' => 'I have saved them, continue',
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
