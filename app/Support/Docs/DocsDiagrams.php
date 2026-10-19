<?php

namespace App\Support\Docs;

/**
 * Data for the architecture and flow diagrams on /docs.
 *
 * Every box and arrow here names something that exists in the code; the file
 * each one comes from is noted beside it. Labels are translation keys under
 * docs_ui.dg.*, rendered by resources/views/docs/diagrams/*.blade.php as
 * inline SVG (no images of text, no third-party libraries).
 */
final class DocsDiagrams
{
    /** Flow pages served at /docs/flows/{flow}. */
    public const FLOWS = ['consent', 'fhir', 'mpi', 'webhooks', 'qr'];

    /** Lucide icon per flow, for nav and cards. */
    public const ICONS = [
        'consent'  => 'shield-check',
        'fhir'     => 'database-zap',
        'mpi'      => 'git-merge',
        'webhooks' => 'webhook',
        'qr'       => 'qr-code',
    ];

    /** Endpoints and classes each flow is drawn from (identifiers, not prose). */
    public const CODE = [
        'consent' => [
            'POST /api/mobile/doctor/consent-requests',
            'POST /api/mobile/consent-requests/{id}/approve',
            'POST /api/mobile/consents/{id}/revoke',
            'App\Support\ClinicalRelationshipGate',
        ],
        'fhir' => [
            'POST /api/fhir/R4',
            'POST /api/fhir/R4/{Observation|DiagnosticReport|Immunization}',
            'GET /api/fhir/R4/metadata',
            'App\Services\Fhir\FhirIngestGuard',
        ],
        'mpi' => [
            '/admin/mpi',
            'POST /admin/mpi/candidates/{candidate}/merge',
            'POST /admin/mpi/merges/{merge}/unmerge',
            'App\Models\Patient::findExistingIdentity()',
        ],
        'webhooks' => [
            '/developer/webhooks',
            'POST /developer/webhooks/deliveries/{delivery}/replay',
            'App\Jobs\SendWebhookJob',
            'X-OpesCare-Signature: t={timestamp},v1={hmac_sha256}',
        ],
        'qr' => [
            'GET /verify/qr/{token}',
            'App\Http\Controllers\MedicalId\VerifyController::qr',
            'App\Services\Identity\QrTokenService',
        ],
    ];

    /**
     * Sequence diagrams. kind: req (call), res (reply), self (internal step),
     * fail (refusal / failure path).
     *
     * @return array{lanes: list<string>, msgs: list<array{0:int,1:int,2:string}>}
     */
    public static function sequence(string $flow): array
    {
        return match ($flow) {
            // routes/mobile_consent_requests.php, routes/api.php (approve/deny/revoke),
            // App\Support\ClinicalRelationshipGate, ConsentGrant (scope, expires_at).
            'consent' => [
                'lanes' => ['clinician', 'api', 'patient', 'record'],
                'msgs'  => [
                    [0, 1, 'req'], [1, 0, 'res'], [0, 1, 'req'], [1, 2, 'req'],
                    [2, 1, 'res'], [1, 1, 'self'], [0, 1, 'req'], [1, 3, 'req'],
                    [1, 1, 'self'], [1, 0, 'res'], [2, 1, 'fail'], [1, 0, 'fail'],
                ],
            ],
            // routes/fhir.php, App\Services\Fhir\FhirIngestGuard (three gates),
            // FhirIngestController::transaction (DB::transaction, transaction-response).
            'fhir' => [
                'lanes' => ['partner', 'bearer', 'guard', 'db'],
                'msgs'  => [
                    [0, 1, 'req'], [1, 1, 'self'], [1, 2, 'req'], [2, 2, 'self'],
                    [2, 2, 'self'], [2, 3, 'req'], [3, 0, 'res'], [2, 0, 'fail'],
                ],
            ],
            // routes/console.php (nightly + weekly scans), /admin/mpi routes in
            // routes/web.php (raise, merge, reject, unmerge).
            'mpi' => [
                'lanes' => ['scheduler', 'detector', 'desk', 'register'],
                'msgs'  => [
                    [0, 1, 'req'], [1, 1, 'self'], [1, 2, 'req'], [2, 2, 'self'],
                    [2, 3, 'req'], [3, 3, 'self'], [2, 3, 'fail'], [2, 3, 'req'],
                ],
            ],
            // App\Services\WebhookService, App\Jobs\SendWebhookJob
            // (HMAC-SHA256, PublicUrlGuard, tries=3, backoff 60s/300s).
            'webhooks' => [
                'lanes' => ['event', 'worker', 'endpoint'],
                'msgs'  => [
                    [0, 1, 'req'], [1, 1, 'self'], [1, 1, 'self'], [1, 2, 'req'],
                    [2, 2, 'self'], [2, 1, 'res'], [1, 2, 'fail'], [1, 1, 'fail'],
                ],
            ],
            // routes/web.php verify.qr, MedicalId\VerifyController::qr (four
            // states), HealthIdVerificationEvent.
            'qr' => [
                'lanes' => ['patient', 'verifier', 'verify', 'log'],
                'msgs'  => [
                    [0, 0, 'self'], [1, 2, 'req'], [2, 2, 'self'], [2, 1, 'res'], [2, 3, 'req'],
                ],
            ],
            default => throw new \InvalidArgumentException("Unknown flow {$flow}"),
        };
    }

    /**
     * Platform overview. Coordinates are in a 960 x 560 viewBox.
     * tone: client | edge | gate | svc | data
     */
    public static function overview(): array
    {
        $svc = ['health_id', 'records', 'finders', 'documents', 'payments', 'webhooks'];
        $boxes = [
            // Clients
            ['web',      20,  70, 190, 64, 'client', 'globe'],
            ['app',      20, 170, 190, 64, 'client', 'smartphone'],
            ['partners', 20, 270, 190, 64, 'client', 'building-2'],
            ['bridge',   20, 370, 190, 64, 'client', 'plug'],
            // opescare.com entry points (routes/web.php, routes/api.php, routes/fhir.php)
            ['r_web',   270,  90, 190, 64, 'edge', 'layout'],
            ['r_api',   270, 210, 190, 64, 'edge', 'braces'],
            ['r_fhir',  270, 330, 190, 64, 'edge', 'flame'],
            // Gates
            ['g_auth',  510,  90, 180, 64, 'gate', 'key-round'],
            ['g_consent', 510, 210, 180, 64, 'gate', 'shield-check'],
            ['g_fhir',  510, 330, 180, 64, 'gate', 'shield'],
            // Data
            ['pg',      270, 478, 250, 60, 'data', 'database'],
            ['queue',   540, 478, 190, 60, 'data', 'list-ordered'],
            ['audit',   750, 478, 190, 60, 'data', 'scroll-text'],
        ];
        foreach ($svc as $i => $k) {
            $boxes[] = ['s_' . $k, 750, 40 + $i * 66, 190, 54, 'svc', null];
        }

        $edges = [
            'M210 102 H262', 'M210 202 C240 202 240 242 262 242', 'M210 302 C240 302 240 362 262 362',
            'M210 302 C240 302 240 242 262 242', 'M210 402 C240 402 240 242 262 242',
            'M460 122 H502', 'M460 242 H502', 'M460 362 H502',
            'M690 122 H720', 'M690 242 H720', 'M690 362 H720',
            'M720 67 V397',
            'M720 67 H742', 'M720 133 H742', 'M720 199 H742', 'M720 265 H742', 'M720 331 H742', 'M720 397 H742',
            'M387 446 V470', 'M635 446 V470', 'M845 450 V470',
        ];

        return [
            'boxes'  => $boxes,
            'edges'  => $edges,
            'groups' => [
                ['clients', 10, 34, 210, 412],
                ['host', 254, 34, 446, 412],
                ['services', 734, 8, 216, 442],
                ['data', 254, 458, 696, 94],
            ],
        ];
    }
}
