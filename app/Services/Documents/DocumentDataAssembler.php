<?php

namespace App\Services\Documents;

use App\Models\Patient;
use App\Models\Admission;
use App\Models\NursingRound;
use App\Models\VitalSign;
use App\Models\Prescription;
use App\Models\ClinicalNote;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use Carbon\Carbon;

class DocumentDataAssembler
{
    // ── Category B methods ────────────────────────────────────────────

    /**
     * Medication Administration Record (MAR)
     */
    public function assembleMar(string $patientId, ?string $admissionId = null): array
    {
        $patient    = Patient::find($patientId);
        $admission  = $this->resolveAdmission($patientId, $admissionId);
        $latestVital = $this->latestVitalSign($patientId);

        $ward       = 'Not assigned';
        $bedNumber  = 'Not assigned';
        if ($admission && $admission->bed) {
            $ward      = $admission->bed->ward ?? 'Not assigned';
            $bedNumber = $admission->bed->bed_number ?? $admission->bed_id ?? 'Not assigned';
        }

        $allergies = [];
        if ($patient) {
            $allergies = $patient->allergies()->pluck('name')->toArray();
        }

        $weightKg = $latestVital->weight ?? 'Not recorded';

        $prescriptions = Prescription::where('patient_id', $patientId)
            ->where('status', '!=', 'cancelled')
            ->with('items')
            ->orderByDesc('prescribed_at')
            ->limit(20)
            ->get();

        $medications = [];
        foreach ($prescriptions as $prescription) {
            foreach ($prescription->items as $item) {
                $medications[] = [
                    'name'      => $item->drug_name ?? 'Not recorded',
                    'route'     => $item->route ?? 'Not recorded',
                    'dose'      => $item->dose ?? 'Not recorded',
                    'frequency' => $item->frequency ?? 'Not recorded',
                    'quantity'  => $item->quantity ?? 'Not recorded',
                    'notes'     => '',
                ];
            }
        }

        $nursingRoundsQuery = NursingRound::orderBy('round_time');
        if ($admissionId) {
            $nursingRoundsQuery->where('admission_id', $admissionId);
        } else {
            $nursingRoundsQuery->where('patient_id', $patientId);
        }
        $nursingRoundsQuery->whereDate('round_time', Carbon::today());
        $nursingRoundsQuery->get(); // fetched but not used in MAR return; kept for potential extension

        return [
            'ward'             => $ward,
            'bed_number'       => $bedNumber,
            'allergies'        => $allergies,
            'weight_kg'        => $weightKg,
            'scheduled_times'  => ['06:00', '10:00', '14:00', '18:00', '22:00'],
            'medications'      => $medications,
            'prn_medications'  => [],
            'date'             => Carbon::today()->toDateString(),
            'nurse_in_charge'  => 'Assigned Nurse',
        ];
    }

    /**
     * ICU Flowsheet
     */
    public function assembleIcuFlowsheet(string $patientId, ?string $admissionId = null): array
    {
        $admission = $this->resolveAdmission($patientId, $admissionId);

        $icuBed           = $admission->bed_id ?? 'ICU Bed';
        $admissionDiagnosis = $admission->admission_reason ?? 'Not recorded';

        $rounds = [];
        if ($admission) {
            $rounds = NursingRound::where('admission_id', $admission->id)
                ->orderByDesc('round_time')
                ->limit(24)
                ->get();
        }

        $hourlyVitals = [];
        foreach ($rounds as $round) {
            $vitalSigns = $this->decodeJson($round->vital_signs);
            $hourlyVitals[] = [
                'time'      => Carbon::parse($round->round_time)->format('H:i'),
                'bp'        => ($vitalSigns['blood_pressure_systolic'] ?? 'N/A') . '/' . ($vitalSigns['blood_pressure_diastolic'] ?? 'N/A'),
                'map'       => null,
                'hr'        => $vitalSigns['pulse'] ?? $round->pulse ?? 'N/A',
                'temp'      => $vitalSigns['temperature'] ?? 'N/A',
                'spo2'      => $vitalSigns['oxygen_saturation'] ?? $vitalSigns['spo2'] ?? 'N/A',
                'fio2'      => '21%',
                'urine_ml'  => null,
                'cvp'       => null,
            ];
        }

        return [
            'date'                => Carbon::today()->toDateString(),
            'icu_bed'             => $icuBed,
            'admission_diagnosis' => $admissionDiagnosis,
            'apache_ii_score'     => 'Pending',
            'sofa_score'          => 'Pending',
            'ventilator_settings' => [
                'mode'         => 'Not set',
                'fio2'         => '21%',
                'peep'         => '0',
                'tidal_volume' => 'N/A',
                'rr_set'       => 0,
                'pip'          => 'N/A',
            ],
            'hourly_vitals'       => $hourlyVitals,
            'infusions'           => [],
            'fluid_balance_24h'   => ['input' => 0, 'output' => 0, 'balance' => '0'],
            'nurse_in_charge'     => 'Assigned Nurse',
            'intensivist'         => 'Attending Physician',
        ];
    }

    /**
     * Nursing Chart
     */
    public function assembleNursingChart(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);

        $ward      = 'Not assigned';
        $bedNumber = 'Not assigned';
        if ($admission && $admission->bed) {
            $ward      = $admission->bed->ward ?? 'Not assigned';
            $bedNumber = $admission->bed->bed_number ?? $admission->bed_id ?? 'Not assigned';
        }

        $roundsQuery = NursingRound::orderBy('round_time')
            ->whereDate('round_time', Carbon::today());

        if ($admissionId) {
            $roundsQuery->where('admission_id', $admissionId);
        } else {
            $roundsQuery->where('patient_id', $patientId);
        }

        $rounds = $roundsQuery->get();

        $roundData = [];
        foreach ($rounds as $round) {
            $roundData[] = [
                'time'                => Carbon::parse($round->round_time)->format('H:i'),
                'pain_level'          => $round->pain_level ?? 'Not recorded',
                'observations'        => $round->observations ?? 'Not recorded',
                'interventions'       => $this->decodeJson($round->interventions),
                'patient_response'    => $round->patient_response ?? 'Not recorded',
                'escalation_required' => $round->escalation_required ?? false,
                'nurse_id'            => $round->nurse_id ?? 'Not recorded',
            ];
        }

        $patientName = 'Not recorded';
        if ($patient) {
            $patientName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
            if ($patientName === '') {
                $patientName = 'Not recorded';
            }
        }

        return [
            'date'         => Carbon::today()->toDateString(),
            'ward'         => $ward,
            'bed_number'   => $bedNumber,
            'rounds'       => $roundData,
            'patient_name' => $patientName,
        ];
    }

    /**
     * Daily Progress Note
     */
    public function assembleDailyProgressNote(string $patientId, ?string $admissionId = null): array
    {
        $admission   = $this->resolveAdmission($patientId, $admissionId);
        $clinicalNote = $this->latestClinicalNote($patientId, $admission);

        $ward      = 'Not assigned';
        $bedNumber = 'Not assigned';
        if ($admission && $admission->bed) {
            $ward      = $admission->bed->ward ?? 'Not assigned';
            $bedNumber = $admission->bed->bed_number ?? $admission->bed_id ?? 'Not assigned';
        }

        $dayOfAdmission = 'N/A';
        if ($admission && $admission->admitted_at) {
            $dayOfAdmission = (int) Carbon::parse($admission->admitted_at)->diffInDays(Carbon::now()) + 1;
        }

        $latestRound = $this->latestRoundToday($patientId, $admissionId);
        $vitalSignsJson = $this->decodeJson($latestRound->vital_signs ?? null);

        $vitalSigns = [
            'bp'           => ($vitalSignsJson['blood_pressure_systolic'] ?? 'N/A') . '/' . ($vitalSignsJson['blood_pressure_diastolic'] ?? 'N/A'),
            'pulse'        => $vitalSignsJson['pulse'] ?? 'N/A',
            'temp'         => $vitalSignsJson['temperature'] ?? 'N/A',
            'spo2'         => $vitalSignsJson['oxygen_saturation'] ?? $vitalSignsJson['spo2'] ?? 'N/A',
            'rr'           => $vitalSignsJson['respiratory_rate'] ?? 'N/A',
            'urine_output' => $vitalSignsJson['urine_output'] ?? 'N/A',
        ];

        return [
            'date'                   => Carbon::today()->toDateString(),
            'time'                   => Carbon::now()->format('H:i'),
            'ward'                   => $ward,
            'bed_number'             => $bedNumber,
            'day_of_admission'       => $dayOfAdmission,
            'subjective'             => $clinicalNote->history_of_present_illness ?? 'Pending documentation',
            'vital_signs'            => $vitalSigns,
            'objective_examination'  => $clinicalNote->examination_findings ?? 'Pending',
            'investigations_today'   => [],
            'assessment'             => 'Pending documentation',
            'plan'                   => [],
            'attending_physician'    => 'Attending Physician',
            'resident_physician'     => 'Resident',
        ];
    }

    /**
     * Glucose Log
     */
    public function assembleGlucoseLog(string $patientId, ?string $admissionId = null): array
    {
        $roundsQuery = NursingRound::where('patient_id', $patientId)
            ->orderBy('round_time')
            ->limit(20);

        if ($admissionId) {
            $roundsQuery->where('admission_id', $admissionId);
        }

        $rounds = $roundsQuery->get();

        $readings          = [];
        $hypoglycaemiaCount = 0;
        $firstDate         = null;
        $lastDate          = null;

        foreach ($rounds as $round) {
            $vitalSigns = $this->decodeJson($round->vital_signs);
            $glucose    = $vitalSigns['blood_glucose'] ?? $vitalSigns['glucose'] ?? null;

            $roundTime = Carbon::parse($round->round_time);
            if ($firstDate === null) {
                $firstDate = $roundTime;
            }
            $lastDate = $roundTime;

            if ($glucose !== null && is_numeric($glucose) && $glucose < 4.0) {
                $hypoglycaemiaCount++;
            }

            $readings[] = [
                'date'    => $roundTime->format('d M'),
                'time'    => $roundTime->format('H:i'),
                'reading' => $glucose ?? 'Not recorded',
                'action'  => '',
            ];
        }

        $monitoringPeriod = 'No records';
        if ($firstDate && $lastDate) {
            $monitoringPeriod = $firstDate->format('d M Y') . ' – ' . $lastDate->format('d M Y');
        }

        return [
            'monitoring_period'      => $monitoringPeriod,
            'target_range'           => 'Fasting: 4.4–7.2 mmol/L | Post-prandial: < 10.0 mmol/L',
            'diabetes_type'          => 'Type 2 Diabetes Mellitus',
            'current_therapy'        => 'Per prescription',
            'readings'               => $readings,
            'hypoglycaemia_episodes' => $hypoglycaemiaCount,
            'hba1c_on_admission'     => 'Pending',
            'endocrinology_review'   => 'Pending',
            'monitored_by'           => 'Nursing Team',
        ];
    }

    /**
     * Handover Note
     */
    public function assembleHandoverNote(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);
        $latestRound = $this->latestRound($patientId, $admissionId);

        $ward      = 'Not assigned';
        $bedNumber = 'Not assigned';
        if ($admission && $admission->bed) {
            $ward      = $admission->bed->ward ?? 'Not assigned';
            $bedNumber = $admission->bed->bed_number ?? $admission->bed_id ?? 'Not assigned';
        }

        $patientName = 'Not recorded';
        if ($patient) {
            $patientName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
            if ($patientName === '') {
                $patientName = 'Not recorded';
            }
        }

        $patients = [
            [
                'bed'              => $bedNumber,
                'name'             => $patientName,
                'diagnosis'        => $admission->admission_reason ?? 'Not recorded',
                'concerns'         => $latestRound->observations ?? 'None',
                'actions_required' => [],
            ],
        ];

        return [
            'handover_date'     => Carbon::today()->toDateString(),
            'handover_time'     => Carbon::now()->format('H:i'),
            'handing_over'      => 'Outgoing shift',
            'receiving'         => 'Incoming shift',
            'ward'              => $ward,
            'patients'          => $patients,
            'critical_alerts'   => 'None',
            'handover_complete' => false,
        ];
    }

    /**
     * Partograph
     */
    public function assemblePartograph(string $patientId, ?string $admissionId = null): array
    {
        $admission = $this->resolveAdmission($patientId, $admissionId);
        $latestAntenatal = AntenatalVisit::where('patient_id', $patientId)
            ->orderByDesc('visit_date')
            ->first();

        $latestRound = $this->latestRound($patientId, $admissionId);
        $vitalSignsJson = $this->decodeJson($latestRound->vital_signs ?? null);

        $maternalVitals = [
            'bp'   => ($vitalSignsJson['blood_pressure_systolic'] ?? 'N/A') . '/' . ($vitalSignsJson['blood_pressure_diastolic'] ?? 'N/A'),
            'pulse' => $vitalSignsJson['pulse'] ?? 'N/A',
            'temp'  => $vitalSignsJson['temperature'] ?? 'N/A',
        ];

        $gestationalAge = 'Not recorded';
        if ($latestAntenatal) {
            $weeks = $latestAntenatal->gestational_age_weeks ?? 0;
            $days  = $latestAntenatal->gestational_age_days ?? 0;
            $gestationalAge = "{$weeks}w {$days}d";
        }

        // Extract cervical dilation and fetal heart rate records from nursing rounds
        $cervicalDilationRecords = [];
        $fetalHeartRateRecords   = [];

        $roundsQuery = NursingRound::orderBy('round_time');
        if ($admissionId) {
            $roundsQuery->where('admission_id', $admissionId);
        } else {
            $roundsQuery->where('patient_id', $patientId);
        }
        $rounds = $roundsQuery->get();

        foreach ($rounds as $round) {
            $interventions  = $this->decodeJson($round->interventions);
            $vitalSignsData = $this->decodeJson($round->vital_signs);

            if (isset($interventions['cervical_dilation'])) {
                $cervicalDilationRecords[] = [
                    'time'      => Carbon::parse($round->round_time)->format('H:i'),
                    'dilation'  => $interventions['cervical_dilation'],
                ];
            }

            if (isset($vitalSignsData['fetal_heart_rate'])) {
                $fetalHeartRateRecords[] = [
                    'time' => Carbon::parse($round->round_time)->format('H:i'),
                    'fhr'  => $vitalSignsData['fetal_heart_rate'],
                ];
            }
        }

        return [
            'date'                     => Carbon::today()->toDateString(),
            'admission_time'           => $admission->admitted_at ?? 'Not recorded',
            'gestational_age'          => $gestationalAge,
            'maternal_vitals'          => $maternalVitals,
            'cervical_dilation_records' => $cervicalDilationRecords,
            'fetal_heart_rate_records'  => $fetalHeartRateRecords,
            'liquor_records'           => [],
            'descent_records'          => [],
            'oxytocin_records'         => [],
            'medication_records'       => [],
            'midwife'                  => 'Assigned Midwife',
            'obstetrician'             => 'Attending Obstetrician',
        ];
    }

    /**
     * NICU Chart
     */
    public function assembleNicuChart(string $patientId, ?string $admissionId = null): array
    {
        $admission      = $this->resolveAdmission($patientId, $admissionId);
        $deliveryRecord = DeliveryRecord::where('patient_id', $patientId)->latest('delivery_date')->first();

        $nicuBed = $admission->bed_id ?? 'NICU Incubator';

        $gestationalAgeAtBirth = $deliveryRecord ? 'Not recorded' : 'Not recorded';
        $birthWeightG          = $deliveryRecord->birth_weight_grams ?? 'Not recorded';

        $postnatalAgeDays = 0;
        if ($admission && $admission->admitted_at) {
            $postnatalAgeDays = (int) Carbon::parse($admission->admitted_at)->diffInDays(Carbon::now());
        }

        $rounds = [];
        if ($admission) {
            $rounds = NursingRound::where('admission_id', $admission->id)
                ->orderByDesc('round_time')
                ->limit(24)
                ->get();
        }

        $vitalSignsMapped = [];
        foreach ($rounds as $round) {
            $vs = $this->decodeJson($round->vital_signs);
            $vitalSignsMapped[] = [
                'time' => Carbon::parse($round->round_time)->format('H:i'),
                'temp' => $vs['temperature'] ?? 'N/A',
                'hr'   => $vs['pulse'] ?? 'N/A',
                'rr'   => $vs['respiratory_rate'] ?? 'N/A',
                'spo2' => $vs['oxygen_saturation'] ?? $vs['spo2'] ?? 'N/A',
                'bp'   => ($vs['blood_pressure_systolic'] ?? 'N/A') . '/' . ($vs['blood_pressure_diastolic'] ?? 'N/A'),
            ];
        }

        return [
            'date'                     => Carbon::today()->toDateString(),
            'nicu_bed'                 => $nicuBed,
            'gestational_age_at_birth' => $gestationalAgeAtBirth,
            'birth_weight_g'           => $birthWeightG,
            'postnatal_age_days'       => $postnatalAgeDays,
            'corrected_gestational_age' => 'Pending',
            'current_weight_g'         => 'Pending',
            'diagnoses'                => [$admission->admission_reason ?? 'Not recorded'],
            'respiratory_support'      => ['mode' => 'Room air'],
            'vital_signs'              => $vitalSignsMapped,
            'feeds'                    => [
                'route'     => 'Per order',
                'type'      => 'Per order',
                'volume'    => 'Per order',
                'tolerance' => 'Monitoring',
            ],
            'medications'              => ['Per prescription'],
            'fluid_balance_24h'        => ['iv_fluids' => 0, 'oral_feeds' => 0, 'urine' => 0, 'balance' => '0'],
            'head_ultrasound'          => 'Pending',
            'attending_neonatologist'  => 'Neonatologist',
            'nurse'                    => 'Assigned Nurse',
        ];
    }

    /**
     * Growth Chart
     */
    public function assembleGrowthChart(string $patientId, ?string $admissionId = null): array
    {
        $patient = Patient::find($patientId);

        $dob = $patient->date_of_birth ?? null;
        $sex = $patient->sex ?? 'Not recorded';

        $vitals = [];
        if ($patient) {
            $vitals = $patient->vitals()
                ->orderBy('created_at')
                ->get(['weight', 'height', 'created_at']);
        }

        $measurements = [];
        $lastUpdated  = null;

        foreach ($vitals as $vital) {
            $createdAt  = Carbon::parse($vital->created_at);
            $ageMonths  = null;

            if ($dob) {
                $ageMonths = (int) Carbon::parse($dob)->diffInMonths($createdAt);
            }

            $weightKg = $vital->weight ?? null;
            $heightCm = $vital->height ?? null;

            $bmi = null;
            if ($weightKg && $heightCm && $heightCm > 0) {
                $heightM = $heightCm / 100;
                $bmi     = round($weightKg / ($heightM * $heightM), 1);
            }

            $measurements[] = [
                'date'               => $createdAt->toDateString(),
                'age_months'         => $ageMonths,
                'weight_kg'          => $weightKg ?? 'Not recorded',
                'height_cm'          => $heightCm ?? 'Not recorded',
                'bmi'                => $bmi,
                'weight_for_age_z'   => null,
                'height_for_age_z'   => null,
            ];

            $lastUpdated = $createdAt->toDateString();
        }

        return [
            'patient_dob'   => $dob ?? 'Not recorded',
            'sex'           => $sex,
            'measurements'  => $measurements,
            'who_standard'  => 'WHO Child Growth Standards 2006',
            'last_updated'  => $lastUpdated ?? 'No records',
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function resolveAdmission(string $patientId, ?string $admissionId): ?Admission
    {
        if ($admissionId) {
            return Admission::with('bed')->find($admissionId);
        }

        return Admission::with('bed')
            ->where('patient_id', $patientId)
            ->where('status', 'admitted')
            ->latest('admitted_at')
            ->first();
    }

    private function latestVitalSign(string $patientId): ?VitalSign
    {
        $patient = Patient::find($patientId);
        if (!$patient) {
            return null;
        }
        return $patient->vitals()->latest()->first();
    }

    private function latestClinicalNote(string $patientId, ?Admission $admission): ?ClinicalNote
    {
        $query = ClinicalNote::orderByDesc('created_at');

        if ($admission && $admission->visit_id) {
            $query->where('visit_id', $admission->visit_id);
        } elseif (method_exists(new ClinicalNote(), 'getAttributes') && array_key_exists('patient_id', (new ClinicalNote())->getAttributes())) {
            $query->where('patient_id', $patientId);
        } else {
            // Fall back: try visit_id from any admission for this patient
            $admissions = Admission::where('patient_id', $patientId)->pluck('visit_id')->filter()->values();
            if ($admissions->isNotEmpty()) {
                $query->whereIn('visit_id', $admissions);
            }
        }

        return $query->first();
    }

    private function latestRoundToday(string $patientId, ?string $admissionId): ?NursingRound
    {
        $query = NursingRound::whereDate('round_time', Carbon::today())
            ->orderByDesc('round_time');

        if ($admissionId) {
            $query->where('admission_id', $admissionId);
        } else {
            $query->where('patient_id', $patientId);
        }

        return $query->first();
    }

    private function latestRound(string $patientId, ?string $admissionId): ?NursingRound
    {
        $query = NursingRound::orderByDesc('round_time');

        if ($admissionId) {
            $query->where('admission_id', $admissionId);
        } else {
            $query->where('patient_id', $patientId);
        }

        return $query->first();
    }

    /**
     * Safely decode a JSON field that may already be an array or null.
     *
     * @param mixed $value
     */
    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
