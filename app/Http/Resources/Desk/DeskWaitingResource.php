<?php

namespace App\Http\Resources\Desk;

use App\Http\Resources\ApiResource;
use App\Models\Appointment;
use App\Services\FrontDesk\FrontDeskService;
use Illuminate\Http\Request;

/**
 * One person in the waiting room, as FrontDeskService::waitingRows() orders
 * them: the position in the queue, when they arrived, how long they have
 * waited, who they are (short name + Health ID, nothing clinical) and whose
 * column they are in.
 *
 *   {position, id, scheduled_at, checked_in_at, waited_minutes, status,
 *    appointment_type, reason, visit_id,
 *    patient:{id, health_id, display_name}|null, provider:{id, name, role}|null}
 *
 * Served by GET /api/mobile/desk/waiting (the whole facility) and carried as
 * `waiting[]` on GET /api/mobile/doctor/home (one clinician's column) through
 * FrontDeskService::waitingFor(), so both surfaces read one shape.
 *
 * @property Appointment $resource
 */
class DeskWaitingResource extends ApiResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        $appointment = $this->resource;
        $patient     = $appointment->patient;
        $provider    = $appointment->provider;

        return [
            'position'         => (int) $appointment->getAttribute('queue_position'),
            'id'               => $appointment->id,
            'scheduled_at'     => $appointment->scheduled_at?->toIso8601String(),
            'checked_in_at'    => $appointment->checked_in_at?->toIso8601String(),
            'waited_minutes'   => $appointment->getAttribute('waited_minutes'),
            'status'           => FrontDeskService::wireStatus($appointment->status),
            'appointment_type' => $appointment->appointment_type,
            'reason'           => $appointment->reason,
            'visit_id'         => $appointment->visit_id,
            'patient'          => $patient ? [
                'id'           => $patient->id,
                'health_id'    => $patient->health_id,
                'display_name' => trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')),
            ] : null,
            'provider'         => $provider ? [
                'id'   => $provider->id,
                'name' => $provider->name,
                'role' => $provider->getAttribute('desk_role') ?? $provider->role?->name,
            ] : null,
        ];
    }
}
