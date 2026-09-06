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
use App\Support\PatientVocabulary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

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
            'photo_url'         => \App\Support\PatientPhoto::urlFor($patient),
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

        // One shape for the patient's own card and a dependent's card
        // (family member detail) — see HealthIdCardPayload.
        // A read-only guardian acting for a dependent sees the card but gets no
        // QR to present with — the same rule the family member detail applies.
        $link   = $request->attributes->get('family_link');
        $withQr = ! ($link instanceof \App\Models\FamilyLink && $link->isReadOnly());

        return response()->json(\App\Support\HealthIdCardPayload::for($patient, withQr: $withQr));
    }

    /**
     * Get the patient's clinical timeline (visits, labs, prescriptions, appointments).
     *
     * GET /api/mobile/timeline
     * Query params: limit (default 20)
     *
     * ── `summary` vs `summary_label` ──────────────────────────────────────
     *
     * `summary` is the English string this endpoint has always emitted —
     * "Prescription — 3 item(s)". It is unchanged, because clients in the field
     * parse it (the records screen splits on the em dash to recover the count
     * and the visit type).
     *
     * It was also being printed verbatim as a row title on the home screen, so
     * a French-speaking patient read "Prescription — 1 item(s)" in the middle
     * of an otherwise French app — with the lazy "(s)" plural that English
     * readers got too. `summary_label` is that same line in the patient's
     * language with a real plural, and `detail_label` is just its tail, for a
     * row whose title already names the kind of entry. Both are built from
     * lang/{en,fr}/patient_timeline.php, so the wording lives in one place
     * rather than being re-derived on each screen.
     */
    public function getTimeline(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $limit     = min((int) $request->query('limit', 20), 100);
        $locale    = PatientVocabulary::locale($request);

        $events = collect();

        // Visits
        $visits = Visit::where('patient_id', $patientId)
            ->with('facility:id,name')
            ->latest('created_at')
            ->take($limit)
            ->get();

        foreach ($visits as $v) {
            $visitType = $v->visit_type ?? 'outpatient';
            // An unlisted visit type is passed through as recorded, never
            // renamed from the shape of its token.
            $visitTypeLabel = Lang::has('patient_timeline.visit_type.' . $visitType, $locale)
                ? __('patient_timeline.visit_type.' . $visitType, [], $locale)
                : $visitType;

            $events->push([
                'event_type'    => 'visit',
                'id'            => $v->id,
                'facility_name' => $v->facility?->name,
                'occurred_at'   => $v->created_at->toIso8601String(),
                'summary'       => 'Clinical visit — ' . $visitType,
                'summary_label' => __('patient_timeline.visit', ['type' => $visitTypeLabel], $locale),
                'detail_label'  => $visitTypeLabel,
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
                // The test name is clinical data and is never translated.
                'summary_label' => __('patient_timeline.lab_result', ['test' => $l->test_name], $locale),
                'detail_label'  => $l->test_name,
            ]);
        }

        // Prescriptions
        $prescriptions = Prescription::where('patient_id', $patientId)
            ->with(['facility:id,name', 'items'])
            ->latest('prescribed_at')
            ->take($limit)
            ->get();

        foreach ($prescriptions as $p) {
            $itemCount = $p->items->count();

            $events->push([
                'event_type'    => 'prescription',
                'id'            => $p->id,
                'facility_name' => $p->facility?->name,
                'occurred_at'   => $p->prescribed_at->toIso8601String(),
                'summary'       => 'Prescription — ' . $itemCount . ' item(s)',
                'summary_label' => trans_choice('patient_timeline.prescription', $itemCount, ['count' => $itemCount], $locale),
                'detail_label'  => trans_choice('patient_timeline.detail_prescription', $itemCount, ['count' => $itemCount], $locale),
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

        // Clinical events are immutable: a mistake is marked entered-in-error, never
        // deleted. Showing those back to the patient would present retracted data as
        // a live condition, so they are excluded here (resolved history is kept).
        $conditions = Diagnosis::where('patient_id', $patientId)
            ->where('status', '!=', 'entered-in-error')
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

        // Same rule as conditions: a retracted dose must not read as administered.
        $immunizations = ImmunizationRecord::where('patient_id', $patientId)
            ->where('status', '!=', 'entered_in_error')
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
            'photo_url'         => \App\Support\PatientPhoto::urlFor($patient),
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
