<?php

return [
    /*
     * The five keys Laravel's own password broker looks up by status name
     * (Illuminate\Auth\Passwords\PasswordBroker::RESET_LINK_SENT and friends).
     * Keep the names exactly as they are — the framework builds them.
     */
    'reset'     => 'Your password has been reset. You can now sign in with it.',
    'sent'      => 'We have emailed your password reset link.',
    'throttled' => 'Please wait before retrying.',
    'token'     => 'This password reset token is invalid.',
    'user'      => 'We cannot find a user with that email address.',

    /*
     * The request form. This message is returned for EVERY address — registered,
     * unregistered, or asked for twice in a minute — so it must not read as a
     * confirmation that an account exists.
     */
    'request' => [
        'desc' => 'Enter the email address on your OpesCare account. If it matches an account, we will send a link you can use to set a new password.',
        'sent' => 'If that address matches an OpesCare account, a reset link is on its way. The link is valid for :minutes minutes and can be used once.',
    ],

    /* The form behind a live link. */
    'form' => [
        'intro'          => 'Choose a new password for :email. You will be signed out everywhere else once it is saved.',
        'policy_title'   => 'Your new password must:',
        'policy_length'  => 'be at least 8 characters long',
        'policy_unique'  => 'be different from passwords you use on other services',
        'policy_private' => 'never be shared with anyone, including OpesCare staff',
    ],

    /* The one visible failure for every dead link. */
    'invalid_link' => [
        'title' => 'This reset link is no longer valid.',
        'body'  => 'A reset link works once and expires after :minutes minutes. This one has already been used, has expired, or was not issued by OpesCare.',
        'next'  => 'Your password has not been changed. Request a new link and it will arrive within a few minutes.',
        'cta'   => 'Request a new link',
    ],

    /* The email that carries the link. */
    'mail' => [
        'subject'    => 'Reset your OpesCare password',
        'greeting'   => 'Hello,',
        'intro'      => 'We received a request to reset the password on your OpesCare account.',
        'action'     => 'Set a new password',
        'expiry'     => 'This link expires in :minutes minutes and can only be used once.',
        'ignore'     => 'If you did not ask for this, you can ignore this email — your password will not change and nobody has been given access to your account.',
        'salutation' => 'The OpesCare team',
    ],

    /* The email sent after a password has actually changed. */
    'changed_mail' => [
        'subject'  => 'Your OpesCare password was changed',
        'greeting' => 'Hello,',
        'intro'    => 'The password on your OpesCare account was just changed using a reset link.',
        'sessions' => 'Everywhere that was signed in to this account has been signed out, on every device.',
        'origin'   => 'The change was made from IP address :ip.',
        'warn'     => 'If this was not you, contact OpesCare support immediately — someone else has access to the email address on your account.',
    ],
];
