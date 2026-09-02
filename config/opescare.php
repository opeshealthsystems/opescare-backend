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
    'support_email' => env('OPESCARE_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', '')),
];
