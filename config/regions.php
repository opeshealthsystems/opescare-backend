<?php

return [
    'current_region'   => env('APP_REGION', 'af-south-1'),
    'fallback_region'  => env('APP_FALLBACK_REGION', ''),
    'health_check_ttl' => env('DB_HEALTH_CHECK_TTL', 30),
    'failover_webhook' => env('FAILOVER_ALERT_WEBHOOK', ''),
];
