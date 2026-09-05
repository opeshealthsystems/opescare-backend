<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Exceptions\SlotFullException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AuditEvent;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mobile Patient API — Appointment reschedule.
 *
 * Before this existed, a patient who could not make an appointment had exactly
 * one option: cancel. That releases the slot to everyone else and drops the
 * patient back into the booking wizard with nothing held — so "I need to come
 * at 11 instead of 9" cost them their place in the diary. This endpoint moves
 * the appointment instead.
 *
 * ── The property that matters ────────────────────────────────────────────────
 * A reschedule is an ATOMIC SWAP. The new slot is claimed and the old one
 * released inside ONE transaction, under row locks taken in a fixed order. The
 * two failure modes that would matter to a patient are therefore impossible:
 *
 *   - zero slots — the old slot is only released once the new one has been
 *     locked and its capacity re-checked under that lock. Anything that fails
 *     after that point rolls the whole thing back, old slot included.
 *   - two slots — the appointment row itself is locked first, so two taps of
 *     the same button (or two devices) serialise; the second one re-reads the
 *     appointment's *current* slot and swaps from there rather than from the
 *     stale one it validated against.
 *
 * A slot that fills between the read and the write loses cleanly: 409
 * SLOT_FULL, transaction rolled back, patient still holds the original
 * appointment — never a 500 and never a partial write.
 *
 * ── Where the patient id comes from ─────────────────────────────────────────
 * `$request->attributes` only, written by the `auth.mobile` middleware from the
 * bearer token. Not the body, not the query, not the path. Unlike
 * MobileAppointmentController this controller has NO `_patient_id` testing
 * escape hatch: a reschedule moves a real booking between two real people's
 * slots, and a body-readable patient id — even one gated on the environment —
 * is the wrong shape for that. The tests authenticate with a real token.
 *
 * ── Booking rules ───────────────────────────────────────────────────────────
 * Identical to the rules `MobileAppointmentController::book()` applies, because
 * a reschedule is a booking: slot `status = open`, `starts_at` in the future,
 * `booked_count < capacity`, and a facility that is actually open for bookings
 * (live `facilities.status` AND, where the facility is published in the public
 * directory, an active `care_facilities` listing). Those checks live in that
 * controller as PRIVATE methods and it is owned by another change right now, so
 * they are mirrored here rather than called. They should be lifted into one
 * shared service the moment both files are free — see the note on
 * facilityAcceptsBookings() below.
 *
 * `AppointmentService::reschedule()` was considered for reuse and deliberately
 * not used: it creates a SECOND appointment with status `scheduled` (the mobile
 * list only shows `booked|confirmed|checked_in`, so the patient's new
 * appointment would simply vanish from the app), it requires a free-text reason,
 * and it gates on `facility_schedules` / `provider_availabilities` rows that the
 * mobile booking path deliberately does not require — every facility without
 * those rows, the whole demo estate included, would be un-reschedulable while
 * still being bookable. Same-facility, same-row move is the semantics the mobile
 * client needs.
 */
class MobileAppointmentRescheduleController extends Controller
{
    /**
     * Facility statuses that may take a patient booking. Mirrors
     * MobileAppointmentController::BOOKABLE_FACILITY_STATUSES — `active_demo`
     * is the demo estate's equivalent of `active`.
     */
    private const BOOKABLE_FACILITY_STATUSES = ['active', 'active_demo'];

    /**
     * Statuses a patient may move. `checked_in`, `completed`, `no_show`,
     * `cancelled` and `rescheduled` are each refused with their OWN message —
     * "you cannot do that" tells a patient nothing they can act on.
     */
    private const RESCHEDULABLE_STATUSES = ['booked', 'confirmed', 'scheduled'];

    /** How far ahead the slot picker looks, and how many rows it returns. */
    private const OPTIONS_LIMIT = 50;

    public function __construct(private NotificationService $notificationService) {}

    /**
     * GET /api/mobile/appointments/{id}/reschedule-options
     *
     * The slot picker's data source. Answers two questions in one round trip:
     * *may this appointment be moved at all* (and if not, exactly why), and
     * *what times are open at the same facility*.
     *
     * The mobile appointment payload carries no `facility_id` — only
     * `facility_name` — so the client cannot call
     * GET /mobile/facilities/{id}/slots for the right facility on its own. This
     * endpoint resolves the facility from the appointment server-side, which is
     * also the safer shape: the patient never nominates the facility, so a
     * reschedule cannot walk an appointment to a different hospital.
     */
    public function options(Request $request, string $id): JsonResponse
    {
        $patientId   = $this->resolvePatientId($request);
        $appointment = $this->findOwnAppointment($id, $patientId);

        if (! $appointment) {
            return $this->error('APPOINTMENT_NOT_FOUND', __('appointments_reschedule.not_found'), 404);
        }

        $blocker = $this->rescheduleBlocker($appointment);

        $slots = collect();

        if (! $blocker) {
            $slots = AppointmentSlot::where('facility_id', $appointment->facility_id)
                ->where('status', 'open')
                ->where('starts_at', '>=', now())
                // The appointment's own slot is kept in the list even when the
                // patient's own booking has filled it, flagged `is_current`, so
                // the picker can show "you are here" instead of a gap where the
                // current time ought to be.
                ->where(function ($q) use ($appointment) {
                    $q->whereRaw('booked_count < capacity');

                    if ($appointment->appointment_slot_id) {
                        $q->orWhere('id', $appointment->appointment_slot_id);
                    }
                })
                ->orderBy('starts_at')
                ->limit(self::OPTIONS_LIMIT)
                ->get();
        }

        return response()->json([
            'data' => [
                'appointment_id'  => $appointment->id,
                'can_reschedule'  => $blocker === null,
                // null when the move is allowed; otherwise the same error_code
                // and message the POST would answer with, so the UI can explain
                // itself before the patient picks anything.
                'blocked_reason'  => $blocker,
                'current' => [
                    'appointment_slot_id' => $appointment->appointment_slot_id,
                    'scheduled_at'        => $appointment->scheduled_at?->toIso8601String(),
                    'facility_name'       => $appointment->facility?->name,
                    'provider_name'       => $appointment->provider?->name,
                    'appointment_type'    => $appointment->appointment_type,
                    'status'              => $appointment->status,
                ],
                'slots' => $slots->map(fn (AppointmentSlot $s) => [
                    'id'              => $s->id,
                    'starts_at'       => $s->starts_at?->toIso8601String(),
                    'ends_at'         => $s->ends_at?->toIso8601String(),
                    'available_count' => max($s->capacity - $s->booked_count, 0),
                    'provider_id'     => $s->provider_id,
                    'is_current'      => $s->id === $appointment->appointment_slot_id,
                ])->values(),
            ],
        ]);
    }

    /**
     * POST /api/mobile/appointments/{id}/reschedule
     *
     * Body:
     *   appointment_slot_id  string  UUID of the appointment_slots row to move to
     *   reason               string  optional, max 1000
     *
     * Anything else in the body is ignored. In particular `patient_id`,
     * `facility_id` and `scheduled_at` are NOT read: the patient comes from the
     * bearer token, the facility from the appointment, and the time from the
     * locked slot row.
     */
    public function reschedule(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $validated = $request->validate([
            'appointment_slot_id' => 'required|uuid|exists:appointment_slots,id',
            'reason'              => 'nullable|string|max:1000',
        ]);

        $appointment = $this->findOwnAppointment($id, $patientId);

        // 404 rather than 403 on someone else's appointment. `cancel()` answers
        // 403, which confirms the id exists to anyone holding any valid token;
        // there is nothing to gain from that here.
        if (! $appointment) {
            return $this->error('APPOINTMENT_NOT_FOUND', __('appointments_reschedule.not_found'), 404);
        }

        if ($blocker = $this->rescheduleBlocker($appointment)) {
            return response()->json($blocker + [
                'appointment' => $this->formatAppointmentDetail($appointment),
            ], 422);
        }

        // ── Pre-flight ──────────────────────────────────────────────────────
        // Cheap, friendly checks before any lock is taken. Every one of them is
        // taken again inside the transaction, where it is actually enforced —
        // these exist to give a specific message, not to be the gate.
        $slot = AppointmentSlot::find($validated['appointment_slot_id']);

        if (! $slot) {
            return $this->rejected($appointment, 'SLOT_NOT_FOUND', __('appointments_reschedule.slot_not_found'), 404);
        }

        if ($slot->id === $appointment->appointment_slot_id) {
            return $this->rejected($appointment, 'SAME_SLOT', __('appointments_reschedule.same_slot'));
        }

        // A reschedule stays at the same facility. Moving hospital is a
        // different decision with different consequences (travel, referral,
        // records) and belongs to a fresh booking, not to a slot swap.
        if ($slot->facility_id !== $appointment->facility_id) {
            return $this->rejected(
                $appointment,
                'FACILITY_SLOT_MISMATCH',
                __('appointments_reschedule.facility_slot_mismatch')
            );
        }

        if ($slot->status !== 'open' || $slot->starts_at?->isPast()) {
            return $this->rejected($appointment, 'SLOT_NOT_BOOKABLE', __('appointments_reschedule.slot_not_bookable'));
        }

        if (! $this->facilityAcceptsBookings($slot->facility_id)) {
            return $this->rejected(
                $appointment,
                'FACILITY_NOT_BOOKABLE',
                __('appointments_reschedule.facility_not_bookable')
            );
        }

        // Snapshot before anything moves — the audit trail's `before_state` and
        // the "your original appointment is unchanged" line both read from it,
        // and after the swap the row no longer remembers where it was.
        $before         = $appointment->withoutRelations()->toArray();
        $previousSlotId = $appointment->appointment_slot_id;
        $previousTime   = $appointment->scheduled_at;

        // ── The swap ────────────────────────────────────────────────────────
        try {
            $appointment = DB::transaction(function () use ($appointment, $slot, $validated) {
                // 1. Lock the APPOINTMENT first. Two concurrent reschedules of
                //    the same appointment now serialise here, so the second one
                //    reads the slot the first one actually left it on — the
                //    guard against a patient ending up holding two slots.
                $locked = Appointment::lockForUpdate()->find($appointment->id);

                if (! $locked) {
                    throw new RescheduleRejected(
                        'APPOINTMENT_NOT_FOUND',
                        __('appointments_reschedule.not_found'),
                        404
                    );
                }

                // 2. Re-check eligibility under the lock. A facility can cancel
                //    or complete an appointment while the patient is choosing.
                if ($blocker = $this->rescheduleBlocker($locked)) {
                    throw new RescheduleRejected($blocker['error_code'], $blocker['message']);
                }

                $oldSlotId = $locked->appointment_slot_id;

                if ($oldSlotId === $slot->id) {
                    throw new RescheduleRejected('SAME_SLOT', __('appointments_reschedule.same_slot'));
                }

                // 3. Lock both slots in ONE statement ordered by id. Two swaps
                //    that cross — A moving X→Y while B moves Y→X — would
                //    deadlock if each locked its own slot first; a fixed global
                //    order makes one of them simply wait.
                $ids = array_values(array_unique(array_filter([$oldSlotId, $slot->id])));
                sort($ids);

                $lockedSlots = AppointmentSlot::whereIn('id', $ids)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                /** @var AppointmentSlot|null $newSlot */
                $newSlot = $lockedSlots->get($slot->id);

                if (! $newSlot) {
                    throw new RescheduleRejected(
                        'SLOT_NOT_FOUND',
                        __('appointments_reschedule.slot_not_found'),
                        404
                    );
                }

                // 4. Re-check the slot under its own lock. Everything above was
                //    advisory; THIS is the gate. A slot closed, moved into the
                //    past, or filled since the pre-flight loses here.
                if ($newSlot->status !== 'open' || $newSlot->starts_at?->isPast()) {
                    throw new RescheduleRejected(
                        'SLOT_NOT_BOOKABLE',
                        __('appointments_reschedule.slot_not_bookable')
                    );
                }

                if ($newSlot->booked_count >= $newSlot->capacity) {
                    // 409, and the rollback below is what keeps the loser of a
                    // race on the last seat holding their original appointment.
                    throw new SlotFullException(__('appointments_reschedule.slot_taken'));
                }

                // 5. Claim the new slot, THEN release the old one. In that
                //    order: if the claim fails the old slot was never touched,
                //    and if the release fails the whole transaction unwinds.
                $newSlot->increment('booked_count');

                if ($oldSlotId) {
                    AppointmentSlot::where('id', $oldSlotId)
                        ->where('booked_count', '>', 0)
                        ->decrement('booked_count');
                }

                $locked->update([
                    'appointment_slot_id' => $newSlot->id,
                    'scheduled_at'        => $newSlot->starts_at,
                    // The clinician comes with the slot, exactly as it does on
                    // a first booking. A slot with no provider must not silently
                    // keep the old one — the patient would be told they are
                    // seeing a doctor who is not on that shift.
                    'provider_id'         => $newSlot->provider_id,
                    'reason'              => $validated['reason'] ?? $locked->reason,
                ]);

                return $locked->fresh();
            });
        } catch (RescheduleRejected $e) {
            // A refusal, not a failure: nothing was written, and the response
            // says which appointment the patient still holds.
            return $this->rejected($appointment, $e->errorCode, $e->getMessage(), $e->status);
        } catch (SlotFullException $e) {
            // Lost a race for the last seat. bootstrap/app.php would render
            // this globally as a bare 409 SLOT_FULL; caught here instead so the
            // body also carries the appointment the patient still holds and the
            // "unchanged" line — the whole reason the swap is a transaction.
            // The status and error_code stay identical to the global renderer,
            // so the mobile client's existing 409/SLOT_FULL mapping still hits.
            return $this->rejected($appointment, 'SLOT_FULL', $e->getMessage(), 409);
        }

        // ── Audit ───────────────────────────────────────────────────────────
        // AuditEvent is the trail the appointment domain actually writes
        // (AppointmentService::audit() records create / reschedule / cancel /
        // check_in / no_show there). Same action_type and resource_type, so a
        // patient-initiated move sits in the same query as a desk-initiated one.
        $this->audit($appointment, $before, $previousSlotId, $previousTime, $request, $validated['reason'] ?? null);

        // Non-fatal, exactly as book() treats its own notification: a
        // notification failure must never undo a completed reschedule.
        try {
            $this->notificationService->sendNotification(
                $appointment->patient_id,
                'appointment.rescheduled',
                [
                    'patient_name'     => 'Patient',
                    'facility_name'    => $appointment->facility?->name ?? 'the facility',
                    'scheduled_at'     => $appointment->scheduled_at?->format('D d M Y, H:i'),
                    'previous_at'      => $previousTime?->format('D d M Y, H:i'),
                    'appointment_type' => $appointment->appointment_type,
                ],
                'high',
                'appointments'
            );
        } catch (\Throwable) {
            // Deliberately swallowed.
        }

        return response()->json([
            'message' => __('appointments_reschedule.success', [
                'when' => $appointment->scheduled_at?->format('D d M Y, H:i') ?? '',
            ]),
            'data' => $this->formatAppointmentDetail($appointment) + [
                'previous_scheduled_at'    => $previousTime?->toIso8601String(),
                'previous_appointment_slot_id' => $previousSlotId,
            ],
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * The appointment, scoped to its owner in the WHERE clause.
     *
     * Scoping in the query rather than fetching-then-comparing means a patient
     * id that is somehow wrong can only ever produce "not found" — it can never
     * load another patient's row into memory in the first place.
     */
    private function findOwnAppointment(string $id, string $patientId): ?Appointment
    {
        if (! Str::isUuid($id)) {
            // Postgres throws on a malformed uuid comparison; a bad id in the
            // path is a 404, not a 500.
            return null;
        }

        return Appointment::where('id', $id)
            ->where('patient_id', $patientId)
            ->with(['facility:id,name', 'provider:id,name'])
            ->first();
    }

    /**
     * Why this appointment cannot be moved, or null if it can.
     *
     * Returns the response body fragment (`error_code` + `message`) so the same
     * answer serves the GET (as `blocked_reason`) and the POST (as the 422).
     * Each state gets its own message: a patient told "cannot reschedule" has
     * no idea whether to call the clinic, book again, or just wait.
     */
    private function rescheduleBlocker(Appointment $appointment): ?array
    {
        $status = (string) $appointment->status;

        $byStatus = [
            'cancelled'   => ['APPOINTMENT_CANCELLED',           'cancelled'],
            'completed'   => ['APPOINTMENT_COMPLETED',           'completed'],
            'no_show'     => ['APPOINTMENT_NO_SHOW',             'no_show'],
            'checked_in'  => ['APPOINTMENT_CHECKED_IN',          'checked_in'],
            'arrived'     => ['APPOINTMENT_CHECKED_IN',          'checked_in'],
            'in_progress' => ['APPOINTMENT_CHECKED_IN',          'checked_in'],
            'rescheduled' => ['APPOINTMENT_ALREADY_RESCHEDULED', 'already_rescheduled'],
        ];

        if (isset($byStatus[$status])) {
            [$code, $key] = $byStatus[$status];

            return ['error_code' => $code, 'message' => __("appointments_reschedule.{$key}")];
        }

        if (! in_array($status, self::RESCHEDULABLE_STATUSES, true)) {
            return [
                'error_code' => 'APPOINTMENT_NOT_RESCHEDULABLE',
                'message'    => __('appointments_reschedule.not_reschedulable', ['status' => $status]),
            ];
        }

        // A still-"booked" appointment whose time has passed is a missed visit
        // the desk has not marked yet. It is not a thing to move.
        if ($appointment->scheduled_at?->isPast()) {
            return [
                'error_code' => 'APPOINTMENT_IN_PAST',
                'message'    => __('appointments_reschedule.in_past', [
                    'when' => $appointment->scheduled_at->format('D d M Y, H:i'),
                ]),
            ];
        }

        return null;
    }

    /**
     * Is this facility one a patient may book into right now?
     *
     * Byte-for-byte the rule MobileAppointmentController::facilityAcceptsBookings()
     * applies, duplicated only because that method is private and its file is
     * being changed by another piece of work. The two must not drift: when both
     * files are free, lift this into a shared service (e.g.
     * App\Services\Booking\FacilityBookability) and have book(), cancel() and
     * this controller call it.
     *
     *   - the `facilities` row itself must be live, not pending or suspended;
     *   - if the facility is published in the public directory, that listing
     *     must still be active — a suspended listing disappears from the finder
     *     and must stop taking bookings with it;
     *   - a facility with no directory listing (integration-only, seeded
     *     fixtures) is bookable on the strength of its own status.
     */
    private function facilityAcceptsBookings(string $facilityId): bool
    {
        $facility = Facility::find($facilityId);

        if (! $facility || ! in_array($facility->status, self::BOOKABLE_FACILITY_STATUSES, true)) {
            return false;
        }

        $listings = CareFacility::where('facility_id', $facilityId);

        if (! (clone $listings)->exists()) {
            return true;
        }

        return (clone $listings)->where('listing_status', 'active')->exists();
    }

    private function audit(
        Appointment $appointment,
        array $before,
        ?string $previousSlotId,
        $previousTime,
        Request $request,
        ?string $reason
    ): void {
        AuditEvent::create([
            'actor_id'      => $appointment->patient_id,
            'actor_role'    => 'patient',
            'facility_id'   => $appointment->facility_id,
            'patient_id'    => $appointment->patient_id,
            'encounter_id'  => $appointment->visit_id,
            'action_type'   => 'reschedule',
            'resource_type' => 'appointment',
            'resource_id'   => $appointment->id,
            'source_system' => 'mobile_app',
            'ip_address'    => $request->ip(),
            'reason'        => $reason,
            'before_state'  => $before,
            'after_state'   => $appointment->toArray() + [
                'previous_appointment_slot_id' => $previousSlotId,
                'previous_scheduled_at'        => $previousTime?->toIso8601String(),
            ],
            'created_at'    => now(),
        ]);
    }

    /**
     * A refusal that names the appointment the patient still holds.
     *
     * The `appointment` block is the point: the client renders "your original
     * appointment on <when> is unchanged" from real data rather than from an
     * assumption about what the server did.
     */
    private function rejected(Appointment $appointment, string $code, string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'error_code'  => $code,
            'message'     => $message,
            'unchanged'   => __('appointments_reschedule.unchanged', [
                'when' => $appointment->scheduled_at?->format('D d M Y, H:i') ?? '',
            ]),
            'appointment' => $this->formatAppointmentDetail($appointment),
        ], $status);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error_code' => $code, 'message' => $message], $status);
    }

    /**
     * Same shape MobileAppointmentController::formatAppointmentDetail() returns,
     * so the mobile client can drop the response straight into the cache entry
     * the detail screen already reads.
     */
    private function formatAppointmentDetail(Appointment $a): array
    {
        return [
            'id'                  => $a->id,
            'appointment_type'    => $a->appointment_type,
            'status'              => $a->status,
            'facility_name'       => $a->facility?->name,
            'provider_name'       => $a->provider?->name,
            'scheduled_at'        => $a->scheduled_at?->toIso8601String(),
            'checked_in_at'       => $a->checked_in_at?->toIso8601String(),
            'reason'              => $a->reason,
            'cancellation_reason' => $a->cancellation_reason,
            'cancelled_at'        => $a->cancelled_at?->toIso8601String(),
            'no_show_at'          => $a->no_show_at?->toIso8601String(),
            'visit_id'            => $a->visit_id,
        ];
    }

    /**
     * The patient id, from the request attributes the `auth.mobile` middleware
     * wrote off the bearer token — and from nowhere else. No body, no query, no
     * path, no session, no environment-gated override.
     */
    private function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');

        if (! $patientId) {
            abort(401, 'Unauthenticated.');
        }

        return (string) $patientId;
    }
}

/**
 * A refusal raised from inside the swap transaction.
 *
 * Throwing rather than returning is what makes the rollback automatic: every
 * exit from the transaction that is not the happy path unwinds the slot
 * increment and the decrement together. Carries the error_code and HTTP status
 * so the caller can answer with the same body shape as a pre-flight refusal.
 *
 * Deliberately NOT a SlotFullException subclass: SlotFullException is rendered
 * globally as 409 SLOT_FULL by bootstrap/app.php, which is exactly right for
 * "someone took the last seat" and wrong for everything else.
 */
class RescheduleRejected extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422
    ) {
        parent::__construct($message);
    }
}
