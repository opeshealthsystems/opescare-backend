<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAccessEvent;
use App\Models\Patient;
use App\Modules\Governance\Services\EmergencyAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mobile Provider API — BREAK-GLASS.
 *
 * The one door in this platform that opens without the patient's permission,
 * and the only reason it exists: an unconscious arrival at a facility that has
 * never seen them before. Everywhere else a clinician needs a relationship
 * (MobileDoctorChartController resolves five of them and refuses when none
 * holds). This is what a clinician does INSTEAD of walking away from that 403
 * — and it is deliberately not the same act, not the same audit signature and
 * not the same amount of record.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT IT OPENS — AND WHAT IT DELIBERATELY DOES NOT
 * ─────────────────────────────────────────────────────────────────────────
 *
 * The EMERGENCY PROFILE and nothing else: who this is, who to call, active
 * allergies, blood group, chronic conditions. No clinical notes, no encounter
 * history, no prescription history, no results, no billing.
 *
 * That boundary IS the guardrail. A break-glass that opened the whole chart
 * would be a second, wider front door with a text box in front of it, and the
 * refusal on the ordinary chart would stop meaning anything. What a clinician
 * needs to keep an unconscious person alive is a short list; that short list
 * is what this hands over.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * TIME-BOXED, NOT PERMANENT
 * ─────────────────────────────────────────────────────────────────────────
 *
 * A grant lives EmergencyAccessService::GRANT_TTL_HOURS (4h) from the moment
 * the glass broke, on an absolute clock — reading under it does not extend it.
 * When it lapses this controller answers 403 again, exactly like the chart
 * does, and the clinician must state a new reason to reopen. The window is
 * sized to the emergency episode, not to the shift; the reasoning is written
 * out on the constant itself.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * A REASON IS MANDATORY, FREE TEXT, AND KEPT
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `reason` is required, at least MIN_REASON_LENGTH characters of prose, and
 * stored verbatim on the event and quoted to the patient in their alert. There
 * is no dropdown: a picklist turns an account of what happened into a click,
 * and the account is the only part of this record a reviewer cannot
 * reconstruct from the data. `acknowledged` must also be true — the client
 * cannot open this door without having shown the clinician what it costs.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHERE THE ACTOR COMES FROM
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `provider_user_id` and `provider_facility_id` come from
 * $request->attributes, stamped by AuthenticateMobileProvider from the bearer
 * token. There is no parameter in this file — body, query, header, session or
 * route — that can name either one, and a posted `facility_id` is simply never
 * read. A clinician who could name their own facility could break glass "at"
 * any facility in the country and put the override on someone else's
 * governance queue.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * NOTHING HERE IS QUIET
 * ─────────────────────────────────────────────────────────────────────────
 *
 *   • the patient is notified synchronously, before the caller is answered;
 *   • every read writes an `access_logs` row with `emergency_access = true`,
 *     so break-glass reads separate from routine ones with one WHERE clause;
 *   • an EmergencyReviewCase is spawned for every grant (escalated on a
 *     repeat), so a human looks at it afterwards;
 *   • repeats and throttle hits raise SecurityIncidents.
 *
 * If the audit cannot be written, the disclosure does not happen (503) — the
 * same rule the chart holds, and doubly so here.
 */
class MobileEmergencyAccessController extends Controller
{
    /** The single data category this door opens. */
    private const DATA_CATEGORY = 'emergency_profile';

    public function __construct(private readonly EmergencyAccessService $emergency)
    {
    }

    // ══════════════════════════════════════════════════════════════════════
    // Endpoints
    // ══════════════════════════════════════════════════════════════════════

    /**
     * POST /api/mobile/doctor/emergency-access
     *
     * Break the glass. Body: { patient, reason, acknowledged }.
     * `patient` is an EXACT patients.id or an EXACT Health ID — the same two
     * forms the chart takes, matched by equality, never searched.
     */
    public function open(Request $request): JsonResponse
    {
        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $clinicianId || ! $facilityId) {
            return response()->json([
                'message'    => __('emergency_access.actor_unresolved'),
                'error_code' => 'EMERGENCY_ACTOR_UNRESOLVED',
            ], 403);
        }

        // Trim before validating so a reason of twenty spaces is not a reason.
        $request->merge([
            'reason' => is_string($request->input('reason')) ? trim($request->input('reason')) : $request->input('reason'),
        ]);

        $validated = $request->validate([
            'patient'      => 'required|string|max:64',
            'reason'       => 'required|string|min:' . EmergencyAccessService::MIN_REASON_LENGTH
                . '|max:' . EmergencyAccessService::MAX_REASON_LENGTH,
            'acknowledged' => 'required|accepted',
        ], [
            'reason.required'       => __('emergency_access.reason_required'),
            'reason.min'            => __('emergency_access.reason_too_short', ['min' => EmergencyAccessService::MIN_REASON_LENGTH]),
            'acknowledged.required' => __('emergency_access.acknowledgement_required'),
            'acknowledged.accepted' => __('emergency_access.acknowledgement_required'),
            'patient.required'      => __('emergency_access.patient_required'),
        ]);

        $patient = $this->resolvePatient($validated['patient']);

        if (! $patient) {
            return response()->json([
                'message'    => __('emergency_access.patient_not_found'),
                'error_code' => 'PATIENT_NOT_FOUND',
            ], 404);
        }

        try {
            // One transaction: the event, its review case and its audit row
            // land together or not at all. A grant with no audit trail is the
            // exact thing this feature exists to make impossible.
            $result = DB::transaction(fn () => $this->emergency->openBreakGlass(
                $patient,
                $facilityId,
                $clinicianId,
                $validated['reason'],
                $request->ip()
            ));
        } catch (\Throwable $e) {
            Log::error('Break-glass could not be recorded', [
                'patient_id'  => $patient->id,
                'clinician_id' => $clinicianId,
                'error'       => $e->getMessage(),
            ]);

            return response()->json([
                'message'    => __('emergency_access.audit_unavailable'),
                'error_code' => 'EMERGENCY_AUDIT_UNAVAILABLE',
            ], 503);
        }

        if ($result['status'] === EmergencyAccessService::STATUS_WITHHELD) {
            return response()->json([
                'message'    => __('emergency_access.withheld_by_patient'),
                'error_code' => 'EMERGENCY_WITHHELD_BY_PATIENT',
            ], 403);
        }

        if ($result['status'] === EmergencyAccessService::STATUS_REVOCATION_LOCKED) {
            return response()->json([
                'message'     => __('emergency_access.revoked_by_patient'),
                'error_code'  => 'EMERGENCY_REVOKED_BY_PATIENT',
                'retry_after' => $result['retry_after'],
            ], 423)->header('Retry-After', (string) $result['retry_after']);
        }

        if ($result['status'] === EmergencyAccessService::STATUS_RATE_LIMITED) {
            return response()->json([
                'message'     => __('emergency_access.rate_limited'),
                'error_code'  => 'EMERGENCY_RATE_LIMITED',
                'retry_after' => $result['retry_after'],
            ], 429)->header('Retry-After', (string) $result['retry_after']);
        }

        /** @var EmergencyAccessEvent $event */
        $event = $result['event'];

        // The first read under the grant is a read like any other: its own
        // access_logs row, flagged emergency.
        $profile = $this->readProfileOrFail($event, $clinicianId);

        if ($profile === null) {
            return response()->json([
                'message'    => __('emergency_access.audit_unavailable'),
                'error_code' => 'EMERGENCY_AUDIT_UNAVAILABLE',
            ], 503);
        }

        return response()->json([
            'data' => [
                'grant'   => $this->grantPayload($event, $patient),
                'profile' => $profile,
            ],
            'meta' => [
                'status'           => $result['status'],
                'repeat'           => $result['repeat'],
                'patient_notified' => $result['notified'],
                'prior_grants_24h' => $result['prior_count'],
            ],
        ], $result['status'] === EmergencyAccessService::STATUS_GRANTED ? 201 : 200);
    }

    /**
     * GET /api/mobile/doctor/emergency-access
     *
     * The grants THIS clinician is currently holding at THIS facility, so the
     * app can show a live countdown rather than discovering the expiry as a
     * 403 mid-resuscitation. Never anyone else's grants.
     */
    public function active(Request $request): JsonResponse
    {
        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $clinicianId || ! $facilityId) {
            return response()->json([
                'message'    => __('emergency_access.actor_unresolved'),
                'error_code' => 'EMERGENCY_ACTOR_UNRESOLVED',
            ], 403);
        }

        $events = EmergencyAccessEvent::query()
            ->where('provider_id', $clinicianId)
            ->where('facility_id', $facilityId)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $events->map(fn (EmergencyAccessEvent $e) => $this->grantPayload($e))->values(),
            'meta' => [
                'ttl_hours' => EmergencyAccessService::GRANT_TTL_HOURS,
            ],
        ], 200);
    }

    /**
     * GET /api/mobile/doctor/emergency-access/{grant}
     *
     * The standing of one grant this clinician opened. Metadata only — no
     * clinical content, so an expired grant can still be explained without
     * disclosing anything.
     */
    public function show(Request $request, string $grant): JsonResponse
    {
        $event = $this->ownGrant($request, $grant);

        if ($event instanceof JsonResponse) {
            return $event;
        }

        return response()->json(['data' => $this->grantPayload($event)], 200);
    }

    /**
     * GET /api/mobile/doctor/emergency-access/{grant}/profile
     *
     * Re-read the emergency profile while the grant is live. Separate from
     * `open` so glancing at the allergy list twice does not create a second
     * override event — but each read is still its own audited disclosure.
     *
     * Once the window closes this answers 403, which is the whole point: the
     * door shuts by itself.
     */
    public function profile(Request $request, string $grant): JsonResponse
    {
        $event = $this->ownGrant($request, $grant);

        if ($event instanceof JsonResponse) {
            return $event;
        }

        if (! $this->emergency->grantIsActive($event)) {
            return response()->json([
                'message'    => $event->revoked_at
                    ? __('emergency_access.grant_revoked')
                    : __('emergency_access.grant_expired'),
                'error_code' => $event->revoked_at ? 'EMERGENCY_GRANT_REVOKED' : 'EMERGENCY_GRANT_EXPIRED',
                'grant'      => $this->grantPayload($event),
            ], 403);
        }

        $profile = $this->readProfileOrFail($event, (string) $this->clinicianId($request));

        if ($profile === null) {
            return response()->json([
                'message'    => __('emergency_access.audit_unavailable'),
                'error_code' => 'EMERGENCY_AUDIT_UNAVAILABLE',
            ], 503);
        }

        return response()->json([
            'data' => [
                'grant'   => $this->grantPayload($event),
                'profile' => $profile,
            ],
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════════════

    /** The clinician — from the token, via the middleware. Nowhere else. */
    private function clinicianId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_user_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * The facility — from the token, via the middleware. Nowhere else.
     *
     * Deliberately does NOT fall back to the body, a header, the session or a
     * configured default. No facility on the token is a refusal, not a guess.
     */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * ONE patient by EXACT id or EXACT Health ID — the same resolver shape the
     * chart uses. No LIKE, no prefix, no name: break-glass must not become the
     * enumeration surface the rest of the platform refuses to be.
     */
    private function resolvePatient(string $id): ?Patient
    {
        $key = trim($id);

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        if (Str::isUuid($key)) {
            return Patient::resolveId($key);
        }

        // Follows a merge: the card in an unconscious patient's pocket may carry
        // a Health ID that was retired last month.
        return Patient::resolveHealthId($key);
    }

    /**
     * A grant addressed by id, belonging to THIS clinician at THIS facility,
     * or a JsonResponse explaining why not.
     *
     * Someone else's grant is a 404, not a 403: "that grant exists but is not
     * yours" is a fact about another clinician's override and there is no
     * reason to confirm it.
     */
    private function ownGrant(Request $request, string $grant): EmergencyAccessEvent|JsonResponse
    {
        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $clinicianId || ! $facilityId) {
            return response()->json([
                'message'    => __('emergency_access.actor_unresolved'),
                'error_code' => 'EMERGENCY_ACTOR_UNRESOLVED',
            ], 403);
        }

        if (! Str::isUuid($grant)) {
            return response()->json([
                'message'    => __('emergency_access.grant_not_found'),
                'error_code' => 'EMERGENCY_GRANT_NOT_FOUND',
            ], 404);
        }

        $event = EmergencyAccessEvent::query()
            ->where('id', $grant)
            ->where('provider_id', $clinicianId)
            ->where('facility_id', $facilityId)
            ->first();

        if (! $event) {
            return response()->json([
                'message'    => __('emergency_access.grant_not_found'),
                'error_code' => 'EMERGENCY_GRANT_NOT_FOUND',
            ], 404);
        }

        return $event;
    }

    /**
     * Audit first, then disclose. Returns null when the trail could not be
     * written — the caller turns that into a 503 and hands over nothing.
     */
    private function readProfileOrFail(EmergencyAccessEvent $event, string $clinicianId): ?array
    {
        try {
            $this->emergency->logEmergencyRead($event, $clinicianId, self::DATA_CATEGORY);
        } catch (\Throwable $e) {
            Log::error('Break-glass read could not be audited', [
                'emergency_access_event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return $this->emergency->buildEmergencyProfileForGrant($event);
    }

    /** The grant, as the app sees it. Metadata only — never clinical content. */
    private function grantPayload(EmergencyAccessEvent $event, ?Patient $patient = null): array
    {
        $patient ??= Patient::find($event->patient_id);
        $active = $this->emergency->grantIsActive($event);

        return [
            'id'                 => $event->id,
            'patient_id'         => $event->patient_id,
            'patient_health_id'  => $patient?->health_id,
            'patient_name'       => $patient
                ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))
                : null,
            // Echoed from the EVENT, which took it from the token. Never from
            // anything the caller sent.
            'facility_id'        => $event->facility_id,
            'reason'             => $event->reason,
            'opened_at'          => $this->iso($event->created_at),
            'expires_at'         => $this->iso($event->expires_at),
            'expires_in_seconds' => $this->emergency->grantSecondsRemaining($event),
            'active'             => $active,
            'state'              => $event->revoked_at ? 'revoked' : ($active ? 'active' : 'expired'),
            'revoked_at'         => $this->iso($event->revoked_at),
            'revoked_by'         => $event->revoked_by_type,
            'is_repeat'          => (bool) $event->is_repeat,
            'patient_notified_at' => $this->iso($event->notified_at),
            'records_viewed'     => $event->records_viewed ?? [],
            'ttl_hours'          => EmergencyAccessService::GRANT_TTL_HOURS,
        ];
    }

    private function iso(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->toIso8601String();
    }
}
