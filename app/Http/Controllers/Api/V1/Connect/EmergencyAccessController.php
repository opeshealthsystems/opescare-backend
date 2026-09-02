<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Enums\AuditEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Connect\PullEmergencyProfileRequest;
use App\Models\AllergyRecord;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Notifications\EmergencyAccessAlertNotification;
use App\Services\Identity\HealthIdAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmergencyAccessController extends Controller
{
    public function __construct(private readonly HealthIdAuditLogger $auditor) {}

    public function pullEmergencyProfile(PullEmergencyProfileRequest $request)
    {
        $validated = $request->validated();

        // 1. Resolve patient
        $patient = Patient::where('health_id', $validated['health_id'])->first();

        if (! $patient) {
            $this->auditor->denied(
                request:   $request,
                eventType: AuditEventType::EmergencyAccessDenied,
                healthId:  $validated['health_id'],
                notes:     'HEALTH_ID_NOT_FOUND | reason: ' . $validated['reason'],
            );

            return response()->json([
                'status'     => 'invalid',
                'error_code' => 'HEALTH_ID_NOT_FOUND',
                'message'    => __('api.health_id_unverified'),
            ], 404);
        }

        // 2. Audit log — must fire BEFORE data is returned [MINSANTE CRITICAL]
        $this->auditor->record(
            request:   $request,
            eventType: AuditEventType::PullEmergencyProfile,
            result:    'success',
            healthId:  $patient->health_id,
            patientId: $patient->id,
            purpose:   'emergency_access',
            notes:     'Emergency reason: ' . $validated['reason'],
        );

        // 3. Alert patient asynchronously (Law No. 2010/012 — patients must be informed)
        $facilityId = $request->attributes->get('facility_id', 'unknown');
        if ($patient->user_id) {
            try {
                $patient->notify(new EmergencyAccessAlertNotification(
                    patientHealthId: $patient->health_id,
                    patientName:     trim($patient->first_name . ' ' . $patient->last_name),
                    accessedAt:      now()->toDateTimeString(),
                    facilityId:      $facilityId,
                    emergencyReason: $validated['reason'],
                    ipAddress:       $request->ip(),
                ));
            } catch (\Throwable $e) {
                Log::warning(AuditEventType::EmergencyAccessPatientNotified->value . '_failed', [
                    'patient_id' => $patient->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // 4. Fetch clinical data
        $allergies = AllergyRecord::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->get(['substance', 'severity', 'status'])
            ->toArray();

        $chronicConditions = Diagnosis::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->get(['code', 'display_name'])
            ->toArray();

        // 5. Build and return profile
        return response()->json([
            'status'  => 'success',
            'message' => __('api.emergency_profile_retrieved'),
            'profile' => [
                'identity' => [
                    'health_id'     => $patient->health_id,
                    'first_name'    => $patient->first_name,
                    'last_name'     => $patient->last_name,
                    'sex'           => $patient->sex,
                    'date_of_birth' => $patient->date_of_birth?->toDateString(),
                ],
                'emergency_contact'  => $patient->emergency_contact ?? 'Not provided',
                // blood_type intentionally null — a wrong blood type in emergency is lethal.
                // Store in PatientIdentityProfile when clinically confirmed.
                'blood_type'         => null,
                'allergies'          => $allergies,
                'chronic_conditions' => $chronicConditions,
            ],
        ], 200);
    }
}
