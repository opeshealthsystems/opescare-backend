<?php

namespace App\Http\Controllers\Api\Fhir;

use App\Exceptions\FhirIngestRefusal;
use App\Http\Controllers\Controller;
use App\Models\FhirIngestEvent;
use App\Models\IdempotencyRecord;
use App\Models\IntegrationClient;
use App\Models\PartnerDiagnosticReport;
use App\Models\PartnerImmunization;
use App\Models\PartnerObservation;
use App\Models\Patient;
use App\Services\Fhir\FhirCapabilityStatement;
use App\Services\Fhir\FhirIngestGuard;
use App\Services\Fhir\FhirIngestService;
use App\Support\Fhir\FhirOutcome;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FHIR R4 INGEST — the door an external system pushes clinical data through.
 *
 * ======================================================================
 * THE RULE THIS CONTROLLER EXISTS TO ENFORCE
 * ======================================================================
 * A PARTNER MAY NEVER CREATE A HEALTH ID. There is no POST /Patient here, no
 * upsert, no conditional create, and no side effect on any endpoint below
 * that mints an identity. A partner supplies a Health ID that already exists;
 * if it does not resolve exactly, the write is refused with a 404 and nothing
 * is written. An external system that can mint identities is how a national
 * register fills with duplicates nobody can later unpick.
 *
 * The other two settled rules:
 *   * Industry standard FHIR R4, with SMART-on-FHIR system scopes.
 *   * No patient consent, no write — refused, logged, and the refusal names
 *     consent as the reason.
 *
 * ======================================================================
 * WHY THIS IS NOT THE PLATFORM'S FHIR MAPPERS RUN BACKWARDS
 * ======================================================================
 * The OpesCare platform's FHIR layer is read-only by design: its mappers turn
 * a row that is already true into FHIR. Ingest is the opposite problem. Every
 * field arrives as a CLAIM from outside, and the job is to decide what to
 * believe: is that a real LOINC code, is that patient real, may this partner
 * write about them, is that timestamp sane, and — the one no mapper ever has
 * to answer — did we already store this exact event two seconds ago.
 *
 * ======================================================================
 * STATUS CODES (platform law)
 * ======================================================================
 *   401  no/invalid token                     VerifyBearerToken
 *   403  missing SMART scope                  VerifyBearerToken / guard
 *   403  no consent                           named as consent
 *   404  Health ID does not resolve           never 403 - existence stays private
 *   400  malformed resource / invalid code
 *   409  Idempotency-Key reused, different body
 *   422  content refused by a business rule
 *
 * Every refusal is a FHIR OperationOutcome, and every attempt — accepted or
 * refused — writes a line to `fhir_ingest_events`. A refused write stores no
 * clinical row by definition, so without that log the two events most worth
 * seeing would leave no trace at all.
 */
class FhirIngestController extends Controller
{
    private const MAX_IDEMPOTENCY_KEY = 191;

    /** How long a replayable response is kept. Matches the Connect convention. */
    private const IDEMPOTENCY_TTL_HOURS = 24;

    /** A Bundle big enough to be a bulk load is not a transaction. */
    private const MAX_BUNDLE_ENTRIES = 100;

    public const SCOPE_OBSERVATION        = 'system/Observation.write';
    public const SCOPE_DIAGNOSTIC_REPORT  = 'system/DiagnosticReport.write';
    public const SCOPE_IMMUNIZATION       = 'system/Immunization.write';

    public function __construct(
        private readonly FhirIngestGuard $guard,
        private readonly FhirIngestService $ingest,
        private readonly FhirCapabilityStatement $capability,
    ) {}

    /**
     * GET /api/fhir/R4/metadata — public, per the FHIR spec.
     */
    public function metadata(): JsonResponse
    {
        return FhirOutcome::respond($this->capability->toArray());
    }

    /**
     * POST /api/fhir/R4/Observation
     */
    public function observation(Request $request): JsonResponse
    {
        return $this->write($request, 'Observation', function (array $resource, array $ctx) {
            $observation = $this->ingest->observation(
                $resource,
                $ctx['patient'],
                $ctx['facility_id'],
                $ctx['client_id'],
                $ctx['source_system'],
                $ctx['idempotency_key'],
            );

            return [$observation, $this->renderObservation($observation)];
        });
    }

    /**
     * POST /api/fhir/R4/DiagnosticReport
     */
    public function diagnosticReport(Request $request): JsonResponse
    {
        return $this->write($request, 'DiagnosticReport', function (array $resource, array $ctx) {
            [$report, $observations] = $this->ingest->diagnosticReport(
                $resource,
                $ctx['patient'],
                $ctx['facility_id'],
                $ctx['client_id'],
                $ctx['source_system'],
                $ctx['idempotency_key'],
            );

            return [$report, $this->renderReport($report, $observations)];
        });
    }

    /**
     * POST /api/fhir/R4/Immunization
     */
    public function immunization(Request $request): JsonResponse
    {
        return $this->write($request, 'Immunization', function (array $resource, array $ctx) {
            $immunization = $this->ingest->immunization(
                $resource,
                $ctx['patient'],
                $ctx['facility_id'],
                $ctx['client_id'],
                $ctx['source_system'],
                $ctx['idempotency_key'],
            );

            return [$immunization, $this->renderImmunization($immunization)];
        });
    }

    /**
     * POST /api/fhir/R4 — a transaction Bundle.
     *
     * All-or-nothing, and it really is: every entry is applied inside ONE
     * database transaction, and any refusal throws, which unwinds the lot. A
     * partially applied lab report is a worse record than a missing one,
     * because nothing downstream can tell which half arrived.
     *
     * Each entry is independently consent-checked and patient-resolved. A
     * Bundle is not a way to write about a second patient under one consent
     * check — every entry names its own subject and every subject is gated.
     *
     * Reads, updates, deletes and conditional requests inside a Bundle are
     * refused: this server has one job here and it is not a generic RESTful
     * proxy.
     */
    public function transaction(Request $request): JsonResponse
    {
        return $this->guarded($request, 'Bundle', function (array $bundle, string $facilityId, string $clientId, ?string $key) use ($request) {
            if (($bundle['resourceType'] ?? null) !== 'Bundle' || ($bundle['type'] ?? null) !== 'transaction') {
                throw FhirIngestRefusal::make(
                    FhirOutcome::structure('This endpoint accepts a Bundle of type "transaction".', 'type'),
                    'bundle_type_invalid',
                    'not a transaction Bundle'
                );
            }

            $entries = (array) ($bundle['entry'] ?? []);

            if ($entries === []) {
                throw FhirIngestRefusal::make(
                    FhirOutcome::businessRule('A transaction Bundle must carry at least one entry.', 'entry'),
                    'bundle_empty',
                    'empty Bundle'
                );
            }

            if (count($entries) > self::MAX_BUNDLE_ENTRIES) {
                throw FhirIngestRefusal::make(
                    FhirOutcome::businessRule(
                        'A transaction Bundle is limited to ' . self::MAX_BUNDLE_ENTRIES . ' entries. '
                        . 'Split a larger load into several transactions.',
                        'entry'
                    ),
                    'bundle_too_large',
                    'bundle over entry limit'
                );
            }

            $sourceSystem = $this->sourceSystem($clientId);

            $responses = DB::transaction(function () use ($entries, $request, $facilityId, $clientId, $sourceSystem, $key) {
                $out = [];

                foreach ($entries as $i => $entry) {
                    $method   = strtoupper((string) ($entry['request']['method'] ?? ''));
                    $resource = $entry['resource'] ?? null;

                    if ($method !== 'POST') {
                        throw FhirIngestRefusal::make(
                            FhirOutcome::businessRule(
                                'Only POST entries are accepted in a transaction Bundle. This server '
                                . 'performs no reads, updates or deletes through a Bundle.',
                                'entry[' . $i . '].request.method'
                            ),
                            'bundle_method_refused',
                            'bundle entry method ' . $method
                        );
                    }

                    if (isset($entry['request']['ifNoneExist'])) {
                        throw FhirIngestRefusal::make(
                            FhirOutcome::businessRule(
                                'Conditional create (ifNoneExist) is not supported. Idempotency on this '
                                . 'API is the Idempotency-Key header.',
                                'entry[' . $i . '].request.ifNoneExist'
                            ),
                            'bundle_conditional_refused',
                            'conditional create in bundle'
                        );
                    }

                    if (! is_array($resource)) {
                        throw FhirIngestRefusal::make(
                            FhirOutcome::structure('entry.resource is required.', 'entry[' . $i . '].resource'),
                            'bundle_entry_malformed',
                            'entry without a resource'
                        );
                    }

                    $type = (string) ($resource['resourceType'] ?? '');

                    // Every entry: its own scope, its own patient, its own consent.
                    $scope = match ($type) {
                        'Observation'       => self::SCOPE_OBSERVATION,
                        'DiagnosticReport'  => self::SCOPE_DIAGNOSTIC_REPORT,
                        'Immunization'      => self::SCOPE_IMMUNIZATION,
                        default             => null,
                    };

                    if ($scope === null) {
                        throw FhirIngestRefusal::make(
                            FhirOutcome::businessRule(
                                'A transaction Bundle may carry only Observation, DiagnosticReport or '
                                . 'Immunization resources. Patient resources are never accepted: this '
                                . 'server does not create patient records.',
                                'entry[' . $i . '].resource.resourceType'
                            ),
                            'bundle_resource_type_refused',
                            'unsupported bundle resourceType: ' . $type
                        );
                    }

                    $this->guard->requireScope($request, $scope);

                    $patient = $this->guard->resolveSubject($resource);
                    $this->guard->requireConsent($patient, $facilityId);

                    $created = match ($type) {
                        'Observation' => $this->renderObservation(
                            $this->ingest->observation($resource, $patient, $facilityId, $clientId, $sourceSystem, $key)
                        ),
                        'DiagnosticReport' => (function () use ($resource, $patient, $facilityId, $clientId, $sourceSystem, $key) {
                            [$report, $observations] = $this->ingest->diagnosticReport(
                                $resource, $patient, $facilityId, $clientId, $sourceSystem, $key
                            );

                            return $this->renderReport($report, $observations);
                        })(),
                        'Immunization' => $this->renderImmunization(
                            $this->ingest->immunization($resource, $patient, $facilityId, $clientId, $sourceSystem, $key)
                        ),
                    };

                    $this->guard->recordAccepted($request, $type, $patient, (string) $created['id'], $key);

                    $out[] = [
                        'response' => [
                            'status'   => '201 Created',
                            'location' => $type . '/' . $created['id'],
                        ],
                        'resource' => $created,
                    ];
                }

                return $out;
            });

            return FhirOutcome::respond([
                'resourceType' => 'Bundle',
                'type'         => 'transaction-response',
                'entry'        => $responses,
            ], 200);
        });
    }

    // =====================================================================
    // The shared write pipeline
    // =====================================================================

    /**
     * Single-resource write: idempotency, then the three gates, then store.
     *
     * @param  \Closure(array,array):array{0:\Illuminate\Database\Eloquent\Model,1:array}  $handler
     */
    private function write(Request $request, string $resourceType, \Closure $handler): JsonResponse
    {
        return $this->guarded($request, $resourceType, function (array $resource, string $facilityId, string $clientId, ?string $key) use ($request, $resourceType, $handler) {
            $patient = $this->guard->resolveSubject($resource);

            // Consent BEFORE anything is written, and before any code
            // validation that could tell a partner what we know about them.
            $this->guard->requireConsent($patient, $facilityId);

            [$model, $rendered] = DB::transaction(fn () => $handler($resource, [
                'patient'         => $patient,
                'facility_id'     => $facilityId,
                'client_id'       => $clientId,
                'source_system'   => $this->sourceSystem($clientId),
                'idempotency_key' => $key,
            ]));

            $this->guard->recordAccepted($request, $resourceType, $patient, (string) $model->getKey(), $key);

            return FhirOutcome::respond($rendered, 201, [
                'Location' => url("/api/fhir/R4/{$resourceType}/{$model->getKey()}"),
            ]);
        });
    }

    /**
     * Everything common to every write: the token's facility, the required
     * Idempotency-Key, single-flight locking, replay, refusal rendering and
     * the audit line.
     *
     * @param  \Closure(array,string,string,?string):JsonResponse  $inner
     */
    private function guarded(Request $request, string $resourceType, \Closure $inner): JsonResponse
    {
        $clientId = $this->guard->clientId($request);
        $key      = trim((string) $request->header('Idempotency-Key', ''));
        $resource = $request->json()->all();
        $submitted = is_array($resource)
            ? (string) ($resource['subject']['identifier']['value'] ?? '')
            : '';

        try {
            if ($key === '' || mb_strlen($key) > self::MAX_IDEMPOTENCY_KEY) {
                throw FhirIngestRefusal::make(
                    FhirOutcome::idempotencyRequired(),
                    'idempotency_key_required',
                    'missing or oversized Idempotency-Key'
                );
            }

            if (! is_array($resource) || $resource === []) {
                throw FhirIngestRefusal::make(
                    FhirOutcome::structure('A FHIR JSON resource body is required.'),
                    'body_missing',
                    'empty or non-JSON body'
                );
            }

            // The facility is the token's, and only the token's. Anything the
            // body says about a facility or an Organization is ignored.
            $facilityId = $this->guard->facilityId($request);

            $hash = hash('sha256', json_encode($resource));

            // Single-flight: two identical retries arriving together must not
            // both slip past the replay check and write two clinical rows.
            $lock = Cache::lock("fhir_ingest:{$clientId}:{$key}", 15);

            try {
                $lock->block(5);

                $replay = $this->replay($clientId, $key, $hash);

                if ($replay !== null) {
                    return $replay;
                }

                $response = $inner($resource, $facilityId, $clientId, $key);

                $this->remember($clientId, $key, $hash, $response);

                return $response;
            } catch (LockTimeoutException) {
                throw FhirIngestRefusal::make(
                    FhirOutcome::idempotencyConflict(),
                    'idempotency_in_flight',
                    'a request with this key is already being processed'
                );
            } finally {
                optional($lock ?? null)->release();
            }
        } catch (FhirIngestRefusal $refusal) {
            $this->guard->recordRefusal(
                $request,
                $resourceType,
                $refusal,
                null,
                $submitted !== '' ? $submitted : null,
                $key !== '' ? $key : null,
            );

            Log::warning('fhir_ingest_refused', [
                'resource_type' => $resourceType,
                'client_id'     => $clientId,
                'refusal_code'  => $refusal->refusalCode,
                'http_status'   => $refusal->status(),
            ]);

            return $refusal->response;
        }
    }

    /**
     * A repeated Idempotency-Key returns the original response and writes
     * nothing. A repeated key with a DIFFERENT body is a 409: two different
     * clinical events sharing one key is a bug at the partner, and guessing
     * which one they meant is not this server's call.
     *
     * @throws FhirIngestRefusal
     */
    private function replay(string $clientId, string $key, string $hash): ?JsonResponse
    {
        try {
            $record = IdempotencyRecord::query()
                ->where('idempotency_key', $key)
                ->where('client_id', $clientId)
                ->first();
        } catch (\Throwable $e) {
            // Unlike a stock sync, a clinical write must NOT proceed when the
            // idempotency store is unreadable: proceeding risks a duplicate
            // clinical event, which is the one outcome this header exists to
            // prevent. Fail closed and let the partner retry.
            Log::error('fhir_idempotency_lookup_failed', ['exception' => $e->getMessage()]);

            throw FhirIngestRefusal::make(
                FhirOutcome::error(
                    503,
                    'transient',
                    'The idempotency store is temporarily unavailable. The write was not attempted; '
                    . 'retry with the same Idempotency-Key.'
                ),
                'idempotency_store_unavailable',
                'idempotency lookup failed'
            );
        }

        if ($record === null) {
            return null;
        }

        if ($record->request_hash !== $hash) {
            throw FhirIngestRefusal::make(
                FhirOutcome::idempotencyConflict(),
                'idempotency_conflict',
                'key reused with a different body'
            );
        }

        return FhirOutcome::respond(
            (array) $record->response_body,
            (int) $record->response_status,
            ['X-Cache-Idempotency' => 'HIT'],
        );
    }

    /** Store a successful response so the next retry replays it. */
    private function remember(string $clientId, string $key, string $hash, JsonResponse $response): void
    {
        if (! in_array($response->status(), [200, 201], true)) {
            return;
        }

        try {
            IdempotencyRecord::create([
                'idempotency_key' => $key,
                'client_id'       => $clientId,
                'request_hash'    => $hash,
                'response_status' => $response->status(),
                'response_body'   => json_decode($response->getContent(), true) ?? [],
                'expires_at'      => now()->addHours(self::IDEMPOTENCY_TTL_HOURS),
            ]);
        } catch (\Throwable $e) {
            Log::error('fhir_idempotency_store_failed', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * The human name of the system that pushed this, for the provenance line
     * a clinician eventually reads. Falls back to the client id rather than
     * to anything invented.
     */
    private function sourceSystem(string $clientId): string
    {
        $name = IntegrationClient::query()
            ->where('client_id', $clientId)
            ->value('name');

        return (string) ($name ?: $clientId);
    }

    // =====================================================================
    // Rendering the stored resource back
    // =====================================================================

    /**
     * Every echoed resource carries meta.security = partner-asserted and a
     * meta.source naming the system that sent it.
     *
     * This is not decoration. It is the same statement the storage layer
     * makes structurally, repeated on the wire so that a partner (or a
     * downstream system reading a partner's copy) cannot present it back to a
     * clinician as something this facility measured.
     */
    private function meta(string $sourceSystem, \DateTimeInterface $storedAt): array
    {
        return [
            'lastUpdated' => $storedAt->format(\DateTimeInterface::ATOM),
            'source'      => 'urn:opescare:partner:' . $sourceSystem,
            'security'    => [[
                'system'  => 'https://opescare.com/fhir/CodeSystem/provenance',
                'code'    => 'partner_reported',
                'display' => 'Asserted by an external partner system; not measured at this facility.',
            ]],
        ];
    }

    private function renderObservation(PartnerObservation $observation): array
    {
        return [
            'resourceType' => 'Observation',
            'id'           => $observation->id,
            'meta'         => $this->meta($observation->source_system, $observation->created_at),
            'status'       => $observation->status,
            'category'     => [[
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                    'code'   => $observation->observation_kind,
                ]],
            ]],
            'code' => [
                'coding' => [[
                    'system'  => 'http://loinc.org',
                    'code'    => $observation->loinc_code,
                    'display' => $observation->loinc_display,
                ]],
            ],
            'effectiveDateTime' => $observation->effective_at?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function renderReport(PartnerDiagnosticReport $report, array $observations): array
    {
        return [
            'resourceType' => 'DiagnosticReport',
            'id'           => $report->id,
            'meta'         => $this->meta($report->source_system, $report->created_at),
            'status'       => $report->status,
            'code'         => [
                'coding' => [[
                    'system'  => 'http://loinc.org',
                    'code'    => $report->loinc_code,
                    'display' => $report->loinc_display,
                ]],
            ],
            'effectiveDateTime' => $report->effective_at?->format(\DateTimeInterface::ATOM),
            'result'            => array_map(
                fn (PartnerObservation $o) => ['reference' => 'Observation/' . $o->id],
                $observations
            ),
        ];
    }

    private function renderImmunization(PartnerImmunization $immunization): array
    {
        return [
            'resourceType' => 'Immunization',
            'id'           => $immunization->id,
            'meta'         => $this->meta($immunization->source_system, $immunization->created_at),
            'status'       => $immunization->status,
            'vaccineCode'  => [
                'coding' => [[
                    'system'  => 'http://hl7.org/fhir/sid/cvx',
                    'code'    => $immunization->cvx_code,
                    'display' => $immunization->vaccine_display,
                ]],
            ],
            'occurrenceDateTime' => $immunization->occurrence_at?->format(\DateTimeInterface::ATOM),
        ];
    }
}
