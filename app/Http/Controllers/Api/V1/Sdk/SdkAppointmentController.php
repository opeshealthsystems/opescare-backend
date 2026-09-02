<?php

namespace App\Http\Controllers\Api\V1\Sdk;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SdkAppointmentController extends Controller
{
    /**
     * Book an appointment via SDK token.
     */
    public function book(Request $request): JsonResponse
    {
        $data = $request->validate([
            'health_id'    => 'required|string',
            'facility_id'  => 'required|uuid',
            'scheduled_at' => 'required|date|after:now',
            'reason'       => 'nullable|string|max:500',
            'appointment_type' => 'nullable|string|max:50',
        ]);

        $patient = Patient::where('health_id', $data['health_id'])->first();

        if (!$patient) {
            return response()->json(['error' => 'patient_not_found', 'message' => __('api.no_patient_with_health_id')], 404);
        }

        $appointment = Appointment::create([
            'patient_id'       => $patient->id,
            'facility_id'      => $data['facility_id'],
            'scheduled_at'     => $data['scheduled_at'],
            'reason'           => $data['reason'] ?? null,
            'appointment_type' => $data['appointment_type'] ?? 'general',
            'status'           => 'scheduled',
            'booked_via'       => 'sdk',
            'booked_by'        => $request->attributes->get('sdk_client_id'),
        ]);

        return response()->json([
            'appointment_id' => $appointment->id,
            'status'         => $appointment->status,
            'scheduled_at'   => $appointment->scheduled_at->toIso8601String(),
            'patient'        => $data['health_id'],
            'facility_id'    => $data['facility_id'],
        ], 201);
    }

    /**
     * Get an appointment by ID.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['error' => 'not_found', 'message' => __('api.appointment_not_found')], 404);
        }

        return response()->json([
            'appointment_id'   => $appointment->id,
            'patient_id'       => $appointment->patient_id,
            'facility_id'      => $appointment->facility_id,
            'scheduled_at'     => $appointment->scheduled_at?->toIso8601String(),
            'status'           => $appointment->status,
            'appointment_type' => $appointment->appointment_type ?? null,
            'reason'           => $appointment->reason ?? null,
        ]);
    }
}
