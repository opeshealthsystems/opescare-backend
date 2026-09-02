<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication (TOTP)
    |--------------------------------------------------------------------------
    |
    | Scaffolding for staff/admin two-factor auth (GAP-009). The TOTP parameters
    | follow RFC 6238 defaults (SHA1, 6 digits, 30s period) for compatibility
    | with Google Authenticator, Microsoft Authenticator, Authy, etc.
    |
    | `required_roles` lists role names that MUST complete MFA before reaching a
    | portal once enforcement is wired into the login flow. Until then this is
    | inert configuration.
    |
    */

    'issuer' => env('MFA_ISSUER', 'OpesCare'),

    'totp' => [
        'digits' => 6,
        'period' => 30,   // seconds
        'window' => 1,    // accept codes ±1 period to allow for clock skew
        'algorithm' => 'sha1',
    ],

    // Roles that require MFA once enforcement is enabled. Empty = none enforced yet.
    'required_roles' => array_filter(explode(',', env('MFA_REQUIRED_ROLES', 'super_admin,admin'))),

    // Number of single-use recovery codes generated at enrollment.
    'recovery_code_count' => 8,

];
