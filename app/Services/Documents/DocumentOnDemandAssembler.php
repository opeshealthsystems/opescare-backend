<?php

namespace App\Services\Documents;

use App\Models\Patient;
use App\Models\Admission;
use App\Models\NursingRound;
use App\Models\VitalSign;
use App\Models\Prescription;
use App\Models\ClinicalNote;
use Carbon\Carbon;

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

    /**
     * Method 1: Surgical Safety Checklist
     */
    public function assembleSurgicalSafetyChecklist(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);

        $patientName = $patient
            ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))
            : 'Unknown Patient';

        $healthId = $patient->health_id ?? 'N/A';

        $allergiesKnown = 'None known';
        if ($patient) {
            try {
                $allergyNames = $patient->allergies()->pluck('name')->filter()->implode(', ');
                if ($allergyNames) {
                    $allergiesKnown = $allergyNames;
                }
            } catch (\Throwable $e) {
                $allergiesKnown = 'None known';
            }
        }

        return [
            'procedure'      => $admission->admission_reason ?? 'Scheduled procedure',
            'scheduled_time' => Carbon::now()->format('d M Y, H:i'),
            'surgeon'        => 'Attending Surgeon',
            'anaesthetist'   => 'Anaesthetist',
            'scrub_nurse'    => 'Scrub Nurse',
            'sign_in'        => [
                'identity_confirmed' => false,
                'site_marked'        => false,
                'consent_obtained'   => false,
                'anaesthesia_check'  => false,
                'pulse_oximeter'     => false,
                'allergies_known'    => $allergiesKnown,
                'difficult_airway'   => false,
                'aspiration_risk'    => false,
                'blood_loss_risk'    => 'Unknown',
            ],
            'time_out'       => [
                'team_introduced'   => false,
                'patient_identity'  => $patientName . ' — ' . $healthId,
                'procedure_confirmed' => false,
                'site_confirmed'    => '',
                'antibiotics_given' => 'Pending',
                'imaging_displayed' => false,
                'critical_steps_shared' => '',
            ],
            'sign_out'       => [
                'procedure_recorded'  => false,
                'instrument_count_ok' => false,
                'specimen_labelled'   => 'None',
                'equipment_issues'    => 'None',
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
        $admissionDate = Carbon::today()->format('d M Y');
        if ($admission && $admission->admitted_at) {
            try {
                $admissionDate = Carbon::parse($admission->admitted_at)->format('d M Y, H:i');
            } catch (\Throwable $e) {
                // keep default
            }
        }

        // Vital signs
        $vitalSigns = [
            'bp'     => 'Not recorded',
            'pulse'  => 'Not recorded',
            'temp'   => 'Not recorded',
            'rr'     => 'Not recorded',
            'spo2'   => 'Not recorded',
            'weight' => 'Not recorded',
            'height' => 'Not recorded',
            'bmi'    => 'Not recorded',
        ];

        if ($patient) {
            try {
                /** @var VitalSign|null $latestVital */
                $latestVital = $patient->vitals()->latest()->first();
                if ($latestVital) {
                    $systolic  = $latestVital->blood_pressure_systolic;
                    $diastolic = $latestVital->blood_pressure_diastolic;
                    if ($systolic !== null && $diastolic !== null) {
                        $vitalSigns['bp'] = $systolic . '/' . $diastolic . ' mmHg';
                    }
                    if ($latestVital->pulse !== null) {
                        $vitalSigns['pulse'] = $latestVital->pulse . ' bpm';
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
                    if ($latestVital->weight !== null) {
                        $vitalSigns['weight'] = $latestVital->weight . ' kg';
                    }
                    if ($latestVital->height !== null) {
                        $vitalSigns['height'] = $latestVital->height . ' cm';
                    }
                    // BMI calculation
                    if ($latestVital->weight !== null && $latestVital->height !== null && (float) $latestVital->height > 0) {
                        $heightM = (float) $latestVital->height / 100;
                        $bmi     = round((float) $latestVital->weight / ($heightM * $heightM), 1);
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
                        'allergen'    => $allergy->name ?? 'Unknown',
                        'reaction'    => 'Reaction',
                        'severity'    => 'Unknown',
                        'recorded_by' => 'Staff',
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
                            'drug_name'     => $item->drug_name    ?? 'Unknown',
                            'drug_code'     => $item->drug_code    ?? '',
                            'dose'          => $item->dose         ?? 'Not specified',
                            'frequency'     => $item->frequency    ?? 'Not specified',
                            'route'         => $item->route        ?? 'Not specified',
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
            'admission_mode'        => $admission->admission_reason ?? 'Emergency',
            'ward'                  => 'Ward',
            'bed_number'            => $admission->bed_id ?? 'Not assigned',
            'chief_complaint'       => $admission->admission_reason ?? 'Not documented',
            'vital_signs_on_admission' => $vitalSigns,
            'allergies'             => $allergies,
            'past_medical_history'  => 'Not documented',
            'current_medications'   => $currentMedications,
            'pain_score'            => null,
            'pain_location'         => '',
            'nutritional_screen'    => [
                'must_score'        => 0,
                'nutritional_risk'  => 'Pending assessment',
                'diet_on_admission' => 'Normal',
            ],
            'fall_risk'             => [
                'morse_score'   => 0,
                'risk_level'    => 'Pending assessment',
                'interventions' => [],
            ],
            'pressure_ulcer_risk'   => [
                'braden_score'   => 0,
                'risk_level'     => 'Pending assessment',
                'skin_condition' => 'Intact',
            ],
            'social_history'        => 'Not documented',
            'admitting_nurse'       => 'Admitting Nurse',
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
                        'department'           => 'Laboratory',
                        'test'                 => $order->test_name ?? 'Test',
                        'tube'                 => 'As required',
                        'fasting'              => false,
                        'special_instructions' => '',
                    ];
                }
            }
        } catch (\Throwable $e) {
            $investigations = [];
        }

        return [
            'urgency'              => 'ROUTINE',
            'requesting_ward'      => $admission->bed_id ?? 'Ward',
            'clinical_indication'  => $admission->admission_reason ?? 'As per clinical assessment',
            'investigations'       => $investigations,
            'sample_taken_by'      => 'Requesting Clinician',
            'sample_taken_at'      => Carbon::now()->format('d M Y, H:i'),
            'expected_turnaround'  => 'As per laboratory protocol',
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
            'assessment_date'  => Carbon::today()->format('d M Y'),
            'assessment_tool'  => 'Morse Fall Scale',
            'morse_items'      => [
                ['item' => 'History of falling (immediate or within 3 months)', 'score' => 0],
                ['item' => 'Secondary diagnosis',                                'score' => 0],
                ['item' => 'Ambulatory aid',                                     'score' => 0],
                ['item' => 'IV / Heparin lock',                                  'score' => 0],
                ['item' => 'Gait / Transferring',                                'score' => 0],
                ['item' => 'Mental status',                                      'score' => 0],
            ],
            'total_morse_score' => $morseScore,
            'risk_level'        => 'Pending Assessment',
            'interventions'     => ['Fall risk assessment pending — complete on admission'],
            'reassessment_due'  => Carbon::tomorrow()->format('d M Y'),
            'assessed_by'       => 'Nursing Staff',
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

        return [
            'assessment_date'  => Carbon::today()->format('d M Y'),
            'assessment_tool'  => 'Braden Scale',
            'braden_items'     => [
                ['subscale' => 'Sensory Perception', 'score' => null, 'descriptor' => 'Pending'],
                ['subscale' => 'Moisture',           'score' => null, 'descriptor' => 'Pending'],
                ['subscale' => 'Activity',           'score' => null, 'descriptor' => 'Pending'],
                ['subscale' => 'Mobility',           'score' => null, 'descriptor' => 'Pending'],
                ['subscale' => 'Nutrition',          'score' => null, 'descriptor' => 'Pending'],
                ['subscale' => 'Friction & Shear',   'score' => null, 'descriptor' => 'Pending'],
            ],
            'total_braden_score' => null,
            'risk_level'         => 'Pending Assessment',
            'skin_inspection'    => [
                'Sacrum' => 'Pending',
                'Heels'  => 'Pending',
                'Elbows' => 'Pending',
            ],
            'existing_wounds'   => 'None documented',
            'prevention_plan'   => ['Assessment pending — complete at admission'],
            'reassessment_due'  => Carbon::tomorrow()->format('d M Y'),
            'assessed_by'       => 'Nursing Staff',
        ];
    }

    /**
     * Method 6: Wound Care Chart
     */
    public function assembleWoundCareChart(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);

        $patientName = $patient
            ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))
            : 'Unknown Patient';

        $admissionDate = $admission->admitted_at ?? null;

        $rounds = NursingRound::where('patient_id', $patientId)
            ->orderBy('round_time')
            ->get();

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
                    'wound_site'  => $wc['wound_site']  ?? 'Not specified',
                    'size'        => $wc['size']         ?? 'Not recorded',
                    'depth'       => $wc['depth']        ?? 'Not recorded',
                    'tissue_type' => $wc['tissue_type']  ?? 'Not recorded',
                    'exudate'     => $wc['exudate']      ?? 'Not recorded',
                    'odour'       => $wc['odour']        ?? 'Not recorded',
                    'peri_wound'  => $wc['peri_wound']   ?? 'Not recorded',
                    'dressing_used' => $wc['dressing_used'] ?? 'Not recorded',
                    'by'          => $round->nurse_id    ?? 'Staff',
                ];
            } catch (\Throwable $e) {
                // skip malformed round
            }
        }

        return [
            'patient_name'       => $patientName,
            'admission_date'     => $admissionDate,
            'wound_assessments'  => $woundAssessments,
            'next_assessment_due' => Carbon::tomorrow()->format('d M Y'),
            'wound_care_nurse'   => 'Wound Care Nurse',
        ];
    }
}
