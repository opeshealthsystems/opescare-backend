<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\EmergencyAccessEvent;
use App\Models\Facility;
use App\Models\User;
use App\Modules\Governance\Services\EmergencyAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Mobile Patient API — the PATIENT's side of break-glass.
 *
 * Break-glass is the one disclosure a patient never agreed to, so it is also
 * the one they are owed the fullest account of. The alert
 * (EmergencyAccessAlertNotification) reaches them the moment the glass breaks
 * and is waiting for them at their next sign-in; this is where they can then
 * see the whole picture and, if it was wrong, shut it.
 *
 *   GET  /api/mobile/emergency-access               every override on THEIR
 *                                                   record — who, where, when,
 *                                                   the reason in the
 *                                                   clinician's own words,
 *                                                   what was opened, how many
 *                                                   times it was read, and
 *                                                   whether it is still live.
 *   POST /api/mobile/emergency-access/{id}/revoke   close a live one now.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * CAN THE PATIENT REVOKE? YES — AND WHY THAT IS SAFE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * The objection writes itself: break-glass exists for an unconscious patient,
 * so letting the patient cancel it seems to hand them a way to stop their own
 * emergency care. It does not, for a simple reason — a patient who can open
 * this app, read the alert and press revoke is by definition conscious, and a
 * conscious patient does not need break-glass. They can grant consent
 * properly, which is the lawful path this one exists to substitute for.
 *
 * Revocation is therefore the correction for the case that actually happens:
 * the override that should never have been opened. It closes the grant
 * immediately, is itself audited as an emergency-flagged event, and locks the
 * same clinician out of re-invoking on this patient for the remainder of the
 * window they revoked from (EmergencyAccessService::openBreakGlass) — an
 * override the patient just refused must not be reopened by pressing the
 * button again; it goes to governance instead.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * SCOPE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `patient_id` comes from $request->attributes, set by
 * AuthenticateMobilePatient from the bearer token. Every query below is keyed
 * on it. There is no id parameter anywhere that can name a different patient,
 * and a grant id belonging to someone else is a 404.
 */
class MobilePatientEmergencyAccessController extends Controller
{
    /** Newest-first page size for the history. */
    private const PER_PAGE = 50;

    public function __construct(private readonly EmergencyAccessService $emergency)
    {
    }

    /**
     * GET /api/mobile/emergency-access
     *
     * Every break-glass on this patient's record — active, expired and
     * revoked. Nothing is hidden once it has lapsed: the point of the log is
     * that the patient can see what happened, not only what is happening.
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->patientId($request);

        if (! $patientId) {
            return response()->json([
                'message'    => __('emergency_access.actor_unresolved'),
                'error_code' => 'EMERGENCY_ACTOR_UNRESOLVED',
            ], 403);
        }

        $events = EmergencyAccessEvent::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->limit(self::PER_PAGE)
            ->get();

        $clinicians = User::whereIn('id', $events->pluck('provider_id')->filter()->unique())->get()->keyBy('id');
        $facilities = Facility::whereIn('id', $events->pluck('facility_id')->filter()->unique())->get()->keyBy('id');

        // How many times each grant was actually READ, straight off the
        // emergency-flagged audit rows. "It was opened" and "it was read
        // eleven times" are different facts and the patient gets both.
        $readCounts = AccessLog::query()
            ->where('patient_id', $patientId)
            ->where('emergency_access', true)
            ->where('access_type', 'read')
            ->get(['purpose'])
            ->groupBy('purpose')
            ->map->count();

        $active = $events->filter(fn (EmergencyAccessEvent $e) => $this->emergency->grantIsActive($e));

        return response()->json([
            'data' => $events->map(function (EmergencyAccessEvent $e) use ($clinicians, $facilities, $readCounts) {
                return $this->eventPayload(
                    $e,
                    $clinicians->get($e->provider_id),
                    $facilities->get($e->facility_id),
                    (int) ($readCounts->get('emergency:break_glass:' . $e->id) ?? 0)
                );
            })->values(),
            'meta' => [
                'total'        => $events->count(),
                'active_count' => $active->count(),
                'ttl_hours'    => EmergencyAccessService::GRANT_TTL_HOURS,
            ],
        ], 200);
    }

    /**
     * POST /api/mobile/emergency-access/{grant}/revoke
     *
     * Close a live override now. Idempotent on an already-revoked grant;
     * refuses on one that has already run out, because there is nothing left
     * to close and pretending otherwise would misreport what the patient did.
     */
    public function revoke(Request $request, string $grant): JsonResponse
    {
        $patientId = $this->patientId($request);

        if (! $patientId) {
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
            ->where('patient_id', $patientId)
            ->first();

        if (! $event) {
            return response()->json([
                'message'    => __('emergency_access.grant_not_found'),
                'error_code' => 'EMERGENCY_GRANT_NOT_FOUND',
            ], 404);
        }

        if ($event->revoked_at !== null) {
            return response()->json([
                'message' => __('emergency_access.already_revoked'),
                'data'    => $this->eventPayload($event, null, null, 0),
            ], 200);
        }

        if (! $this->emergency->grantIsActive($event)) {
            return response()->json([
                'message'    => __('emergency_access.grant_already_ended'),
                'error_code' => 'EMERGENCY_GRANT_ALREADY_ENDED',
                'data'       => $this->eventPayload($event, null, null, 0),
            ], 409);
        }

        // The linked portal user, when there is one, is the only thing
        // access_logs can name as an actor (its actor_id is a FK onto users).
        // Resolved by the auth middleware from the token — never sent by the
        // caller.
        $linkedUserId = $request->attributes->get('patient_user_id');

        $this->emergency->revokeGrant(
            $event,
            'patient',
            $patientId,
            is_string($linkedUserId) && $linkedUserId !== '' ? $linkedUserId : null
        );

        return response()->json([
            'message' => __('emergency_access.revoked'),
            'data'    => $this->eventPayload($event->refresh(), null, null, 0),
        ], 200);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /** The patient — from the token, via the middleware. Nowhere else. */
    private function patientId(Request $request): ?string
    {
        $id = $request->attributes->get('patient_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function eventPayload(
        EmergencyAccessEvent $event,
        ?User $clinician,
        ?Facility $facility,
        int $readCount
    ): array {
        $active = $this->emergency->grantIsActive($event);

        return [
            'id'            => $event->id,
            'state'         => $event->revoked_at ? 'revoked' : ($active ? 'active' : 'expired'),
            'active'        => $active,
            // The clinician's own words, kept verbatim. This is the part of
            // the record the patient is most entitled to.
            'reason'        => $event->reason,
            'clinician_id'  => $event->provider_id,
            'clinician_name' => $clinician?->name,
            'facility_id'   => $event->facility_id,
            'facility_name' => $facility?->name,
            'opened_at'     => $this->iso($event->created_at),
            'expires_at'    => $this->iso($event->expires_at),
            'expires_in_seconds' => $this->emergency->grantSecondsRemaining($event),
            'revoked_at'    => $this->iso($event->revoked_at),
            'revoked_by'    => $event->revoked_by_type,
            'is_repeat'     => (bool) $event->is_repeat,
            'notified_at'   => $this->iso($event->notified_at),
            'records_viewed' => $event->records_viewed ?? [],
            'read_count'    => $readCount,
            'can_revoke'    => $active,
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
