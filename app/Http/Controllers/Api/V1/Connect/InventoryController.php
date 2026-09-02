<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Http\Controllers\Controller;
use App\Enums\BloodGroup;
use App\Enums\OpesCareErrorCode;
use App\Enums\PharmacyStockStatus;
use App\Events\AuditEventCreated;
use App\Models\IdempotencyRecord;
use App\Modules\CareMap\Services\BloodAvailabilityProjector;
use App\Modules\Connect\Services\PartnerStockIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * InventoryController
 *
 * Handles pharmacy and blood-bank stock synchronisation from external HIS.
 *
 * SECURITY:
 *  - facility_id MUST come from $request->attributes->get('facility_id') only
 *    (set by VerifyBearerToken middleware). An integration client may only
 *    sync stock for the facility bound to its bearer token.
 *  - facility_reference in the request body is informational (the HIS-side
 *    reference number) and is NEVER used for scoping or authorisation.
 *
 * [H-1 FIX] Removed any path where facility_reference from the request body
 *   could substitute for or override the middleware-resolved facility_id.
 *   The authenticated facility_id is now the only authorisation scope used.
 *
 * [M-1 FIX] Expired/unsafe items are now collected and reported in bulk
 *   rather than short-circuiting on the first offending item. The caller
 *   receives a complete list of which items were rejected and why.
 *
 * [PERSISTENCE FIX] Both endpoints used to validate, audit and answer
 *   `{"status":"synced"}` without a single model write. Partners integrating
 *   against the published Connect API were told their stock was stored and it
 *   was not — the largest single reason the Medicine Finder and Blood Finder
 *   carry no real coverage. Persistence now runs through
 *   PartnerStockIngestService, which reuses the portal's write semantics and
 *   the blood projector rather than inventing a parallel path, and the response
 *   states exactly how many items were created, updated and rejected — with a
 *   reason on every rejection.
 */
class InventoryController extends Controller
{
    public function __construct(
        private readonly PartnerStockIngestService $ingest,
    ) {
    }

    public function syncPharmacyStock(Request $request)
    {
        // [H-1 FIX] facility_id from middleware only — never from body
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'FACILITY_UNRESOLVABLE',
                'message'    => __('api.bearer_no_facility_scope'),
            ], 403);
        }

        $validated = $request->validate([
            'facility_reference' => ['required', 'string', 'max:100'],
            'items'              => ['required', 'array', 'min:1'],
            // Resolved against the `medicines` catalogue: either a catalogue
            // UUID or a WHO ATC code. Unresolvable codes are reported back as
            // rejected, never dropped.
            'items.*.drug_code'  => ['required', 'string'],
            'items.*.quantity'   => ['required', 'integer', 'min:0'],
            'items.*.expiry_date'=> ['nullable', 'date'],
            // Optional, additive. A partner that keeps its own availability
            // state may say so; otherwise quantity decides. Optional price and
            // pack size feed the same finder fields the portal writes.
            'items.*.stock_status' => ['nullable', 'string', Rule::in(PharmacyStockStatus::values())],
            'items.*.pack_size'    => ['nullable', 'string', 'max:100'],
            'items.*.unit_price'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $clientId      = $request->attributes->get('integration_client_id', 'unknown_client');
        $correlationId = $request->header('X-Correlation-Id') ?? ('req_' . bin2hex(random_bytes(8)));

        if ($replay = $this->idempotentReplay($request, $clientId, $correlationId)) {
            return $replay;
        }

        // [M-1 FIX] Collect ALL expired items before rejecting — give caller full picture
        $expiredItems = [];
        foreach ($validated['items'] as $index => $item) {
            if (
                isset($item['expiry_date']) &&
                strtotime($item['expiry_date']) < time()
            ) {
                $expiredItems[] = [
                    'index'       => $index,
                    'drug_code'   => $item['drug_code'],
                    'expiry_date' => $item['expiry_date'],
                ];
            }
        }

        // Unchanged and deliberately all-or-nothing: a batch carrying expired
        // stock is not partially trustworthy. Nothing is written.
        if (! empty($expiredItems)) {
            return response()->json([
                'status'         => 'rejected',
                'error_code'     => OpesCareErrorCode::UNSAFE_STOCK_STATUS->value,
                'message'        => __('api.expired_stock_sync_blocked'),
                'expired_items'  => $expiredItems,
                'correlation_id' => $correlationId,
            ], 422);
        }

        $result = $this->ingest->ingestPharmacyStock($facilityId, $clientId, $validated['items']);
        $stored = $result['created'] + $result['updated'];

        event(new AuditEventCreated(
            'pharmacy_stock_synced',
            $clientId,
            $facilityId,          // real facility, not a magic UUID
            'system_sync',
            $correlationId,
            [
                'items_count'        => count($validated['items']),
                'created_rows'       => $result['created'],
                'updated_rows'       => $result['updated'],
                'rejected_rows'      => count($result['rejected']),
                'source_system'      => $result['source_system'],
                'facility_reference' => $validated['facility_reference'],
            ]
        ));

        $payload = [
            'status'             => $stored > 0 ? 'synced' : 'rejected',
            'facility_id'        => $facilityId,
            'facility_reference' => $validated['facility_reference'],
            // The provenance stamped on every row written here. The public
            // finders withhold seeded and unattributed stock, so this value is
            // what makes a partner's report reach a patient at all.
            'source_system'      => $result['source_system'],
            'submitted_count'    => count($validated['items']),
            'accepted_count'     => $result['created'],
            'updated_count'      => $result['updated'],
            'rejected_count'     => count($result['rejected']),
            // Kept for callers already reading it — now the number of items
            // that genuinely reached the database rather than the batch size.
            'synced_items_count' => $stored,
            'rejected_items'     => $result['rejected'],
            // Stored correctly but still not visible to patients (unlinked,
            // inactive or un-geocoded listing). Not a rejection.
            'warnings'           => $result['warnings'],
            'timestamp'          => time(),
            'correlation_id'     => $correlationId,
        ];

        if ($stored === 0) {
            // Answering 200/"synced" when nothing was written is precisely the
            // defect this endpoint used to have.
            $payload['error_code'] = OpesCareErrorCode::VALIDATION_FAILED->value;
            $payload['message']    = __('api.partner_stock_nothing_stored');

            return response()->json($payload, 422);
        }

        return $this->rememberIdempotent($request, $clientId, response()->json($payload, 200));
    }

    public function syncBloodStock(Request $request)
    {
        // [H-1 FIX] facility_id from middleware only
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'FACILITY_UNRESOLVABLE',
                'message'    => __('api.bearer_no_facility_scope'),
            ], 403);
        }

        // A blood inventory row is keyed on (facility_id, blood_group, component),
        // so the payload has to carry the group. It previously did not, which is
        // why this endpoint could validate, audit, answer "synced" and still
        // store nothing — there was no way to express what to store.
        //
        // blood_group is a new required field. Safe to add: blood-stock/sync
        // appears only in the generated public/openapi.json, never in the
        // hand-authored contracts/openapi/opescare-connect-v1.yaml, and no SDK
        // references it. Overloading component_code to encode the group was the
        // alternative and would have been a hack.
        $validated = $request->validate([
            'facility_reference'        => ['required', 'string', 'max:100'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.blood_group'       => ['required', 'string', Rule::in(BloodGroup::values())],
            'items.*.component_code'    => ['required', 'string', Rule::in(BloodAvailabilityProjector::operationalComponents())],
            'items.*.units'             => ['required', 'integer', 'min:0'],
            'items.*.screening_status'  => ['required', 'string'],
        ]);

        $clientId      = $request->attributes->get('integration_client_id', 'unknown_client');
        $correlationId = $request->header('X-Correlation-Id') ?? ('req_' . bin2hex(random_bytes(8)));

        if ($replay = $this->idempotentReplay($request, $clientId, $correlationId)) {
            return $replay;
        }

        // [M-1 FIX] Collect ALL unsafe components before rejecting
        $unsafeItems = [];
        foreach ($validated['items'] as $index => $item) {
            if ($item['screening_status'] !== 'screened_safe') {
                $unsafeItems[] = [
                    'index'            => $index,
                    'component_code'   => $item['component_code'],
                    'screening_status' => $item['screening_status'],
                ];
            }
        }

        if (! empty($unsafeItems)) {
            return response()->json([
                'status'         => 'rejected',
                'error_code'     => OpesCareErrorCode::UNSAFE_BLOOD_STATUS->value,
                'message'        => __('api.unsafe_blood_sync_forbidden'),
                'unsafe_items'   => $unsafeItems,
                'correlation_id' => $correlationId,
            ], 422);
        }

        // Persist through the service, not a raw write: upsertUnit() re-publishes
        // the patient-facing blood_availability row via BloodAvailabilityProjector,
        // so the Blood Finder stays in step with what the bank actually holds.
        // Every item here passed the screening gate above, so none is unsafe.
        $result = $this->ingest->ingestBloodStock($facilityId, $clientId, $validated['items']);
        $stored = $result['created'] + $result['updated'];

        event(new AuditEventCreated(
            'blood_stock_synced',
            $clientId,
            $facilityId,          // real facility
            'system_sync',
            $correlationId,
            [
                'components_count'   => count($validated['items']),
                'stored_rows'        => $stored,
                'created_rows'       => $result['created'],
                'updated_rows'       => $result['updated'],
                'source_system'      => $result['source_system'],
                'facility_reference' => $validated['facility_reference'],
            ]
        ));

        return $this->rememberIdempotent($request, $clientId, response()->json([
            'status'                  => 'synced',
            'facility_id'             => $facilityId,
            'facility_reference'      => $validated['facility_reference'],
            'source_system'           => $result['source_system'],
            'submitted_count'         => count($validated['items']),
            'accepted_count'          => $result['created'],
            'updated_count'           => $result['updated'],
            'rejected_count'          => count($result['rejected']),
            'synced_components_count' => count($validated['items']),
            'stored_rows'             => $stored,
            'rejected_items'          => $result['rejected'],
            'warnings'                => $result['warnings'],
            'timestamp'               => time(),
            'correlation_id'          => $correlationId,
        ], 200));
    }

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    |
    | Partners retry. Two things make a retry safe here:
    |
    |  1. The writes themselves are natural-key upserts —
    |     (medicine_id, care_facility_id) for pharmacy stock and
    |     (facility_id, blood_group, component) for blood — each taken under a
    |     row lock. A repeat sync updates in place and can never duplicate,
    |     with or without a header.
    |  2. When the caller sends `Idempotency-Key`, the stored response is
    |     replayed verbatim, exactly as App\Http\Middleware\IdempotencyProtection
    |     does for the /records/* writes: same `idempotency_records` table, same
    |     SHA-256 body hash, same 409 when a key is reused with a different
    |     payload, same `X-Cache-Idempotency: HIT` marker.
    |
    | It is applied here rather than by that middleware because these two routes
    | sit outside its group in routes/api.php, which is a SEALED file. Honouring
    | the header in the controller keeps the convention without editing the seal
    | — and without making the header mandatory, which would turn every existing
    | partner's call into a 400 the moment the endpoint started working.
    */

    /** Replay a stored response for a repeated Idempotency-Key, if there is one. */
    private function idempotentReplay(Request $request, string $clientId, string $correlationId): ?JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        if (! $key) {
            return null;
        }

        try {
            $record = IdempotencyRecord::where('idempotency_key', $key)
                ->where('client_id', $clientId)
                ->first();
        } catch (\Throwable $e) {
            // A failing idempotency store must not block a stock sync: the
            // upsert is safe to repeat regardless.
            Log::error('idempotency_lookup_failed', [
                'key'       => $key,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $record) {
            return null;
        }

        if ($record->request_hash !== $this->hashPayload($request)) {
            return response()->json([
                'status'         => 'rejected',
                'error_code'     => OpesCareErrorCode::IDEMPOTENCY_CONFLICT->value,
                'message'        => 'Idempotency conflict. A request with this key was already submitted with a different body payload.',
                'correlation_id' => $correlationId,
            ], 409);
        }

        $response = response()->json($record->response_body, $record->response_status);
        $response->headers->set('X-Cache-Idempotency', 'HIT');

        return $response;
    }

    /** Store a successful response against its Idempotency-Key, if one was sent. */
    private function rememberIdempotent(Request $request, string $clientId, JsonResponse $response): JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        if (! $key || $response->status() !== 200) {
            return $response;
        }

        try {
            IdempotencyRecord::create([
                'idempotency_key' => $key,
                'client_id'       => $clientId,
                'request_hash'    => $this->hashPayload($request),
                'response_status' => $response->status(),
                'response_body'   => json_decode($response->getContent(), true) ?? [],
                'expires_at'      => now()->addHours(24),
            ]);
        } catch (\Throwable $e) {
            Log::error('idempotency_key_store_failed', [
                'key'       => $key,
                'exception' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    private function hashPayload(Request $request): string
    {
        return hash('sha256', (string) json_encode($request->all()));
    }
}
