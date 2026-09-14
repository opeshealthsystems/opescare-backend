<?php

namespace App\Services\Documents;

use App\Models\Patient;
use App\Models\Admission;
use App\Models\ClinicalCodeDictionary;
use App\Models\NursingRound;
use App\Models\TriageVitalSign;
use App\Models\Prescription;
use App\Models\ClinicalNote;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use Carbon\Carbon;

/**
 * Assembles the data arrays behind the ward charts (MAR, ICU flowsheet,
 * nursing chart, progress note, glucose log, handover, partograph, NICU and
 * growth charts).
 *
 * Every human-readable string here goes through lang/{en,fr}/document.php and
 * every date through Carbon::isoFormat(), so the document comes out in the
 * locale the request resolved (App\Http\Middleware\SetLocale — the patient's
 * own session or Accept-Language when they download it themselves). Clinical
 * titles are never translated here: the one diagnosis title this class prints
 * is read from clinical_code_dictionary in the reader's language.
 */
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

        [$ward, $bedNumber] = $this->wardAndBed($admission);

        $allergies = [];
        if ($patient) {
            $allergies = $patient->allergies()->pluck('substance')->toArray();
        }

        $weightKg = $latestVital->weight_kg ?? __('document.not_recorded');

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
                    'name'      => $item->drug_name ?? __('document.not_recorded'),
                    'route'     => $item->route ?? __('document.not_recorded'),
                    'dose'      => $item->dose ?? __('document.not_recorded'),
                    'frequency' => $item->frequency ?? __('document.not_recorded'),
                    'quantity'  => $item->quantity ?? __('document.not_recorded'),
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
            'nurse_in_charge'  => __('document.role_assigned_nurse'),
        ];
    }

    /**
     * ICU Flowsheet
     */
    public function assembleIcuFlowsheet(string $patientId, ?string $admissionId = null): array
    {
        $admission = $this->resolveAdmission($patientId, $admissionId);

        $icuBed           = $admission->bed_id ?? __('document.icu_bed_default');
        $admissionDiagnosis = $admission->admission_reason ?? __('document.not_recorded');

        $rounds = [];
        if ($admission) {
            $rounds = NursingRound::where('admission_id', $admission->id)
                ->orderByDesc('round_time')
                ->limit(24)
                ->get();
        }

        $na = __('document.na');

        $hourlyVitals = [];
        foreach ($rounds as $round) {
            $vitalSigns = $this->decodeJson($round->vital_signs);
            $hourlyVitals[] = [
                'time'      => Carbon::parse($round->round_time)->format('H:i'),
                'bp'        => ($vitalSigns['blood_pressure_systolic'] ?? $na) . '/' . ($vitalSigns['blood_pressure_diastolic'] ?? $na),
                'map'       => null,
                'hr'        => $vitalSigns['pulse'] ?? $round->pulse ?? $na,
                'temp'      => $vitalSigns['temperature'] ?? $na,
                'spo2'      => $vitalSigns['oxygen_saturation'] ?? $vitalSigns['spo2'] ?? $na,
                'fio2'      => '21%',
                'urine_ml'  => null,
                'cvp'       => null,
            ];
        }

        return [
            'date'                => Carbon::today()->toDateString(),
            'icu_bed'             => $icuBed,
            'admission_diagnosis' => $admissionDiagnosis,
            'apache_ii_score'     => __('document.pending'),
            'sofa_score'          => __('document.pending'),
            'ventilator_settings' => [
                'mode'         => __('document.not_set'),
                'fio2'         => '21%',
                'peep'         => '0',
                'tidal_volume' => $na,
                'rr_set'       => 0,
                'pip'          => $na,
            ],
            'hourly_vitals'       => $hourlyVitals,
            'infusions'           => [],
            'fluid_balance_24h'   => ['input' => 0, 'output' => 0, 'balance' => '0'],
            'nurse_in_charge'     => __('document.role_assigned_nurse'),
            'intensivist'         => __('document.role_attending_physician'),
        ];
    }

    /**
     * Nursing Chart
     */
    public function assembleNursingChart(string $patientId, ?string $admissionId = null): array
    {
        $patient   = Patient::find($patientId);
        $admission = $this->resolveAdmission($patientId, $admissionId);

        [$ward, $bedNumber] = $this->wardAndBed($admission);

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
                'pain_level'          => $round->pain_level ?? __('document.not_recorded'),
                'observations'        => $round->observations ?? __('document.not_recorded'),
                'interventions'       => $this->decodeJson($round->interventions),
                'patient_response'    => $round->patient_response ?? __('document.not_recorded'),
                'escalation_required' => $round->escalation_required ?? false,
                'nurse_id'            => $round->nurse_id ?? __('document.not_recorded'),
            ];
        }

        return [
            'date'         => Carbon::today()->toDateString(),
            'ward'         => $ward,
            'bed_number'   => $bedNumber,
            'rounds'       => $roundData,
            'patient_name' => $this->patientName($patient),
        ];
    }

    /**
     * Daily Progress Note
     */
    public function assembleDailyProgressNote(string $patientId, ?string $admissionId = null): array
    {
        $admission   = $this->resolveAdmission($patientId, $admissionId);
        $clinicalNote = $this->latestClinicalNote($patientId, $admission);

        [$ward, $bedNumber] = $this->wardAndBed($admission);

        $na = __('document.na');

        $dayOfAdmission = $na;
        if ($admission && $admission->admitted_at) {
            $dayOfAdmission = (int) Carbon::parse($admission->admitted_at)->diffInDays(Carbon::now()) + 1;
        }

        $latestRound = $this->latestRoundToday($patientId, $admissionId);
        $vitalSignsJson = $this->decodeJson($latestRound->vital_signs ?? null);

        $vitalSigns = [
            'bp'           => ($vitalSignsJson['blood_pressure_systolic'] ?? $na) . '/' . ($vitalSignsJson['blood_pressure_diastolic'] ?? $na),
            'pulse'        => $vitalSignsJson['pulse'] ?? $na,
            'temp'         => $vitalSignsJson['temperature'] ?? $na,
            'spo2'         => $vitalSignsJson['oxygen_saturation'] ?? $vitalSignsJson['spo2'] ?? $na,
            'rr'           => $vitalSignsJson['respiratory_rate'] ?? $na,
            'urine_output' => $vitalSignsJson['urine_output'] ?? $na,
        ];

        return [
            'date'                   => Carbon::today()->toDateString(),
            'time'                   => Carbon::now()->format('H:i'),
            'ward'                   => $ward,
            'bed_number'             => $bedNumber,
            'day_of_admission'       => $dayOfAdmission,
            'subjective'             => $clinicalNote->history_of_present_illness ?? __('document.pending_documentation'),
            'vital_signs'            => $vitalSigns,
            'objective_examination'  => $clinicalNote->examination_findings ?? __('document.pending'),
            'investigations_today'   => [],
            'assessment'             => __('document.pending_documentation'),
            'plan'                   => [],
            'attending_physician'    => __('document.role_attending_physician'),
            'resident_physician'     => __('document.role_resident'),
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
                'date'    => $roundTime->isoFormat('DD MMM'),
                'time'    => $roundTime->format('H:i'),
                'reading' => $glucose ?? __('document.not_recorded'),
                'action'  => '',
            ];
        }

        $monitoringPeriod = __('document.no_records');
        if ($firstDate && $lastDate) {
            $monitoringPeriod = __('document.date_range', [
                'from' => $firstDate->isoFormat('DD MMM YYYY'),
                'to'   => $lastDate->isoFormat('DD MMM YYYY'),
            ]);
        }

        return [
            'monitoring_period'      => $monitoringPeriod,
            'target_range'           => __('document.glu_target_range'),
            'diabetes_type'          => $this->codeTitle('ICD-10', 'E11', __('document.glu_diabetes_type_fallback')),
            'current_therapy'        => __('document.per_prescription'),
            'readings'               => $readings,
            'hypoglycaemia_episodes' => $hypoglycaemiaCount,
            'hba1c_on_admission'     => __('document.pending'),
            'endocrinology_review'   => __('document.pending'),
            'monitored_by'           => __('document.role_nursing_team'),
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

        [$ward, $bedNumber] = $this->wardAndBed($admission);

        $patients = [
            [
                'bed'              => $bedNumber,
                'name'             => $this->patientName($patient),
                'diagnosis'        => $admission->admission_reason ?? __('document.not_recorded'),
                'concerns'         => $latestRound->observations ?? __('document.none'),
                'actions_required' => [],
            ],
        ];

        return [
            'handover_date'     => Carbon::today()->toDateString(),
            'handover_time'     => Carbon::now()->format('H:i'),
            'handing_over'      => __('document.shift_outgoing'),
            'receiving'         => __('document.shift_incoming'),
            'ward'              => $ward,
            'patients'          => $patients,
            'critical_alerts'   => __('document.none'),
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

        $na = __('document.na');

        $maternalVitals = [
            'bp'   => ($vitalSignsJson['blood_pressure_systolic'] ?? $na) . '/' . ($vitalSignsJson['blood_pressure_diastolic'] ?? $na),
            'pulse' => $vitalSignsJson['pulse'] ?? $na,
            'temp'  => $vitalSignsJson['temperature'] ?? $na,
        ];

        $gestationalAge = __('document.not_recorded');
        if ($latestAntenatal) {
            $gestationalAge = __('document.gestational_age', [
                'weeks' => $latestAntenatal->gestational_age_weeks ?? 0,
                'days'  => $latestAntenatal->gestational_age_days ?? 0,
            ]);
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
            'admission_time'           => $admission->admitted_at ?? __('document.not_recorded'),
            'gestational_age'          => $gestationalAge,
            'maternal_vitals'          => $maternalVitals,
            'cervical_dilation_records' => $cervicalDilationRecords,
            'fetal_heart_rate_records'  => $fetalHeartRateRecords,
            'liquor_records'           => [],
            'descent_records'          => [],
            'oxytocin_records'         => [],
            'medication_records'       => [],
            'midwife'                  => __('document.role_assigned_midwife'),
            'obstetrician'             => __('document.role_attending_obstetrician'),
        ];
    }

    /**
     * NICU Chart
     */
    public function assembleNicuChart(string $patientId, ?string $admissionId = null): array
    {
        $admission      = $this->resolveAdmission($patientId, $admissionId);
        $deliveryRecord = DeliveryRecord::where('patient_id', $patientId)->latest('delivery_date')->first();

        $nicuBed = $admission->bed_id ?? __('document.nicu_bed_default');

        // Gestational age at birth is not held on the delivery record.
        $gestationalAgeAtBirth = __('document.not_recorded');
        $birthWeightG          = $deliveryRecord->birth_weight_grams ?? __('document.not_recorded');

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

        $na = __('document.na');

        $vitalSignsMapped = [];
        foreach ($rounds as $round) {
            $vs = $this->decodeJson($round->vital_signs);
            $vitalSignsMapped[] = [
                'time' => Carbon::parse($round->round_time)->format('H:i'),
                'temp' => $vs['temperature'] ?? $na,
                'hr'   => $vs['pulse'] ?? $na,
                'rr'   => $vs['respiratory_rate'] ?? $na,
                'spo2' => $vs['oxygen_saturation'] ?? $vs['spo2'] ?? $na,
                'bp'   => ($vs['blood_pressure_systolic'] ?? $na) . '/' . ($vs['blood_pressure_diastolic'] ?? $na),
            ];
        }

        return [
            'date'                     => Carbon::today()->toDateString(),
            'nicu_bed'                 => $nicuBed,
            'gestational_age_at_birth' => $gestationalAgeAtBirth,
            'birth_weight_g'           => $birthWeightG,
            'postnatal_age_days'       => $postnatalAgeDays,
            'corrected_gestational_age' => __('document.pending'),
            'current_weight_g'         => __('document.pending'),
            'diagnoses'                => [$admission->admission_reason ?? __('document.not_recorded')],
            'respiratory_support'      => ['mode' => __('document.room_air')],
            'vital_signs'              => $vitalSignsMapped,
            'feeds'                    => [
                'route'     => __('document.per_order'),
                'type'      => __('document.per_order'),
                'volume'    => __('document.per_order'),
                'tolerance' => __('document.monitoring'),
            ],
            'medications'              => [__('document.per_prescription')],
            'fluid_balance_24h'        => ['iv_fluids' => 0, 'oral_feeds' => 0, 'urine' => 0, 'balance' => '0'],
            'head_ultrasound'          => __('document.pending'),
            'attending_neonatologist'  => __('document.role_neonatologist'),
            'nurse'                    => __('document.role_assigned_nurse'),
        ];
    }

    /**
     * Growth Chart
     */
    public function assembleGrowthChart(string $patientId, ?string $admissionId = null): array
    {
        $patient = Patient::find($patientId);

        $dob = $patient->date_of_birth ?? null;
        $sex = $patient->sex ?? __('document.not_recorded');

        $vitals = [];
        if ($patient) {
            $vitals = $patient->vitals()
                ->orderBy('created_at')
                ->get(['weight_kg', 'height_cm', 'created_at']);
        }

        $measurements = [];
        $lastUpdated  = null;

        foreach ($vitals as $vital) {
            $createdAt  = Carbon::parse($vital->created_at);
            $ageMonths  = null;

            if ($dob) {
                $ageMonths = (int) Carbon::parse($dob)->diffInMonths($createdAt);
            }

            $weightKg = $vital->weight_kg ?? null;
            $heightCm = $vital->height_cm ?? null;

            $bmi = null;
            if ($weightKg && $heightCm && $heightCm > 0) {
                $heightM = $heightCm / 100;
                $bmi     = round($weightKg / ($heightM * $heightM), 1);
            }

            $measurements[] = [
                'date'               => $createdAt->toDateString(),
                'age_months'         => $ageMonths,
                'weight_kg'          => $weightKg ?? __('document.not_recorded'),
                'height_cm'          => $heightCm ?? __('document.not_recorded'),
                'bmi'                => $bmi,
                'weight_for_age_z'   => null,
                'height_for_age_z'   => null,
            ];

            $lastUpdated = $createdAt->toDateString();
        }

        return [
            'patient_dob'   => $dob ?? __('document.not_recorded'),
            'sex'           => $sex,
            'measurements'  => $measurements,
            'who_standard'  => __('document.growth_who_standard'),
            'last_updated'  => $lastUpdated ?? __('document.no_records'),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────

    /**
     * Ward and bed labels for an admission, or the "not assigned" placeholder.
     *
     * @return array{0: string, 1: string}
     */
    private function wardAndBed(?Admission $admission): array
    {
        $ward      = __('document.not_assigned');
        $bedNumber = __('document.not_assigned');

        if ($admission && $admission->bed) {
            $ward      = $admission->bed->ward ?? __('document.not_assigned');
            $bedNumber = $admission->bed->bed_number ?? $admission->bed_id ?? __('document.not_assigned');
        }

        return [(string) $ward, (string) $bedNumber];
    }

    private function patientName(?Patient $patient): string
    {
        if (! $patient) {
            return __('document.not_recorded');
        }

        $name = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));

        return $name === '' ? __('document.not_recorded') : $name;
    }

    /**
     * The published title of a clinical code in the reader's language.
     *
     * Reads clinical_code_dictionary (display / display_fr) rather than
     * translating a diagnosis here; the caller's fallback is used only when
     * the code is not in the dictionary.
     */
    private function codeTitle(string $system, string $code, string $fallback): string
    {
        try {
            $row = ClinicalCodeDictionary::query()
                ->where('system', $system)
                ->where('code', $code)
                ->first();
        } catch (\Throwable) {
            $row = null;
        }

        return $row ? $row->label : $fallback;
    }

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

    private function latestVitalSign(string $patientId): ?TriageVitalSign
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
