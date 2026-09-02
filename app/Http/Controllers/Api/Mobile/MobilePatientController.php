<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\ImmunizationRecord;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — Patient Profile & Timeline
 */
class MobilePatientController extends Controller
{
    /**
     * Get the authenticated patient's profile.
     *
     * GET /api/mobile/me
     */
    public function getMe(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $patient   = Patient::find($patientId);

        if (!$patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $allergiesCount   = AllergyRecord::where('patient_id', $patient->id)->where('status', 'active')->count();
        $conditionsCount  = Diagnosis::where('patient_id', $patient->id)->whereIn('status', ['active', 'chronic'])->count();

        return response()->json([
            'health_id'         => $patient->health_id,
            'display_name'      => trim($patient->first_name . ' ' . substr($patient->last_name, 0, 1) . '.'),
            'first_name'        => $patient->first_name,
            'last_name'         => $patient->last_name,
            'phone'             => $patient->phone_number,
            'email'             => $patient->email ?? null,
            'dob'               => $patient->date_of_birth?->toDateString(),
            'sex'               => $patient->sex,
            'blood_group'       => $patient->blood_group,
            'address'           => $patient->address,
            'emergency_contact' => $patient->emergency_contact,
            'status'            => $patient->identity_status ?? 'active',
            'allergies_count'   => $allergiesCount,
            'conditions_count'  => $conditionsCount,
        ]);
    }

    /**
     * Get the patient's digital Health ID card details (for wallet display).
     *
     * GET /api/mobile/health-id-card
     */
    public function getHealthIdCard(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $patient   = Patient::find($patientId);

        if (!$patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $qrPayload = base64_encode(json_encode([
            'hid' => $patient->health_id,
            'fn'  => $patient->first_name,
            'ln'  => substr($patient->last_name, 0, 1),
            'dob' => $patient->date_of_birth?->toDateString(),
            'sex' => $patient->sex,
            'ts'  => now()->toIso8601String(),
        ]));

        return response()->json([
            'health_id'    => $patient->health_id,
            'display_name' => $patient->first_name . ' ' . $patient->last_name,
            'sex'          => $patient->sex,
            'dob'          => $patient->date_of_birth?->toDateString(),
            'blood_type'   => $patient->blood_group,
            'qr_payload'   => $qrPayload,
            'status'       => $patient->identity_status ?? 'active',
        ]);
    }

    /**
     * Get the patient's clinical timeline (visits, labs, prescriptions, appointments).
     *
     * GET /api/mobile/timeline
     * Query params: limit (default 20)
     */
    public function getTimeline(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $limit     = min((int) $request->query('limit', 20), 100);

        $events = collect();

        // Visits
        $visits = Visit::where('patient_id', $patientId)
            ->with('facility:id,name')
            ->latest('created_at')
            ->take($limit)
            ->get();

        foreach ($visits as $v) {
            $events->push([
                'event_type'    => 'visit',
                'id'            => $v->id,
                'facility_name' => $v->facility?->name,
                'occurred_at'   => $v->created_at->toIso8601String(),
                'summary'       => 'Clinical visit — ' . ($v->visit_type ?? 'outpatient'),
            ]);
        }

        // Lab orders (resulted)
        $labs = LabOrder::where('patient_id', $patientId)
            ->where('status', 'resulted')
            ->with('facility:id,name')
            ->latest('resulted_at')
            ->take($limit)
            ->get();

        foreach ($labs as $l) {
            $events->push([
                'event_type'    => 'lab_result',
                'id'            => $l->id,
                'facility_name' => $l->facility?->name,
                'occurred_at'   => $l->resulted_at?->toIso8601String() ?? $l->ordered_at->toIso8601String(),
                'summary'       => 'Lab result — ' . $l->test_name,
            ]);
        }

        // Prescriptions
        $prescriptions = Prescription::where('patient_id', $patientId)
            ->with(['facility:id,name', 'items'])
            ->latest('prescribed_at')
            ->take($limit)
            ->get();

        foreach ($prescriptions as $p) {
            $events->push([
                'event_type'    => 'prescription',
                'id'            => $p->id,
                'facility_name' => $p->facility?->name,
                'occurred_at'   => $p->prescribed_at->toIso8601String(),
                'summary'       => 'Prescription — ' . $p->items->count() . ' item(s)',
            ]);
        }

        // Sort all events newest-first and take up to $limit
        $timeline = $events->sortByDesc('occurred_at')->take($limit)->values();

        return response()->json(['timeline' => $timeline]);
    }

    /**
     * GET /api/mobile/allergies
     * Patient's active allergy list for mobile display.
     */
    public function getAllergies(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        if (! $patientId) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $allergies = AllergyRecord::where('patient_id', $patientId)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get(['id', 'substance', 'severity', 'status', 'created_at']);

        return response()->json([
            'blood_group' => Patient::find($patientId)?->blood_group,
            'allergies'   => $allergies->map(fn ($a) => [
                'id'        => $a->id,
                'substance' => $a->substance,
                'severity'  => $a->severity,
                'status'    => $a->status,
                'recorded'  => $a->created_at?->toDateString(),
            ]),
        ]);
    }

    /**
     * GET /api/mobile/clinical
     * Patient's active diagnoses and chronic conditions.
     */
    public function getClinical(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        if (! $patientId) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $conditions = Diagnosis::where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get(['id', 'display_name', 'code', 'code_system', 'snomed_code', 'status', 'created_at']);

        return response()->json([
            'conditions' => $conditions->map(fn ($c) => [
                'id'           => $c->id,
                'display_name' => $c->display_name,
                'code'         => $c->code,
                'code_system'  => $c->code_system,
                'status'       => $c->status,
                'recorded'     => $c->created_at?->toDateString(),
            ]),
        ]);
    }

    /**
     * GET /api/mobile/immunizations
     * Patient's vaccination history.
     */
    public function getImmunizations(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        if (! $patientId) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $immunizations = ImmunizationRecord::where('patient_id', $patientId)
            ->orderByDesc('administered_at')
            ->get(['id', 'vaccine_name', 'lot_number', 'dose_number', 'administered_at', 'status']);

        return response()->json([
            'immunizations' => $immunizations->map(fn ($i) => [
                'id'              => $i->id,
                'vaccine_name'    => $i->vaccine_name,
                'lot_number'      => $i->lot_number,
                'dose_number'     => $i->dose_number,
                'administered_at' => $i->administered_at?->toDateString(),
                'status'          => $i->status ?? 'completed',
            ]),
        ]);
    }

    /**
     * Update the authenticated patient's own profile fields.
     * PATCH /api/mobile/me
     */
    public function updateMe(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $patient   = Patient::find($patientId);

        if (!$patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $validated = $request->validate([
            'first_name'                        => 'sometimes|string|max:100',
            'last_name'                         => 'sometimes|string|max:100',
            'blood_group'                       => 'sometimes|nullable|string|max:10|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'sex'                                => 'sometimes|nullable|string|in:male,female',
            'address'                            => 'sometimes|nullable|string|max:500',
            'emergency_contact'                  => 'sometimes|nullable|array',
            'emergency_contact.name'             => 'required_with:emergency_contact|string|max:120',
            'emergency_contact.relationship'     => 'required_with:emergency_contact|string|max:80',
            'emergency_contact.phone'            => 'required_with:emergency_contact|string|max:30',
        ]);

        $patient->update($validated);

        $allergiesCount  = \App\Models\AllergyRecord::where('patient_id', $patient->id)->where('status', 'active')->count();
        $conditionsCount = \App\Models\Diagnosis::where('patient_id', $patient->id)->whereIn('status', ['active', 'chronic'])->count();

        return response()->json([
            'health_id'         => $patient->health_id,
            'display_name'      => trim($patient->first_name . ' ' . substr($patient->last_name, 0, 1) . '.'),
            'first_name'        => $patient->first_name,
            'last_name'         => $patient->last_name,
            'phone'             => $patient->phone_number,
            'email'             => $patient->email,
            'dob'               => $patient->date_of_birth?->toDateString() ?? null,
            'sex'               => $patient->sex,
            'blood_group'       => $patient->blood_group,
            'address'           => $patient->address,
            'emergency_contact' => $patient->emergency_contact,
            'status'            => $patient->identity_status ?? 'active',
            'allergies_count'   => $allergiesCount,
            'conditions_count'  => $conditionsCount,
        ]);
    }

    // -------------------------------------------------------------------------

    private function resolvePatientId(Request $request): ?string
    {
        return $request->attributes->get('patient_id');
    }
}
