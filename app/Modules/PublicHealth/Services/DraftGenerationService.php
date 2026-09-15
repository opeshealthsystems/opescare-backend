<?php

namespace App\Modules\PublicHealth\Services;

use App\Models\LabOrder;
use App\Models\PublicHealthReport;
use App\Models\ReportItem;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imported from the platform (Modules/PublicHealth/Services/DraftGenerationService)
 * and adapted for the Finder's DHIS2 monthly aggregate.
 *
 * AGGREGATE ONLY: every query below is a COUNT grouped by facility (and by vaccine
 * or test where DHIS2 disaggregates). No patient id, name, Health ID, date of birth
 * or free text is ever selected, so none can reach a draft or DHIS2.
 *
 * Removed platform report builders (and why):
 *  - notifiable_disease: counted diagnoses (EMR) and could create an
 *    'identifiable' report when a rule required patient identity — a patient-level path;
 *  - facility_aggregate: outpatient visits / inpatient admissions come from the
 *    visits encounter + ADT module, which the Finder does not run;
 *  - lab_surveillance: "lab positive" was read from clinical diagnoses (EMR);
 *  - pharmacy_stockout / blood_shortage: point-in-time stock snapshots, not monthly
 *    counts, and not a MINSANTE dataValueSet;
 *  - the ReportingRule loop: the Finder has one report, the DHIS2 monthly aggregate.
 *
 * Elements that remain (indicator code => source):
 *  - appointments_completed            appointments.status = completed, scheduled_at in month
 *  - immunisation_doses.{vaccine}      immunization_records administered at the facility in month
 *                                      (not historical, not voided/not-done/entered-in-error)
 *  - lab_tests_released.{test}         lab_orders released (LabOrder::RELEASED_STATUSES), resulted_at in month
 *  - prescriptions_dispensed           prescriptions.dispensed_at in month, not voided
 *  - referrals_sent                    referral_cases.referring_facility_id, sent (not draft/cancelled), created in month
 *  - referrals_received                referral_cases.receiving_facility_id, accepted_at in month
 * Immunisation age bands were dropped: date of birth is encrypted and no age is
 * stored with the dose, so a band would have to be invented.
 * A count of zero is not written: a month with no source rows sends nothing for that element.
 */
class DraftGenerationService
{
    public const REPORT_TYPE_CODE = 'dhis2_monthly_aggregate';

    /**
     * Generate the monthly DHIS2 draft for each facility.
     *
     * @param  array<int, string>  $facilityIds
     * @return array<int, PublicHealthReport>
     */
    public function generateDrafts(array $facilityIds, string $yearMonth, ?string $userId = null): array
    {
        $generatedReports = [];
        foreach ($facilityIds as $facilityId) {
            $report = $this->generateMonthlyDraft((string) $facilityId, $yearMonth, $userId);
            if ($report) {
                $generatedReports[] = $report;
            }
        }

        return $generatedReports;
    }

    /**
     * Build (or rebuild, while still a draft) the facility's aggregate for the month.
     * An approved or submitted report is never rewritten; it is returned untouched.
     */
    public function generateMonthlyDraft(string $facilityId, string $yearMonth, ?string $userId = null): PublicHealthReport
    {
        $from = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $yearMonth . '-01 00:00:00')->startOfMonth();
        $to = $from->endOfMonth();
        $type = $this->reportType();

        return DB::transaction(function () use ($facilityId, $from, $to, $type, $userId) {
            // Check if a report already exists to avoid duplication
            $report = PublicHealthReport::where('report_type_id', $type->id)
                ->where('facility_id', $facilityId)
                ->where('reporting_period_start', $from)
                ->lockForUpdate()
                ->first();

            if ($report && $report->status !== 'draft') {
                return $report;
            }

            $isNew = ! $report;
            $report ??= PublicHealthReport::create([
                'report_type_id' => $type->id,
                'facility_id' => $facilityId,
                'reporting_period_start' => $from,
                'reporting_period_end' => $to,
                'status' => 'draft',
                'sensitivity_level' => 'aggregate',
                'data_classification' => 'internal',
                'generated_by_system' => $userId === null,
                'data_quality_score' => 100,
                'requires_review' => true,
                'created_by' => $userId,
            ]);

            $report->items()->delete();
            foreach ($this->aggregate($facilityId, $from, $to) as $row) {
                ReportItem::create(['report_id' => $report->id] + $row);
            }

            $report->forceFill(['updated_by' => $userId, 'payload_json' => ['period' => $from->format('Ym')]])->save();

            // changed_by is NOT NULL: a scheduled (system) run is audited by the command instead.
            if ($userId !== null) ReportStatusHistory::create([
                'report_id' => $report->id,
                'old_status' => $isNew ? 'none' : 'draft',
                'new_status' => 'draft',
                'changed_by' => $userId,
                'reason' => $isNew ? 'generated' : 'regenerated',
                'changed_at' => now(),
            ]);

            // Run data quality scoring
            (new DataQualityCheckService())->runQualityChecks($report->fresh());

            return $report->fresh(['items', 'qualityChecks']);
        });
    }

    /** @return array<int, array{indicator_code: string, indicator_name: string, value: int, metadata_json?: array}> */
    public function aggregate(string $facilityId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = [];

        $appointments = DB::table('appointments')
            ->where('facility_id', $facilityId)
            ->where('status', 'completed')
            ->whereBetween('scheduled_at', [$from, $to])
            ->count();
        $this->push($rows, 'appointments_completed', 'Appointments completed', $appointments);

        $doses = DB::table('immunization_records')
            ->where('facility_id', $facilityId)
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->where('is_historical', false)->orWhereNull('is_historical'))
            ->where(fn ($q) => $q->whereNull('status')
                ->orWhereNotIn('status', ['entered-in-error', 'entered_in_error', 'not-done', 'not_done', 'voided']))
            ->whereBetween('administered_at', [$from, $to])
            ->selectRaw("COALESCE(NULLIF(vaccine_code, ''), vaccine_name) AS vaccine, MAX(vaccine_name) AS name, COUNT(*) AS total")
            ->groupByRaw("COALESCE(NULLIF(vaccine_code, ''), vaccine_name)")
            ->get();
        foreach ($doses as $dose) {
            if ($dose->vaccine === null || $dose->vaccine === '') {
                continue;
            }
            $this->push($rows, 'immunisation_doses.' . $this->slug($dose->vaccine), 'Immunisation doses — ' . ($dose->name ?: $dose->vaccine), (int) $dose->total, ['vaccine' => $dose->vaccine]);
        }

        $tests = DB::table('lab_orders')
            ->where('facility_id', $facilityId)
            ->whereIn('status', LabOrder::RELEASED_STATUSES)
            ->whereBetween('resulted_at', [$from, $to])
            ->selectRaw("COALESCE(NULLIF(test_code, ''), test_name) AS test, MAX(test_name) AS name, COUNT(*) AS total")
            ->groupByRaw("COALESCE(NULLIF(test_code, ''), test_name)")
            ->get();
        foreach ($tests as $test) {
            if ($test->test === null || $test->test === '') {
                continue;
            }
            $this->push($rows, 'lab_tests_released.' . $this->slug($test->test), 'Lab tests released — ' . ($test->name ?: $test->test), (int) $test->total, ['test' => $test->test]);
        }

        $dispensed = DB::table('prescriptions')
            ->where('facility_id', $facilityId)
            ->whereNull('voided_at')
            ->whereBetween('dispensed_at', [$from, $to])
            ->count();
        $this->push($rows, 'prescriptions_dispensed', 'Prescriptions dispensed', $dispensed);

        $sent = DB::table('referral_cases')
            ->where('referring_facility_id', $facilityId)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $this->push($rows, 'referrals_sent', 'Referrals sent', $sent);

        $received = DB::table('referral_cases')
            ->where('receiving_facility_id', $facilityId)
            ->whereNull('deleted_at')
            ->whereBetween('accepted_at', [$from, $to])
            ->count();
        $this->push($rows, 'referrals_received', 'Referrals received', $received);

        return $rows;
    }

    public function reportType(): ReportType
    {
        return ReportType::firstOrCreate(['code' => self::REPORT_TYPE_CODE], [
            'name' => 'DHIS2 monthly aggregate (MINSANTE)',
            'description' => 'Monthly aggregate counts per facility for the national DHIS2. Never patient-level.',
            'sensitivity_level' => 'aggregate',
            'default_review_required' => true,
            'is_active' => true,
        ]);
    }

    private function push(array &$rows, string $code, string $name, int $value, array $meta = []): void
    {
        // Never invent: no source rows means nothing for this element.
        if ($value <= 0) {
            return;
        }
        $rows[] = array_filter([
            'indicator_code' => Str::limit($code, 120, ''),
            'indicator_name' => Str::limit($name, 250, ''),
            'value' => $value,
            'metadata_json' => $meta ?: null,
        ], fn ($v) => $v !== null);
    }

    private function slug(string $value): string
    {
        return Str::slug(Str::lower($value), '_') ?: md5($value);
    }
}
