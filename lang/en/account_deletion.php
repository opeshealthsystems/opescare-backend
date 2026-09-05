<?php

/*
|--------------------------------------------------------------------------
| Account deletion — the itemised disclosure
|--------------------------------------------------------------------------
|
| Every sentence in this file is a promise, and every promise here is one the
| code in App\Services\Compliance\AccountDeletionService actually keeps. If a
| line stops being true, the line is wrong — not the code.
|
| The rule that shaped the wording: say what is NOT deleted, in the same size
| type as what is. A patient who closes an account on a medical-records
| platform and later discovers their prescriptions were kept was misled, even
| if keeping them was lawful and correct. So `cannot` is a first-class section,
| not a footnote.
|
| Deliberately absent: any claim that the closed record is hidden from
| clinicians. The service sets IdentityStatus::ErasurePending, and
| IdentityStatus::isBlocked() describes that status as hidden from routine
| lookups — but isBlocked() has no callers in this codebase, so nothing
| enforces it. "Marked closed" is what is true, so "marked closed" is what
| this file says.
|
| Placeholder parity with lang/fr/account_deletion.php is a CI gate
| (php scripts/i18n-audit.php). :days, :years, :date, :phrase must appear on
| both sides.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Screen chrome
    |----------------------------------------------------------------------
    */

    'title'     => 'Delete your account',
    'subtitle'  => 'Close your OpesCare account and erase your personal details.',
    'headline'  => 'Closing your account is not the same as deleting your medical record.',
    'summary'   => 'We erase everything that identifies you and everything that lets anyone sign in as you. Your medical record itself is kept, because the law requires it. Nothing happens for :days days, and you can stop it at any point in that time.',

    /*
    |----------------------------------------------------------------------
    | 1. Erased outright
    |----------------------------------------------------------------------
    */

    'erased' => [
        'title' => 'Erased immediately, permanently',
        'note'  => 'These are overwritten or deleted in the database. They are not archived, and we cannot get them back for you afterwards.',
        'items' => [
            'sign_in'           => 'Your sign-in email address',
            'password'          => 'Your password',
            'pin'               => 'Your PIN',
            'sessions'          => 'Every signed-in session, on this phone and any other',
            'devices'           => 'Your registered devices and push notification tokens',
            'phone'             => 'Your phone number, and the lookup key built from it',
            'email'             => 'Your contact email address',
            'address'           => 'Your home address',
            'emergency_contact' => 'Your emergency contact and their phone number',
            'national_id'       => 'Your national ID number and your CNAMGS number',
            'referral_code'     => 'Your referral code',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | 2. De-identified
    |----------------------------------------------------------------------
    */

    'deidentified' => [
        'title' => 'Left in place, but no longer you',
        'note'  => 'Your patient record cannot be deleted without deleting the clinical record attached to it, so it stays — emptied of the details that identify you.',
        'items' => [
            'name'          => 'Your name is overwritten with "Closed Account"',
            'record_marked' => 'Your record is marked as closed and erased, so staff can see the account was ended at your request',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | 3. Retained under a legal duty
    |----------------------------------------------------------------------
    */

    'retained' => [
        'title'       => 'Kept, because the law requires it',
        'note'        => 'Your Health ID, date of birth, sex and blood group stay attached to your medical record, because a prescription or a lab result cannot be read safely without them.',
        'legal_basis' => 'Cameroon health regulations (MINSANTE) require a medical record to be kept for :years years. A facility must be able to show what it prescribed, dispensed and tested. We are not permitted to delete this sooner, and we will not tell you otherwise.',
        'purge_note'  => 'Kept until :date. On that date the retained record — including your patient record itself — is destroyed.',
    ],

    /*
    |----------------------------------------------------------------------
    | 4. What we cannot delete at all. The section most screens omit.
    |----------------------------------------------------------------------
    */

    'cannot' => [
        'title' => 'What we cannot delete',
        'items' => [
            'clinical_events' => 'Prescriptions, lab results, diagnoses and visits are permanent records of things that happened. They can be corrected or marked as entered in error, but never removed.',
            'access_logs'     => 'The log of who opened your record stays. It is your evidence, not ours — erasing it would destroy the only proof of who read your file.',
            'facility_copies' => 'Any copy a hospital, pharmacy or laboratory holds in its own system or on paper. Ask them directly; we cannot reach it.',
            'fiscal'          => 'Invoices and payment records, which tax and accounting rules require separately.',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Counts of what survives, shown next to the real numbers
    |----------------------------------------------------------------------
    */

    'kept_items' => [
        'prescriptions'     => 'Prescriptions',
        'lab_results'       => 'Laboratory results',
        'lab_orders'        => 'Laboratory orders',
        'encounters'        => 'Clinical encounters',
        'visits'            => 'Facility visits',
        'appointments'      => 'Appointments',
        'imaging_orders'    => 'Imaging orders',
        'radiology_reports' => 'Radiology reports',
        'immunizations'     => 'Immunisation records',
        'problems'          => 'Problem list entries',
        'diagnoses'         => 'Diagnoses',
        'invoices'          => 'Invoices',
        'payments'          => 'Payments',
        'access_events'     => 'Health ID access events',
        'access_logs'       => 'Record access log entries',
    ],

    /*
    |----------------------------------------------------------------------
    | Confirmation
    |----------------------------------------------------------------------
    */

    'confirm_phrase'      => 'DELETE',
    'confirm_instruction' => 'Type :phrase to confirm you have read what is erased and what is kept.',
    'reauth_note'         => 'Enter your password or PIN. We ask because closing an account must not be something a stranger holding your unlocked phone can do.',
    'reason_label'        => 'Why are you closing your account? (optional)',
    'reason_placeholder'  => 'This helps us fix what drove you away. It is not required.',

    /*
    |----------------------------------------------------------------------
    | Grace period / status
    |----------------------------------------------------------------------
    */

    'grace' => [
        'title'      => 'Your account closes on :date',
        'countdown'  => ':days days left to change your mind.',
        'today'      => 'Your account closes today.',
        'body'       => 'Nothing has been erased yet. Until :date you can cancel this and keep your account exactly as it is. After that date the erasure runs and cannot be undone.',
        'cancel_cta' => 'Cancel — keep my account',
        'requested'  => 'Requested on :date',
    ],

    'completed' => [
        'title' => 'This account is closed',
        'body'  => 'Your account was closed on :date. Your personal details have been erased. Your medical record is retained until :until.',
    ],

    /*
    |----------------------------------------------------------------------
    | Actions and outcomes
    |----------------------------------------------------------------------
    */

    'cta'                 => 'Delete my account',
    'cta_confirm'         => 'Yes, delete my account',
    'back'                => 'Keep my account',
    'request_created'     => 'Your account will close on :date. You can cancel any time before then.',
    'request_cancelled'   => 'Your account will not be closed. Nothing was erased.',
    'already_pending'     => 'You already asked to close this account. It closes on :date.',
    'nothing_pending'     => 'You have no pending account closure.',
    'executed'            => 'Your account has been closed and your personal details erased.',

    /*
    |----------------------------------------------------------------------
    | Errors — each one says what went wrong, not just that it did
    |----------------------------------------------------------------------
    */

    'errors' => [
        'bad_credential'   => 'That password or PIN is not correct. Your account has not been changed.',
        'bad_confirmation' => 'The confirmation word does not match. Nothing has been changed.',
        'no_credential'    => 'This account has no password or PIN set, so we cannot confirm it is you. Please set one first, or ask the facility that registered you to help you close the account.',
        'not_found'        => 'We could not find your patient record. Nothing has been changed.',
        'already_closed'   => 'This account is already closed.',
    ],

    /*
    |----------------------------------------------------------------------
    | Public web page — Google Play requires a deletion route reachable
    | without installing the app or signing in.
    |----------------------------------------------------------------------
    */

    'web' => [
        'page_title'       => 'Delete your OpesCare account',
        'meta_description' => 'How to delete your OpesCare account, what is erased, what is kept under Cameroon medical record retention rules, and how long it takes.',
        'badge'            => 'Your data rights',
        'hero_title'       => 'Delete your OpesCare account',
        'hero_subtitle'    => 'You can close your account and erase your personal details from inside the app. This page explains exactly what that does — and what it does not do.',
        'how_title'        => 'How to delete your account',
        'how_intro'        => 'The fastest route is in the app, and it takes effect without anyone having to approve it.',
        'step_app'         => 'Open the OpesCare app, go to Settings, and choose "Delete account".',
        'step_confirm'     => 'Read the itemised list of what is erased and what is kept, enter your password or PIN, and type the confirmation word.',
        'step_grace'       => 'Your account closes :days days later. Until then you can cancel it in the app and nothing is erased.',
        'no_app_title'     => 'If you cannot use the app',
        'no_app_body'      => 'Write to the address below from the email address on your account, or ask any OpesCare facility to raise the request for you. We will confirm your identity before acting, and the same :days-day grace period applies.',
        'contact_label'    => 'Data protection contact',
        'timing_title'     => 'How long it takes',
        'timing_body'      => 'Erasure runs :days days after you ask. Your personal details are erased that day. Your medical record is kept for :years years from that date, then destroyed.',
        'last_updated'     => 'Last updated',
    ],

];
