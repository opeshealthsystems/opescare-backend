<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LiteConflict;
use App\Models\LiteDevice;
use App\Modules\OpesCareLite\Services\OpesCareLiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * OpesCare Lite Sync & Device API
 *
 * Provides endpoints for Lite client device registration, config fetch,
 * offline event push/pull, and conflict management.
 *
 * ## Request Authentication (HMAC-SHA256)
 *
 * After registration, every request to authenticated Lite endpoints must include:
 *
 *   X-Lite-Device-Id:  <device_uuid>
 *   X-Lite-Timestamp:  <unix_timestamp>   (UTC, 5-minute replay window)
 *   X-Lite-Signature:  HMAC-SHA256(<device_id>.<timestamp>.<sha256(body)>, device_secret)
 *
 * The device_secret is returned ONCE in the registration response and must be
 * stored securely on the device (e.g. in platform secure storage / Keychain).
 * It is never returned again after the initial registration call.
 *
 * Devices registered before HMAC authentication was introduced (device_secret = null)
 * are allowed through with a warning log until secrets are rotated.
 */
class LiteApiController extends Controller
{
    public function __construct(private readonly OpesCareLiteService $liteService) {}

    /**
     * POST /api/v1/lite/register-device
     *
     * Register a new Lite device for a facility.
     * Returns the device_secret ONCE — store it securely; it is never shown again.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'facility_id'         => 'required|uuid|exists:facilities,id',
            'device_name'         => 'required|string|max:120',
            'device_fingerprint'  => 'required|string|max:128',
            'platform'            => 'nullable|in:web,pwa,tablet,flutter',
            'os_info'             => 'nullable|string|max:200',
            'app_version'         => 'nullable|string|max:40',
            'extra_modules'       => 'nullable|array',
            'extra_modules.*'     => 'string|max:80',
            'offline_allowed'     => 'nullable|boolean',
        ]);

        // Duplicate fingerprint guard — return existing device (without secret)
        if (LiteDevice::where('device_fingerprint', $data['device_fingerprint'])->exists()) {
            $device = LiteDevice::where('device_fingerprint', $data['device_fingerprint'])->first();
            return response()->json([
                'message' => __('api.device_already_registered'),
                'device'  => [
                    'id'     => $device->id,
                    'status' => $device->status,
                ],
            ], 200);
        }

        $actorId = $request->input('authorized_by', 'api');

        $device = $this->liteService->registerDevice(
            facilityId:        $data['facility_id'],
            deviceName:        $data['device_name'],
            deviceFingerprint: $data['device_fingerprint'],
            authorizedBy:      $actorId,
            platform:          $data['platform'] ?? 'web',
            extraModules:      $data['extra_modules'] ?? [],
            offlineAllowed:    (bool) ($data['offline_allowed'] ?? false),
        );

        return response()->json([
            'message' => __('api.device_registered_pending'),
            'device'  => [
                'id'            => $device->id,
                'status'        => $device->status,
                'config'        => $this->liteService->getConfig($device),
                // ONE-TIME secret — must be stored securely; never returned again
                'device_secret' => $device->device_secret,
            ],
        ], 201);
    }

    /**
     * GET /api/v1/lite/config
     *
     * Fetch current config for the authenticated device.
     */
    public function config(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['message' => __('api.device_invalid')], 403);
        }

        $device->touchSeen();

        return response()->json([
            'config' => $this->liteService->getConfig($device),
        ]);
    }

    /**
     * POST /api/v1/lite/sync/push
     *
     * Push offline-captured events to the server for processing.
     */
    public function syncPush(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['message' => __('api.device_invalid')], 403);
        }

        $data = $request->validate([
            'events'               => 'required|array|min:1|max:200',
            'events.*.event_type'  => 'required|string|max:80',
            'events.*.client_id'   => 'required|string|max:64',
            'events.*.payload'     => 'required|array',
            'events.*.captured_at' => 'nullable|date',
        ]);

        $result = $this->liteService->pushOfflineEvents($device, $data['events']);

        return response()->json([
            'message' => __('api.sync_push_completed'),
            'result'  => $result,
        ]);
    }

    /**
     * GET /api/v1/lite/sync/pull
     *
     * Pull pending server updates and open conflicts for the device.
     */
    public function syncPull(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['message' => __('api.device_invalid')], 403);
        }

        $since = $request->query('since'); // ISO8601 timestamp

        return response()->json(
            $this->liteService->pullSync($device, $since)
        );
    }

    /**
     * POST /api/v1/lite/offline-events
     *
     * Submit a single offline event (alternative to batch sync push).
     */
    public function offlineEvent(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json(['message' => __('api.device_invalid')], 403);
        }

        $data = $request->validate([
            'event_type'  => 'required|string|max:80',
            'client_id'   => 'required|string|max:64',
            'payload'     => 'required|array',
            'captured_at' => 'nullable|date',
        ]);

        $result = $this->liteService->pushOfflineEvents($device, [$data]);

        return response()->json([
            'message' => __('api.offline_event_submitted'),
            'result'  => $result,
        ], 201);
    }

    /**
     * PATCH /api/v1/lite/conflicts/{conflict}/resolve
     *
     * Let the Lite client resolve an open conflict by declaring which version wins,
     * or by dismissing it. Only the owning device may resolve its own conflicts.
     *
     * Resolution values:
     *   accept_server  — overwrite device-side record with the server version
     *   accept_device  — server will adopt the device-side version on next push
     *   dismiss        — conflict acknowledged, no data change (used for non-clinical conflicts)
     */
    public function resolveConflict(Request $request, LiteConflict $conflict): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (! $device) {
            return response()->json(['message' => __('api.device_invalid')], 403);
        }

        // A device may only resolve conflicts that belong to it
        if ($conflict->lite_device_id !== $device->id) {
            return response()->json(['message' => __('api.conflict_wrong_device')], 403);
        }

        if (! $conflict->isOpen()) {
            return response()->json(['message' => __('api.conflict_already_resolved')], 409);
        }

        $data = $request->validate([
            'resolution'      => 'required|in:accept_server,accept_device,dismiss',
            'resolution_note' => 'nullable|string|max:500',
        ]);

        $resolved = $this->liteService->resolveConflict(
            conflict:   $conflict,
            resolvedBy: $device->id,
            resolution: $data['resolution'],
            note:       $data['resolution_note'] ?? '',
        );

        return response()->json([
            'message'  => __('api.conflict_resolved'),
            'conflict' => [
                'id'              => $resolved->id,
                'status'          => $resolved->status,
                'resolved_at'     => $resolved->resolved_at?->toIso8601String(),
                'resolution_note' => $resolved->resolution_note,
            ],
        ]);
    }

    /**
     * GET /api/v1/lite/formulary
     *
     * Download the current drug formulary for the authenticated device's facility.
     *
     * Returns only available drugs — intended as the offline prescription cache
     * for Lite devices operating without network access. Clients should refresh
     * this list on every successful pull-sync and store locally (e.g. SQLite).
     *
     * Response is intentionally lightweight: no prescription history, no prices.
     * Controlled substances are flagged via `is_controlled` so the UI can enforce
     * additional confirmation steps before offline prescribing.
     */
    public function formulary(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (! $device) {
            return response()->json(['message' => __('api.device_invalid')], 403);
        }

        $formulary = \App\Models\DrugFormulary::where('facility_id', $device->facility_id)
            ->where('is_available', true)
            ->orderBy('generic_name')
            ->get([
                'id', 'generic_name', 'brand_names', 'drug_code', 'drug_class',
                'form', 'strength', 'unit', 'is_controlled', 'requires_prior_auth',
            ]);

        return response()->json([
            'facility_id'     => $device->facility_id,
            'as_of'           => now()->toIso8601String(),
            'formulary_count' => $formulary->count(),
            'formulary'       => $formulary,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Resolve and authenticate the device making the request.
     *
     * Authentication flow:
     * 1. Read X-Lite-Device-Id (or device_id query/body param).
     * 2. Load device from DB; verify it is active.
     * 3. If device has a device_secret → validate HMAC-SHA256 signature:
     *      message   = device_id . "." . timestamp . "." . sha256(request_body)
     *      signature = HMAC-SHA256(message, device_secret)
     *      Reject if timestamp is > 5 minutes old (replay protection).
     * 4. If device_secret is null (legacy/pre-auth device) → allow but warn.
     *
     * Returns null on any authentication failure.
     */
    private function resolveDevice(Request $request): ?LiteDevice
    {
        $deviceId = $request->header('X-Lite-Device-Id')
            ?? $request->query('device_id')
            ?? $request->input('device_id');

        if (! $deviceId) {
            return null;
        }

        $device = LiteDevice::find($deviceId);

        if (! $device || ! $device->isActive()) {
            return null;
        }

        // ── HMAC-SHA256 signature validation ──────────────────────────────────
        if ($device->device_secret !== null) {
            $timestamp = $request->header('X-Lite-Timestamp');
            $signature = $request->header('X-Lite-Signature');

            if (! $timestamp || ! $signature) {
                Log::warning('Lite device request missing HMAC headers', [
                    'device_id' => $deviceId,
                    'ip'        => $request->ip(),
                ]);
                return null;
            }

            // Replay protection: reject requests older than 5 minutes
            if (abs(time() - (int) $timestamp) > 300) {
                Log::warning('Lite device request timestamp out of window (possible replay)', [
                    'device_id' => $deviceId,
                    'timestamp' => $timestamp,
                    'ip'        => $request->ip(),
                ]);
                return null;
            }

            // Compute expected HMAC:
            //   message = device_id . "." . unix_timestamp . "." . sha256(raw_request_body)
            $bodyHash = hash('sha256', $request->getContent());
            $message  = $deviceId . '.' . $timestamp . '.' . $bodyHash;
            $expected = hash_hmac('sha256', $message, $device->device_secret);

            if (! hash_equals($expected, strtolower($signature))) {
                Log::warning('Lite device HMAC signature mismatch', [
                    'device_id' => $deviceId,
                    'ip'        => $request->ip(),
                ]);
                return null;
            }
        } else {
            // Legacy device without secret — allow but log for rotation tracking
            Log::warning('Lite device authenticated WITHOUT HMAC secret (legacy device — rotate secret)', [
                'device_id' => $deviceId,
                'ip'        => $request->ip(),
            ]);
        }

        return $device;
    }
}
