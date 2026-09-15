<?php
namespace App\Services\Interoperability;

use App\Models\Dhis2DataElementMapping;
use App\Models\Dhis2OrgUnitMapping;
use App\Models\Dhis2PushRecord;
use App\Models\PublicHealthReport;
use App\Models\ReportStatusHistory;
use App\Services\Integration\Dhis2Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Imported from the platform, where it posted one arbitrary data point
 * (data_element / period / org_unit / value) with credentials passed in.
 *
 * Finder adaptations:
 *  - it pushes an APPROVED monthly aggregate report only; a draft is refused;
 *  - org unit and data element ids come from the mapping tables (config fallback
 *    for data elements); an unmapped facility is skipped as "not_mapped", an
 *    unmapped indicator is never sent;
 *  - idempotent per (facility, period, indicator): a value DHIS2 already imported
 *    unchanged is not sent again; every attempt records DHIS2's import summary;
 *  - HTTP goes through Dhis2Service (credentials from config, never logged).
 * Only indicator codes and counts are sent — the report holds nothing else.
 */
class Dhis2PushService
{
    public const PUSHABLE_STATUSES = ['approved_for_submission', 'submission_failed', 'submitted'];

    public function __construct(private Dhis2Service $dhis2) {}

    public function orgUnitFor(string $facilityId): ?string
    {
        return Dhis2OrgUnitMapping::where('facility_id', $facilityId)->value('org_unit_id') ?: null;
    }

    /** @return array{data_element_id: string, category_option_combo_id: ?string}|null */
    public function dataElementFor(string $indicatorCode): ?array
    {
        $row = Dhis2DataElementMapping::where('indicator_code', $indicatorCode)->first();
        if ($row) {
            return ['data_element_id' => $row->data_element_id, 'category_option_combo_id' => $row->category_option_combo_id];
        }

        $fallback = (string) (config('dhis2.data_element_map')[$indicatorCode] ?? '');

        return $fallback !== '' ? ['data_element_id' => $fallback, 'category_option_combo_id' => null] : null;
    }

    /**
     * @return array{outcome: string, sent: int, skipped_unchanged: int, unmapped: int, summary?: ?array, error?: string}
     *   outcome: pushed | nothing_to_send | not_mapped | not_configured | not_approved | failed
     */
    public function push(PublicHealthReport $report, ?string $userId = null): array
    {
        if (! in_array($report->status, self::PUSHABLE_STATUSES, true)) {
            return ['outcome' => 'not_approved', 'sent' => 0, 'skipped_unchanged' => 0, 'unmapped' => 0];
        }
        if (! $this->dhis2->isConfigured()) {
            return ['outcome' => 'not_configured', 'sent' => 0, 'skipped_unchanged' => 0, 'unmapped' => 0];
        }
        $orgUnit = $this->orgUnitFor($report->facility_id);
        if ($orgUnit === null) {
            return ['outcome' => 'not_mapped', 'sent' => 0, 'skipped_unchanged' => 0, 'unmapped' => 0];
        }

        $period = $report->reporting_period_start->format('Ym');
        $values = [];
        $pending = [];
        $unchanged = 0;
        $unmapped = 0;

        foreach ($report->items()->get() as $item) {
            $de = $this->dataElementFor($item->indicator_code);
            if ($de === null) {
                $unmapped++;
                continue;
            }
            $existing = Dhis2PushRecord::where('facility_id', $report->facility_id)
                ->where('period', $period)
                ->where('indicator_code', $item->indicator_code)
                ->first();
            if ($existing && $existing->status === Dhis2PushRecord::STATUS_IMPORTED
                && (int) $existing->value === (int) $item->value
                && $existing->data_element_id === $de['data_element_id']
                && $existing->category_option_combo_id === $de['category_option_combo_id']
                && $existing->org_unit_id === $orgUnit) {
                $unchanged++;
                continue;
            }
            $values[] = [
                'dataElement' => $de['data_element_id'],
                'categoryOptionCombo' => $de['category_option_combo_id'],
                'value' => (int) $item->value,
            ];
            $pending[] = [$item, $de];
        }

        if ($values === []) {
            $this->transition($report, 'submitted', $userId, 'nothing_to_send');

            return ['outcome' => 'nothing_to_send', 'sent' => 0, 'skipped_unchanged' => $unchanged, 'unmapped' => $unmapped];
        }

        $result = $this->dhis2->pushDataValues($orgUnit, $period, $values);
        $ok = (bool) $result['success'];

        DB::transaction(function () use ($report, $pending, $period, $orgUnit, $result, $ok, $userId) {
            foreach ($pending as [$item, $de]) {
                $record = Dhis2PushRecord::firstOrNew([
                    'facility_id' => $report->facility_id,
                    'period' => $period,
                    'indicator_code' => $item->indicator_code,
                ]);
                $record->fill([
                    'report_id' => $report->id,
                    'org_unit_id' => $orgUnit,
                    'data_element_id' => $de['data_element_id'],
                    'category_option_combo_id' => $de['category_option_combo_id'],
                    'value' => (int) $item->value,
                    'status' => $ok ? Dhis2PushRecord::STATUS_IMPORTED : Dhis2PushRecord::STATUS_FAILED,
                    'import_summary' => $result['summary'] ?? null,
                    'attempts' => (int) $record->attempts + 1,
                    'pushed_by' => $userId,
                    'pushed_at' => now(),
                ])->save();
            }

            $this->transition($report, $ok ? 'submitted' : 'submission_failed', $userId, $ok ? 'pushed' : 'push_failed');
        });

        if (! $ok) {
            Log::warning('DHIS2 push unsuccessful', ['report_id' => $report->id, 'period' => $period, 'error' => $result['error'] ?? null]);
        }

        return [
            'outcome' => $ok ? 'pushed' : 'failed',
            'sent' => count($values),
            'skipped_unchanged' => $unchanged,
            'unmapped' => $unmapped,
            'summary' => $result['summary'] ?? null,
            'error' => $result['error'] ?? null,
        ];
    }

    private function transition(PublicHealthReport $report, string $to, ?string $userId, string $reason): void
    {
        $from = $report->status;
        $report->forceFill(['status' => $to, 'updated_by' => $userId])->save();
        if ($userId === null) {
            return; // changed_by is NOT NULL; the job's audit event records a system push
        }
        ReportStatusHistory::create([
            'report_id' => $report->id,
            'old_status' => $from,
            'new_status' => $to,
            'changed_by' => $userId,
            'reason' => $reason,
            'changed_at' => now(),
        ]);
    }
}
