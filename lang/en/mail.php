<?php

/*
|--------------------------------------------------------------------------
| Transactional email — the shared layout chrome and every message body
|--------------------------------------------------------------------------
|
| Rendered by resources/views/emails/layout.blade.php (HTML) and
| layout-text.blade.php (plain-text alternative). Composed by the Mailables
| under app/Mail and the toMail() methods under app/Notifications.
|
| Rules carried by these strings:
|   - a SUBJECT never contains a Health ID, a diagnosis, a result or a
|     medication name;
|   - a code email says, in so many words, that nobody at OpesCare will ever
|     ask for the code;
|   - an emergency-access alert is urgent without shouting.
|
| Keep 1:1 with lang/fr/mail.php (scripts/i18n-audit.php).
*/

return [

    /* ── Layout chrome: header, footer, fallbacks ───────────────────────── */
    'chrome' => [
        'tagline'       => 'Your Health ID, wherever you are treated',
        'kind'          => 'Account message',
        'link_fallback' => 'If the button does not work, copy this address into your browser:',
        'no_reply'      => 'This message was sent automatically — please do not reply to it. For help, write to :email.',
        'no_reply_plain' => 'This message was sent automatically — please do not reply to it.',
        'privacy'       => 'To protect your privacy, OpesCare emails never contain diagnoses, results or medication details. Sign in to your secure portal to read your record.',
        'preferences'   => 'Manage which emails you receive',
        'legal'         => 'OpesCare — a digital health identity platform operated under Cameroon Law No. 2010/012 on the protection of personal data.',
        'copyright'     => '© :year OpesCare. All rights reserved.',
        'signature'     => 'The OpesCare team',
    ],

    'common' => [
        'greeting'       => 'Hello,',
        'greeting_named' => 'Hello :name,',
        'never_share'    => 'Never share this code. OpesCare staff will never ask you for it — by phone, by message or in person.',
        'what_to_do'     => 'What to do',
        'what_this_means' => 'What this means',
    ],

    /* ── One-time sign-in code (OtpMail) ────────────────────────────────── */
    'otp' => [
        'subject'    => 'Your OpesCare sign-in code',
        'preheader'  => 'Your one-time code is inside. It expires in :minutes minutes.',
        'eyebrow'    => 'Sign-in verification',
        'title'      => 'Your one-time sign-in code',
        'intro'      => 'Enter the code below to finish signing in to OpesCare.',
        'code_label' => 'Verification code',
        'expiry'     => 'Valid for :minutes minutes',
        'ignore'     => 'If you were not trying to sign in, you can ignore this email — nobody can get into your account without this code.',
    ],

    /* ── Password reset code (PasswordResetCodeMail — mobile app) ───────── */
    'reset_code' => [
        'subject'    => 'Your OpesCare password reset code',
        'preheader'  => 'Use this code in the app to choose a new password. It expires in :minutes minutes.',
        'eyebrow'    => 'Password reset',
        'title'      => 'Reset your password',
        'intro'      => 'We received a request to reset the password on your OpesCare account. Enter the code below in the app to choose a new one.',
        'code_label' => 'Reset code',
        'expiry'     => 'Valid for :minutes minutes',
        'ignore'     => 'If you did not ask for this, you can ignore this email — your password will not change.',
    ],

    /* ── Password reset link (PasswordResetLinkNotification — web) ──────── */
    'reset_link' => [
        'preheader' => 'Set a new password for your OpesCare account. The link expires in :minutes minutes.',
        'eyebrow'   => 'Password reset',
    ],

    /* ── Password changed (PasswordChangedNotification) ─────────────────── */
    'password_changed' => [
        'preheader'   => 'The password on your account was just changed. If that was not you, act now.',
        'eyebrow'     => 'Security notice',
        'panel_title' => 'If this was not you',
        'panel_1'     => 'Request a new password reset straight away from the sign-in page.',
        'panel_2'     => 'Check that you still control the email address on your account.',
        'panel_3'     => 'Then contact OpesCare support so we can review the account with you.',
        'action'      => 'Go to sign in',
    ],

    /* ── Facility staff invitation (FacilityStaffInviteNotification) ────── */
    'staff_invite' => [
        'preheader' => 'You have been invited to join :facility on OpesCare. Activate your account before the link expires.',
        'eyebrow'   => 'Team invitation',
        'facility'  => 'Facility',
        'role'      => 'Role',
    ],

    /* ── Health ID expiry warning (HealthIdExpiryNotification) ──────────── */
    'health_id_expiry' => [
        'subject'        => 'Your Health ID expires in :days days',
        'subject_urgent' => 'Action needed: your Health ID expires in :days days',
        'preheader'      => 'Renew before :date to keep using health services without interruption.',
        'eyebrow'        => 'Health ID renewal',
        'eyebrow_urgent' => 'Renewal needed soon',
        'title'          => 'Your Health ID is approaching its expiry date',
        'intro'          => 'Your OpesCare Health ID will expire on :date. After that date it can no longer be used to identify you at a facility.',
        'days_left'      => ':days days remain before your ID becomes invalid.',
        'fact_health_id' => 'Health ID',
        'fact_expires'   => 'Expires on',
        'fact_days_left' => 'Days remaining',
        'action'         => 'Renew my Health ID',
        'panel_title'    => 'How to renew',
        'panel_1'        => 'Sign in to your patient portal and request a renewal from your Health ID page.',
        'panel_2'        => 'Your record, history and consents stay exactly as they are — only the validity date changes.',
        'error_note'     => 'If you believe you received this message in error, contact OpesCare support.',
        'legal'          => 'Health IDs are valid for ten years under the MINSANTE Digital Health Strategy 2026–2030.',
    ],

    /* ── Emergency (break-glass) access alert (EmergencyAccessAlertNotification) */
    'emergency' => [
        'subject'       => 'Your health record was opened under emergency access',
        'preheader'     => 'A clinician used emergency access to open your record. Every access is logged — review it now.',
        'eyebrow'       => 'Emergency access notice',
        'title'         => 'Your health record was opened under emergency access',
        'intro'         => 'A clinician opened your health record using the emergency procedure, which allows care to be given when you cannot give consent yourself.',
        'fact_health_id' => 'Health ID',
        'fact_when'     => 'Accessed at',
        'fact_reason'   => 'Reason given',
        'logged'        => 'This access has been recorded in your access log, together with the facility and the account that opened it. You are being told because the law requires it, and because you have the right to know.',
        'action'        => 'Review my access log',
        'panel_title'   => 'If you did not expect this',
        'panel_1'       => 'Open your access log and check the facility and time against your own recollection.',
        'panel_2'       => 'If the access looks wrong, contact OpesCare support immediately — every emergency access is reviewed.',
        'legal'         => 'OpesCare informs you of every access to your record, as required by Cameroon Law No. 2010/012.',
    ],

    /* ── Family / guardian event (FamilyEventNotification) ──────────────── */
    'family_event' => [
        'subject'   => 'An update about a family member in your care',
        'preheader' => 'Something changed on a record you are a guardian for. Sign in to see the details.',
        'eyebrow'   => 'Family update',
        'title'     => 'An update about :name',
        'intro'     => 'As a guardian on OpesCare, you receive a note when something changes on a record you look after. Here is the latest:',
        'action'    => 'Open the family page',
        'note'      => 'Details are shown only inside your secure portal, never in this email.',
        'dependent_fallback' => 'a family member in your care',
    ],

    /* ── Family / guardian access invitation (FamilyInviteNotification) ─── */
    'family_invite' => [
        'subject'          => 'Someone is asking for guardian access to your record',
        'preheader'        => ':guardian is asking to act as your guardian on OpesCare. Nothing is shared until you accept.',
        'eyebrow'          => 'Guardian access request',
        'title'            => 'A request for guardian access to your record',
        'intro'            => ':guardian is asking for guardian access to :patient\'s OpesCare health record. Nothing is shared unless you accept.',
        'intro_self'       => ':guardian is asking for guardian access to your OpesCare health record. Nothing is shared unless you accept.',
        'someone'          => 'Someone',
        'fact_access'      => 'Access requested',
        'fact_relationship' => 'Relationship',
        'access_full'      => 'Full access',
        'access_read_only' => 'Read-only access',
        'action'           => 'Review and decide',
        'expiry'           => 'This invitation expires in :hours hours.',
        'ignore'           => 'If you were not expecting this request, you can ignore this email — no access is granted until you accept.',
    ],

    /* ── Generic platform notice (OpesCareNotificationMail) ─────────────── */
    'notification' => [
        'preheader' => 'A message from OpesCare about your account.',
        'eyebrow'   => 'OpesCare notice',
    ],

];
