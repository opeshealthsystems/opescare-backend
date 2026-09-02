<?php

return [
    'enabled' => env('OPESCARE_DEMO_MODE', false),
    'public_enabled' => env('OPESCARE_PUBLIC_DEMO_MODE', false),
    'internal_enabled' => env('OPESCARE_INTERNAL_DEMO_MODE', false),
    'external_services_simulated' => env('OPESCARE_DEMO_EXTERNAL_SERVICES_SIMULATED', false),
    'allowed_ips' => env('DEMO_ALLOWED_IPS', ''),

    'session' => [
        'public_lifetime_minutes' => 30,
        'internal_lifetime_minutes' => 120, // 2 hours
    ],

    'webhook' => [
        'allowed_domains' => [
            'localhost',
            '127.0.0.1',
            '.example.test',
        ],
    ],
];
