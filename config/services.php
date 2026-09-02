<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid'   => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from'  => env('TWILIO_FROM'),
    ],

    'kms' => [
        'driver' => env('KMS_DRIVER', 'local'),
    ],

    'pharmacy' => [
        'routing_webhook_url' => env('PHARMACY_ROUTING_WEBHOOK_URL'),
    ],

    'mtn_momo' => [
        'base_url'         => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
        'api_key'          => env('MTN_MOMO_API_KEY'),
        'user_id'          => env('MTN_MOMO_USER_ID'),
        'environment'      => env('MTN_MOMO_ENVIRONMENT', 'sandbox'),
        'currency'         => env('MTN_MOMO_CURRENCY', 'XAF'),
        'callback_url'     => env('MTN_MOMO_CALLBACK_URL'),
        'callback_secret'  => env('MTN_MOMO_CALLBACK_SECRET'),
        // Outbound HTTP resilience (seconds / attempts). A hung telco endpoint
        // must never block a request thread indefinitely.
        'connect_timeout'  => env('MTN_MOMO_CONNECT_TIMEOUT', 5),
        'timeout'          => env('MTN_MOMO_TIMEOUT', 30),
        'retries'          => env('MTN_MOMO_RETRIES', 3),
    ],

    'orange_money' => [
        'base_url'      => env('ORANGE_MONEY_BASE_URL', 'https://api.orange.com'),
        'client_id'     => env('ORANGE_MONEY_CLIENT_ID'),
        'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
        'merchant_key'  => env('ORANGE_MONEY_MERCHANT_KEY'),
        'currency'      => env('ORANGE_MONEY_CURRENCY', 'XAF'),
        'return_url'    => env('ORANGE_MONEY_RETURN_URL'),
        'cancel_url'    => env('ORANGE_MONEY_CANCEL_URL'),
        'notif_url'     => env('ORANGE_MONEY_NOTIF_URL'),
        'callback_secret' => env('ORANGE_MONEY_CALLBACK_SECRET'),
        // Outbound HTTP resilience (seconds / attempts).
        'connect_timeout' => env('ORANGE_MONEY_CONNECT_TIMEOUT', 5),
        'timeout'         => env('ORANGE_MONEY_TIMEOUT', 30),
        'retries'         => env('ORANGE_MONEY_RETRIES', 3),
    ],

    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token'    => env('WHATSAPP_ACCESS_TOKEN'),
        'api_version'     => env('WHATSAPP_API_VERSION', 'v18.0'),
    ],

    'fcm' => [
        'project_id'           => env('FCM_PROJECT_ID'),
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON'),
        'server_key'           => env('FCM_SERVER_KEY'), // Legacy — kept for backward compat
    ],

    'africastalking' => [
        'ussd_callback_secret' => env('AFRICASTALKING_USSD_CALLBACK_SECRET'),
    ],

    'pacs' => [
        'wado_url' => env('PACS_WADO_URL', ''),
        'stow_url' => env('PACS_STOW_URL', ''),
        'auth'     => env('PACS_AUTH_TOKEN', ''),
    ],

    'dhis2' => [
        'url'      => env('DHIS2_URL', 'https://dhis2.minsante.cm'),
        'username' => env('DHIS2_USERNAME'),
        'password' => env('DHIS2_PASSWORD'),
    ],

    // OpesCare Connect/SDK JWT. Read via config (not env() in the service) so the
    // configured TTL survives config:cache in production.
    'opescare_jwt' => [
        'ttl' => (int) env('OPESCARE_JWT_TTL', 3600),
    ],

    // OAuth 2.0 Authorization Server Metadata (RFC 8414). `issuer` is the public
    // API base URL advertised in /.well-known/oauth-authorization-server; falls
    // back to APP_URL when unset. The JWT `iss` claim remains "opescare-connect".
    'opescare_oauth' => [
        'issuer' => env('OPESCARE_OAUTH_ISSUER'),
    ],

];
