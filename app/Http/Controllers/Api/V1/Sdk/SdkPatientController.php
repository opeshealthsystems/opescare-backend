<?php

namespace App\Http\Controllers\Api\V1\Sdk;

use App\Http\Controllers\Controller;
use App\Models\AllergyRecord;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SdkPatientController extends Controller
{
    /**
     * Return a patient's clinical summary by health_id.
     */
    public function summary(Request $request, string $health_id): JsonResponse
    {
        $patient = Patient::where('health_id', $health_id)->first();

        if (!$patient) {
            return response()->json(['error' => 'patient_not_found', 'message' => __('api.no_patient_with_health_id')], 404);
        }

        $allergies = AllergyRecord::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->get(['substance', 'severity'])
            ->map(fn ($a) => ['substance' => $a->substance, 'severity' => $a->severity]);

        return response()->json([
            'health_id'     => $patient->health_id,
            'full_name'     => $patient->full_name ?? ($patient->first_name . ' ' . $patient->last_name),
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'sex'           => $patient->sex,
            'blood_group'   => $patient->blood_group,
            'allergies'     => $allergies,
            'retrieved_at'  => now()->toIso8601String(),
        ]);
    }

    /**
     * Return paginated encounter history for a patient.
     */
    public function encounters(Request $request, string $health_id): JsonResponse
    {
        $patient = Patient::where('health_id', $health_id)->first();

        if (!$patient) {
            return response()->json(['error' => 'patient_not_found', 'message' => __('api.no_patient_with_health_id')], 404);
        }

        $encounters = MedicalRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'visit_type', 'chief_complaint', 'diagnosis', 'created_at']);

        return response()->json([
            'health_id'  => $health_id,
            'encounters' => $encounters->map(fn($e) => [
                'id'             => $e->id,
                'visit_type'     => $e->visit_type,
                'chief_complaint'=> $e->chief_complaint,
                'diagnosis'      => $e->diagnosis,
                'date'           => $e->created_at->toDateString(),
            ]),
            'retrieved_at' => now()->toIso8601String(),
        ]);
    }
}
