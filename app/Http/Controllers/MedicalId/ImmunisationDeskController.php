<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\ClinicalCodeDictionary;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Chart\PatientChartAccess;
use App\Services\Chart\PatientChartService;
use App\Services\Immunisation\ImmunisationDeskBoard;
use App\Services\Immunisation\ImmunisationRecordService;
use App\Services\Portal\DeskFacilityContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The web immunisation desk (HC-2) — record a dose onto a patient's card from
 * a desk PC on vaccination day.
 *
 * THE DOOR IS THE CHART'S DOOR. PatientChartAccess (ClinicalRelationshipGate)
 * decides, exactly as PatientChartController applies it: no basis → 404 and a
 * `denied` access-log row. Writing requires the full-record basis, as the
 * clinician app's write path does.
 *
 * THE WRITE IS THE APP'S WRITE. App\Services\Immunisation\ImmunisationRecordService
 * holds the duplicate guard, the insert and the course-line closure; the
 * clinician app's MobileDoctorImmunizationController calls the same service.
 *
 * VOCABULARY. A vaccine is picked from the imported CDC CVX codes in
 * clinical_code_dictionary (kind `vaccine`, active). A code not in that list is
 * refused; a vaccine name is never typed free-hand here. An empty dictionary
 * renders an honest empty state instead of a form.
 *
 * IMMUTABLE. There is no update and no delete. The one correction is to mark a
 * dose entered-in-error (recording facility only) and record the truth anew.
 * `immunization_records` has no notes column, so this desk records no notes.
 */
class ImmunisationDeskController extends Controller
{
    private const DATA_CATEGORY = 'immunization';
    private const RESOURCE_TYPE = 'ImmunizationRecord';
    private const LIST_LIMIT    = 200;
    private const VACCINE_LIMIT = 400;

    public function __construct(private readonly ImmunisationRecordService $doses)
    {
    }

    /**
     * The facility-wide desk: KPIs, due today, overdue, recently given.
     * Built by ImmunisationDeskBoard — patients tied to this facility AND
     * passing the chart's gate, from real schedule rows only.
     */
    public function desk(Request $request, ImmunisationDeskBoard $board): View
    {
        $facilityId = $this->facilityId();

        $tab = in_array($request->query('tab'), ['due', 'overdue', 'recent'], true)
            ? $request->query('tab') : 'due';

        return view('portals.immunisations.desk', $board->build($facilityId, (string) auth()->id()) + [
            'tab' => $tab,
        ]);
    }

    /**
     * Desk search: EXACT Health ID only — the platform does not let a
     * professional enumerate patients by name (same rule as the triage,
     * queue and prescriber desks). A hit redirects to the per-patient page,
     * which applies the chart's gate itself; a miss is a flash, not a 404,
     * and says nothing about whether the patient exists elsewhere.
     */
    public function find(Request $request): RedirectResponse
    {
        $this->facilityId();

        $validated = $request->validate(['health_id' => ['required', 'string', 'max:64']]);

        $patient = Patient::resolveHealthId(trim($validated['health_id']));

        if ($patient === null) {
            return redirect()->route('portals.immunisations.desk')->withInput()
                ->with('error', __('immunisation_desk.search_not_found'));
        }

        return redirect()->route('portals.immunisations.index', $patient->id);
    }

    public function index(string $patientId): View
    {
        [$patient, $access, $facilityId] = $this->open($patientId);

        $consentRequired = ! $access->covers(self::DATA_CATEGORY);

        if ($consentRequired) {
            $access->auditDenial(self::DATA_CATEGORY, self::RESOURCE_TYPE);
        } elseif (! $access->auditRead(self::DATA_CATEGORY, self::RESOURCE_TYPE)) {
            abort(503, __('immunisation_desk.audit_unavailable'));
        }

        $records = $consentRequired ? collect() : ImmunizationRecord::query()
            ->where('patient_id', $patient->id)
            ->with('facility:id,name')
            ->orderByDesc('administered_at')
            ->limit(self::LIST_LIMIT)
            ->get();

        return view('portals.immunisations.index', [
            'patient'         => $patient,
            'identity'        => app(PatientChartService::class)->identity($patient),
            'records'         => $records,
            'consentRequired' => $consentRequired,
            'facilityId'      => $facilityId,
            'vaccines'        => $consentRequired ? collect() : $this->vaccines(),
            'statuses'        => ImmunisationRecordService::WRITABLE_STATUSES,
            'routes'          => ImmunisationRecordService::ROUTES,
        ]);
    }

    public function store(Request $request, string $patientId): RedirectResponse
    {
        [$patient, $access, $facilityId] = $this->open($patientId);

        if (! $access->covers(self::DATA_CATEGORY)) {
            $access->auditDenial(self::DATA_CATEGORY, self::RESOURCE_TYPE);
            abort(404);
        }

        $validated = $request->validate([
            'vaccine_code'      => ['required', 'string', 'max:50'],
            'dose_number'       => ['nullable', 'integer', 'min:1', 'max:20'],
            'administered_on'   => ['required', 'date', 'before_or_equal:today'],
            'lot_number'        => ['nullable', 'string', 'max:100'],
            'site'              => ['nullable', 'string', 'max:100'],
            'route'             => ['nullable', Rule::in(ImmunisationRecordService::ROUTES)],
            'status'            => ['required', Rule::in(ImmunisationRecordService::WRITABLE_STATUSES)],
            'not_done_reason'   => ['nullable', 'required_if:status,not_done', 'string', 'max:2000'],
            'is_historical'     => ['nullable', 'boolean'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ]);

        $vaccine = $this->vaccineQuery()->where('code', $validated['vaccine_code'])->first();

        if (! $vaccine) {
            return back()->withInput()->withErrors(['vaccine_code' => __('immunisation_desk.vaccine_unknown')]);
        }

        // A date, not a time: a dose given today is stamped now; an earlier
        // date is that day. Never in the future (validated above).
        $day = Carbon::parse($validated['administered_on'])->startOfDay();
        $administeredAt = $day->isToday() ? Carbon::now() : $day->setTime(12, 0);

        $doseNumber = isset($validated['dose_number']) ? (int) $validated['dose_number'] : null;

        if (! $request->boolean('confirm_duplicate')
            && $this->doses->findDuplicate($patient->id, $vaccine->code, $administeredAt, $doseNumber)) {
            return back()->withInput()->with('duplicate', true)
                ->withErrors(['vaccine_code' => __('immunisation_desk.duplicate_dose')]);
        }

        if (! $this->audit($patient->id, 'create', null, $facilityId)) {
            abort(503, __('immunisation_desk.audit_unavailable'));
        }

        $this->doses->record(
            $patient,
            $facilityId,
            (string) auth()->id(),
            [
                'vaccine_code'    => $vaccine->code,
                'vaccine_system'  => $vaccine->system,
                'vaccine_name'    => $vaccine->display,
                'lot_number'      => $validated['lot_number'] ?? null,
                'dose_number'     => $doseNumber,
                'route'           => $validated['route'] ?? null,
                'site'            => $validated['site'] ?? null,
                'not_done_reason' => $validated['not_done_reason'] ?? null,
            ],
            $validated['status'],
            $request->boolean('is_historical'),
            $administeredAt,
        );

        return redirect()->route('portals.immunisations.index', $patient->id)
            ->with('success', __('immunisation_desk.recorded'));
    }

    public function void(Request $request, string $patientId, string $recordId): RedirectResponse
    {
        [$patient, $access, $facilityId] = $this->open($patientId);

        if (! $access->covers(self::DATA_CATEGORY)) {
            $access->auditDenial(self::DATA_CATEGORY, self::RESOURCE_TYPE);
            abort(404);
        }

        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        $record = ImmunizationRecord::query()
            ->whereKey($recordId)
            ->where('patient_id', $patient->id)
            ->first();

        if (! $record) {
            abort(404);
        }

        // Correcting another facility's entry would be an overwrite of their record.
        if ($record->facility_id !== $facilityId) {
            abort(403, __('immunisation_desk.not_recording_facility'));
        }

        if ($record->status === 'entered_in_error') {
            return back()->with('error', __('immunisation_desk.already_voided'));
        }

        if (! $this->audit($patient->id, 'update', $record->id, $facilityId)) {
            abort(503, __('immunisation_desk.audit_unavailable'));
        }

        $this->doses->void($record, $validated['reason']);

        return redirect()->route('portals.immunisations.index', $patient->id)
            ->with('success', __('immunisation_desk.voided'));
    }

    // ── internals ─────────────────────────────────────────────────────────

    /**
     * Resolve the patient (uuid or EXACT Health ID) and the chart's verdict.
     * No patient, or no basis → 404 (the latter access-logged as denied).
     *
     * @return array{0: Patient, 1: PatientChartAccess, 2: string}
     */
    private function open(string $patientId): array
    {
        $facilityId = $this->facilityId();

        $key = trim($patientId);
        $patient = Str::isUuid($key) ? Patient::resolveId($key) : Patient::resolveHealthId($key);

        if ($patient === null) {
            abort(404);
        }

        $access = PatientChartAccess::evaluate($patient, (string) auth()->id(), $facilityId);

        if (! $access->holds()) {
            $access->auditDenial(self::DATA_CATEGORY, self::RESOURCE_TYPE);
            abort(404);
        }

        return [$patient, $access, $facilityId];
    }

    private function vaccineQuery()
    {
        return ClinicalCodeDictionary::query()
            ->where('system', 'CVX')
            ->where('kind', 'vaccine')
            ->where('is_active', true);
    }

    private function vaccines()
    {
        return $this->vaccineQuery()
            ->orderBy('display')
            ->limit(self::VACCINE_LIMIT)
            ->get(['code', 'system', 'display', 'short_name']);
    }

    private function facilityId(): string
    {
        $facilityId = app(DeskFacilityContext::class)->resolve();

        if (! $facilityId) {
            abort(403, __('immunisation_desk.no_facility_context'));
        }

        return (string) $facilityId;
    }

    private function audit(string $patientId, string $accessType, ?string $resourceId, string $facilityId): bool
    {
        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: (string) auth()->id(),
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: 'treatment:immunization',
                dataCategory: self::DATA_CATEGORY,
                resourceType: self::RESOURCE_TYPE,
                resourceId: $resourceId,
                accessType: $accessType,
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Web immunisation action refused: access log could not be written.', [
                'patient_id' => $patientId,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }
}
