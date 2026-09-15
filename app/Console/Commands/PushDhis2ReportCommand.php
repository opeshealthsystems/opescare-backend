<?php

namespace App\Console\Commands;

use App\Jobs\PushPublicHealthToDhis2Job;
use App\Models\Dhis2OrgUnitMapping;
use App\Models\PublicHealthReport;
use App\Modules\PublicHealth\Services\DraftGenerationService;
use App\Services\Integration\Dhis2Service;
use App\Services\Interoperability\Dhis2PushService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Imported from the platform (same signature name). The platform command built
 * the aggregate from visits/patients/diagnoses and pushed it straight to DHIS2
 * with no review. Finder adaptations:
 *  - by default it only GENERATES monthly drafts (DraftGenerationService);
 *  - --push queues APPROVED reports only — drafts are never pushed from here;
 *  - the platform aggregate (opd by sex via a patients join, under-5, malaria /
 *    hypertension / diabetes diagnoses, maternal visits hard-coded to 0) was
 *    dropped: see DraftGenerationService for what remains and why.
 */
class PushDhis2ReportCommand extends Command
{
    protected $signature = 'opescare:push-dhis2
                            {--month= : Year-month (YYYY-MM). Defaults to last month.}
                            {--facility= : Facility ID. Defaults to every facility mapped to a DHIS2 org unit.}
                            {--push : Queue the push of APPROVED reports for the month (never drafts).}
                            {--dry-run : Print the aggregate without writing a draft.}
                            {--test-connection : Test DHIS2 connection only.}';

    protected $description = 'Generate monthly DHIS2 aggregate drafts (MINSANTE Cameroon); --push sends approved reports';

    private Dhis2Service $dhis2;

    // Resolved per run, not in the constructor: commands are built at boot, before
    // the config a run reads (the platform injected it in the constructor).
    public function handle(DraftGenerationService $drafts, Dhis2Service $dhis2): int
    {
        $this->dhis2 = $dhis2;
        if ($this->option('test-connection')) {
            $result = $this->dhis2->testConnection();
            if ($result['connected']) {
                $this->info('DHIS2 connected' . ($result['version'] ? " (version {$result['version']})" : ''));
                return self::SUCCESS;
            }
            $this->error('DHIS2 connection failed: ' . ($result['error'] ?? 'unknown'));
            return self::FAILURE;
        }

        $month = $this->option('month') ?? Carbon::now()->subMonth()->format('Y-m');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->error('Invalid month format. Use YYYY-MM, e.g. 2026-05');
            return self::FAILURE;
        }

        $facilityIds = $this->option('facility')
            ? [$this->option('facility')]
            : Dhis2OrgUnitMapping::pluck('facility_id')->all();

        if ($this->option('push')) {
            if (! $this->dhis2->isConfigured()) {
                $this->error('DHIS2 is not configured; nothing pushed.');
                return self::FAILURE;
            }
            $type = $drafts->reportType();
            $reports = PublicHealthReport::where('report_type_id', $type->id)
                ->whereIn('facility_id', $facilityIds)
                ->where('reporting_period_start', Carbon::parse("{$month}-01")->startOfMonth())
                ->whereIn('status', Dhis2PushService::PUSHABLE_STATUSES)
                ->get();
            foreach ($reports as $report) {
                PushPublicHealthToDhis2Job::dispatch($report->id);
            }
            $this->info("Queued {$reports->count()} approved report(s) for {$month}.");
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $from = \Carbon\CarbonImmutable::parse("{$month}-01")->startOfMonth();
            foreach ($facilityIds as $facilityId) {
                $rows = $drafts->aggregate((string) $facilityId, $from, $from->endOfMonth());
                $this->line("Facility {$facilityId}");
                $this->table(['Indicator', 'Count'], array_map(fn ($r) => [$r['indicator_code'], $r['value']], $rows));
            }
            $this->warn('[DRY RUN] nothing written.');
            return self::SUCCESS;
        }

        $generated = $drafts->generateDrafts($facilityIds, $month);
        \App\Models\AuditEvent::create([
            'actor_id' => null,
            'actor_role' => 'system:schedule',
            'action_type' => 'dhis2.draft.generated',
            'resource_type' => 'dhis2_reporting',
            'source_system' => 'finder_dhis2',
            'after_state' => ['month' => $month, 'report_ids' => array_map(fn ($r) => $r->id, $generated)],
            'created_at' => now(),
        ]);
        $this->info('Drafts for ' . $month . ': ' . count($generated) . ' (pending platform-admin review).');

        return self::SUCCESS;
    }
}
