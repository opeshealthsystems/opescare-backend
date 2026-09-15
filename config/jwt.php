<?php

/*
|--------------------------------------------------------------------------
| JWT signing keys (RS256)
|--------------------------------------------------------------------------
|
| The key pair is generated per environment and NEVER committed. Create it
| with `php artisan opescare:jwt-keys`. The defaults keep the historical
| location so existing environments need no change.
*/

return [
    'private_key_path' => env('JWT_PRIVATE_KEY_PATH', storage_path('keys/jwt_private.pem')),
    'public_key_path'  => env('JWT_PUBLIC_KEY_PATH', storage_path('keys/jwt_public.pem')),
];
