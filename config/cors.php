<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Allows the OpesCare Flutter patient app (running at a different port
    | during development) to call the API. In production, restrict
    | allowed_origins to the actual app domains.
    |
    */

    'paths' => ['api/*', 'mobile/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'https://opescare.cloud,https://www.opescare.cloud'
    )))),

    /*
     | Any localhost / 127.0.0.1 port, so an Expo dev server reaches the API
     | whichever port it lands on. Metro picks 8081, then 8082, 8083 … as ports
     | are taken, and a fixed allow-list silently blocks the app's reachability
     | probe on the next port up — the app then reports "no internet" and the
     | login screen refuses to submit, while the API is answering 200 all along.
     |
     | Safe: this widens nothing on the public internet. A loopback origin only
     | exists on the developer's own machine, the API still requires a bearer
     | token, and supports_credentials is false so no cookie can ride along.
     */
    'allowed_origins_patterns' => [
        '#^http://(localhost|127\.0\.0\.1)(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
