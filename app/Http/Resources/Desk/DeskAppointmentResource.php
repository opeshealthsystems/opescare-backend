<?php

namespace App\Http\Resources\Desk;

use App\Http\Resources\ApiResource;
use App\Models\Appointment;
use App\Services\FrontDesk\FrontDeskService;
use Illuminate\Http\Request;

/**
 * One row of the facility's appointment book as the front desk app sees it
 * (contract: role-dashboards plan, "Front desk").
 *
 *   {id, scheduled_at, status, appointment_type, reason, checked_in_at,
 *    patient:{id, health_id, display_name}|null, provider:{id, name, role}|null}
 *
 * `status` is the desk's closed wire vocabulary
 * (FrontDeskService::WIRE_STATUS): scheduled | confirmed | checked_in |
 * in_progress | completed | cancelled | no_show.
 *
 * `patient` is null when the row's patient is outside the caller's demo
 * partition (IsDemoRecord) — scoped out, not missing. `provider.role` is the
 * role the clinician holds at THIS facility (stamped as `desk_role` by
 * FrontDeskService), falling back to their global role.
 *
 * The fields after `provider` are additive: the closed rows carry why and
 * when they closed, a checked-in row names its visit.
 *
 * @property Appointment $resource
 */
class DeskAppointmentResource extends ApiResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        $appointment = $this->resource;
        $patient     = $appointment->patient;
        $provider    = $appointment->provider;

        return [
            'id'                  => $appointment->id,
            'scheduled_at'        => $appointment->scheduled_at?->toIso8601String(),
            'status'              => FrontDeskService::wireStatus($appointment->status),
            'appointment_type'    => $appointment->appointment_type,
            'reason'              => $appointment->reason,
            'checked_in_at'       => $appointment->checked_in_at?->toIso8601String(),
            'patient'             => $patient ? [
                'id'           => $patient->id,
                'health_id'    => $patient->health_id,
                'display_name' => trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')),
            ] : null,
            'provider'            => $provider ? [
                'id'   => $provider->id,
                'name' => $provider->name,
                'role' => $provider->getAttribute('desk_role') ?? $provider->role?->name,
            ] : null,
            'facility_id'         => $appointment->facility_id,
            'visit_id'            => $appointment->visit_id,
            'booked_by_type'      => $appointment->booked_by_type,
            'cancellation_reason' => $appointment->cancellation_reason,
            'cancelled_at'        => $appointment->cancelled_at?->toIso8601String(),
            'no_show_at'          => $appointment->no_show_at?->toIso8601String(),
            'created_at'          => $appointment->created_at?->toIso8601String(),
        ];
    }
}
