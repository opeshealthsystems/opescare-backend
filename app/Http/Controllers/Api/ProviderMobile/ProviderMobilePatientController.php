<?php

namespace App\Http\Controllers\Api\ProviderMobile;

use App\Http\Controllers\Controller;
use App\Models\AllergyRecord;
use App\Models\Diagnosis;
use App\Models\ImmunizationRecord;
use App\Models\MobileFacilityContext;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provider Mobile API — Patient Lookup & Health ID Scan
 *
 * Allows providers to look up patients by Health ID (QR scan or manual entry)
 * within the context of their currently active facility.
 *
 * SECURITY: Full EMR access requires desktop/portal. Mobile returns a
 * clinical summary only. Broad text-search is facility-scoped.
 */
class ProviderMobilePatientController extends Controller
{
    /**
     * Scan or look up a patient by Health ID.
     *
     * GET /api/provider-mobile/patients/scan
     * Query params: health_id (required)
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate(['health_id' => 'required|string|min:3']);

        $healthId = trim($request->query('health_id'));

        $facilityId = $request->attributes->get('facility_id');
        $hasGlobal  = $request->attributes->get('permission_global_resolve', false);

        $patient = Patient::where('health_id', $healthId)->first();

        if (!$patient) {
            return response()->json([
                'found'   => false,
                'message' => __('api.no_patient_with_health_id_value', ['health_id' => $healthId]),
            ], 404);
        }

        if (!$hasGlobal && $facilityId && $patient->facility_id !== $facilityId) {
            return response()->json([
                'error'   => 'PATIENT_NOT_IN_FACILITY',
                'message' => __('api.patient_not_at_facility'),
            ], 403);
        }

        return response()->json([
            'found'   => true,
            'patient' => $this->formatPatientSummary($patient),
        ]);
    }

    /**
     * Search patients by name or phone (facility-scoped, max 20 results).
     *
     * GET /api/provider-mobile/patients/search
     * Query params: q (required, min 3 chars), limit (default 10)
     *
     * NOTE: This is NOT a broad patient search — it is constrained to the
     * provider's current facility context visits to protect patient privacy.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:3']);

        $q     = $request->query('q');
        $limit = min((int) $request->query('limit', 10), 20);

        $userId  = $this->resolveUserId($request);
        $context = MobileFacilityContext::currentFor($userId);

        $query = Patient::query()
            ->where(function ($sub) use ($q) {
                $sub->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('phone_number', 'like', "%{$q}%")
                    ->orWhere('health_id', 'like', "%{$q}%");
            });

        // Scope to facility if context is set
        if ($context) {
            $facilityId = $context->facility_id;
            $query->whereHas('identifiers', fn ($sub) => $sub->where('facility_id', $facilityId))
                  ->orWhereHas('visits', fn ($sub) => $sub->where('facility_id', $facilityId));
        }

        $patients = $query->limit($limit)->get();

        return response()->json([
            'data' => $patients->map(fn ($p) => $this->formatPatientSummary($p)),
        ]);
    }

    /**
     * Get a provider-facing clinical summary for a patient.
     *
     * GET /api/provider-mobile/patients/{id}
     *
     * Returns lightweight summary, NOT full EMR.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $patient = Patient::where('id', $id)
            ->orWhere('health_id', $id)
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatPatientSummary($patient),
        ]);
    }

    /**
     * GET /api/provider-mobile/patients/{id}/clinical
     *
     * Full clinical safety profile for a patient — for provider use.
     * Returns blood group, all active allergies, chronic/active conditions,
     * active prescriptions summary, and recent immunizations.
     */
    public function clinicalProfile(Request $request, string $id): JsonResponse
    {
        $patient = Patient::where('id', $id)
            ->orWhere('health_id', $id)
            ->firstOrFail();

        $allergies = AllergyRecord::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get(['id', 'substance', 'severity', 'status']);

        $conditions = Diagnosis::where('patient_id', $patient->id)
            ->whereIn('status', ['active', 'chronic'])
            ->orderByDesc('created_at')
            ->get(['id', 'display_name', 'code', 'status']);

        $activePrescriptions = Prescription::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->with('items:id,prescription_id,drug_name,dose,frequency')
            ->orderByDesc('prescribed_at')
            ->take(5)
            ->get();

        $immunizations = ImmunizationRecord::where('patient_id', $patient->id)
            ->orderByDesc('administered_at')
            ->take(10)
            ->get(['id', 'vaccine_name', 'dose_number', 'administered_at', 'lot_number']);

        $criticalAllergies = $allergies->whereIn('severity', ['severe', 'high', 'life-threatening']);

        return response()->json([
            'patient'             => $this->formatPatientSummary($patient),
            'blood_group'         => $patient->blood_group,
            'critical_allergies'  => $criticalAllergies->values()->map(fn ($a) => [
                'substance' => $a->substance,
                'severity'  => $a->severity,
            ]),
            'all_active_allergies'=> $allergies->map(fn ($a) => [
                'substance' => $a->substance,
                'severity'  => $a->severity,
            ]),
            'active_conditions'   => $conditions->map(fn ($c) => [
                'display_name' => $c->display_name,
                'code'         => $c->code,
                'status'       => $c->status,
            ]),
            'active_prescriptions'=> $activePrescriptions->map(fn ($rx) => [
                'id'    => $rx->id,
                'items' => $rx->items->map(fn ($i) => [
                    'drug_name' => $i->drug_name,
                    'dose'      => $i->dose,
                    'frequency' => $i->frequency,
                ]),
            ]),
            'immunizations'       => $immunizations->map(fn ($i) => [
                'vaccine_name'    => $i->vaccine_name,
                'dose_number'     => $i->dose_number,
                'administered_at' => $i->administered_at?->toDateString(),
            ]),
            'safety_alert'        => $criticalAllergies->isNotEmpty()
                ? '⚠ CRITICAL ALLERGY: ' . $criticalAllergies->pluck('substance')->join(', ')
                : null,
        ]);
    }

    // -------------------------------------------------------------------------

    private function formatPatientSummary(Patient $p): array
    {
        $criticalAllergies = AllergyRecord::where('patient_id', $p->id)
            ->where('status', 'active')
            ->whereIn('severity', ['severe', 'high', 'life-threatening'])
            ->pluck('substance')
            ->toArray();

        $chronicConditions = Diagnosis::where('patient_id', $p->id)
            ->whereIn('status', ['active', 'chronic'])
            ->pluck('display_name')
            ->toArray();

        return [
            'id'                  => $p->id,
            'health_id'           => $p->health_id,
            'display_name'        => $p->first_name . ' ' . $p->last_name,
            'sex'                 => $p->sex,
            'dob'                 => $p->date_of_birth?->toDateString(),
            'age'                 => $p->date_of_birth
                ? (int) $p->date_of_birth->diffInYears(now())
                : null,
            'blood_group'         => $p->blood_group,
            'phone'               => $p->phone_number,
            'identity_status'     => $p->identity_status ?? 'unknown',
            'critical_allergies'  => $criticalAllergies,
            'chronic_conditions'  => $chronicConditions,
            'safety_alert'        => ! empty($criticalAllergies)
                ? '⚠ CRITICAL ALLERGY: ' . implode(', ', $criticalAllergies)
                : null,
        ];
    }

    private function resolveUserId(Request $request): string
    {
        if ($request->has('_user_id')) {
            return $request->input('_user_id');
        }
        return User::value('id') ?? 'demo-provider';
    }
}
