<?php

namespace App\Services\Immunisation;

use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\VaccinationSchedule;
use App\Services\Chart\PatientChartAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The facility-wide immunisation board: who is due today, who is overdue, and
 * what this facility recently gave.
 *
 * NOTHING IS INVENTED. "Due" and "overdue" come only from
 * `vaccination_schedules` rows (status due/overdue, a real due_date), which the
 * clinician app writes when a course is planned and ImmunisationRecordService
 * closes when a dose is given. There is no national EPI table in this codebase,
 * so a patient with no schedule rows is simply not on the due lists — an empty
 * list is the honest answer, never a computed guess.
 *
 * WHO APPEARS. Candidates are patients with a tie to THIS facility (a dose
 * recorded here, an appointment here, or an active consent grant to it). Each
 * candidate is then put through the chart's own door, PatientChartAccess
 * (ClinicalRelationshipGate), and kept only when that verdict covers the
 * `immunization` category for this clinician. A tie is a reason to look, not a
 * reason to see. Every patient named on the board is access-logged as a read;
 * one whose log row cannot be written is left off rather than shown untracked.
 */
class ImmunisationDeskBoard
{
    public const DATA_CATEGORY = 'immunization';
    public const RESOURCE_TYPE = 'ImmunizationRecord';

    /** Bound on the candidate set: the gate runs once per candidate. */
    private const CANDIDATE_LIMIT = 300;
    private const LIST_LIMIT      = 100;
    private const RECENT_DAYS     = 14;

    /** @var array<string, PatientChartAccess> */
    private array $access = [];

    /** `vaccination_schedules.status` values that still await a dose. */
    private const OPEN_STATUSES = ['due', 'overdue'];

    /**
     * @return array{kpis: array<string,int>, due_today: Collection, overdue: Collection, recent: Collection}
     */
    public function build(string $facilityId, string $clinicianId): array
    {
        $today = Carbon::today();

        $visible = $this->visiblePatients($facilityId, $clinicianId);
        $ids     = $visible->keys()->all();

        $open = fn () => VaccinationSchedule::query()
            ->whereIn('patient_id', $ids)
            ->whereIn('status', self::OPEN_STATUSES)
            ->whereNull('completed_by_immunization_id')
            ->whereNotNull('due_date');

        $dueToday = $open()->whereDate('due_date', $today)
            ->orderBy('vaccine_name')->limit(self::LIST_LIMIT)->get();

        $overdue = $open()->whereDate('due_date', '<', $today)
            ->orderBy('due_date')->limit(self::LIST_LIMIT)->get();

        $given = fn () => ImmunizationRecord::query()
            ->where('facility_id', $facilityId)
            ->whereIn('patient_id', $ids)
            ->where('status', 'completed');

        $recent = $given()
            ->where('administered_at', '>=', $today->copy()->subDays(self::RECENT_DAYS - 1))
            ->orderByDesc('administered_at')->limit(self::LIST_LIMIT)->get();

        // Only the patients actually named on the board are disclosed, so only
        // they are access-logged; a row whose log cannot be written is dropped.
        $logged = [];
        foreach ($dueToday->concat($overdue)->concat($recent)->pluck('patient_id')->unique() as $pid) {
            $logged[$pid] = $this->access[$pid]->auditRead(self::DATA_CATEGORY, self::RESOURCE_TYPE);
        }

        $attach = fn (Collection $rows) => $rows
            ->filter(fn ($row) => $logged[$row->patient_id] ?? false)
            ->each(fn ($row) => $row->setRelation('patient', $visible[$row->patient_id]))
            ->values();

        return [
            'kpis' => [
                'given_today' => $given()->where('administered_at', '>=', $today)->count(),
                'due_today'   => $open()->whereDate('due_date', $today)->count(),
                'overdue'     => $open()->whereDate('due_date', '<', $today)->count(),
                'given_week'  => $given()->where('administered_at', '>=', $today->copy()->subDays(6))->count(),
            ],
            'due_today' => $attach($dueToday),
            'overdue'   => $attach($overdue),
            'recent'    => $attach($recent),
            'patients'  => $visible->count(),
        ];
    }

    /**
     * Patients tied to this facility whom this clinician may read
     * immunisations for, keyed by id.
     *
     * @return Collection<string, Patient>
     */
    private function visiblePatients(string $facilityId, string $clinicianId): Collection
    {
        $candidateIds = DB::table('immunization_records')->where('facility_id', $facilityId)->whereNull('deleted_at')->select('patient_id')
            ->union(DB::table('appointments')->where('facility_id', $facilityId)->whereNotNull('patient_id')->select('patient_id'))
            ->union(DB::table('consent_grants')->where('facility_id', $facilityId)->where('status', 'active')->select('patient_id'))
            ->limit(self::CANDIDATE_LIMIT)
            ->pluck('patient_id')
            ->filter()
            ->unique()
            ->all();

        if ($candidateIds === []) {
            return collect();
        }

        return Patient::query()
            ->whereIn('id', $candidateIds)
            ->get()
            ->filter(function (Patient $patient) use ($clinicianId, $facilityId) {
                $access = PatientChartAccess::evaluate($patient, $clinicianId, $facilityId);
                $this->access[$patient->id] = $access;

                return $access->covers(self::DATA_CATEGORY);
            })
            ->keyBy('id');
    }
}
