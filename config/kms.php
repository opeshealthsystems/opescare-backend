<?php
// config/kms.php
return [
    'driver' => env('KMS_DRIVER', 'local'),
    'aws' => [
        'key_id'  => env('KMS_AWS_KEY_ID'),
        'region'  => env('KMS_AWS_REGION', 'eu-west-1'),
        'version' => 'latest',
    ],
];
