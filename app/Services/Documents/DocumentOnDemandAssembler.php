<?php

namespace App\Services\Documents;

use App\Models\Patient;
use App\Models\Admission;
use App\Models\NursingRound;
use App\Models\Prescription;
use App\Models\ClinicalNote;
use Carbon\Carbon;

/**
 * Assembles the data behind the on-demand ward forms (surgical safety
 * checklist, nursing admission assessment, investigation request, fall risk,
 * pressure ulcer risk, wound care chart).
 *
 * Every human-readable string goes through lang/{en,fr}/document.php and every
 * date through Carbon::isoFormat(), so the form comes out in the locale the
 * request resolved (App\Http\Middleware\SetLocale). Instrument names and
 * subscale labels (Morse, Braden) are kept to their published wording; where a
 * French form was not verified the lang file says so and keeps the English.
 */
class DocumentOnDemandAssembler
{
    /**
     * Resolve the active (or specified) admission for a patient.
     */
    private function resolveAdmission(string $patientId, ?string $admissionId): ?Admission
    {
        if ($admissionId) {
            return Admission::where('id', $admissionId)
                ->where('patient_id', $patientId)
                ->first();
        }

        return Admission::where('patient_id', $patientId)
            ->where('status', 'active')
            ->latest('admitted_at')
            ->first()
            ?? Admission::where('patient_id', $patientId)
                ->latest('admitted_at')
                ->first();
    }

    private function patientName(?Patient $patient): string
    {
        return $patient
            ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))
            : __('document.unknown_patient');
    }

    /**
     * Method 1: Surgical Safety Checklist
     */
    public function assembleSurgicalSafetyChecklist(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);

        $patientName = $this->patientName($patient);

        $healthId = $patient->health_id ?? __('document.na');

        $allergiesKnown = __('document.none_known');
        if ($patient) {
            try {
                $allergyNames = $patient->allergies()->pluck('substance')->filter()->implode(', ');
                if ($allergyNames) {
                    $allergiesKnown = $allergyNames;
                }
            } catch (\Throwable $e) {
                $allergiesKnown = __('document.none_known');
            }
        }

        return [
            'procedure'      => $admission->admission_reason ?? __('document.ssc_scheduled_procedure'),
            'scheduled_time' => Carbon::now()->isoFormat('DD MMM YYYY, HH:mm'),
            'surgeon'        => __('document.role_attending_surgeon'),
            'anaesthetist'   => __('document.role_anaesthetist'),
            'scrub_nurse'    => __('document.role_scrub_nurse'),
            'sign_in'        => [
                'identity_confirmed' => false,
                'site_marked'        => false,
                'consent_obtained'   => false,
                'anaesthesia_check'  => false,
                'pulse_oximeter'     => false,
                'allergies_known'    => $allergiesKnown,
                'difficult_airway'   => false,
                'aspiration_risk'    => false,
                'blood_loss_risk'    => __('document.unknown'),
            ],
            'time_out'       => [
                'team_introduced'   => false,
                'patient_identity'  => $patientName . ' — ' . $healthId,
                'procedure_confirmed' => false,
                'site_confirmed'    => '',
                'antibiotics_given' => __('document.pending'),
                'imaging_displayed' => false,
                'critical_steps_shared' => '',
            ],
            'sign_out'       => [
                'procedure_recorded'  => false,
                'instrument_count_ok' => false,
                'specimen_labelled'   => __('document.none'),
                'equipment_issues'    => __('document.none'),
                'recovery_concerns'   => '',
            ],
            'completed_at'   => null,
        ];
    }

    /**
     * Method 2: Nursing Admission Assessment
     */
    public function assembleNursingAdmissionAssessment(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);

        // Admission date
        $admissionDate = Carbon::today()->isoFormat('DD MMM YYYY');
        if ($admission && $admission->admitted_at) {
            try {
                $admissionDate = Carbon::parse($admission->admitted_at)->isoFormat('DD MMM YYYY, HH:mm');
            } catch (\Throwable $e) {
                // keep default
            }
        }

        // Vital signs
        $notRecorded = __('document.not_recorded');
        $vitalSigns = [
            'bp'     => $notRecorded,
            'pulse'  => $notRecorded,
            'temp'   => $notRecorded,
            'rr'     => $notRecorded,
            'spo2'   => $notRecorded,
            'weight' => $notRecorded,
            'height' => $notRecorded,
            'bmi'    => $notRecorded,
        ];

        if ($patient) {
            try {
                /** @var \App\Models\TriageVitalSign|null $latestVital */
                $latestVital = $patient->vitals()->latest()->first();
                if ($latestVital) {
                    $systolic  = $latestVital->systolic_bp;
                    $diastolic = $latestVital->diastolic_bp;
                    if ($systolic !== null && $diastolic !== null) {
                        $vitalSigns['bp'] = $systolic . '/' . $diastolic . ' mmHg';
                    }
                    if ($latestVital->pulse_rate !== null) {
                        $vitalSigns['pulse'] = $latestVital->pulse_rate . ' bpm';
                    }
                    if ($latestVital->temperature !== null) {
                        $vitalSigns['temp'] = $latestVital->temperature . '°C';
                    }
                    if ($latestVital->respiratory_rate !== null) {
                        $vitalSigns['rr'] = $latestVital->respiratory_rate . '/min';
                    }
                    if ($latestVital->oxygen_saturation !== null) {
                        $vitalSigns['spo2'] = $latestVital->oxygen_saturation . '%';
                    }
                    if ($latestVital->weight_kg !== null) {
                        $vitalSigns['weight'] = $latestVital->weight_kg . ' kg';
                    }
                    if ($latestVital->height_cm !== null) {
                        $vitalSigns['height'] = $latestVital->height_cm . ' cm';
                    }
                    // BMI calculation
                    if ($latestVital->weight_kg !== null && $latestVital->height_cm !== null && (float) $latestVital->height_cm > 0) {
                        $heightM = (float) $latestVital->height_cm / 100;
                        $bmi     = round((float) $latestVital->weight_kg / ($heightM * $heightM), 1);
                        $vitalSigns['bmi'] = (string) $bmi;
                    }
                }
            } catch (\Throwable $e) {
                // keep defaults
            }
        }

        // Allergies
        $allergies = [];
        if ($patient) {
            try {
                foreach ($patient->allergies()->get() as $allergy) {
                    $allergies[] = [
                        'allergen'    => $allergy->substance ?? __('document.unknown'),
                        'reaction'    => __('document.naa_reaction'),
                        'severity'    => __('document.unknown'),
                        'recorded_by' => __('document.staff'),
                    ];
                }
            } catch (\Throwable $e) {
                $allergies = [];
            }
        }

        // Current medications from latest prescription
        $currentMedications = [];
        if ($patient) {
            try {
                $latestPrescription = Prescription::where('patient_id', $patientId)
                    ->latest('prescribed_at')
                    ->first();
                if ($latestPrescription) {
                    foreach ($latestPrescription->items()->get() as $item) {
                        $currentMedications[] = [
                            'drug_name'     => $item->drug_name    ?? __('document.unknown'),
                            'drug_code'     => $item->drug_code    ?? '',
                            'dose'          => $item->dose         ?? __('document.not_specified'),
                            'frequency'     => $item->frequency    ?? __('document.not_specified'),
                            'route'         => $item->route        ?? __('document.not_specified'),
                            'duration_days' => $item->duration_days ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $currentMedications = [];
            }
        }

        return [
            'admission_date'        => $admissionDate,
            'admission_mode'        => $admission->admission_reason ?? __('document.naa_admission_mode_emergency'),
            'ward'                  => __('document.ward'),
            'bed_number'            => $admission->bed_id ?? __('document.not_assigned'),
            'chief_complaint'       => $admission->admission_reason ?? __('document.not_documented'),
            'vital_signs_on_admission' => $vitalSigns,
            'allergies'             => $allergies,
            'past_medical_history'  => __('document.not_documented'),
            'current_medications'   => $currentMedications,
            'pain_score'            => null,
            'pain_location'         => '',
            'nutritional_screen'    => [
                'must_score'        => 0,
                'nutritional_risk'  => __('document.pending_assessment'),
                'diet_on_admission' => __('document.diet_normal'),
            ],
            'fall_risk'             => [
                'morse_score'   => 0,
                'risk_level'    => __('document.pending_assessment'),
                'interventions' => [],
            ],
            'pressure_ulcer_risk'   => [
                'braden_score'   => 0,
                'risk_level'     => __('document.pending_assessment'),
                'skin_condition' => __('document.skin_intact'),
            ],
            'social_history'        => __('document.not_documented'),
            'admitting_nurse'       => __('document.role_admitting_nurse'),
        ];
    }

    /**
     * Method 3: Investigation Request
     */
    public function assembleInvestigationRequest(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);

        // Attempt to load LabOrder if the model exists
        $investigations = [];
        try {
            if (class_exists(\App\Models\LabOrder::class)) {
                $labOrders = \App\Models\LabOrder::where('patient_id', $patientId)->get();
                foreach ($labOrders as $order) {
                    $investigations[] = [
                        'department'           => __('document.laboratory'),
                        'test'                 => $order->test_name ?? __('document.inv_test_default'),
                        'tube'                 => __('document.inv_tube_as_required'),
                        'fasting'              => false,
                        'special_instructions' => '',
                    ];
                }
            }
        } catch (\Throwable $e) {
            $investigations = [];
        }

        return [
            'urgency'              => __('document.urgency_routine'),
            'requesting_ward'      => $admission->bed_id ?? __('document.ward'),
            'clinical_indication'  => $admission->admission_reason ?? __('document.inv_indication_default'),
            'investigations'       => $investigations,
            'sample_taken_by'      => __('document.role_requesting_clinician'),
            'sample_taken_at'      => Carbon::now()->isoFormat('DD MMM YYYY, HH:mm'),
            'expected_turnaround'  => __('document.inv_turnaround_default'),
        ];
    }

    /**
     * Method 4: Fall Risk Assessment (Morse Fall Scale)
     */
    public function assembleFallRiskAssessment(string $patientId, ?string $admissionId = null): array
    {
        $latestRound = NursingRound::where('patient_id', $patientId)
            ->latest('round_time')
            ->first();

        // Try to extract morse score from nursing round JSON fields
        $morseScore = 0;
        if ($latestRound) {
            try {
                $vitalJson       = is_array($latestRound->vital_signs)    ? $latestRound->vital_signs    : json_decode($latestRound->vital_signs ?? '{}', true) ?? [];
                $interventionJson = is_array($latestRound->interventions) ? $latestRound->interventions : json_decode($latestRound->interventions ?? '{}', true) ?? [];

                $morseScore = (int) ($vitalJson['fall_risk_morse_score']       ?? $interventionJson['fall_risk_morse_score'] ?? 0);
            } catch (\Throwable $e) {
                $morseScore = 0;
            }
        }

        return [
            'assessment_date'  => Carbon::today()->isoFormat('DD MMM YYYY'),
            'assessment_tool'  => __('document.fall_tool'),
            'morse_items'      => [
                ['item' => __('document.fall_item_history'),      'score' => 0],
                ['item' => __('document.fall_item_secondary_dx'), 'score' => 0],
                ['item' => __('document.fall_item_aid'),          'score' => 0],
                ['item' => __('document.fall_item_iv'),           'score' => 0],
                ['item' => __('document.fall_item_gait'),         'score' => 0],
                ['item' => __('document.fall_item_mental'),       'score' => 0],
            ],
            'total_morse_score' => $morseScore,
            'risk_level'        => __('document.pending_assessment'),
            'interventions'     => [__('document.fall_pending_intervention')],
            'reassessment_due'  => Carbon::tomorrow()->isoFormat('DD MMM YYYY'),
            'assessed_by'       => __('document.role_nursing_staff'),
        ];
    }

    /**
     * Method 5: Pressure Ulcer Assessment (Braden Scale)
     */
    public function assemblePressureUlcerAssessment(string $patientId, ?string $admissionId = null): array
    {
        // Query latest nursing round (unused data kept for future enrichment)
        NursingRound::where('patient_id', $patientId)
            ->latest('round_time')
            ->first();

        $pending = __('document.pending');

        return [
            'assessment_date'  => Carbon::today()->isoFormat('DD MMM YYYY'),
            'assessment_tool'  => __('document.braden_tool'),
            'braden_items'     => [
                ['subscale' => __('document.braden_sensory'),   'score' => null, 'descriptor' => $pending],
                ['subscale' => __('document.braden_moisture'),  'score' => null, 'descriptor' => $pending],
                ['subscale' => __('document.braden_activity'),  'score' => null, 'descriptor' => $pending],
                ['subscale' => __('document.braden_mobility'),  'score' => null, 'descriptor' => $pending],
                ['subscale' => __('document.braden_nutrition'), 'score' => null, 'descriptor' => $pending],
                ['subscale' => __('document.braden_friction'),  'score' => null, 'descriptor' => $pending],
            ],
            'total_braden_score' => null,
            'risk_level'         => __('document.pending_assessment'),
            'skin_inspection'    => [
                __('document.braden_site_sacrum') => $pending,
                __('document.braden_site_heels')  => $pending,
                __('document.braden_site_elbows') => $pending,
            ],
            'existing_wounds'   => __('document.none_documented'),
            'prevention_plan'   => [__('document.braden_pending_plan')],
            'reassessment_due'  => Carbon::tomorrow()->isoFormat('DD MMM YYYY'),
            'assessed_by'       => __('document.role_nursing_staff'),
        ];
    }

    /**
     * Method 6: Wound Care Chart
     */
    public function assembleWoundCareChart(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);

        $patientName = $this->patientName($patient);

        $admissionDate = $admission->admitted_at ?? null;

        $rounds = NursingRound::where('patient_id', $patientId)
            ->orderBy('round_time')
            ->get();

        $notRecorded = __('document.not_recorded');

        $woundAssessments = [];
        foreach ($rounds as $round) {
            try {
                $interventions = is_array($round->interventions)
                    ? $round->interventions
                    : json_decode($round->interventions ?? '{}', true) ?? [];

                if (!isset($interventions['wound_care'])) {
                    continue;
                }

                $wc = $interventions['wound_care'];

                $woundAssessments[] = [
                    'date'        => $round->round_time,
                    'wound_site'  => $wc['wound_site']  ?? __('document.not_specified'),
                    'size'        => $wc['size']         ?? $notRecorded,
                    'depth'       => $wc['depth']        ?? $notRecorded,
                    'tissue_type' => $wc['tissue_type']  ?? $notRecorded,
                    'exudate'     => $wc['exudate']      ?? $notRecorded,
                    'odour'       => $wc['odour']        ?? $notRecorded,
                    'peri_wound'  => $wc['peri_wound']   ?? $notRecorded,
                    'dressing_used' => $wc['dressing_used'] ?? $notRecorded,
                    'by'          => $round->nurse_id    ?? __('document.staff'),
                ];
            } catch (\Throwable $e) {
                // skip malformed round
            }
        }

        return [
            'patient_name'       => $patientName,
            'admission_date'     => $admissionDate,
            'wound_assessments'  => $woundAssessments,
            'next_assessment_due' => Carbon::tomorrow()->isoFormat('DD MMM YYYY'),
            'wound_care_nurse'   => __('document.role_wound_care_nurse'),
        ];
    }
}
