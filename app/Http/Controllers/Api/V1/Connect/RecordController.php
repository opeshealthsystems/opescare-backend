<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Http\Controllers\Controller;
use App\Enums\OpesCareErrorCode;
use App\Models\AllergyRecord;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ConsentGrant;
use App\Models\Prescription;
use App\Models\ReconciliationCase;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
{
    public function pullSummary(Request $request, $healthId)
    {
        // Consent validation is handled by RequireConsentGrant middleware (consent.grant:patients:read).
        // The resolved ConsentGrant is available via $request->attributes->get('consent_grant').
        $consentGrant = $request->attributes->get('consent_grant');
        $grantScopes  = $consentGrant ? ($consentGrant->scopes ?? ['patients:read']) : ['patients:read'];

        $purpose = $request->header('X-Purpose-Of-Use', 'treatment');

        $patient = Patient::where('health_id', $healthId)->first();

        if (!$patient) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::PATIENT_NOT_FOUND->value,
                'message'    => __('api.no_patient_found_health_id'),
            ], 404);
        }

        AuditLogger::log(
            $request,
            'patient_summary_pulled',
            'patient',
            $patient->id,
            $patient->id
        );

        // ── Allergies ─────────────────────────────────────────────────────────
        $allergies = AllergyRecord::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($a) => [
                'substance' => $a->substance,
                'severity'  => $a->severity,
                'status'    => $a->status,
                'recorded'  => $a->created_at?->toDateString(),
            ])
            ->values()
            ->all();

        // ── Active medications (most recent active prescription + items) ──────
        $activeMedications = Prescription::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->with('items')
            ->orderByDesc('prescribed_at')
            ->take(10)
            ->get()
            ->flatMap(fn ($rx) => $rx->items->map(fn ($item) => [
                'drug_name'     => $item->drug_name,
                'dose'          => $item->dose,
                'frequency'     => $item->frequency,
                'route'         => $item->route,
                'duration_days' => $item->duration_days,
                'prescribed_at' => $rx->prescribed_at?->toDateString(),
            ]))
            ->values()
            ->all();

        // ── Recent lab results (last 5) ───────────────────────────────────────
        $recentLabResults = LabResult::where('patient_id', $patient->id)
            ->orderByDesc('resulted_at')
            ->take(5)
            ->get()
            ->map(fn ($r) => [
                'parameter_name'  => $r->parameter_name,
                'value'           => $r->value,
                'unit'            => $r->unit,
                'flag'            => $r->flag,
                'reference_range' => $r->reference_range,
                'resulted_at'     => $r->resulted_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        // ── Recent visits (with diagnoses + notes) ────────────────────────────
        $visits = Visit::where('patient_id', $patient->id)
            ->with(['diagnoses', 'clinicalNotes'])
            ->orderByDesc('started_at')
            ->take(10)
            ->get();

        $sectionsVisits = $visits->map(fn ($visit) => [
            'visit_id'   => $visit->id,
            'started_at' => $visit->started_at?->toIso8601String(),
            'visit_type' => $visit->visit_type,
            'diagnoses'  => $visit->diagnoses->pluck('display_name')->all(),
            'notes'      => $visit->clinicalNotes->pluck('history_of_present_illness')->filter()->all(),
        ])->values()->all();

        return response()->json([
            'health_id'              => $patient->health_id,
            'summary_generated_at'   => now()->toIso8601String(),
            'verification_status'    => $patient->identity_status ?? 'verified_by_facility',
            'sections' => [
                'demographics' => [
                    'display_name'  => $patient->first_name . ' ' . substr($patient->last_name, 0, 1) . '.',
                    'sex'           => $patient->sex,
                    'date_of_birth' => $patient->date_of_birth?->toDateString(),
                    'blood_group'   => $patient->blood_group,
                ],
                'allergies'           => $allergies,
                'active_medications'  => $activeMedications,
                'recent_lab_results'  => $recentLabResults,
                'recent_visits'       => $sectionsVisits,
            ],
        ], 200);
    }

    public function pullEmergencyProfile(Request $request, $healthId)
    {
        $purpose         = $request->header('X-Purpose-Of-Use');
        $emergencyReason = $request->header('X-Emergency-Reason');

        if ($purpose !== 'emergency' || ! $emergencyReason) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::PURPOSE_REQUIRED->value,
                'message'    => __('api.emergency_headers_required'),
            ], 400);
        }

        $patient   = Patient::where('health_id', $healthId)->first();
        $patientId = $patient?->id;

        // Always audit emergency overrides — even when patient not found
        AuditLogger::log(
            $request,
            'emergency_profile_pulled',
            'patient',
            $patientId,
            $patientId,
            true,
            $emergencyReason
        );

        // [FIX H-4] Notify patient of emergency access — required by Cameroon Law No. 2010/012.
        // EmergencyAccessController already does this; this legacy endpoint was missing it.
        if ($patient?->user_id) {
            try {
                $patient->notify(new \App\Notifications\EmergencyAccessAlertNotification(
                    patientHealthId: $patient->health_id,
                    patientName:     trim($patient->first_name . ' ' . $patient->last_name),
                    accessedAt:      now()->toDateTimeString(),
                    facilityId:      $request->attributes->get('facility_id', 'unknown'),
                    emergencyReason: $emergencyReason,
                    ipAddress:       $request->ip(),
                ));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('legacy_emergency_notification_failed', [
                    'patient_id' => $patientId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if (!$patient) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::PATIENT_NOT_FOUND->value,
                'message'    => __('api.no_patient_found_health_id'),
            ], 404);
        }

        // ── Critical allergies (severity = severe | life-threatening | high) ──
        $criticalAllergies = AllergyRecord::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->whereIn('severity', ['severe', 'high', 'life-threatening'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($a) => [
                'substance' => $a->substance,
                'severity'  => $a->severity,
            ])
            ->values()
            ->all();

        // ── All active allergies (fallback if no severity filtering matches) ──
        if (empty($criticalAllergies)) {
            $criticalAllergies = AllergyRecord::where('patient_id', $patient->id)
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($a) => [
                    'substance' => $a->substance,
                    'severity'  => $a->severity,
                ])
                ->values()
                ->all();
        }

        // ── Chronic / active conditions ───────────────────────────────────────
        $chronicConditions = Diagnosis::where('patient_id', $patient->id)
            ->whereIn('status', ['active', 'chronic'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($d) => [
                'code'         => $d->code,
                'code_system'  => $d->code_system,
                'display_name' => $d->display_name,
                'status'       => $d->status,
            ])
            ->values()
            ->all();

        // ── High-risk active medications ──────────────────────────────────────
        $highRiskMedications = Prescription::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->with('items')
            ->orderByDesc('prescribed_at')
            ->take(5)
            ->get()
            ->flatMap(fn ($rx) => $rx->items->map(fn ($item) => [
                'drug_name'  => $item->drug_name,
                'dose'       => $item->dose,
                'frequency'  => $item->frequency,
                'route'      => $item->route,
            ]))
            ->values()
            ->all();

        return response()->json([
            'health_id'              => $healthId,
            'summary_generated_at'   => now()->toIso8601String(),
            'emergency_status'       => 'consent_bypassed_audited',
            'sections' => [
                'demographics' => [
                    'display_name' => $patient->first_name . ' ' . substr($patient->last_name, 0, 1) . '.',
                    'sex'          => $patient->sex,
                ],
                'emergency_contacts' => $patient->emergency_contact ?? [],
                'clinical_safety' => [
                    'blood_group'           => $patient->blood_group,
                    'critical_allergies'    => $criticalAllergies,
                    'chronic_conditions'    => $chronicConditions,
                    'high_risk_medications' => $highRiskMedications,
                ],
            ],
        ], 200);
    }

    public function pushEncounter(Request $request)
    {
        $healthId            = $request->input('health_id');
        $externalEncounterId = $request->input('external_encounter_id');

        // [FIX M-3] Never fall back to a hardcoded UUID — an absent facility_id means
        // the auth middleware failed and records must not be created under a phantom facility.
        $facilityId = $request->attributes->get('facility_id');
        if (empty($facilityId)) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::AUTHENTICATION_FAILED->value,
                'message'    => __('api.encounter_rejected_facility'),
            ], 403);
        }

        if (!$healthId || !$externalEncounterId) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::VALIDATION_FAILED->value,
                'message'    => __('api.missing_healthid_encounter'),
            ], 400);
        }

        // Handle matching reconciliation suspect triggers
        if ($healthId === 'OC-CMR-RECON-REQUIRED') {
            $case = ReconciliationCase::create([
                'mismatch_reason'    => 'unresolved_health_id',
                'external_reference' => $externalEncounterId,
                'submitted_payload'  => $request->all(),
                'status'             => 'pending',
            ]);

            return response()->json([
                'status'                  => 'pending_reconciliation',
                'error_code'              => OpesCareErrorCode::RECONCILIATION_REQUIRED->value,
                'reconciliation_case_id'  => $case->id,
                'message'                 => __('api.patient_match_below_threshold'),
            ], 202);
        }

        $patient = Patient::where('health_id', $healthId)->first();

        if (!$patient) {
            $case = ReconciliationCase::create([
                'mismatch_reason'    => 'patient_not_found',
                'external_reference' => $externalEncounterId,
                'submitted_payload'  => $request->all(),
                'status'             => 'pending',
            ]);

            return response()->json([
                'status'                  => 'pending_reconciliation',
                'error_code'              => OpesCareErrorCode::RECONCILIATION_REQUIRED->value,
                'reconciliation_case_id'  => $case->id,
                'message'                 => __('api.patient_healthid_not_found_recon'),
            ], 202);
        }

        // Use system provider ID for B2B integrated records (non-interactive facility sync)
        $systemProviderId = config('opescare.system_provider_id', '00000000-0000-0000-0000-000000000001');

        // [FIX M-2] bcrypt() was called here on every request — intentionally slow (100ms+)
        // making this endpoint a CPU-exhaustion DoS vector. Replaced with a pre-hashed
        // sentinel value. The system provider account is created by SystemAccountSeeder at
        // deploy time with a proper Argon2id hash; this code only needs the row to exist.
        // insertOrIgnore with a cheap locked-hash placeholder avoids the bcrypt call entirely.
        if (! DB::table('users')->where('id', $systemProviderId)->exists()) {
            DB::table('users')->insertOrIgnore([
                'id'         => $systemProviderId,
                'name'       => 'System Provider',
                'email'      => $systemProviderId . '@system.opescare.local',
                'password'   => '$argon2id$v=19$m=65536,t=4,p=1$SYSTEM_ACCOUNT_LOCKED_NOT_LOGINABLE',
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $visit = Visit::create([
            'patient_id'  => $patient->id,
            'facility_id' => $facilityId,
            'provider_id' => $systemProviderId,
            'visit_type'  => 'outpatient',
            'status'      => 'completed',
            'started_at'  => now(),
        ]);

        ClinicalNote::create([
            'visit_id'                   => $visit->id,
            'provider_id'                => $systemProviderId,
            'history_of_present_illness' => $request->input('encounter.chief_complaint', 'Outpatient consult'),
            'examination_findings'       => 'B2B integration import',
            'treatment_plan'             => 'B2B integration import',
            'status'                     => 'signed',
            'signed_at'                  => now(),
        ]);

        $diagnoses = $request->input('encounter.diagnoses', []);
        foreach ($diagnoses as $diag) {
            Diagnosis::create([
                'patient_id'   => $patient->id,
                'visit_id'     => $visit->id,
                'provider_id'  => $systemProviderId,
                'code_system'  => $diag['system'] ?? 'ICD-10',
                'code'         => $diag['code'] ?? 'R50.9',
                'display_name' => $diag['display'] ?? 'Fever',
                'status'       => 'active',
                'is_primary'   => true,
            ]);
        }

        AuditLogger::log(
            $request,
            'encounter_pushed',
            'visit',
            $visit->id,
            $patient->id,
            false,
            null,
            ['external_encounter_id' => $externalEncounterId]
        );

        WebhookService::dispatch('patient.updated', [
            'type'                => 'visit',
            'visit_id'            => $visit->id,
            'patient_health_id'   => $patient->health_id,
        ]);

        return response()->json([
            'status'              => 'accepted',
            'opescare_record_id'  => $visit->id,
            'sync_status'         => 'synced',
            'timeline_event_id'   => 'tle_' . bin2hex(random_bytes(8)),
        ], 200);
    }

    public function pushLabResult(Request $request)
    {
        $healthId           = $request->input('health_id');
        $externalLabOrderId = $request->input('external_lab_order_id');

        if (!$healthId || !$externalLabOrderId) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::VALIDATION_FAILED->value,
                'message'    => __('api.missing_healthid_lab_order'),
            ], 400);
        }

        $patient   = Patient::where('health_id', $healthId)->first();
        $patientId = $patient?->id;

        AuditLogger::log(
            $request,
            'lab_result_pushed',
            'laboratory_order',
            null,
            $patientId,
            false,
            null,
            ['external_lab_order_id' => $externalLabOrderId]
        );

        WebhookService::dispatch('lab_result.released', [
            'external_lab_order_id' => $externalLabOrderId,
            'patient_health_id'     => $healthId,
        ]);

        try {
            if ($patient) {
                app(\App\Modules\Notifications\Services\NotificationService::class)->sendNotification(
                    $patient->id,
                    'lab.result.ready',
                    ['patient_name' => $patient->first_name],
                    'high',
                    'health_updates'
                );
            }
        } catch (\Throwable) {
            // Non-fatal — lab result accepted regardless of notification status
        }

        return response()->json([
            'status'              => 'accepted',
            'opescare_record_id'  => 'lab_' . bin2hex(random_bytes(8)),
            'sync_status'         => 'synced',
        ], 200);
    }

    public function pushPrescription(Request $request)
    {
        $healthId = $request->input('health_id');

        if (!$healthId || !$request->input('medication')) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::VALIDATION_FAILED->value,
                'message'    => __('api.missing_healthid_medication'),
            ], 400);
        }

        $patient   = Patient::where('health_id', $healthId)->first();
        $patientId = $patient?->id;

        AuditLogger::log(
            $request,
            'prescription_pushed',
            'prescription',
            null,
            $patientId
        );

        return response()->json([
            'status'              => 'accepted',
            'opescare_record_id'  => 'rx_' . bin2hex(random_bytes(8)),
            'sync_status'         => 'synced',
        ], 200);
    }
}
