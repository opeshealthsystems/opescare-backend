<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Appointments\Services\PatientSelfBookingService;
use App\Modules\Appointments\Services\WaitlistService;
use App\Services\Documents\DocumentIssuanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_unresolved_auth')], 403);
        }

        $query = Appointment::query()->orderBy('scheduled_at');
        $query->where('facility_id', $facilityId);

        if ($request->query('scope') === 'patient') {
            abort_unless($request->filled('patient_id'), 403, 'PATIENT_SCOPE_REQUIRES_PATIENT_ID');
            $query->where('patient_id', $request->query('patient_id'));
        } elseif ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->query('provider_id'));
        }

        return response()->json(['data' => $query->get()->map(fn (Appointment $appointment) => $this->serialize($appointment))->values()]);
    }

    public function store(Request $request, AppointmentService $service)
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_unresolved_auth')], 403);
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'uuid'],
            'provider_id' => ['nullable', 'uuid'],
            'appointment_slot_id' => ['nullable', 'uuid'],
            'scheduled_at' => ['nullable', 'date'],
            'appointment_type' => ['required', 'string'],
            'booked_by_type' => ['nullable', 'string'],
            'booked_by_id' => ['nullable', 'uuid'],
            'reason' => ['nullable', 'string'],
        ]);
        $validated['facility_id'] = $facilityId;

        $appointment = $service->book($validated);

        try {
            $apptId = is_array($appointment) ? ($appointment['id'] ?? null) : ($appointment->id ?? null);
            $scheduledAt = is_array($appointment) ? ($appointment['scheduled_at'] ?? null) : ($appointment->scheduled_at ?? null);
            app(DocumentIssuanceService::class)->issueFromModel(
                'APT',
                'Appointment Confirmation',
                ['appointment_id' => $apptId, 'patient_id' => $validated['patient_id'], 'appointment_type' => $validated['appointment_type'], 'scheduled_at' => $scheduledAt, 'provider_id' => $validated['provider_id'] ?? null, 'reason' => $validated['reason'] ?? null],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['booked_by_id'] ?? null
            );
        } catch (\Throwable) {}

        return response()->json(['data' => $this->serialize($appointment)], 201);
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentService $service)
    {
        $rescheduled = $service->reschedule($appointment, $request->validate([
            'appointment_slot_id' => ['nullable', 'uuid'],
            'scheduled_at' => ['nullable', 'date'],
            'reason' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]));

        return response()->json(['data' => $this->serialize($rescheduled)]);
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentService $service)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        return response()->json(['data' => $this->serialize($service->cancel($appointment, $validated['reason'], $validated['actor_id'] ?? null))]);
    }

    public function checkIn(Request $request, Appointment $appointment, AppointmentService $service)
    {
        $validated = $request->validate(['actor_id' => ['nullable', 'uuid']]);

        return response()->json(['data' => $this->serialize($service->checkIn($appointment, $validated['actor_id'] ?? null))]);
    }

    public function noShow(Request $request, AppointmentService $service)
    {
        $validated = $request->validate([
            'as_of' => ['nullable', 'date'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        $count = $service->markNoShows(isset($validated['as_of']) ? Carbon::parse($validated['as_of']) : now(), $validated['actor_id'] ?? null);

        return response()->json(['status' => 'ok', 'marked_no_show' => $count]);
    }

    // ── Waitlist ──────────────────────────────────────────────────────────

    /**
     * Add a patient to the appointment waitlist.
     * facility_id from middleware attributes; body value cross-checked if provided.
     *
     * Body: { patient_id, provider_id, facility_id?, preferred_dates: [date,...], reason? }
     */
    public function addToWaitlist(Request $request, WaitlistService $service): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'patient_id'       => ['required', 'uuid', 'exists:patients,id'],
            'provider_id'      => ['required', 'uuid'],
            'facility_id'      => ['nullable', 'uuid'],
            'preferred_dates'  => ['required', 'array', 'min:1'],
            'preferred_dates.*' => ['date'],
            'reason'           => ['nullable', 'string', 'max:1000'],
        ]);

        if (!empty($validated['facility_id']) && $facilityId && $validated['facility_id'] !== $facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_id_mismatch')], 403);
        }

        $effectiveFacilityId = $facilityId ?? $validated['facility_id'];
        if (!$effectiveFacilityId) {
            return response()->json(['message' => __('api.facility_unresolved_id')], 422);
        }

        $entry = $service->addToWaitlist(
            $validated['patient_id'],
            $validated['provider_id'],
            $effectiveFacilityId,
            $validated['preferred_dates'],
            $validated['reason'] ?? null
        );

        return response()->json(['message' => __('api.added_to_waitlist'), 'data' => $entry], 201);
    }

    /**
     * Trigger a waitlist backfill job for a cancelled slot.
     * Dispatches BackfillWaitlistJob asynchronously.
     *
     * Body: { provider_id, facility_id?, date }
     */
    public function triggerBackfill(Request $request, WaitlistService $service): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'provider_id' => ['required', 'uuid'],
            'facility_id' => ['nullable', 'uuid'],
            'date'        => ['required', 'date', 'after_or_equal:today'],
        ]);

        if (!empty($validated['facility_id']) && $facilityId && $validated['facility_id'] !== $facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_id_mismatch')], 403);
        }

        $effectiveFacilityId = $facilityId ?? $validated['facility_id'];
        if (!$effectiveFacilityId) {
            return response()->json(['message' => __('api.facility_unresolved_id')], 422);
        }

        $service->triggerBackfill($validated['provider_id'], $effectiveFacilityId, $validated['date']);

        return response()->json(['message' => __('api.waitlist_dispatched'), 'date' => $validated['date']]);
    }

    // ── Patient Self-Booking ──────────────────────────────────────────────

    /**
     * Patient self-books an available slot.
     * Validates provider availability and no double-booking before creating.
     *
     * Body: { patient_id, provider_id, facility_id?, date_time, reason }
     */
    public function selfBook(Request $request, PatientSelfBookingService $service): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'patient_id'  => ['required', 'uuid', 'exists:patients,id'],
            'provider_id' => ['required', 'uuid'],
            'facility_id' => ['nullable', 'uuid'],
            'date_time'   => ['required', 'date', 'after:now'],
            'reason'      => ['required', 'string', 'max:500'],
        ]);

        if (!empty($validated['facility_id']) && $facilityId && $validated['facility_id'] !== $facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.facility_id_mismatch')], 403);
        }

        $effectiveFacilityId = $facilityId ?? $validated['facility_id'];
        if (!$effectiveFacilityId) {
            return response()->json(['message' => __('api.facility_unresolved_id')], 422);
        }

        try {
            $appointment = $service->bookSlot(
                $validated['patient_id'],
                $validated['provider_id'],
                $effectiveFacilityId,
                Carbon::parse($validated['date_time']),
                $validated['reason']
            );
        } catch (\Exception $e) {
            $message = match ($e->getMessage()) {
                'SLOT_OUTSIDE_AVAILABILITY' => 'The selected time is outside provider availability hours.',
                'SLOT_ALREADY_BOOKED'       => 'This slot has already been booked.',
                default                     => $e->getMessage(),
            };
            return response()->json(['message' => $message], 422);
        }

        return response()->json(['message' => __('api.appointment_booked'), 'data' => $this->serialize($appointment)], 201);
    }

    private function serialize(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'facility_id' => $appointment->facility_id,
            'provider_id' => $appointment->provider_id,
            'appointment_slot_id' => $appointment->appointment_slot_id,
            'visit_id' => $appointment->visit_id,
            'rescheduled_from_appointment_id' => $appointment->rescheduled_from_appointment_id,
            'appointment_type' => $appointment->appointment_type,
            'status' => $appointment->status,
            'scheduled_at' => $appointment->scheduled_at?->toISOString(),
            'billing_deferred' => $appointment->billing_deferred,
            'telemedicine_deferred' => $appointment->telemedicine_deferred,
        ];
    }
}
