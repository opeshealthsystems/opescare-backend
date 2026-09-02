<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HL7 v2 ADT Outbound Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials and connection settings for the HL7 v2 ADT MLLP outbound
    | sender. All values are read from environment variables.
    |
    */
    'host'        => env('HL7_HOST', '127.0.0.1'),
    'port'        => (int) env('HL7_PORT', 2575),
    'facility_id' => env('HL7_FACILITY_ID', 'OPESCARE'),
    'sending_app' => env('HL7_SENDING_APP', 'OPESCARE_EMR'),
    'timeout'     => (int) env('HL7_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | [FIX M-1] Transport Layer Security (MLLP-S)
    |--------------------------------------------------------------------------
    |
    | HL7 v2 ADT messages contain PHI and MUST be encrypted in transit.
    | ISO 27799 §8.2 and ISO 27001 A.13.2.3 both require this.
    |
    | tls             — Enable TLS. Default TRUE. Only set to false for plain
    |                   TCP environments where TLS termination is done upstream
    |                   (e.g. a local MLLP proxy). Always true in production.
    | tls_verify_peer — Verify the server TLS certificate. Default TRUE.
    |                   Set to false ONLY for self-signed certs in staging.
    | tls_cafile      — Path to CA bundle for peer verification (optional;
    |                   uses system bundle when empty).
    |
    */
    'tls'             => (bool) env('HL7_TLS', true),
    'tls_verify_peer' => (bool) env('HL7_TLS_VERIFY_PEER', true),
    'tls_cafile'      => env('HL7_TLS_CAFILE', null),
];
