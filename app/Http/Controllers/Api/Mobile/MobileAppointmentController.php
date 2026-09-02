<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mobile Patient API — Appointments
 *
 * Read-only view of the authenticated patient's appointments (upcoming/past).
 * Appointment creation flows through the booking portal.
 */
class MobileAppointmentController extends Controller
{
    /**
     * Facility statuses that may take a patient booking. `active_demo` is the
     * demo estate's equivalent of `active`; demo and real rows never meet in
     * one query (Facility carries the IsDemoRecord global scope).
     */
    private const BOOKABLE_FACILITY_STATUSES = ['active', 'active_demo'];

    public function __construct(private NotificationService $notificationService) {}

    /**
     * List appointments.
     *
     * GET /api/mobile/appointments
     * Query params: scope (upcoming|past|all), limit (default 20)
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $scope     = $request->query('scope', 'upcoming'); // upcoming|past|all
        $limit     = min((int) $request->query('limit', 20), 100);

        $query = Appointment::where('patient_id', $patientId)
            ->with(['facility:id,name', 'provider:id,name'])
            ->orderBy('scheduled_at', $scope === 'past' ? 'desc' : 'asc');

        match ($scope) {
            'upcoming' => $query->whereIn('status', ['booked', 'confirmed', 'checked_in'])
                                ->where('scheduled_at', '>=', now()),
            // The two "past" conditions MUST stay grouped. Left un-nested, the
            // trailing orWhere escapes the patient_id constraint entirely
            // ((patient_id = ? AND status NOT IN (..)) OR scheduled_at < now())
            // and every patient's past appointments — reason text included —
            // leaks into this response.
            'past'     => $query->where(function ($sub) {
                $sub->whereNotIn('status', ['booked', 'confirmed'])
                    ->orWhere('scheduled_at', '<', now());
            }),
            default    => null,
        };

        $appointments = $query->paginate($limit);

        return response()->json([
            'data'       => $appointments->map(fn ($a) => $this->formatAppointment($a)),
            'pagination' => [
                'total'        => $appointments->total(),
                'per_page'     => $appointments->perPage(),
                'current_page' => $appointments->currentPage(),
                'last_page'    => $appointments->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single appointment detail.
     *
     * GET /api/mobile/appointments/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $appointment = Appointment::where('id', $id)
            ->where('patient_id', $patientId)
            ->with(['facility:id,name', 'provider:id,name'])
            ->firstOrFail();

        return response()->json(['data' => $this->formatAppointmentDetail($appointment)]);
    }

    /**
     * POST /api/mobile/appointments
     *
     * Atomically book an appointment slot for a patient.
     * Uses pessimistic lock to prevent concurrent double-booking.
     *
     * Body:
     *   _patient_id         string  (test helper; production resolves from auth token)
     *   facility_id         string  UUID of facilities (not care_facilities) row
     *   appointment_slot_id string  UUID of appointment_slots row
     *   appointment_type    string  e.g. "consultation", "follow_up"
     *   reason              string  optional
     *
     * `facility_id` legitimately comes from the request here — a patient is the
     * one who decides which facility to attend, and the mobile client echoes
     * back the id MobileFacilityController::slots() handed it. What it must not
     * do is *decide* where the appointment lands: the booked slot belongs to
     * exactly one facility, and the row is written from the locked slot, never
     * from the body. A body value naming a different facility used to file the
     * appointment into that facility's register while consuming this one's
     * capacity — one field edit, a booking in a hospital across the country.
     * So the body value is now checked against the slot rather than trusted,
     * and the facility it resolves to has to be one that is actually open for
     * booking.
     */
    public function book(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $validated = $request->validate([
            'facility_id'         => 'required|uuid|exists:facilities,id',
            'appointment_slot_id' => 'required|uuid|exists:appointment_slots,id',
            'appointment_type'    => 'required|string|max:100',
            'reason'              => 'nullable|string|max:1000',
        ]);

        // 404 (not 422) if it vanished between validation and here.
        $slot = AppointmentSlot::findOrFail($validated['appointment_slot_id']);

        // The slot is the authority on which facility this appointment belongs
        // to. A body value that disagrees is either a stale client or someone
        // trying to write across facilities.
        if ($slot->facility_id !== $validated['facility_id']) {
            return response()->json([
                'error_code' => 'FACILITY_SLOT_MISMATCH',
                'message'    => __('api.appointment_facility_slot_mismatch'),
            ], 422);
        }

        // Same bookability rules the patient-facing slot listing applies, so a
        // slot that is closed or in the past cannot be booked by replaying an
        // id the app saw earlier.
        if ($slot->status !== 'open' || $slot->starts_at?->isPast()) {
            return response()->json([
                'error_code' => 'SLOT_NOT_BOOKABLE',
                'message'    => __('api.appointment_slot_not_bookable'),
            ], 422);
        }

        if (! $this->facilityAcceptsBookings($slot->facility_id)) {
            return response()->json([
                'error_code' => 'FACILITY_NOT_BOOKABLE',
                'message'    => __('api.appointment_facility_not_bookable'),
            ], 422);
        }

        $appointment = DB::transaction(function () use ($patientId, $validated, $slot) {
            // Pessimistic lock prevents concurrent double-booking of the same slot
            $slot = AppointmentSlot::lockForUpdate()->findOrFail($slot->id);

            if ($slot->booked_count >= $slot->capacity) {
                throw new \App\Exceptions\SlotFullException('This slot is fully booked.');
            }

            $slot->increment('booked_count');

            return Appointment::create([
                'patient_id'          => $patientId,
                // From the locked slot, not the request body — see the method
                // docblock. The two are already proven equal above; taking it
                // from the slot is what keeps them that way.
                'facility_id'         => $slot->facility_id,
                // Carry the slot's clinician onto the appointment. The slot has
                // always known its provider, but this create dropped it — which
                // left every patient-booked appointment with provider_name null
                // and made provider messaging (which needs an appointment with a
                // provider) unreachable from the patient app. Taken from the
                // locked slot row, never from request input.
                'provider_id'         => $slot->provider_id,
                'appointment_slot_id' => $slot->id,
                'appointment_type'    => $validated['appointment_type'],
                'status'              => 'booked',
                'scheduled_at'        => $slot->starts_at,
                'booked_by_type'      => 'patient',
                'booked_by_id'        => $patientId,
                'reason'              => $validated['reason'] ?? null,
            ]);
        });

        // Fire booking notification — non-fatal (never rolls back a successful booking)
        try {
            $this->notificationService->sendNotification(
                $appointment->patient_id,
                'appointment.booked',
                [
                    'patient_name'     => 'Patient',
                    'facility_name'    => $appointment->facility?->name ?? 'the facility',
                    'scheduled_at'     => $appointment->scheduled_at?->format('D d M Y, H:i'),
                    'appointment_type' => $appointment->appointment_type,
                ],
                'high',
                'appointments'
            );
        } catch (\Throwable) {
            // Notification failure must not affect the booking response
        }

        return response()->json(['data' => $this->formatAppointmentDetail($appointment)], 201);
    }

    /**
     * POST /api/mobile/appointments/{id}/cancel
     *
     * Cancel a patient's own appointment and restore the slot count.
     *
     * Body:
     *   _patient_id  string  (test helper)
     *   reason       string  optional
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $appointment = Appointment::where('id', $id)->firstOrFail();

        if ($appointment->patient_id !== $patientId) {
            return response()->json([
                'error_code' => 'FORBIDDEN',
                'message'    => __('api.appointment_cancel_own_only'),
            ], 403);
        }

        if (!in_array($appointment->status, ['booked', 'confirmed'])) {
            return response()->json([
                'error_code' => 'INVALID_STATUS',
                'message'    => "Cannot cancel an appointment with status '{$appointment->status}'.",
            ], 422);
        }

        DB::transaction(function () use ($appointment, $request) {
            $appointment->update([
                'status'              => 'cancelled',
                'cancellation_reason' => $request->input('reason'),
                'cancelled_at'        => now(),
                'cancelled_by_id'     => $appointment->patient_id,
            ]);

            if ($appointment->appointment_slot_id) {
                AppointmentSlot::where('id', $appointment->appointment_slot_id)
                    ->where('booked_count', '>', 0)
                    ->decrement('booked_count');
            }
        });

        return response()->json(['data' => $this->formatAppointmentDetail($appointment->fresh())]);
    }

    // -------------------------------------------------------------------------

    /**
     * Is this facility one a patient may book into right now?
     *
     * Two gates, because a facility can be reachable two ways:
     *   - the `facilities` row itself must be live, not pending or suspended;
     *   - if it is published in the public directory the patient browsed, that
     *     listing must still be active — a suspended listing disappears from
     *     the finder and must stop taking bookings with it.
     * A facility with no directory listing (integration-only, seeded fixtures)
     * is still bookable on the strength of its own status.
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

    private function formatAppointment(Appointment $a): array
    {
        return [
            'id'               => $a->id,
            'appointment_type' => $a->appointment_type,
            'status'           => $a->status,
            'facility_name'    => $a->facility?->name,
            'provider_name'    => $a->provider?->name,
            'scheduled_at'     => $a->scheduled_at?->toIso8601String(),
            'checked_in_at'    => $a->checked_in_at?->toIso8601String(),
            'reason'           => $a->reason,
        ];
    }

    private function formatAppointmentDetail(Appointment $a): array
    {
        $base = $this->formatAppointment($a);
        $base['cancellation_reason'] = $a->cancellation_reason;
        $base['cancelled_at']        = $a->cancelled_at?->toIso8601String();
        $base['no_show_at']          = $a->no_show_at?->toIso8601String();
        $base['visit_id']            = $a->visit_id;
        return $base;
    }

    private function resolvePatientId(Request $request): string
    {
        if (app()->environment('testing') && $request->has('_patient_id')) {
            return $request->input('_patient_id');
        }

        $patientId = $request->attributes->get('patient_id');
        if ($patientId) {
            return $patientId;
        }

        abort(401, 'Unauthenticated.');
    }
}
