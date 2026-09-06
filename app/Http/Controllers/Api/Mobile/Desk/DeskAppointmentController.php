<?php

namespace App\Http\Controllers\Api\Mobile\Desk;

use App\Http\Controllers\Controller;
use App\Http\Resources\Desk\DeskAppointmentResource;
use App\Models\Appointment;
use App\Services\FrontDesk\DeskActor;
use App\Services\FrontDesk\FrontDeskRefusal;
use App\Services\FrontDesk\FrontDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Front desk mobile API — writing the facility's book.
 *
 *   POST  /api/mobile/desk/appointments                 book by exact Health ID
 *   PUT   /api/mobile/desk/appointments/{id}            move it
 *   PATCH /api/mobile/desk/appointments/{id}/route      hand it to a clinician
 *   POST  /api/mobile/desk/appointments/{id}/confirm
 *   POST  /api/mobile/desk/appointments/{id}/check-in   opens the visit
 *   POST  /api/mobile/desk/appointments/{id}/no-show
 *   POST  /api/mobile/desk/appointments/{id}/cancel     {reason}
 *
 * Facility and actor come from the bearer token via the request attributes
 * (DeskActor::fromRequest) and from nowhere else. Every {id} is looked up
 * INSIDE the facility bound, so another facility's appointment is a 404 —
 * never a 403, which would confirm it exists.
 *
 * The rules (what may follow what, which rows are written) live in
 * FrontDeskService; this controller validates input, translates a
 * FrontDeskRefusal into `{error_code, message}` and tells the patient.
 */
class DeskAppointmentController extends Controller
{
    public function __construct(private readonly FrontDeskService $desk)
    {
    }

    /**
     * POST /api/mobile/desk/appointments
     * {health_id, scheduled_at, appointment_type, provider_id?, reason?} → 201 {data: appointment}
     *
     * The patient is named by exact Health ID; a partial or unknown one is a
     * 404 and reveals nothing. A `provider_id`, when given, must be a
     * clinician posted at THIS facility (422 otherwise); left out, the
     * appointment is booked unassigned and routed later.
     */
    public function store(Request $request): JsonResponse
    {
        $actor = DeskActor::fromRequest($request);

        if (! $actor) {
            return $this->refuse('provider_not_found', 401);
        }

        $validated = $request->validate([
            'health_id'        => ['required', 'string', 'max:64'],
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'appointment_type' => ['required', 'string', 'max:100'],
            'provider_id'      => ['nullable', 'uuid'],
            'reason'           => ['nullable', 'string', 'max:500'],
        ]);

        $patient = $this->desk->patientByHealthId($validated['health_id']);

        if (! $patient) {
            return $this->refuse('patient_not_found', 404);
        }

        $providerId = $validated['provider_id'] ?? null;

        if ($providerId !== null && ! $this->desk->isClinicianAt($actor->facilityId, $providerId)) {
            return $this->refuse('provider_not_at_facility', 422);
        }

        try {
            $appointment = $this->desk->bookAppointment(
                actor: $actor,
                patient: $patient,
                when: Carbon::parse($validated['scheduled_at'], config('app.timezone')),
                appointmentType: $validated['appointment_type'],
                providerId: $providerId,
                reason: $validated['reason'] ?? null,
                historyReason: __('desk_api.reason_booked'),
            );
        } catch (FrontDeskRefusal $e) {
            return $this->refuse($e->key, $e->status);
        }

        $appointment = $this->desk->reload($actor->facilityId, $appointment);
        $this->desk->notifyPatient($appointment, 'booked', push: false);

        return response()->json(['data' => DeskAppointmentResource::make($appointment)], 201);
    }

    /** PUT /api/mobile/desk/appointments/{id} {scheduled_at} → {data: appointment} */
    public function reschedule(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        return $this->act($request, $id, function (DeskActor $actor, Appointment $appointment) use ($validated) {
            $moved = $this->desk->reschedule($actor, $appointment, Carbon::parse($validated['scheduled_at'], config('app.timezone')));
            $moved = $this->desk->reload($actor->facilityId, $moved);
            $this->desk->notifyPatient($moved, 'rescheduled', push: true);

            return $moved;
        });
    }

    /** PATCH /api/mobile/desk/appointments/{id}/route {provider_id} → {data: appointment} */
    public function route(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => ['required', 'uuid'],
        ]);

        return $this->act($request, $id, function (DeskActor $actor, Appointment $appointment) use ($validated) {
            $routed = $this->desk->route($actor, $appointment, $validated['provider_id']);
            $routed = $this->desk->reload($actor->facilityId, $routed);
            $this->desk->notifyPatient($routed, 'routed', push: true);

            return $routed;
        });
    }

    /** POST /api/mobile/desk/appointments/{id}/confirm → {data: appointment} */
    public function confirm(Request $request, string $id): JsonResponse
    {
        return $this->act($request, $id, function (DeskActor $actor, Appointment $appointment) {
            $confirmed = $this->desk->confirm($actor, $appointment);
            $confirmed = $this->desk->reload($actor->facilityId, $confirmed);
            $this->desk->notifyPatient($confirmed, 'confirmed', push: true);

            return $confirmed;
        });
    }

    /** POST /api/mobile/desk/appointments/{id}/check-in → {data: appointment} (visit_id now set) */
    public function checkIn(Request $request, string $id): JsonResponse
    {
        return $this->act($request, $id, fn (DeskActor $actor, Appointment $appointment) => $this->desk->checkIn($actor, $appointment));
    }

    /** POST /api/mobile/desk/appointments/{id}/no-show → {data: appointment} */
    public function noShow(Request $request, string $id): JsonResponse
    {
        return $this->act($request, $id, fn (DeskActor $actor, Appointment $appointment) => $this->desk->noShow($actor, $appointment));
    }

    /**
     * POST /api/mobile/desk/appointments/{id}/cancel {reason} → {data: appointment}
     *
     * A reason is required, as on the web desk (min 5 characters): the patient
     * is told their slot is gone and the register has to say why.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        return $this->act($request, $id, function (DeskActor $actor, Appointment $appointment) use ($validated) {
            $cancelled = $this->desk->cancel($actor, $appointment, $validated['reason']);
            $cancelled = $this->desk->reload($actor->facilityId, $cancelled);
            $this->desk->notifyPatient($cancelled, 'cancelled', push: false);

            return $cancelled;
        });
    }

    // ──────────────────────────────────────────────────────────────────

    /**
     * Resolve the actor and the appointment inside the facility bound, run the
     * transition, answer with the re-read row. A FrontDeskRefusal becomes the
     * `{error_code, message}` pair with the status the service chose.
     *
     * @param  callable(DeskActor, Appointment): Appointment  $transition
     */
    private function act(Request $request, string $id, callable $transition): JsonResponse
    {
        $actor = DeskActor::fromRequest($request);

        if (! $actor) {
            return $this->refuse('provider_not_found', 401);
        }

        $appointment = $this->desk->find($actor->facilityId, $id);

        if (! $appointment) {
            return $this->refuse('appointment_not_found', 404);
        }

        try {
            $result = $transition($actor, $appointment);
        } catch (FrontDeskRefusal $e) {
            return $this->refuse($e->key, $e->status);
        }

        return response()->json([
            'data' => DeskAppointmentResource::make($this->desk->reload($actor->facilityId, $result)),
        ], 200);
    }

    private function refuse(string $key, int $status): JsonResponse
    {
        return response()->json([
            'error_code' => $key,
            'message'    => __('desk_api.' . $key),
        ], $status);
    }
}
