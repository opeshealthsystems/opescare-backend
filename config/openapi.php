<?php

/*
|--------------------------------------------------------------------------
| OpenAPI curation
|--------------------------------------------------------------------------
|
| 'schemas'   — extra component schemas merged into the generated spec.
| 'overrides' — full operation objects keyed "METHOD /full/path" that replace
|               the auto-generated stub for that exact operation. Everything
|               else is generated from the route table by
|               `php artisan opescare:generate-openapi`.
|
*/

$iso = ['type' => 'string', 'format' => 'date-time', 'nullable' => true];
$uuid = ['type' => 'string', 'format' => 'uuid'];

$clientAuth = [['ClientId' => [], 'ClientSecret' => []]];

$jsonBody = fn (array $schema, bool $required = true) => [
    'required' => $required,
    'content'  => ['application/json' => ['schema' => $schema]],
];
$dataResponse = fn (string $ref, string $status = '200', string $desc = 'Success') => [
    $status => [
        'description' => $desc,
        'content' => ['application/json' => ['schema' => [
            'type' => 'object',
            'properties' => ['message' => ['type' => 'string'], 'data' => ['$ref' => '#/components/schemas/' . $ref]],
        ]]],
    ],
    '422' => ['description' => 'Validation failed', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
];

return [

    'schemas' => [
        'ClinicalNote' => ['type' => 'object', 'properties' => [
            'id' => $uuid, 'visit_id' => $uuid, 'provider_id' => $uuid,
            'status' => ['type' => 'string', 'enum' => ['draft', 'signed', 'amended']],
            'history_of_present_illness' => ['type' => 'string', 'nullable' => true],
            'examination_findings' => ['type' => 'string', 'nullable' => true],
            'treatment_plan' => ['type' => 'string', 'nullable' => true],
            'signed_at' => $iso, 'amends_note_id' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true], 'created_at' => $iso,
        ]],
        'AllergyRecord' => ['type' => 'object', 'properties' => [
            'id' => $uuid, 'patient_id' => $uuid, 'provider_id' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
            'substance' => ['type' => 'string'],
            'severity' => ['type' => 'string', 'enum' => ['mild', 'moderate', 'severe', 'life_threatening']],
            'status' => ['type' => 'string'], 'created_at' => $iso,
        ]],
        'Diagnosis' => ['type' => 'object', 'properties' => [
            'id' => $uuid, 'patient_id' => $uuid, 'visit_id' => $uuid, 'provider_id' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
            'display_name' => ['type' => 'string'],
            'code_system' => ['type' => 'string', 'enum' => ['ICD-10', 'SNOMED', 'other']],
            'code' => ['type' => 'string', 'nullable' => true], 'snomed_code' => ['type' => 'string', 'nullable' => true],
            'snomed_display' => ['type' => 'string', 'nullable' => true],
            'status' => ['type' => 'string', 'enum' => ['active', 'resolved', 'ruled_out']],
            'is_primary' => ['type' => 'boolean'], 'created_at' => $iso,
        ]],
        'TokenResponse' => ['type' => 'object', 'properties' => [
            'access_token' => ['type' => 'string'], 'token_type' => ['type' => 'string', 'example' => 'Bearer'],
            'expires_in' => ['type' => 'integer', 'example' => 3600], 'scope' => ['type' => 'string'],
        ]],
        'IntrospectionResponse' => ['type' => 'object', 'description' => 'RFC 7662. Inactive tokens return only {active:false}.', 'properties' => [
            'active' => ['type' => 'boolean'], 'token_type' => ['type' => 'string'], 'scope' => ['type' => 'string'],
            'client_id' => ['type' => 'string'], 'sub' => ['type' => 'string'], 'aud' => ['type' => 'string'],
            'iss' => ['type' => 'string'], 'exp' => ['type' => 'integer'], 'iat' => ['type' => 'integer'], 'jti' => ['type' => 'string'],
        ]],
        'Jwk' => ['type' => 'object', 'properties' => [
            'kty' => ['type' => 'string', 'example' => 'RSA'], 'use' => ['type' => 'string', 'example' => 'sig'],
            'alg' => ['type' => 'string', 'example' => 'RS256'], 'kid' => ['type' => 'string'],
            'n' => ['type' => 'string'], 'e' => ['type' => 'string', 'example' => 'AQAB'],
        ]],
        'Jwks' => ['type' => 'object', 'properties' => ['keys' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Jwk']]]],
        'OAuthServerMetadata' => ['type' => 'object', 'properties' => [
            'issuer' => ['type' => 'string'], 'token_endpoint' => ['type' => 'string'],
            'introspection_endpoint' => ['type' => 'string'], 'jwks_uri' => ['type' => 'string'],
            'grant_types_supported' => ['type' => 'array', 'items' => ['type' => 'string']],
            'scopes_supported' => ['type' => 'array', 'items' => ['type' => 'string']],
        ]],
    ],

    'overrides' => [
        'POST /api/v1/connect/auth/token' => [
            'tags' => ['Connect'], 'summary' => 'Issue an access token (client_credentials)', 'operationId' => 'Connect_token',
            'security' => [],
            'requestBody' => $jsonBody(['type' => 'object', 'required' => ['client_id', 'client_secret', 'grant_type'], 'properties' => [
                'client_id' => ['type' => 'string'], 'client_secret' => ['type' => 'string'],
                'grant_type' => ['type' => 'string', 'enum' => ['client_credentials']],
            ]]),
            'responses' => [
                '200' => ['description' => 'Token issued', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/TokenResponse']]]],
                '401' => ['description' => 'Invalid credentials', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
            ],
        ],
        'POST /api/v1/connect/auth/introspect' => [
            'tags' => ['Connect'], 'summary' => 'Introspect a token (RFC 7662)', 'operationId' => 'Connect_introspect',
            'security' => $clientAuth,
            'requestBody' => $jsonBody(['type' => 'object', 'properties' => ['token' => ['type' => 'string']]]),
            'responses' => [
                '200' => ['description' => 'Introspection result', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/IntrospectionResponse']]]],
                '401' => ['description' => 'Missing client credentials', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
            ],
        ],
        'GET /.well-known/jwks.json' => [
            'tags' => ['Discovery'], 'summary' => 'JSON Web Key Set (RFC 7517)', 'operationId' => 'Discovery_jwks', 'security' => [],
            'responses' => ['200' => ['description' => 'JWK Set', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Jwks']]]]],
        ],
        'GET /.well-known/oauth-authorization-server' => [
            'tags' => ['Discovery'], 'summary' => 'OAuth 2.0 Authorization Server Metadata (RFC 8414)', 'operationId' => 'Discovery_oauthMetadata', 'security' => [],
            'responses' => ['200' => ['description' => 'Server metadata', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/OAuthServerMetadata']]]]],
        ],
        'POST /api/v1/encounters/notes' => [
            'tags' => ['Encounters'], 'summary' => 'Save a clinical note (draft or signed)', 'operationId' => 'Encounter_saveNote',
            'security' => $clientAuth,
            'requestBody' => $jsonBody(['type' => 'object', 'required' => ['visit_id', 'provider_id'], 'properties' => [
                'visit_id' => $uuid, 'provider_id' => $uuid,
                'history_of_present_illness' => ['type' => 'string'], 'examination_findings' => ['type' => 'string'],
                'treatment_plan' => ['type' => 'string'], 'status' => ['type' => 'string', 'enum' => ['draft', 'signed']], 'actor_id' => $uuid,
            ]]),
            'responses' => $dataResponse('ClinicalNote', '201', 'Note saved'),
        ],
        'GET /api/v1/encounters/notes/{note}' => [
            'tags' => ['Encounters'], 'summary' => 'Retrieve a clinical note', 'operationId' => 'Encounter_showNote',
            'security' => $clientAuth,
            'parameters' => [['name' => 'note', 'in' => 'path', 'required' => true, 'schema' => $uuid]],
            'responses' => $dataResponse('ClinicalNote'),
        ],
        'POST /api/v1/encounters/notes/{note}/amend' => [
            'tags' => ['Encounters'], 'summary' => 'Amend a signed clinical note', 'operationId' => 'Encounter_amendNote',
            'security' => $clientAuth,
            'parameters' => [['name' => 'note', 'in' => 'path', 'required' => true, 'schema' => $uuid]],
            'requestBody' => $jsonBody(['type' => 'object', 'required' => ['amendment_reason'], 'properties' => [
                'history_of_present_illness' => ['type' => 'string'], 'examination_findings' => ['type' => 'string'],
                'treatment_plan' => ['type' => 'string'], 'amendment_reason' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 1000], 'actor_id' => $uuid,
            ]]),
            'responses' => $dataResponse('ClinicalNote', '201', 'Amended'),
        ],
        'POST /api/v1/encounters/allergies' => [
            'tags' => ['Encounters'], 'summary' => 'Record a patient allergy', 'operationId' => 'Encounter_recordAllergy',
            'security' => $clientAuth,
            'requestBody' => $jsonBody(['type' => 'object', 'required' => ['patient_id', 'substance'], 'properties' => [
                'patient_id' => $uuid, 'substance' => ['type' => 'string', 'maxLength' => 255],
                'severity' => ['type' => 'string', 'enum' => ['mild', 'moderate', 'severe', 'life_threatening']], 'actor_id' => $uuid,
            ]]),
            'responses' => $dataResponse('AllergyRecord', '201', 'Allergy recorded'),
        ],
        'POST /api/v1/encounters/diagnoses' => [
            'tags' => ['Encounters'], 'summary' => 'Record a diagnosis (ICD-10 / SNOMED)', 'operationId' => 'Encounter_recordDiagnosis',
            'security' => $clientAuth,
            'requestBody' => $jsonBody(['type' => 'object', 'required' => ['patient_id', 'visit_id', 'display_name'], 'properties' => [
                'patient_id' => $uuid, 'visit_id' => $uuid, 'display_name' => ['type' => 'string', 'maxLength' => 255],
                'code_system' => ['type' => 'string', 'enum' => ['ICD-10', 'SNOMED', 'other']], 'code' => ['type' => 'string', 'maxLength' => 50],
                'status' => ['type' => 'string', 'enum' => ['active', 'resolved', 'ruled_out']], 'is_primary' => ['type' => 'boolean'], 'actor_id' => $uuid,
            ]]),
            'responses' => $dataResponse('Diagnosis', '201', 'Diagnosis recorded'),
        ],
    ],

];
