<?php

namespace App\Services\Chart;

use App\Enums\PreconsultStatus;
use App\Enums\VitalProvenance;
use App\Models\AllergyRecord;
use App\Models\ClinicalCodeDictionary;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\ImmunizationRecord;
use App\Models\LabResult;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\PatientRecordedVital;
use App\Models\PreconsultSubmission;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\ProblemList;
use App\Models\ReferenceRange;
use App\Models\TriageRecord;
use App\Models\TriageVitalSign;
use App\Models\User;
use App\Models\VitalSign;
use App\Services\Preconsult\PreconsultQuestionnaireService;
use App\Services\Triage\TriageDeskService;
use App\Support\ResultFlagResolver;
use App\Support\TriageBandContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the sections of the READ-ONLY web patient chart.
 *
 * Data choices mirror MobileDoctorChartController (the API-tier chart) so the
 * two doors show the same record: same tables, same ordering, same refusals to
 * derive (no BMI, no assessment borrowed from a plan, no "no known allergies"
 * inferred from an empty list). Every method here is a read; there is no write
 * to any clinical table and no delete anywhere in this service.
 *
 * NOT READ, DELIBERATELY: the `fhir_partner_*` tables. How partner-asserted
 * observations, reports and immunizations should sit beside facility-measured
 * rows on a clinician's screen is an open clinical decision
 * (App\Enums\VitalProvenance::PartnerReported documents why they are not the
 * same act). Until that decision is made, this chart shows none of them, and
 * says nothing — an absence here is not a claim that no partner data exists.
 */
final class PatientChartService
{
    /** Hard ceiling on rows read for any one section. */
    public const SCAN_HARD_CAP = 500;

    /** Newest facility-measured recording sets shown on the vitals tab. */
    public const VITALS_LIMIT = 30;

    /** The patient's own home readings shown as context on the vitals tab. */
    public const SELF_REPORTED_LIMIT = 20;

    /**
     * tab → [access-log data category, resource type]. The category is what
     * the audit row says was opened, so it is fixed here, not in a view.
     */
    public const TABS = [
        'summary'       => ['patient_identity',        'Patient'],
        'encounters'    => ['encounters',              'Encounter'],
        'vitals'        => ['vitals',                  'VitalSign'],
        'diagnoses'     => ['diagnoses',               'Diagnosis'],
        'labs'          => ['lab_results',             'LabResult'],
        'immunizations' => ['immunization',            'ImmunizationRecord'],
        'preconsult'    => ['patient_reported_intake', 'PreconsultSubmission'],
        'triage'        => ['triage',                  'TriageRecord'],
        'documents'     => ['documents',               'OfficialDocument'],
    ];

    /** `prescriptions.status` values that are a CURRENT medicine. */
    private const CURRENT_RX_STATUSES = ['active', 'partially_dispensed'];

    public function __construct(
        private readonly TriageDeskService $triage,
        private readonly PreconsultQuestionnaireService $preconsult,
    ) {
    }

    // ══════════════════════════════════════════════════════════════════
    // Summary
    // ══════════════════════════════════════════════════════════════════

    /**
     * The identity card. Age in years and the published age band, NOT the
     * date of birth: the DOB is encrypted at rest and this screen does not
     * need the date to confirm identity — the Health ID does that.
     *
     * @return array<string,mixed>
     */
    public function identity(Patient $patient): array
    {
        $dob = $patient->date_of_birth;

        return [
            'name'        => trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')),
            'health_id'   => $patient->health_id,
            'sex'         => $patient->sex,
            'blood_group' => $patient->blood_group,
            'age_years'   => $dob ? (int) $dob->age : null,
            'age_band'    => $dob ? ResultFlagResolver::ageBand($dob) : null,
            'privacy'     => [
                'requires_consent_for_full_record' => $patient->requiresConsentForFullRecord(),
                'emergency_access_allowed'         => $patient->allowsEmergencyAccess(),
            ],
        ];
    }

    /** @return Collection<int,AllergyRecord> */
    public function allergies(Patient $patient): Collection
    {
        return AllergyRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();
    }

    /** The standing problem list, active entries first. @return Collection<int,ProblemList> */
    public function problems(Patient $patient): Collection
    {
        return ProblemList::where('patient_id', $patient->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByRaw('onset_date DESC NULLS LAST')
            ->orderByDesc('created_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();
    }

    /**
     * Current medicines: one row per line of a prescription that is still
     * active or partially dispensed. Voided, dispensed and expired scripts
     * are history and live on the encounters tab through their visit.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function currentMedicines(Patient $patient): Collection
    {
        $scripts = Prescription::where('patient_id', $patient->id)
            ->whereIn('status', self::CURRENT_RX_STATUSES)
            ->orderByDesc('prescribed_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();

        if ($scripts->isEmpty()) {
            return collect();
        }

        $items = PrescriptionItem::whereIn('prescription_id', $scripts->pluck('id')->all())
            ->orderBy('created_at')
            ->get()
            ->groupBy('prescription_id');

        $prescribers = $this->userNames($scripts->pluck('prescribed_by')->filter()->unique()->all());

        $rows = collect();

        foreach ($scripts as $script) {
            foreach ($items->get($script->id, collect()) as $item) {
                $rows->push([
                    'drug_name'      => $item->drug_name,
                    'dose'           => $item->dose,
                    'route'          => $item->route,
                    'frequency'      => $item->frequency,
                    'duration_days'  => $item->duration_days,
                    'status'         => $script->status,
                    'reference'      => $script->reference,
                    'prescribed_at'  => $script->prescribed_at,
                    'prescribed_by'  => $prescribers[$script->prescribed_by] ?? null,
                ]);
            }
        }

        return $rows;
    }

    // ══════════════════════════════════════════════════════════════════
    // Encounters
    // ══════════════════════════════════════════════════════════════════

    /**
     * Newest first, with the visit's newest clinical note (an amendment is the
     * newer row, so "newest per visit" is the current text).
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function encounters(Patient $patient): Collection
    {
        $rows = Encounter::where('patient_id', $patient->id)
            ->orderByDesc('started_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();

        $notes      = $this->latestNotesByVisit($rows->pluck('visit_id')->filter()->all());
        $doctors    = $this->userNames($rows->pluck('attending_provider_id')->filter()->all());
        $facilities = $this->facilityNames($rows->pluck('facility_id')->filter()->all());

        return $rows->map(function (Encounter $e) use ($notes, $doctors, $facilities) {
            $note = $e->visit_id ? ($notes[$e->visit_id] ?? null) : null;

            return [
                'id'              => $e->id,
                'type'            => $e->encounter_type,
                'status'          => $e->status,
                'started_at'      => $e->started_at,
                'ended_at'        => $e->ended_at,
                'facility_name'   => $facilities[$e->facility_id] ?? null,
                'doctor_name'     => $doctors[$e->attending_provider_id] ?? null,
                'reason'          => $e->reason_for_encounter,
                'discharge'       => $e->discharge_disposition,
                'history'         => $note?->history_of_present_illness,
                'examination'     => $note?->examination_findings,
                'plan'            => $note?->treatment_plan,
                'note_status'     => $note?->status,
            ];
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // Vitals
    // ══════════════════════════════════════════════════════════════════

    /**
     * Facility-measured recordings, newest first, each carrying its provenance
     * and — ONLY for a patient this platform has bands for — advisory flags.
     *
     * Two facility sources, as the API chart documents them: `triage_vital_signs`
     * (patient-scoped, real `recorded_at`) and the legacy `vital_signs`
     * (reached through triage_records → visits, no recorded_at — shown with
     * created_at knowingly, never passed off as the reading time).
     *
     * Interpretation goes through TriageBandContext per row, with the
     * pregnancy status of the triage the reading belongs to: a pregnant
     * patient's readings get no bands even when the patient is an adult. A
     * child, an unknown DOB or a pregnancy yields an EMPTY flag list, and the
     * view prints the refusal instead.
     *
     * @return array{rows: Collection<int,array<string,mixed>>, bands: TriageBandContext}
     */
    public function facilityVitals(Patient $patient): array
    {
        $dob = $patient->date_of_birth;

        $triage = TriageVitalSign::where('patient_id', $patient->id)
            ->with('triageRecord:id,pregnancy_status')
            ->orderByDesc('recorded_at')
            ->limit(self::VITALS_LIMIT)
            ->get();

        $legacy = VitalSign::query()
            ->join('triage_records', 'triage_records.id', '=', 'vital_signs.triage_record_id')
            ->join('visits', 'visits.id', '=', 'triage_records.visit_id')
            ->where('visits.patient_id', $patient->id)
            ->orderByDesc('vital_signs.created_at')
            ->limit(self::VITALS_LIMIT)
            ->select('vital_signs.*', 'triage_records.pregnancy_status as source_pregnancy_status')
            ->get();

        $recorders = $this->userNames($triage->pluck('recorded_by')->filter()->unique()->all());

        $rows = collect();

        foreach ($triage as $v) {
            $bands = TriageBandContext::for($dob, $v->triageRecord?->pregnancy_status);

            $rows->push([
                'provenance'        => VitalProvenance::FacilityMeasured,
                'temperature'       => $v->temperature,
                'pulse_rate'        => $v->pulse_rate,
                'respiratory_rate'  => $v->respiratory_rate,
                'systolic_bp'       => $v->systolic_bp,
                'diastolic_bp'      => $v->diastolic_bp,
                'oxygen_saturation' => $v->oxygen_saturation,
                'weight_kg'         => $v->weight_kg,
                'height_cm'         => $v->height_cm,
                'gcs_score'         => $v->gcs_score,
                'recorded_by'       => $recorders[$v->recorded_by] ?? null,
                'recorded_at'       => $v->recorded_at,
                'time_is_entry'     => false,
                'pregnancy'         => $v->triageRecord?->pregnancy_status === 'pregnant',
                'flags'             => $bands->interpret(TriageBandContext::readingsFrom($v)),
                'sort'              => $v->recorded_at?->toIso8601String() ?? $v->created_at?->toIso8601String() ?? '',
            ]);
        }

        foreach ($legacy as $v) {
            $bands = TriageBandContext::for($dob, $v->source_pregnancy_status);

            $rows->push([
                'provenance'        => VitalProvenance::FacilityMeasured,
                'temperature'       => $v->temperature,
                'pulse_rate'        => $v->pulse,
                'respiratory_rate'  => $v->respiratory_rate,
                'systolic_bp'       => $v->blood_pressure_systolic,
                'diastolic_bp'      => $v->blood_pressure_diastolic,
                'oxygen_saturation' => $v->oxygen_saturation,
                'weight_kg'         => $v->weight,
                'height_cm'         => $v->height,
                'gcs_score'         => null,
                'recorded_by'       => null,
                'recorded_at'       => $v->created_at,
                // `vital_signs` has no recorded_at: this is the entry time.
                'time_is_entry'     => true,
                'pregnancy'         => $v->source_pregnancy_status === 'pregnant',
                'flags'             => $bands->interpret([
                    'temperature'       => ['value' => $v->temperature,               'unit' => '°C'],
                    'pulse_rate'        => ['value' => $v->pulse,                     'unit' => 'bpm'],
                    'respiratory_rate'  => ['value' => $v->respiratory_rate,          'unit' => '/min'],
                    'blood_pressure'    => ['value' => $v->blood_pressure_systolic, 'secondary' => $v->blood_pressure_diastolic, 'unit' => 'mmHg'],
                    'oxygen_saturation' => ['value' => $v->oxygen_saturation,         'unit' => '%'],
                ]),
                'sort'              => $v->created_at?->toIso8601String() ?? '',
            ]);
        }

        return [
            'rows'  => $rows->sortByDesc('sort')->values()->take(self::VITALS_LIMIT),
            // The patient-level answer, used for the heading. Row-level
            // pregnancy can only withdraw bands further, never add them.
            'bands' => TriageBandContext::for($dob, null),
        ];
    }

    /**
     * The patient's OWN readings — context, never evidence. Returned apart from
     * facility rows so no loop can mix them, and never interpreted.
     *
     * @return Collection<int,PatientRecordedVital>
     */
    public function selfReportedVitals(Patient $patient): Collection
    {
        return PatientRecordedVital::query()
            ->where('patient_id', $patient->id)
            ->whereNull('voided_at')
            ->orderByDesc('measured_at')
            ->limit(self::SELF_REPORTED_LIMIT)
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════
    // Diagnoses
    // ══════════════════════════════════════════════════════════════════

    /**
     * Coded diagnoses, newest first, titled from the code dictionary in the
     * reader's language (ICD-10 titles exist in French; the `label` accessor
     * chooses). The row's own `display_name` is the fallback when the code is
     * not in the dictionary — a published title is never re-translated here.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function diagnoses(Patient $patient): Collection
    {
        $rows = Diagnosis::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();

        $codes = $rows->pluck('code')->filter()->map(fn ($c) => strtoupper(trim((string) $c)))->unique()->values();

        $titles = $codes->isEmpty()
            ? collect()
            : ClinicalCodeDictionary::query()
                ->where('system', 'ICD-10')
                ->whereIn('code', $codes->all())
                ->get()
                ->keyBy(fn (ClinicalCodeDictionary $row) => strtoupper((string) $row->code));

        $providers = $this->userNames($rows->pluck('provider_id')->filter()->unique()->all());
        $visitDates = $this->visitDates($rows->pluck('visit_id')->filter()->unique()->all());

        return $rows->map(function (Diagnosis $d) use ($titles, $providers, $visitDates) {
            $code = $d->code !== null ? strtoupper(trim((string) $d->code)) : null;
            $dict = $code !== null ? $titles->get($code) : null;

            return [
                'code'          => $d->code,
                'code_system'   => $d->code_system,
                'title'         => $dict?->label ?: $d->display_name,
                'title_source'  => $dict ? 'dictionary' : 'record',
                'is_primary'    => (bool) $d->is_primary,
                'status'        => $d->status,
                'provider_name' => $providers[$d->provider_id] ?? null,
                'visit_at'      => $d->visit_id ? ($visitDates[$d->visit_id] ?? null) : null,
                'recorded_at'   => $d->created_at,
            ];
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // Laboratory
    // ══════════════════════════════════════════════════════════════════

    /**
     * Released analytes, newest first. The flag on each row is
     * ResultFlagResolver's — the platform's one laboratory-flag authority —
     * carrying its `source` so the view can say whether the laboratory flagged
     * it or a range did. `unflagged` is "nobody compared it", not "normal".
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function labResults(Patient $patient): Collection
    {
        $rows = LabResult::where('patient_id', $patient->id)
            ->orderByRaw('resulted_at DESC NULLS LAST')
            ->orderByDesc('created_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $ranges = $this->publishedRanges($rows);
        $sex    = $patient->sex;
        $dob    = $patient->date_of_birth;

        return $rows->map(function (LabResult $r) use ($ranges, $sex, $dob) {
            $published = ResultFlagResolver::matchPublished($r, $ranges, $sex, $dob);
            $resolved  = ResultFlagResolver::forResult($r, $published);

            return [
                'parameter'       => $r->parameter_name,
                'loinc_code'      => $r->loinc_code,
                'loinc_display'   => $r->loinc_display,
                'value'           => $r->value,
                'unit'            => $r->unit,
                'reference_range' => $r->reference_range,
                'flag'            => $resolved['flag'],
                'flag_source'     => $resolved['source'],
                'severity'        => ResultFlagResolver::severity($resolved['flag']),
                'resulted_at'     => $r->resulted_at,
                'notes'           => $r->notes,
            ];
        });
    }

    // ══════════════════════════════════════════════════════════════════
    // Immunizations
    // ══════════════════════════════════════════════════════════════════

    /**
     * Every dose from every facility — a card that stopped at the facility
     * door would not be a card. Doses entered in error are excluded, as on the
     * patient's own card.
     *
     * @return Collection<int,ImmunizationRecord>
     */
    public function immunizations(Patient $patient): Collection
    {
        return ImmunizationRecord::query()
            ->where('patient_id', $patient->id)
            ->where('status', '!=', 'entered_in_error')
            ->with('facility:id,name')
            ->orderByDesc('administered_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════
    // Pre-consult (patient-reported)
    // ══════════════════════════════════════════════════════════════════

    /**
     * Submitted questionnaires for appointments at THIS facility only — an
     * intake is a disclosure the patient made to the visit it prepares.
     * Drafts are never shown. Everything here is patient-reported, which is
     * why it is its own tab and its own audit category rather than a section
     * of the clinical record (see MobileDoctorPreconsultController).
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function preconsult(Patient $patient, string $facilityId): Collection
    {
        return PreconsultSubmission::query()
            ->where('patient_id', $patient->id)
            ->where('facility_id', $facilityId)
            ->where('status', PreconsultStatus::Submitted->value)
            ->with('answers')
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get()
            ->map(fn (PreconsultSubmission $s) => $this->preconsult->present($s));
    }

    // ══════════════════════════════════════════════════════════════════
    // Triage history
    // ══════════════════════════════════════════════════════════════════

    /**
     * Every triage assessment across facilities, newest first, with the
     * priority it carries NOW (latest reassessment wins — TriageDeskService
     * is the one reader of that rule). Links to the triage desk open only for
     * records at the active facility, because that desk is facility-scoped.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function triageHistory(Patient $patient, ?string $facilityId): Collection
    {
        $records = TriageRecord::query()
            ->whereHas('visit', fn ($q) => $q->where('patient_id', $patient->id))
            ->with(['visit:id,facility_id,patient_id', 'nurse:id,name'])
            ->orderByDesc('created_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();

        $facilities = $this->facilityNames($records->pluck('visit.facility_id')->filter()->unique()->all());

        return $records->map(fn (TriageRecord $r) => [
            'id'                   => $r->id,
            'presenting_complaint' => $r->presenting_complaint,
            'priority'             => $this->triage->currentPriority($r),
            'nurse_name'           => $r->nurse?->name,
            'facility_name'        => $facilities[$r->visit?->facility_id] ?? null,
            'at_active_facility'   => $facilityId !== null && $r->visit?->facility_id === $facilityId,
            'assessed_at'          => $r->created_at,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // Documents
    // ══════════════════════════════════════════════════════════════════

    /**
     * Issued official documents. Listing only: the chart links nowhere that
     * would render a PDF, because those routes are the patient's own.
     *
     * @return Collection<int,OfficialDocument>
     */
    public function documents(Patient $patient): Collection
    {
        return OfficialDocument::where('patient_id', $patient->id)
            ->where('status', 'issued')
            ->with('facility:id,name')
            ->orderByDesc('issued_at')
            ->limit(self::SCAN_HARD_CAP)
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════════

    /** @param  array<int,string>  $visitIds  @return array<string,ClinicalNote> */
    private function latestNotesByVisit(array $visitIds): array
    {
        if ($visitIds === []) {
            return [];
        }

        $byVisit = [];

        foreach (ClinicalNote::whereIn('visit_id', array_values(array_unique($visitIds)))->orderBy('created_at')->get() as $note) {
            $byVisit[$note->visit_id] = $note;
        }

        return $byVisit;
    }

    /** @param  array<int,string>  $visitIds  @return array<string,\Illuminate\Support\Carbon|null> */
    private function visitDates(array $visitIds): array
    {
        if ($visitIds === []) {
            return [];
        }

        return DB::table('visits')
            ->whereIn('id', array_values(array_unique($visitIds)))
            ->pluck('started_at', 'id')
            ->map(fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v) : null)
            ->all();
    }

    /** @param  array<int,string>  $userIds  @return array<string,string> */
    private function userNames(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::whereIn('id', array_values(array_unique($userIds)))->pluck('name', 'id')->all();
    }

    /**
     * Facility id → name. `facility_id` columns point at the tenant register
     * on some rows and the public directory on others; both are consulted.
     *
     * @param  array<int,string>  $facilityIds
     * @return array<string,string>
     */
    private function facilityNames(array $facilityIds): array
    {
        if ($facilityIds === []) {
            return [];
        }

        $ids   = array_values(array_unique($facilityIds));
        $names = DB::table('facilities')->whereIn('id', $ids)->pluck('name', 'id')->all();

        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            $names += DB::table('care_facilities')->whereIn('id', $missing)->pluck('facility_name', 'id')->all();
        }

        return $names;
    }

    /** @param  Collection<int,LabResult>  $analytes  @return Collection<int,ReferenceRange> */
    private function publishedRanges(Collection $analytes): Collection
    {
        $names = $analytes->map(fn (LabResult $r) => strtolower(trim((string) $r->parameter_name)))->filter()->unique()->values();
        $loincs = $analytes->map(fn (LabResult $r) => trim((string) $r->loinc_code))->filter()->unique()->values();

        if ($names->isEmpty() && $loincs->isEmpty()) {
            return collect();
        }

        return ReferenceRange::query()
            ->where('is_active', true)
            ->where(function ($query) use ($names, $loincs) {
                if ($names->isNotEmpty()) {
                    $query->orWhereIn(DB::raw('lower(test_name)'), $names->all());
                    $query->orWhereIn(DB::raw('lower(test_code)'), $names->all());
                }
                if ($loincs->isNotEmpty()) {
                    $query->orWhereIn('loinc_code', $loincs->all());
                }
            })
            ->get();
    }
}
