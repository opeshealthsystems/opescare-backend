<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Health ID Generation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the OpesCare Health ID generation service.
    | Health IDs follow the format: CM-HID-XXXX-XXXX-XXXX
    | where X is a character from the safe alphabet (no 0, O, I, 1, L).
    |
    */

    /*
     | Maximum number of retry attempts when a generated Health ID collides
     | with an existing one in the database. With 32^12 = ~6.7 trillion possible
     | IDs the probability of collision is negligible, but we retry defensively.
     | Increase this only if you observe repeated generation failures in logs.
     */
    'max_retries' => env('HEALTH_ID_MAX_RETRIES', 10),

    /*
     | Country code used as the default prefix when none is specified.
     | Follows ISO 3166-1 alpha-2 standard (CM = Cameroon).
     */
    'default_country' => env('HEALTH_ID_DEFAULT_COUNTRY', 'CM'),

];
