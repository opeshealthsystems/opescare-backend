<?php

return [
    'system_provider_id' => env('OPESCARE_SYSTEM_PROVIDER_ID', '00000000-0000-0000-0000-000000000001'),
    'demo' => [
        'enabled'     => env('OPESCARE_DEMO_MODE', false),
        'allowed_ips' => env('DEMO_ALLOWED_IPS', ''),
    ],
    'family' => [
        'invite_ttl_hours' => env('FAMILY_INVITE_TTL_HOURS', 48),
    ],
    'health_id' => [
        'default_country' => env('OPESCARE_DEFAULT_COUNTRY', 'CM'),
    ],

    // Set to true in production once subdomains are configured in Nginx.
    // When enabled, each subdomain only serves its designated routes (404 otherwise).
    'subdomain_routing' => env('SUBDOMAIN_ROUTING', false),

    // Support contact — shown in SMS reminders and system notifications.
    // Override via OPESCARE_SUPPORT_PHONE in .env
    'support_phone' => env('OPESCARE_SUPPORT_PHONE', ''),

    // Admin / operations support email
    // No fallback to MAIL_FROM_ADDRESS. That address is a no-reply mailbox, and
    // the email footer used this value in "for help, write to :email" — so an
    // unconfigured support address told every patient to write to a bin. Empty
    // means the sentence is omitted; the owner sets OPESCARE_SUPPORT_EMAIL.
    'support_email' => env('OPESCARE_SUPPORT_EMAIL', ''),
];
