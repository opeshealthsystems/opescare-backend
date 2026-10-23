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

    // Operator-only routes that are real but must NOT be advertised in the
    // public spec / Postman collection. Keyed "METHOD /full/path". The
    // generator skips them and OpenApiContractTest excludes them from "no gaps".
    'internal' => [
        'POST /api/demo/reset',
    ],

    'schemas' => [
        'PartnerReading' => ['type' => 'object', 'description' => 'A reading pushed in by a partner system over FHIR R4. Never a facility measurement; always labelled with the sending facility and system.', 'properties' => [
            'id' => $uuid,
            'type' => ['type' => 'string', 'enum' => ['observation', 'diagnostic_report', 'immunization']],
            'kind' => ['type' => 'string', 'nullable' => true, 'description' => 'vital-signs | laboratory | report category | immunization'],
            'display' => ['type' => 'string', 'nullable' => true],
            'code' => ['type' => 'string'],
            'code_system' => ['type' => 'string', 'enum' => ['LOINC', 'CVX']],
            'value' => ['type' => 'number', 'nullable' => true],
            'unit' => ['type' => 'string', 'nullable' => true],
            'value_text' => ['type' => 'string', 'nullable' => true],
            'components' => ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'object', 'properties' => [
                'code' => ['type' => 'string', 'nullable' => true], 'display' => ['type' => 'string', 'nullable' => true],
                'value' => ['type' => 'number', 'nullable' => true], 'unit' => ['type' => 'string', 'nullable' => true],
                'value_text' => ['type' => 'string', 'nullable' => true],
            ]]],
            'reference_range' => ['type' => 'string', 'nullable' => true, 'description' => 'As sent by the partner'],
            'interpretation' => ['type' => 'string', 'nullable' => true, 'description' => 'The partner\'s own flag, never re-judged'],
            'conclusion' => ['type' => 'string', 'nullable' => true],
            'status' => ['type' => 'string'],
            'effective_at' => $iso,
            'received_at' => $iso,
            'sending_facility' => ['type' => 'object', 'properties' => ['id' => $uuid, 'name' => ['type' => 'string', 'nullable' => true]]],
            'sending_system' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string'], 'client_id' => ['type' => 'string']]],
            'provenance' => ['type' => 'string', 'enum' => ['partner_reported']],
            'provenance_label' => ['type' => 'string'],
        ]],
        'PainSite' => ['type' => 'object', 'properties' => [
            'id' => $uuid,
            'region' => ['type' => 'string', 'enum' => ['head', 'forehead', 'face', 'neck', 'chest', 'upper_abdomen', 'lower_abdomen', 'pelvis', 'right_arm', 'left_arm', 'right_leg', 'left_leg', 'upper_back', 'lower_back']],
            'region_label' => ['type' => 'string'],
            'severity' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10],
            'onset_at' => $iso,
            'trend' => ['type' => 'string', 'enum' => ['better', 'same', 'worse'], 'nullable' => true],
            'trend_label' => ['type' => 'string', 'nullable' => true],
            'note' => ['type' => 'string', 'nullable' => true],
            'source' => ['type' => 'string', 'enum' => ['patient_checkin', 'triage', 'clinician']],
            'source_label' => ['type' => 'string'],
            'resolved_at' => $iso,
            'recorded_at' => $iso,
        ]],
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
        'GET /api/mobile/records/partner-readings' => [
            'tags' => ['Mobile'], 'summary' => 'The patient\'s partner-reported readings, labelled and apart from facility readings', 'operationId' => 'MobilePartnerReadings_index',
            'description' => 'Observations, reports and immunizations pushed in by partner systems over FHIR R4, newest first. Every row names the sending facility and system and carries provenance partner_reported. Patient from the bearer token only.',
            'security' => [['MobileToken' => []]],
            'parameters' => [
                ['name' => 'page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20]],
                ['name' => 'per_page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50]],
            ],
            'responses' => [
                '200' => ['description' => 'Partner-reported readings', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/PartnerReading']],
                    'meta' => ['type' => 'object', 'properties' => [
                        'page' => ['type' => 'integer'], 'per_page' => ['type' => 'integer'], 'total' => ['type' => 'integer'],
                        'has_more' => ['type' => 'boolean'], 'provenance' => ['type' => 'string'],
                        'provenance_label' => ['type' => 'string'], 'note' => ['type' => 'string'],
                    ]],
                ]]]]],
                '401' => ['description' => 'Unauthenticated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
                '422' => ['description' => 'Validation failed', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
            ],
        ],
        'GET /api/mobile/preconsult/appointments/{appointment}/pain-sites' => [
            'tags' => ['Mobile'], 'summary' => 'List the pain sites sent at check-in for this appointment', 'operationId' => 'MobilePreconsultPainSite_index',
            'security' => [['MobileToken' => []]],
            'parameters' => [['name' => 'appointment', 'in' => 'path', 'required' => true, 'schema' => $uuid]],
            'responses' => [
                '200' => ['description' => 'Unresolved check-in sites', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/PainSite']]]]]]],
                '404' => ['description' => 'Not this patient’s appointment (indistinguishable from none)'],
            ],
        ],
        'POST /api/mobile/preconsult/appointments/{appointment}/pain-sites' => [
            'tags' => ['Mobile'], 'summary' => 'Report where it hurts at pre-consult check-in', 'operationId' => 'MobilePreconsultPainSite_store',
            'description' => 'Patient (or guardian acting for a dependent) reports 1-8 pain sites. Facility is taken from the appointment. Accepted only while the pre-consult window is open (409 preconsult_window_closed otherwise). Left/right are the patient’s.',
            'security' => [['MobileToken' => []]],
            'parameters' => [['name' => 'appointment', 'in' => 'path', 'required' => true, 'schema' => $uuid]],
            'requestBody' => $jsonBody(['type' => 'object', 'required' => ['sites'], 'properties' => [
                'sites' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 8, 'items' => ['type' => 'object', 'required' => ['region', 'severity'], 'properties' => [
                    'region' => ['type' => 'string', 'enum' => ['head', 'forehead', 'face', 'neck', 'chest', 'upper_abdomen', 'lower_abdomen', 'pelvis', 'right_arm', 'left_arm', 'right_leg', 'left_leg', 'upper_back', 'lower_back']],
                    'severity' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10],
                    'onset_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'trend' => ['type' => 'string', 'enum' => ['better', 'same', 'worse'], 'nullable' => true],
                    'note' => ['type' => 'string', 'maxLength' => 500, 'nullable' => true],
                ]]],
            ]]),
            'responses' => [
                '201' => ['description' => 'Recorded', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['message' => ['type' => 'string'], 'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/PainSite']]]]]]],
                '404' => ['description' => 'Not this patient’s appointment'],
                '409' => ['description' => 'Pre-consult window closed'],
                '422' => ['description' => 'Validation failed', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
            ],
        ],
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
        // ── Connect partner patient / consent API (PatientController, ConsentController) ──
        'POST /api/v1/connect/patients/resolve' => [
            'tags' => ['Connect'], 'summary' => 'Resolve a patient by Health ID or demographics (never creates)', 'operationId' => 'Connect_resolvePatient',
            'description' => 'Scope health_id:read. Sandbox tokens see demo patients only. A demographic lookup needs consent_acknowledged=true before any search; an ambiguous match returns no Health ID.',
            'security' => [['BearerAuth' => []]],
            'requestBody' => $jsonBody(['type' => 'object', 'required' => ['purpose'], 'properties' => [
                'health_id' => ['type' => 'string', 'maxLength' => 30],
                'first_name' => ['type' => 'string'], 'last_name' => ['type' => 'string'], 'full_name' => ['type' => 'string'],
                'date_of_birth' => ['type' => 'string', 'format' => 'date'], 'dob' => ['type' => 'string', 'format' => 'date'],
                'sex' => ['type' => 'string', 'enum' => ['male', 'female']],
                'phone_number' => ['type' => 'string'], 'phone' => ['type' => 'string'], 'country_code' => ['type' => 'string'],
                'purpose' => ['type' => 'string'], 'consent_acknowledged' => ['type' => 'boolean'], 'external_reference' => ['type' => 'string'],
            ]]),
            'responses' => [
                '200' => ['description' => 'found: {status, health_id, match{confidence, method}, patient{display_name (masked), sex, year_of_birth, country_code, verification_status, identity_status, active, verified}, next_action}'],
                '404' => ['description' => 'HEALTH_ID_NOT_FOUND | PATIENT_NOT_FOUND'],
                '409' => ['description' => 'AMBIGUOUS_MATCH (no Health IDs returned)'],
                '422' => ['description' => 'MISSING_LOOKUP_CRITERIA | CONSENT_REQUIRED | INVALID_PAYLOAD'],
            ],
        ],
        'GET /api/v1/connect/patients/verify/{health_id}' => [
            'tags' => ['Connect'], 'summary' => 'Verify a Health ID (format, check block, registration, status)', 'operationId' => 'Connect_verifyHealthId',
            'security' => [['BearerAuth' => []]],
            'parameters' => [['name' => 'health_id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
            'responses' => [
                '200' => ['description' => '{status: valid, health_id, active, verified, resolved_via_alias, queried_health_id, patient{...}}'],
                '404' => ['description' => 'HEALTH_ID_NOT_FOUND'],
                '422' => ['description' => 'HEALTH_ID_INVALID_FORMAT (format or check block)'],
            ],
        ],
        'GET /api/v1/connect/patients/{health_id}/summary' => [
            'tags' => ['Connect'], 'summary' => 'Pull the consented patient summary', 'operationId' => 'Connect_patientSummary',
            'description' => 'Scope patients:read and an active consent grant from this patient to the token facility covering patients:read.',
            'security' => [['BearerAuth' => []]],
            'parameters' => [
                ['name' => 'health_id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                ['name' => 'X-Consent-Grant-Id', 'in' => 'header', 'required' => true, 'schema' => $uuid],
                ['name' => 'X-Purpose-Of-Use', 'in' => 'header', 'required' => false, 'schema' => ['type' => 'string', 'default' => 'treatment']],
            ],
            'responses' => [
                '200' => ['description' => '{status, health_id, summary_generated_at, verification_status, consent{grant_id, scope, expires_at}, demographics{...}, sections{recent_visits, recent_lab_results, medications, allergies, chronic_conditions, immunizations}}'],
                '403' => ['description' => 'FACILITY_REQUIRED | CONSENT_REQUIRED (reason: facility_mismatch | scope_insufficient when applicable)'],
            ],
        ],
        'POST /api/v1/connect/consents/request-medical-id' => [
            'tags' => ['Connect'], 'summary' => 'Ask a patient for consent by Health ID (facility from the token)', 'operationId' => 'Connect_requestConsent',
            'security' => [['BearerAuth' => []]],
            'requestBody' => $jsonBody(['type' => 'object', 'required' => ['health_id', 'purpose', 'requested_scope'], 'properties' => [
                'health_id' => ['type' => 'string'], 'purpose' => ['type' => 'string', 'pattern' => '^[a-z0-9_.:-]+$'],
                'requested_scope' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['patients:read']]],
                'expires_in_days' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 365, 'default' => 30],
                'justification' => ['type' => 'string', 'maxLength' => 500],
            ]]),
            'responses' => [
                '201' => ['description' => '{status: success, consent_id, request_id, consent_status: pending, grant_id: null, requested_scope, expires_in_days, expires_at, existing: false}'],
                '200' => ['description' => 'Open request returned (existing: true) or already granted (consent_status: granted, grant_id, grant_expires_at)'],
                '403' => ['description' => 'FACILITY_REQUIRED | HEALTH_ID_SUSPENDED'],
                '404' => ['description' => 'HEALTH_ID_NOT_FOUND'],
                '422' => ['description' => 'INVALID_PAYLOAD'],
            ],
        ],
        'GET /api/v1/connect/consents/status/{health_id}' => [
            'tags' => ['Connect'], 'summary' => 'The token facility\'s consent status with a patient', 'operationId' => 'Connect_consentStatus',
            'security' => [['BearerAuth' => []]],
            'parameters' => [['name' => 'health_id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
            'responses' => [
                '200' => ['description' => '{health_id, status (granted|pending|denied|revoked|expired|none), consent_status, grant_id, request_id, id, scope[], expires_at}'],
                '403' => ['description' => 'FACILITY_REQUIRED'],
                '404' => ['description' => 'HEALTH_ID_NOT_FOUND'],
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
